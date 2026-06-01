<?php

namespace App\Services\Api;

use App\Models\Coins;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\SecondOpinion;
use App\Models\DiagnosticTestBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use App\Models\UserDevice;
use App\Models\RazorpayPayment;
use App\Services\CoinsWalletService;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Razorpay\Api\Api;

class PaymentApiService
{
    protected NotificationService $notificationService;
    protected ?Api $razorpayApi = null;
    protected string $razorpayKeyId;
    private ?bool $hasCoinsBalanceColumn = null;
    private ?float $coinAmountValue = null;

    /**
     * Initialize PaymentApiService with Razorpay API client.
     * 
     * Supports both naming conventions for Razorpay credentials:
     * - Primary: RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET
     * - Fallback: RAZORPAY_KEY and RAZORPAY_SECRET
     * 
     * @param NotificationService $notificationService
     * @throws InvalidArgumentException
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        
        // Try both naming conventions for Razorpay credentials
        // Primary: RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET (standard Razorpay docs)
        // Fallback: RAZORPAY_KEY and RAZORPAY_SECRET (legacy or config/services.php naming)
        $keyId = env('RAZORPAY_KEY_ID') ?? env('RAZORPAY_KEY');
        $keySecret = env('RAZORPAY_KEY_SECRET') ?? env('RAZORPAY_SECRET');
        
        if (!$keyId || !$keySecret) {
            Log::warning('Razorpay credentials not configured; payment APIs will be unavailable', [
                'has_key_id' => !empty($keyId),
                'has_key_secret' => !empty($keySecret),
                'env_vars' => [
                    'RAZORPAY_KEY_ID' => !empty(env('RAZORPAY_KEY_ID')),
                    'RAZORPAY_KEY' => !empty(env('RAZORPAY_KEY')),
                    'RAZORPAY_KEY_SECRET' => !empty(env('RAZORPAY_KEY_SECRET')),
                    'RAZORPAY_SECRET' => !empty(env('RAZORPAY_SECRET')),
                ]
            ]);
            return;
        }
        
        $this->razorpayKeyId = $keyId;
        
        try {
            $this->razorpayApi = new Api($keyId, $keySecret);
            Log::info('Razorpay API initialized successfully');
        } catch (\Exception $e) {
            Log::error('Failed to initialize Razorpay API', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'key_id_length' => strlen($keyId),
            ]);
            $this->razorpayApi = null;
        }
    }

    private function getRazorpayApi(): Api
    {
        if ($this->razorpayApi instanceof Api) {
            return $this->razorpayApi;
        }

        throw new InvalidArgumentException(
            'Razorpay API credentials are not properly configured. Please set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET (or RAZORPAY_KEY and RAZORPAY_SECRET) in .env file.'
        );
    }

    private function hasCoinsBalanceColumn(): bool
    {
        if ($this->hasCoinsBalanceColumn === null) {
            $this->hasCoinsBalanceColumn = Schema::hasColumn('healthinpocket_users', 'coins_balance');
        }

        return $this->hasCoinsBalanceColumn;
    }

    private function amountForOneCoin(): float
    {
        if ($this->coinAmountValue === null) {
            $this->coinAmountValue = (float) env('AMOUNT_For_ONE_COIN', env('AMOUNT_FOR_ONE_COIN', 0.10));
        }

        return $this->coinAmountValue;
    }

    private function resolveAvailableCoins(Coins $coinsWallet, ?HIPUser $hipUser): int
    {
        $walletCoins = (int) ($coinsWallet->coins ?? 0);

        if (!$hipUser || !$this->hasCoinsBalanceColumn()) {
            return max(0, $walletCoins);
        }

        $legacyBalance = (int) ($hipUser->coins_balance ?? 0);
        if ($legacyBalance > $walletCoins) {
            $coinsWallet->coins = $legacyBalance;
            $coinsWallet->save();
            return $legacyBalance;
        }

        return max(0, $walletCoins);
    }

    private function resolveCoinsWallet(string $personId, ?int $organizationId): Coins
    {
        $walletQuery = Coins::where('person_id', $personId);

        if ($organizationId) {
            $walletQuery->where('organization_id', $organizationId);
        }

        $wallet = $walletQuery->latest('id')->first();
        if ($wallet) {
            return $wallet;
        }

        // Reuse any existing wallet row for the person to avoid duplicate rows per person.
        $fallbackWallet = Coins::where('person_id', $personId)->latest('id')->first();
        if ($fallbackWallet) {
            if ($organizationId && empty($fallbackWallet->organization_id)) {
                $fallbackWallet->organization_id = $organizationId;
                $fallbackWallet->save();
            }
            return $fallbackWallet;
        }

        return Coins::create([
            'person_id' => $personId,
            'organization_id' => $organizationId,
            'coins' => 0,
        ]);
    }

    private function resolveFamilyPrimaryPerson(?Persons $person): ?Persons
    {
        if (! $person) {
            return null;
        }

        if (!empty($person->parent_id)) {
            return Persons::find((string) $person->parent_id) ?: $person;
        }

        return $person;
    }

    private function resolveOrganizationId(?HIPUser $hipUser, ?Invoice $invoice): ?int
    {
        $invoiceCreator = ($invoice && !empty($invoice->created_by))
            ? HIPUser::find((string) $invoice->created_by)
            : null;

        $organizationId = $hipUser?->organization_id ?: $invoiceCreator?->organization_id;
        return $organizationId ? (int) $organizationId : null;
    }

    /**
     * @return array{invoice: \App\Models\Invoice, coins_earned: int}
     */
    public function createInvoiceForPayment(array $payload): array
    {
        $personId = (string) ($payload['person_id'] ?? '');
        if ($personId === '') {
            throw new InvalidArgumentException('person_id is required.');
        }

        $person = Persons::findOrFail($personId);
        $requestedPrimaryId = (string) ($payload['primary_person_id'] ?? ($person->parent_id ?: $person->id));
        $requestedPrimary = Persons::find($requestedPrimaryId) ?: $person;
        $primaryPerson = $this->resolveFamilyPrimaryPerson($requestedPrimary) ?: $person;

        $serviceTypes = array_values($payload['service_types'] ?? []);
        $invoiceDetails = $payload['invoice_details'] ?? [];

        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $totalGst = (float) ($payload['total_gst'] ?? 0);
        $serviceCharges = (float) ($payload['service_charges'] ?? 0);
        $paymentGatewayCharges = (float) ($payload['payment_gateway_charges'] ?? 0);
        $totalAmount = (float) ($payload['total_amount'] ?? 0);
        $discountPrice = (float) ($payload['discount_price'] ?? 0);

        /** @var UploadedFile|null $prescriptionFile */
        $prescriptionFile = $payload['prescription_file'] ?? null;
        $prescriptionPath = null;

        if ($prescriptionFile instanceof UploadedFile) {
            $ext = $prescriptionFile->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . ($ext ?: 'pdf');
            $prescriptionFile->storeAs('invoices/prescriptions', $fileName, 'public');
            $prescriptionPath = 'invoices/prescriptions/' . $fileName;
        }

        $coinsEarned = (int) round($totalAmount * 0.01);
        $serviceTypesString = implode(',', $serviceTypes);
        $personName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
        $memberId = $person->hipUser?->hip_id ?? $primaryPerson->hipUser?->hip_id ?? null;
        $deviceId = $payload['device_id'] ?? null;

        try {
            $invoice = Invoice::create([
                'primary_person_id' => $primaryPerson->id,
                'person_id' => $person->id,
                'service_types' => $serviceTypes,
                'invoice_details' => $invoiceDetails,
                'prescription_img' => $prescriptionPath,
                'total_amount' => $totalAmount,
                'total_gst' => $totalGst,
                'amount' => $subtotal,
                'service_charges' => $serviceCharges,
                'payment_gateway_charges' => $paymentGatewayCharges,
                'discount_price' => $discountPrice,
                'status' => 'pending',
                'payment_method' => null,
                'coins_earned' => $coinsEarned,
            ]);

            // notify the customer about the new invoice; helper will handle first‑time vs reminder logic.
            $deviceId = $payload['device_id'] ?? null;
            $this->sendInvoiceNotification($invoice, false, $deviceId);
        } catch (\Throwable $e) {
            if ($prescriptionPath && Storage::disk('public')->exists($prescriptionPath)) {
                Storage::disk('public')->delete($prescriptionPath);
            }
            throw $e;
        }

        return [
            'invoice' => $invoice,
            'coins_earned' => $coinsEarned,
        ];
    }

    /**
     * Send fcm notification for an invoice. Handles both initial and reminder notices.
     *
     * @param Invoice $invoice
     * @param bool $reminder   true for a periodic reminder, false for first-time alert
     * @param string|null $deviceId  specific device identifier if available
     * @return bool            whether at least one push was successfully delivered
     */
    public function sendInvoiceNotification(Invoice $invoice, bool $reminder = false, ?string $deviceId = null): bool
    {
        // load relations if not already
        $invoice->load(['person', 'primaryPerson']);
        $person = $invoice->person;
        $primaryPerson = $invoice->primaryPerson ?: $person;
        if (!$person || !$primaryPerson) {
            Log::warning('Invoice notification skipped; missing person mapping', ['invoice_id' => $invoice->id]);
            return false;
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        if (!$hipUser) {
            Log::warning('Invoice notification skipped; HIP user not found', ['invoice_id' => $invoice->id]);
            return false;
        }

        $totalAmount = $invoice->total_amount ?? 0;
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];
        $serviceTypesString = implode(',', $serviceTypes);
        $personName = trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));

        $signedToken = URL::temporarySignedRoute(
            'payments.invoice.page',
            now()->addMinutes(30),
            ['invoice_id' => $invoice->id]
        );

        // Log token_url for Postman testing: GET {{base_url}}/payment-requests/{{invoice_id}}?token=<token_url>
        // Log::info('Payment notification token_url (copy for Postman)', [
        //     'invoice_id' => $invoice->id,
        //     'token_url' => $signedToken,
        //     'postman_example' => sprintf('%s/payment-requests/%d?token=%s', rtrim(config('app.url'), '/'), $invoice->id, rawurlencode($signedToken)),
        // ]);

        $frontendBase = rtrim((string) env('FRONTEND_APP_URL', config('app.url')), '/');
        $paymentQuery = http_build_query([
            'token' => $signedToken,
            'api_base' => rtrim(config('app.url'), '/'),
            'invoice_id' => $invoice->id,
            'amount' => number_format($totalAmount, 2, '.', ''),
            'person_name' => $personName,
        ]);
        $appRoute = '/payment-request/' . $invoice->id . '?' . $paymentQuery;
        $paymentUrl = $frontendBase . $appRoute;

        $title = $reminder ? 'Payment Reminder' : 'New Invoice Created';
        $body = $reminder
            ? 'Your invoice of Rs ' . number_format($totalAmount, 2) . ' is still pending. Please pay now.'
            : 'Your payment of Rs ' . number_format($totalAmount, 2) . ' is ready. Tap to pay now.';

        $data = [
            'type' => 'navigate',
            'route' => $appRoute,
            'screen' => 'payment_request',
            'invoice_token' => $signedToken,
            'api_base' => rtrim(config('app.url'), '/'),
            'url' => $paymentUrl,
            'invoice_id' => (string) $invoice->id,
            'amount' => number_format($totalAmount, 1, '.', ''),
            'person_name' => $personName,
            'service_types' => $serviceTypesString,
            'coins_earned' => (string) ($invoice->coins_earned ?? 0),
        ];

        $sentAny = false;

        // save a single notification record regardless of number of targets
        // but avoid duplicates for the same invoice+user
        $existing = \App\Models\Notification::where('user_id', $hipUser->id)
            ->where('data->invoice_id', (string) $invoice->id)
            ->where('data->type', 'navigate')
            ->exists();
        if (! $existing) {
            $this->notificationService->storeNotification($hipUser->id, $title, $body, $data);
            Log::info('Payment notification stored; token_url for Postman', [
                'invoice_id' => $invoice->id,
                'token_url' => $signedToken,
            ]);
        }

        if ($deviceId) {
            // send to one specific device without storing again
            $sentAny = $this->notificationService->sendToDevice(
                $hipUser->id,
                $deviceId,
                $title,
                $body,
                $data,
                false // don't create another DB row
            );
        } else {
            $devices = UserDevice::where('user_id', $hipUser->id)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get(['user_id', 'device_id', 'fcm_token']);

            $targets = $devices->unique('fcm_token')->values();
            Log::info('Invoice notification targets resolved', [
                'invoice_id' => $invoice->id,
                'user_id' => $hipUser->id,
                'raw_devices_count' => $devices->count(),
                'unique_token_count' => $targets->count(),
            ]);

            foreach ($targets as $target) {
                $result = $this->notificationService->sendToToken(
                    (string) $target->fcm_token,
                    $title,
                    $body,
                    $data,
                    [
                        'device_id' => $target->device_id, // omit user_id to prevent storing
                    ]
                );

                if ($result) {
                    $sentAny = true;
                }
            }
        }

        if ($sentAny) {
            if ($reminder) {
                $invoice->last_reminder_sent_at = now();
            } else {
                $invoice->is_notified = true;
            }
            $invoice->save();
        }

        return $sentAny;
    }

    /**
     * Send a lightweight push inviting the customer to review the hospital
     * associated with an invoice. This method is intentionally forgiving and
     * never throws – callers should catch exceptions if they care about
     * failures.
     *
     * The payload contains the member identifier and hospital id so the
     * client can navigate directly to the appropriate review screen.
     *
     * @param \App\Models\Invoice $invoice
     * @return bool whether at least one push was delivered
     */
    private function sendHospitalReviewRequest(Invoice $invoice): bool
    {
        $invoice->load(['person', 'primaryPerson']);
        $person = $invoice->person;
        $primaryPerson = $invoice->primaryPerson ?: $person;
        if (! $person || ! $primaryPerson) {
            Log::warning('Review request skipped; invalid invoice person mapping', ['invoice_id' => $invoice->id]);
            return false;
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        if (! $hipUser) {
            Log::warning('Review request skipped; HIP user not found', ['invoice_id' => $invoice->id]);
            return false;
        }

        // construct member identifier string the same way other parts of
        // the app build it so clients can display it verbatim.
        $memberId = $person->hipUser?->hip_id ?? $primaryPerson->hipUser?->hip_id ?? null;

        // attempt to locate the hospital that generated the invoice. the
        // creator may be a HIPUser with a hospital_id; otherwise we omit it.
        $hospitalId = null;
        if ($invoice->created_by) {
            $creator = HIPUser::find($invoice->created_by);
            $hospitalId = $creator?->hospital_id;
        }

        $title = 'How was your visit?';
        $body = 'Please review the hospital for member ' . $memberId;

        $data = [
            'type' => 'review_popup',
            'entity_type' => 'hospital',
            'entity_id' =>  $hospitalId ? (string) $hospitalId : null,
        ];

        // create notification row so we can show history even if push fails
        $this->notificationService->storeNotification($hipUser->id, $title, $body, $data);

        $sentAny = false;
        $devices = UserDevice::where('user_id', $hipUser->id)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get(['user_id', 'device_id', 'fcm_token']);

        $targets = $devices->unique('fcm_token')->values();
        foreach ($targets as $target) {
            $result = $this->notificationService->sendToToken(
                (string) $target->fcm_token,
                $title,
                $body,
                $data,
                ['device_id' => $target->device_id]
            );

            if ($result) {
                $sentAny = true;
            }
        }

        return $sentAny;
    }

    public function getInvoicePaymentRequestData(int $invoiceId): array
    {
        $invoice = Invoice::with(['person', 'primaryPerson'])->findOrFail($invoiceId);
        $person = $invoice->person;
        $primaryPerson = $this->resolveFamilyPrimaryPerson($invoice->primaryPerson ?? $person) ?? $person;
        if (!$person || !$primaryPerson) {
            throw new InvalidArgumentException('Invalid invoice member mapping.');
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);
        $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId);
        $coinsBalance = $this->resolveAvailableCoins($coinsWallet, $hipUser);

        $created_by = HIPUser::find($invoice->created_by);
        $hospital = Hospital::find($created_by->hospital_id);
       
        $amountForOneCoin = $this->amountForOneCoin();
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];

        return [
            'invoice_id' => (int) $invoice->id,
            'status' => (string) $invoice->status,
            'hospital_name' => (string) $hospital->name,
            'hospital_type' => (string) ($hospital->subtitle ?? ''),
            'hospital_logo' => $hospital->logo ? asset('storage/hospital/'. $hospital->logo):null,
            'person_id' => (string) $primaryPerson->id,
            'primary_person_id' => (string) $primaryPerson->id,
            'member_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
            'member_id' => $person->hipUser?->hip_id ?? $primaryPerson->hipUser?->hip_id ?? null,
            'member_image' => $person->image ? asset('storage/users/'.$person->image):null,
            'service_types' => $serviceTypes,
            'invoice_details' => $invoice->invoice_details ?? [],
            'subtotal' => (float) ($invoice->amount ?? 0),
            'total_gst' => (float) ($invoice->total_gst ?? 0),
            'service_charges' => (float) ($invoice->service_charges ?? 0),
            'gateway_charges' => (float) ($invoice->payment_gateway_charges ?? 0),
            'discount' => (float) ($invoice->discount_price ?? 0),
            'amount' => (float) ($invoice->total_amount ?? 0),
            'coins_balance' => (int) $coinsBalance,
            'amount_for_one_coin' => (float) $amountForOneCoin,
            'coins_value' => (float) round($coinsBalance * $amountForOneCoin, 2),
            'coins_earned' => (int) ($invoice->coins_earned ?? round(((float) $invoice->total_amount) * 0.01)),
            'prescription' => !empty($invoice->prescription_img),
            'payment_method' => (string) ($invoice->payment_method ?? ''),
            'created_at' => optional($invoice->created_at)?->toDateTimeString(),
        ];
    }

    public function calculateCoinsApplication(int $invoiceId, int $requestedCoins): array
    {
        $invoice = Invoice::with(['primaryPerson'])->findOrFail($invoiceId);
        $primaryPerson = $this->resolveFamilyPrimaryPerson($invoice->primaryPerson);
        if (!$primaryPerson) {
            throw new InvalidArgumentException('Primary person not found for invoice.');
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);
        $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId);

        $availableCoins = $this->resolveAvailableCoins($coinsWallet, $hipUser);
        $effectiveCoins = max(0, min($requestedCoins, $availableCoins));
        $amountForOneCoin = $this->amountForOneCoin();
        $originalAmount = (float) ($invoice->total_amount ?? 0);
        $coinsDiscountAmount = min(round($effectiveCoins * $amountForOneCoin, 2), $originalAmount);
        $payableAmount = round(max(0, $originalAmount - $coinsDiscountAmount), 2);

        return [
            'invoice_id' => (int) $invoice->id,
            'coins_available' => (int) $availableCoins,
            'coins_requested' => (int) $requestedCoins,
            'coins_applied' => (int) $effectiveCoins,
            'amount_for_one_coin' => (float) $amountForOneCoin,
            'coins_discount_amount' => (float) $coinsDiscountAmount,
            'original_amount' => (float) $originalAmount,
            'payable_amount' => (float) $payableAmount,
            'coins_earned_preview' => (int) round($payableAmount * 0.01),
        ];
    }

    /**
     * Create Razorpay order and initialize payment flow.
     * 
     * @return array{success: bool, order_id: string, razorpay_key: string, amount: int, invoice_id: int, transaction_id: string}
     */
    public function createRazorpayOrder(int $invoiceId, int $coinsApplied = 0): array
    {
        Log::info('Razorpay order creation initiated', [
            'invoice_id' => $invoiceId,
            'coins_applied' => $coinsApplied,
        ]);

        return DB::transaction(function () use ($invoiceId, $coinsApplied) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            // Prevent duplicate payment creation
            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is not pending.");
            }

            // Fetch all required data
            $primaryPersonId = (string) $invoice->primary_person_id;
            $primaryPerson = Persons::find($primaryPersonId);
            if (!$primaryPerson) {
                throw new InvalidArgumentException("Primary person not found for invoice.");
            }

            $hipUser = HIPUser::find($primaryPerson->hip_user_id);
            $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

            // Calculate payable amount after coins
            $coinValue = $this->amountForOneCoin();
            $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
            $walletCoins = $this->resolveAvailableCoins($coinsWallet, $hipUser);
            $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoins));
            $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), (float) $invoice->total_amount);
            $payableAmount = max(0, (float) $invoice->total_amount - $coinsDiscountAmount);

            // Create Razorpay order (amount in paise)
            try {
                $order = $this->getRazorpayApi()->order->create([
                    'receipt' => 'invoice_' . $invoice->id,
                    'amount' => (int) round($payableAmount * 100), // Convert to paise
                    'currency' => 'INR',
                ]);
            } catch (\Exception $e) {
                Log::error('Razorpay order creation failed', [
                    'invoice_id' => $invoiceId,
                    'amount' => $payableAmount,
                    'error' => $e->getMessage(),
                    'error_code' => method_exists($e, 'getCode') ? $e->getCode() : 'unknown',
                ]);
                throw new InvalidArgumentException('Failed to create Razorpay order: ' . $e->getMessage());
            }

            // Create pending transaction
            $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];
            if (is_string($invoice->service_types)) {
                $serviceTypes = array_values(array_filter(array_map('trim', explode(',', $invoice->service_types))));
            }

            $transaction = Transactions::create([
                'invoice_id' => $invoice->id,
                'service_types' => $serviceTypes,
                'invoice_details' => $invoice->invoice_details,
                'transaction_amount' => (float) $invoice->total_amount,
                'service_charges' => (float) ($invoice->service_charges ?? 0),
                'payment_gateway_charges' => (float) ($invoice->payment_gateway_charges ?? 0),
                'discount_amount' => 0,
                'total_gst' => (float) ($invoice->total_gst ?? 0),
                'total_amount' => (float) $invoice->total_amount,
                'status' => 'pending',
                'payment_method' => 'razorpay',
            ]);

            // Create razorpay_payments record
            RazorpayPayment::create([
                'invoice_id' => $invoice->id,
                'razorpay_order_id' => $order->id,
                'coins_applied' => $effectiveAppliedCoins,
                'amount_paid' => $payableAmount,
                'currency' => 'INR',
                'payment_status' => 'created',
            ]);

            $transactionReference = 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);

            Log::info('Razorpay order created', [
                'invoice_id' => $invoice->id,
                'razorpay_order_id' => $order->id,
                'amount' => $payableAmount,
                'transaction_id' => $transactionReference,
            ]);

            return [
                'success' => true,
                'order_id' => (string) $order->id,
                'razorpay_key' => (string) $this->razorpayKeyId,
                'amount' => (int) round($payableAmount * 100), // Return in paise (int for frontend index/parsing)
                'invoice_id' => (int) $invoice->id,
                'transaction_id' => (string) $transactionReference,
            ];
        });
    }

    /**
     * Verify Razorpay payment and complete transaction.
     * 
     * @return array{success: bool, invoice_id: int, transaction_id: string, status: string, payment_method: string, amount_paid: float, coins_applied: int, coins_earned: int}
     */
    public function completeRazorpayPayment(array $payload): array
    {
        $razorpayPaymentId = (string) ($payload['razorpay_payment_id'] ?? '');
        $razorpayOrderId = (string) ($payload['razorpay_order_id'] ?? '');
        $razorpaySignature = (string) ($payload['razorpay_signature'] ?? '');
        $invoiceId = (int) ($payload['invoice_id'] ?? 0);
        $coinsApplied = (int) ($payload['coins_applied'] ?? 0);

        if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature || $invoiceId <= 0) {
            throw new InvalidArgumentException('Missing required payment verification data.');
        }

        return DB::transaction(function () use (
            $razorpayPaymentId,
            $razorpayOrderId,
            $razorpaySignature,
            $invoiceId,
            $coinsApplied,
            $payload
        ) {
            $invoice = Invoice::lockForUpdate()->with(['person'])->findOrFail($invoiceId);

            // Prevent duplicate payment completion
            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is already processed.");
            }

            // compute member identifier for return
            $person = $invoice->person;
            $memberId = $person?->hipUser?->hip_id ?? null;

            $razorpayPaymentRecord = RazorpayPayment::where('invoice_id', $invoiceId)
                ->where('razorpay_order_id', $razorpayOrderId)
                ->firstOrFail();

            // Verify Razorpay signature
            try {
                $this->getRazorpayApi()->utility->verifyPaymentSignature([
                    'razorpay_order_id' => $razorpayOrderId,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $razorpaySignature,
                ]);
            } catch (\Exception $e) {
                Log::warning('Razorpay signature verification failed', [
                    'invoice_id' => $invoiceId,
                    'error' => $e->getMessage(),
                ]);

                // Mark payment as failed
                $razorpayPaymentRecord->update([
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $razorpaySignature,
                    'payment_status' => 'failed',
                ]);

                $transaction = Transactions::where('invoice_id', $invoiceId)->latest()->first();
                if ($transaction) {
                    $transaction->update(['status' => 'failed']);
                }

                throw new InvalidArgumentException('Payment verification failed. Invalid signature.');
            }

            // Fetch detailed payment info from Razorpay
            $payment = $this->getRazorpayApi()->payment->fetch($razorpayPaymentId);

            // Update razorpay_payments record with full details
            $razorpayPaymentRecord->update([
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
                'payment_method' => $payment->method ?? null,
                'bank' => $payment->bank ?? null,
                'wallet' => $payment->wallet ?? null,
                'vpa' => $payment->vpa ?? null,
                'card_last4' => $payment->card_id ? $payment->card->last4 : null,
                'card_network' => $payment->card_id ? $payment->card->network : null,
                'razorpay_fee' => isset($payment->fee) ? $payment->fee / 100 : 0,
                'razorpay_tax' => isset($payment->tax) ? $payment->tax / 100 : 0,
                'payment_status' => 'captured',
            ]);

            // Get transaction and update it
            $transaction = Transactions::where('invoice_id', $invoiceId)
                ->where('status', 'pending')
                ->latest()
                ->first();

            if (!$transaction) {
                throw new InvalidArgumentException("Pending transaction not found for invoice {$invoiceId}.");
            }

            // Apply coins logic (earn coins from amount paid; doctor bookings deduct reserved coins separately)
            $storedCoinsApplied = isset($razorpayPaymentRecord->coins_applied)
                ? (int) $razorpayPaymentRecord->coins_applied
                : $coinsApplied;

            $paidAmountOverride = null;
            if ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id) {
                $storedCoinsApplied = 0;
                $paidAmountOverride = (float) (
                    $razorpayPaymentRecord->amount_paid
                    ?? $transaction->total_amount
                    ?? $invoice->total_amount
                    ?? 0
                );
            }

            $coinResult = $this->applyCoinsLogic(
                $invoice,
                $transaction,
                $storedCoinsApplied,
                $paidAmountOverride
            );

            // Update transaction to completed
            $transaction->update([
                'status' => 'completed',
                'payment_method' => $payment->method ?? 'razorpay',
                'discount_amount' => $coinResult['coinsDiscountAmount'],
                'total_amount' => $coinResult['payableAmount'],
            ]);

            $doctorBookingResult = $this->finalizeDoctorBookingAfterPayment($invoice, $transaction);
            $secondOpinionResult = $this->finalizeSecondOpinionAfterPayment($invoice, $transaction);
            $diagnosticBookingResult = $this->finalizeDiagnosticTestBookingAfterPayment($invoice, $transaction);

            $invoiceCoinsApplied = ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id)
                ? (int) ($invoice->coins_applied ?? 0)
                : (int) $coinResult['effectiveAppliedCoins'];

            // Update invoice to completed
            $invoice->update([
                'status' => 'completed',
                'payment_method' => 'razorpay',
                'coins_applied' => $invoiceCoinsApplied,
                'coins_earned' => $coinResult['coinsEarned'],
                'discount_price' => round((float) ($invoice->discount_price ?? 0) + $coinResult['coinsDiscountAmount'], 2),
            ]);

            $transactionReference = 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);

                Log::info('Razorpay payment completed', [
                'invoice_id' => $invoice->id,
                'razorpay_payment_id' => $razorpayPaymentId,
                'transaction_id' => $transactionReference,
                'amount_paid' => $coinResult['payableAmount'],
                'coins_applied' => $invoiceCoinsApplied,
                'coins_earned' => $coinResult['coinsEarned'],
                'doctor_booking_id' => $invoice->doctor_booking_id,
            ]);

            // after a successful payment we prompt the customer to review the hospital
            try {
                $this->sendHospitalReviewRequest($invoice);
            } catch (\Throwable $e) {
                // non‑fatal, just log so we can investigate
                Log::warning('Failed to send hospital review push', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'invoice_id' => (int) $invoice->id,
                'member_id' => $memberId !== null ? (string) $memberId : null,
                'transaction_id' => (string) $transactionReference,
                'status' => 'completed',
                'payment_method' => (string) ($payment->method ?? 'razorpay'),
                'amount_paid' => (float) $coinResult['payableAmount'],
                'coins_applied' => (int) $invoiceCoinsApplied,
                'coins_earned' => (int) $coinResult['coinsEarned'],
                'doctor_booking_id' => $doctorBookingResult['doctor_booking_id'] ?? null,
                'booking_payment_status' => $doctorBookingResult['payment_status'] ?? null,
                'second_opinion_id' => $secondOpinionResult['second_opinion_id'] ?? null,
                'second_opinion_payment_status' => $secondOpinionResult['payment_status'] ?? null,
                'diagnostic_test_booking_id' => $diagnosticBookingResult['diagnostic_test_booking_id'] ?? null,
                'diagnostic_booking_payment_status' => $diagnosticBookingResult['payment_status'] ?? null,
            ];
        });
    }

    /**
     * Create a pending invoice for a doctor booking (online payment flow).
     */
    public function createInvoiceForDoctorBooking(DoctorBooking $booking, string $patientPersonId): Invoice
    {
        $patient = Persons::findOrFail($patientPersonId);
        $primaryPerson = $this->resolveFamilyPrimaryPerson($patient) ?: $patient;

        $doctor = $booking->relationLoaded('doctor')
            ? $booking->doctor
            : Doctor::query()->find($booking->doctor_id);

        $doctorName = trim((string) ($doctor?->name ?? 'Doctor'));
        $totalAmount = (float) ($booking->total_amount ?? 0);

        return Invoice::create([
            'primary_person_id' => $primaryPerson->id,
            'person_id' => $patient->id,
            'doctor_booking_id' => $booking->id,
            'service_types' => ['doctor_consultation'],
            'invoice_details' => [
                [
                    'service' => 'Doctor Consultation',
                    'doctor_booking_id' => $booking->id,
                    'doctor_id' => $booking->doctor_id,
                    'doctor_name' => $doctorName,
                    'booking_date' => $booking->booking_date?->format('Y-m-d'),
                    'appointment_type' => $booking->appointment_type,
                    'branch_id' => $booking->branch_id,
                ],
            ],
            'total_amount' => $totalAmount,
            'amount' => (float) ($booking->consultation_fee ?? 0),
            'service_charges' => (float) ($booking->service_charges ?? 0),
            'payment_gateway_charges' => 0,
            'discount_price' => (float) ($booking->total_discount ?? 0),
            'total_gst' => 0,
            'status' => 'pending',
            'payment_method' => null,
            'coins_applied' => (int) ($booking->coins_used ?? 0),
            'coins_earned' => (int) round($totalAmount * 0.01),
        ]);
    }

    /**
     * Initialize Razorpay checkout for a doctor booking invoice.
     * Coins discount is already included in booking total_amount; do not apply again at gateway.
     *
     * @return array{success: bool, order_id: string, razorpay_key: string, amount: int, invoice_id: int, transaction_id: string}
     */
    public function createDoctorBookingRazorpayOrder(DoctorBooking $booking): array
    {
        if (! $booking->invoice_id) {
            throw new InvalidArgumentException('Invoice not found for this doctor booking.');
        }

        if ((float) ($booking->total_amount ?? 0) <= 0) {
            throw new InvalidArgumentException('No payable amount for this booking.');
        }

        return $this->createRazorpayOrder((int) $booking->invoice_id, 0);
    }

    /**
     * Deduct booking coins and mark booking paid after successful Razorpay verification.
     *
     * @return array{doctor_booking_id: int|null, payment_status: string|null}
     */
    public function finalizeDoctorBookingAfterPayment(Invoice $invoice, Transactions $transaction): array
    {
        if (! $invoice->doctor_booking_id) {
            return ['doctor_booking_id' => null, 'payment_status' => null];
        }

        $booking = DoctorBooking::query()
            ->lockForUpdate()
            ->with('doctor')
            ->find($invoice->doctor_booking_id);

        if (! $booking) {
            return ['doctor_booking_id' => null, 'payment_status' => null];
        }

        if ($booking->payment_status === 'paid') {
            return [
                'doctor_booking_id' => (int) $booking->id,
                'payment_status' => 'paid',
            ];
        }

        $this->deductDoctorBookingCoins($booking);

        $booking->update([
            'payment_status' => 'paid',
            'invoice_id' => $invoice->id,
        ]);

        Log::info('Doctor booking payment finalized', [
            'doctor_booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'transaction_id' => $transaction->id,
        ]);

        return [
            'doctor_booking_id' => (int) $booking->id,
            'payment_status' => 'paid',
        ];
    }

    /**
     * Deduct coins reserved on a doctor booking (online payment flow).
     */
    public function deductDoctorBookingCoins(DoctorBooking $booking): void
    {
        $coinsUsed = (int) ($booking->coins_used ?? 0);

        if ($coinsUsed <= 0) {
            return;
        }

        $patient = Persons::query()->find($booking->patient_id);

        if (! $patient) {
            throw new InvalidArgumentException('Patient not found for coin deduction.');
        }

        $walletPersonId = $patient->parent_id ?? $patient->id;

        $coinsWallet = Coins::query()
            ->where('person_id', $walletPersonId)
            ->lockForUpdate()
            ->first();

        if (! $coinsWallet instanceof Coins) {
            throw new InvalidArgumentException('Coin wallet not found for this user.');
        }

        $availableCoins = (int) ($coinsWallet->coins ?? 0);

        if ($coinsUsed > $availableCoins) {
            throw new InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
        }

        app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
    }

    public function createInvoiceForSecondOpinion(SecondOpinion $booking, string $patientPersonId): Invoice
    {
        $patient = Persons::findOrFail($patientPersonId);
        $primaryPerson = $this->resolveFamilyPrimaryPerson($patient) ?: $patient;

        $doctor = $booking->relationLoaded('doctor')
            ? $booking->doctor
            : Doctor::query()->find($booking->doctor_id);

        $doctorName = trim((string) ($doctor?->name ?? 'Doctor'));
        $totalAmount = (float) ($booking->total_amount ?? 0);

        return Invoice::create([
            'primary_person_id'   => $primaryPerson->id,
            'person_id'           => $patient->id,
            'second_opinion_id'   => $booking->id,
            'service_types'       => ['second_opinion'],
            'invoice_details'     => [
                [
                    'service'            => 'Second Opinion',
                    'second_opinion_id'  => $booking->id,
                    'doctor_id'          => $booking->doctor_id,
                    'doctor_name'        => $doctorName,
                    'preferred_date'     => $booking->preferred_date?->format('Y-m-d'),
                    'mode_of_consultation' => $booking->mode_of_consultation,
                    'branch_id'          => $booking->branch_id,
                ],
            ],
            'total_amount'            => $totalAmount,
            'amount'                  => (float) ($booking->consultation_fee ?? 0),
            'service_charges'         => (float) ($booking->service_charges ?? 0),
            'payment_gateway_charges' => 0,
            'discount_price'          => (float) ($booking->total_discount ?? 0),
            'total_gst'               => 0,
            'status'                  => 'pending',
            'payment_method'          => null,
            'coins_applied'           => (int) ($booking->coins_used ?? 0),
            'coins_earned'            => (int) round($totalAmount * 0.01),
        ]);
    }

    /**
     * @return array{success: bool, order_id: string, razorpay_key: string, amount: int, invoice_id: int, transaction_id: string}
     */
    public function createSecondOpinionRazorpayOrder(SecondOpinion $booking): array
    {
        if (! $booking->invoice_id) {
            throw new InvalidArgumentException('Invoice not found for this second opinion request.');
        }

        if ((float) ($booking->total_amount ?? 0) <= 0) {
            throw new InvalidArgumentException('No payable amount for this second opinion request.');
        }

        return $this->createRazorpayOrder((int) $booking->invoice_id, 0);
    }

    /**
     * @return array{second_opinion_id: int|null, payment_status: string|null}
     */
    public function finalizeSecondOpinionAfterPayment(Invoice $invoice, Transactions $transaction): array
    {
        if (! $invoice->second_opinion_id) {
            return ['second_opinion_id' => null, 'payment_status' => null];
        }

        $booking = SecondOpinion::query()
            ->lockForUpdate()
            ->with('doctor')
            ->find($invoice->second_opinion_id);

        if (! $booking) {
            return ['second_opinion_id' => null, 'payment_status' => null];
        }

        if ($booking->payment_status === 'paid') {
            return [
                'second_opinion_id' => (int) $booking->id,
                'payment_status'    => 'paid',
            ];
        }

        $this->deductSecondOpinionCoins($booking);

        $booking->update([
            'payment_status' => 'paid',
            'invoice_id'     => $invoice->id,
        ]);

        Log::info('Second opinion payment finalized', [
            'second_opinion_id' => $booking->id,
            'invoice_id'        => $invoice->id,
            'transaction_id'    => $transaction->id,
        ]);

        return [
            'second_opinion_id' => (int) $booking->id,
            'payment_status'    => 'paid',
        ];
    }

    public function deductSecondOpinionCoins(SecondOpinion $booking): void
    {
        $coinsUsed = (int) ($booking->coins_used ?? 0);

        if ($coinsUsed <= 0) {
            return;
        }

        $patient = Persons::query()->find($booking->patient_id);

        if (! $patient) {
            throw new InvalidArgumentException('Patient not found for coin deduction.');
        }

        $walletPersonId = $patient->parent_id ?? $patient->id;

        $coinsWallet = Coins::query()
            ->where('person_id', $walletPersonId)
            ->lockForUpdate()
            ->first();

        if (! $coinsWallet instanceof Coins) {
            throw new InvalidArgumentException('Coin wallet not found for this user.');
        }

        $availableCoins = (int) ($coinsWallet->coins ?? 0);

        if ($coinsUsed > $availableCoins) {
            throw new InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
        }

        app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
    }

    public function createInvoiceForDiagnosticTestBooking(DiagnosticTestBooking $booking, string $patientPersonId): Invoice
    {
        $patient = Persons::findOrFail($patientPersonId);
        $primaryPerson = $this->resolveFamilyPrimaryPerson($patient) ?: $patient;

        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $totalAmount = (float) ($booking->total_amount ?? 0);

        return Invoice::create([
            'primary_person_id'         => $primaryPerson->id,
            'person_id'                 => $patient->id,
            'diagnostic_test_booking_id' => $booking->id,
            'service_types'             => ['diagnostic_package'],
            'invoice_details'           => [
                [
                    'service'                => $packageLabel,
                    'diagnostic_test_booking_id' => $booking->id,
                    'package_id'             => $booking->package_id,
                    'package_type'           => $booking->package_type,
                    'diagnostic_center_id'   => $booking->diagnostic_center_id,
                    'branch_id'              => $booking->branch_id,
                    'booking_date'           => $booking->booking_date?->format('Y-m-d'),
                    'sample_collection'      => $booking->sample_collection,
                ],
            ],
            'total_amount'              => $totalAmount,
            'amount'                    => (float) ($booking->package_fee ?? 0),
            'service_charges'           => (float) ($booking->service_charges ?? 0),
            'payment_gateway_charges'   => 0,
            'discount_price'            => (float) ($booking->total_discount ?? 0),
            'total_gst'                 => 0,
            'status'                    => 'pending',
            'payment_method'            => null,
            'coins_applied'             => (int) ($booking->coins_used ?? 0),
            'coins_earned'              => (int) round($totalAmount * 0.01),
        ]);
    }

    public function createDiagnosticTestBookingRazorpayOrder(DiagnosticTestBooking $booking): array
    {
        if (! $booking->invoice_id) {
            throw new InvalidArgumentException('Invoice not found for this package booking.');
        }

        if ((float) ($booking->total_amount ?? 0) <= 0) {
            throw new InvalidArgumentException('No payable amount for this booking.');
        }

        return $this->createRazorpayOrder((int) $booking->invoice_id, 0);
    }

    /**
     * @return array{diagnostic_test_booking_id: int|null, payment_status: string|null}
     */
    public function finalizeDiagnosticTestBookingAfterPayment(Invoice $invoice, Transactions $transaction): array
    {
        if (! $invoice->diagnostic_test_booking_id) {
            return ['diagnostic_test_booking_id' => null, 'payment_status' => null];
        }

        $booking = DiagnosticTestBooking::query()
            ->lockForUpdate()
            ->find($invoice->diagnostic_test_booking_id);

        if (! $booking) {
            return ['diagnostic_test_booking_id' => null, 'payment_status' => null];
        }

        if ($booking->payment_status === 'paid') {
            return [
                'diagnostic_test_booking_id' => (int) $booking->id,
                'payment_status'             => 'paid',
            ];
        }

        $this->deductDiagnosticTestBookingCoins($booking);

        $booking->update([
            'payment_status' => 'paid',
            'invoice_id'     => $invoice->id,
        ]);

        Log::info('Diagnostic package booking payment finalized', [
            'diagnostic_test_booking_id' => $booking->id,
            'invoice_id'                 => $invoice->id,
            'transaction_id'             => $transaction->id,
        ]);

        return [
            'diagnostic_test_booking_id' => (int) $booking->id,
            'payment_status'             => 'paid',
        ];
    }

    public function deductDiagnosticTestBookingCoins(DiagnosticTestBooking $booking): void
    {
        $coinsUsed = (int) ($booking->coins_used ?? 0);

        if ($coinsUsed <= 0) {
            return;
        }

        $patient = Persons::query()->find($booking->patient_id);

        if (! $patient) {
            throw new InvalidArgumentException('Patient not found for coin deduction.');
        }

        $walletPersonId = $patient->parent_id ?? $patient->id;

        $coinsWallet = Coins::query()
            ->where('person_id', $walletPersonId)
            ->lockForUpdate()
            ->first();

        if (! $coinsWallet instanceof Coins) {
            throw new InvalidArgumentException('Coin wallet not found for this user.');
        }

        $availableCoins = (int) ($coinsWallet->coins ?? 0);

        if ($coinsUsed > $availableCoins) {
            throw new InvalidArgumentException('Insufficient coins. Available: '.$availableCoins);
        }

        app(CoinsWalletService::class)->debit($coinsWallet, $coinsUsed);
    }

    /**
     * Apply coins logic to invoice payment (deduct and earn).
     * 
     * @return array{effectiveAppliedCoins: int, coinsDiscountAmount: float, payableAmount: float, coinsEarned: int}
     */
    private function applyCoinsLogic(
        Invoice $invoice,
        Transactions $transaction,
        int $coinsApplied,
        ?float $paidAmountOverride = null
    ): array {
        $primaryPerson = $this->resolveFamilyPrimaryPerson(
            $invoice->relationLoaded('primaryPerson')
                ? $invoice->primaryPerson
                : Persons::find((string) $invoice->primary_person_id)
        );
        if (!$primaryPerson) {
            throw new InvalidArgumentException("Primary person not found for invoice.");
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

        $coinValue = $this->amountForOneCoin();
        $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
        $walletCoinsBefore = $this->resolveAvailableCoins($coinsWallet, $hipUser);

        if ($paidAmountOverride !== null) {
            $effectiveAppliedCoins = 0;
            $coinsDiscountAmount = 0.0;
            $payableAmount = round(max(0, $paidAmountOverride), 2);
        } else {
            $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoinsBefore));
            $baseAmount = (float) ($transaction->transaction_amount ?? $invoice->total_amount ?? 0);
            $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), $baseAmount);
            $payableAmount = round(max(0, $baseAmount - $coinsDiscountAmount), 2);
        }

        // Credit 1% of the amount actually paid as HIP coins (same rule as other invoice payments).
        $coinsEarned = (int) round($payableAmount * 0.01);

        $walletService = app(CoinsWalletService::class);

        if ($effectiveAppliedCoins > 0) {
            $walletService->debit($coinsWallet, $effectiveAppliedCoins);
        }

        if ($coinsEarned > 0) {
            $walletService->creditAfterPayment($coinsWallet, $coinsEarned);
        }

        $finalCoinsBalance = (int) ($coinsWallet->fresh()->coins ?? 0);

        if ($hipUser && $this->hasCoinsBalanceColumn()) {
            $hipUser->coins_balance = $finalCoinsBalance;
            $hipUser->save();
        }

        return [
            'effectiveAppliedCoins' => $effectiveAppliedCoins,
            'coinsDiscountAmount' => $coinsDiscountAmount,
            'payableAmount' => $payableAmount,
            'coinsEarned' => $coinsEarned,
        ];
    }

    /**
     * @return array{success: bool, invoice_id: int, transaction_id: string, status: string, payment_method: string, amount_paid: float, coins_applied: int, coins_earned: int}
     */
    public function completeInvoicePayment(array $payload): array
    {
        $invoiceId = (int) ($payload['invoice_id'] ?? 0);
        $paymentMethod = (string) ($payload['payment_method'] ?? '');
        $paidAmountFromClient = (float) ($payload['total_amount'] ?? 0);
        $originalAmount = (float) ($payload['original_amount'] ?? 0);
        $coinsApplied = (int) ($payload['coins_applied'] ?? 0);
        $coinsDiscountAmountFromClient = (float) ($payload['coins_discount_amount'] ?? 0);
        $status = (string) ($payload['status'] ?? 'completed');

        return DB::transaction(function () use (
            $invoiceId,
            $paymentMethod,
            $paidAmountFromClient,
            $originalAmount,
            $coinsApplied,
            $coinsDiscountAmountFromClient,
            $status,
            $payload
        ) {
            $invoice = Invoice::lockForUpdate()->with(['person'])->findOrFail($invoiceId);

            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is not pending.");
            }

            $person = $invoice->person;
            $memberId = $person?->hipUser?->hip_id ?? null;

            $serviceTypes = $payload['service_types'] ?? $invoice->service_types ?? [];
            if (is_string($serviceTypes)) {
                $serviceTypes = array_values(array_filter(array_map('trim', explode(',', $serviceTypes))));
            }

            $coinValue = $this->amountForOneCoin();
            $coinsDiscountAmount = 0.0;
            $paidAmount = round(max(0, $originalAmount), 2);
            $coinsEarned = (int) round($paidAmount * 0.01);
            $primaryPerson = $this->resolveFamilyPrimaryPerson(
                Persons::find((string) ($payload['primary_person_id'] ?? $invoice->primary_person_id))
            );
            $hipUser = $primaryPerson ? HIPUser::find($primaryPerson->hip_user_id) : null;
            $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

            $effectiveAppliedCoins = 0;
            $walletCoinsBefore = 0;
            if ($primaryPerson) {
                $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
                $walletCoinsBefore = $this->resolveAvailableCoins($coinsWallet, $hipUser);
                $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoinsBefore));
                $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), $originalAmount);
                $paidAmount = round(max(0, $originalAmount - $coinsDiscountAmount), 2);
                $coinsEarned = (int) round($paidAmount * 0.01);
            }

            $transaction = Transactions::create([
                'invoice_id' => $invoice->id,
                'service_types' => $serviceTypes,
                'invoice_details' => $invoice->invoice_details,
                'transaction_amount' => $originalAmount,
                'service_charges' => (float) ($invoice->service_charges ?? 0),
                'payment_gateway_charges' => (float) ($invoice->payment_gateway_charges ?? 0),
                'discount_amount' => $coinsDiscountAmount,
                'total_gst' => (float) ($invoice->total_gst ?? 0),
                'total_amount' => $paidAmount,
                'status' => $status,
                'payment_method' => $paymentMethod,
            ]);

            $invoice->update([
                'status' => 'completed',
                'payment_method' => $paymentMethod,
                'coins_applied' => $effectiveAppliedCoins,
                'coins_earned' => $coinsEarned,
                'discount_price' => round((float) ($invoice->discount_price ?? 0) + $coinsDiscountAmount, 2),
            ]);

            if ($transaction->status === 'completed' && isset($coinsWallet)) {
                $walletService = app(CoinsWalletService::class);

                if ($effectiveAppliedCoins > 0) {
                    $walletService->debit($coinsWallet, $effectiveAppliedCoins);
                }

                if ($coinsEarned > 0) {
                    $walletService->creditAfterPayment($coinsWallet, $coinsEarned);
                }

                $finalCoinsBalance = (int) ($coinsWallet->fresh()->coins ?? 0);
            }

            if ($hipUser && $this->hasCoinsBalanceColumn() && isset($finalCoinsBalance)) {
                $hipUser->coins_balance = $finalCoinsBalance;
                $hipUser->save();
            }

            $transactionReference = 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);

            Log::info('Invoice payment completed', [
                'invoice_id' => $invoice->id,
                'transaction_id' => $transactionReference,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'amount_paid_client' => $paidAmountFromClient,
                'amount_paid' => $paidAmount,
                'coins_applied' => $effectiveAppliedCoins,
                'coins_discount_amount_client' => $coinsDiscountAmountFromClient,
                'coins_discount_amount' => $coinsDiscountAmount,
                'amount_for_one_coin' => $coinValue,
                'coins_earned' => $coinsEarned,
            ]);

            // prompt review after a manual invoice payment completion as well
            try {
                $this->sendHospitalReviewRequest($invoice);
            } catch (\Throwable $e) {
                Log::warning('Failed to send hospital review push', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return [
                'success' => true,
                'invoice_id' => (int) $invoice->id,
                'member_id' => $memberId !== null ? (string) $memberId : null,
                'transaction_id' => (string) $transactionReference,
                'status' => (string) $status,
                'payment_method' => (string) $paymentMethod,
                'amount_paid' => (float) $paidAmount,
                'coins_applied' => (int) $effectiveAppliedCoins,
                'coins_earned' => (int) $coinsEarned,
            ];
        });
    }
}

