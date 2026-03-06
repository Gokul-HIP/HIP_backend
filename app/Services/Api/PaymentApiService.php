<?php

namespace App\Services\Api;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use App\Models\UserDevice;
use App\Models\RazorpayPayment;
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
    protected Api $razorpayApi;
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
            Log::error('Razorpay credentials not configured', [
                'has_key_id' => !empty($keyId),
                'has_key_secret' => !empty($keySecret),
                'env_vars' => [
                    'RAZORPAY_KEY_ID' => !empty(env('RAZORPAY_KEY_ID')),
                    'RAZORPAY_KEY' => !empty(env('RAZORPAY_KEY')),
                    'RAZORPAY_KEY_SECRET' => !empty(env('RAZORPAY_KEY_SECRET')),
                    'RAZORPAY_SECRET' => !empty(env('RAZORPAY_SECRET')),
                ]
            ]);
            throw new InvalidArgumentException('Razorpay API credentials are not properly configured. Please set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET (or RAZORPAY_KEY and RAZORPAY_SECRET) in .env file.');
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
            throw new InvalidArgumentException('Failed to initialize Razorpay API: ' . $e->getMessage());
        }
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

    private function resolveCoinsWallet(int $personId, ?int $organizationId): Coins
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

    private function resolveOrganizationId(?HIPUser $hipUser, ?Invoice $invoice): ?int
    {
        $invoiceCreator = ($invoice && !empty($invoice->created_by))
            ? HIPUser::find((int) $invoice->created_by)
            : null;

        $organizationId = $hipUser?->organization_id ?: $invoiceCreator?->organization_id;
        return $organizationId ? (int) $organizationId : null;
    }

    /**
     * @return array{invoice: \App\Models\Invoice, coins_earned: int}
     */
    public function createInvoiceForPayment(array $payload): array
    {
        $personId = (int) ($payload['person_id'] ?? 0);
        if ($personId <= 0) {
            throw new InvalidArgumentException('person_id is required.');
        }

        $person = Persons::findOrFail($personId);
        $primaryPersonId = (int) ($payload['primary_person_id'] ?? ($person->parent_id ?: $person->id));
        $primaryPerson = Persons::find($primaryPersonId) ?: $person;

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
        $memberId = 'HIP-' . str_pad((string) $person->id, 6, '0', STR_PAD_LEFT);
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

            $hipUser = HIPUser::find($primaryPerson->hip_user_id);
            if (!$hipUser) {
                Log::warning('Invoice created but HIP user not found for notification.', [
                    'invoice_id' => $invoice->id,
                    'primary_person_id' => $primaryPerson->id,
                ]);

                return [
                    'invoice' => $invoice,
                    'coins_earned' => $coinsEarned,
                ];
            }

            $organizationId = $this->resolveOrganizationId($hipUser, $invoice);
            $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId);
            $coinsBalance = $this->resolveAvailableCoins($coinsWallet, $hipUser);
            $coinsValue = round($coinsBalance * $this->amountForOneCoin(), 2);
            $signedToken = URL::temporarySignedRoute(
                'payments.invoice.page',
                now()->addMinutes(30),
                ['invoice_id' => $invoice->id]
            );

            $queryData = [
                'invoice_id' => $invoice->id,
                'amount' => number_format($totalAmount, 2, '.', ''),
                'person_id' => $person->id,
                'primary_person_id' => $primaryPerson->id,
                'person_name' => $personName,
                'member_id' => $memberId,
                'service_types' => $serviceTypesString,
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total_gst' => number_format($totalGst, 2, '.', ''),
                'service_charges' => number_format($serviceCharges, 2, '.', ''),
                'gateway_charges' => number_format($paymentGatewayCharges, 2, '.', ''),
                'discount' => number_format($discountPrice, 2, '.', ''),
                'coins_balance' => (string) $coinsBalance,
                'coins_value' => number_format($coinsValue, 2, '.', ''),
                'coins_earned' => (string) $coinsEarned,
                'prescription' => $prescriptionPath ? '1' : '0',
                'invoice_details' => json_encode($invoiceDetails, JSON_UNESCAPED_UNICODE),
                'token' => $signedToken,
            ];

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
            $title = 'New Invoice Created';
            $body = 'Your payment of Rs ' . number_format($totalAmount, 2) . ' is ready. Tap to pay now.';
            $data = [
                // Mobile app expects this shape to navigate in-app (same as booking notifications).
                'type' => 'navigate',
                'route' => $appRoute,
                'screen' => 'payment_request',
                'invoice_token' => $signedToken,
                'api_base' => rtrim(config('app.url'), '/'),
                // Keep url for backward compatibility/fallback.
                'url' => $paymentUrl,
                'invoice_id' => (string) $invoice->id,
                'amount' => number_format($totalAmount, 1, '.', ''),
                'person_name' => $personName,
                'service_types' => $serviceTypesString,
                'coins_earned' => (string) $coinsEarned,
            ];

            if ($deviceId) {
                $notified = $this->notificationService->sendToDevice($hipUser->id, $deviceId, $title, $body, $data);
                if ($notified) {
                    $invoice->is_notified = true;
                    $invoice->save();
                }
            } else {
                // Deduplicate by FCM token so same physical device does not receive duplicate pushes.
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
                            'user_id' => $target->user_id,
                            'device_id' => $target->device_id,
                        ]
                    );
                    if ($result) {
                        $invoice->is_notified = true;
                        $invoice->save();
                    }
                }
            }
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

    public function getInvoicePaymentRequestData(int $invoiceId): array
    {
        $invoice = Invoice::with(['person', 'primaryPerson'])->findOrFail($invoiceId);
        $person = $invoice->person;
        $primaryPerson = $invoice->primaryPerson ?? $person;
        if (!$person || !$primaryPerson) {
            throw new InvalidArgumentException('Invalid invoice member mapping.');
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);
        $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId);
        $coinsBalance = $this->resolveAvailableCoins($coinsWallet, $hipUser);

        $created_by = HIPUser::find($invoice->created_by);
        $hospital = Hospital::find($created_by->hospital_id);
       
        $amountForOneCoin = $this->amountForOneCoin();
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];

        return [
            'invoice_id' => (int) $invoice->id,
            'status' => (string) $invoice->status,
            'hospital_name' => $hospital->name,
            'hospital_type' => $hospital->subtitle,
            'hospital_logo' => $hospital->logo ? asset('storage/hospital/'. $hospital->logo):null,
            'person_id' => (int) $primaryPerson->id,
            'primary_person_id' => (int) $primaryPerson->id,
            'member_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
            'member_id' => 'MEM-' . str_pad((string) $person->id, 6, '0', STR_PAD_LEFT),
            'member_image' => $person->image ? asset('storage/users/'.$person->image):null,
            'service_types' => $serviceTypes,
            'invoice_details' => $invoice->invoice_details ?? [],
            'subtotal' => (float) ($invoice->amount ?? 0),
            'total_gst' => (float) ($invoice->total_gst ?? 0),
            'service_charges' => (float) ($invoice->service_charges ?? 0),
            'gateway_charges' => (float) ($invoice->payment_gateway_charges ?? 0),
            'discount' => (float) ($invoice->discount_price ?? 0),
            'amount' => (float) ($invoice->total_amount ?? 0),
            'coins_balance' => $coinsBalance,
            'amount_for_one_coin' => $amountForOneCoin,
            'coins_value' => round($coinsBalance * $amountForOneCoin, 2),
            'coins_earned' => (int) ($invoice->coins_earned ?? round(((float) $invoice->total_amount) * 0.01)),
            'prescription' => !empty($invoice->prescription_img),
            'payment_method' => $invoice->payment_method,
            'created_at' => optional($invoice->created_at)?->toDateTimeString(),
        ];
    }

    public function calculateCoinsApplication(int $invoiceId, int $requestedCoins): array
    {
        $invoice = Invoice::with(['primaryPerson'])->findOrFail($invoiceId);
        $primaryPerson = $invoice->primaryPerson;
        if (!$primaryPerson) {
            throw new InvalidArgumentException('Primary person not found for invoice.');
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);
        $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId);

        $availableCoins = $this->resolveAvailableCoins($coinsWallet, $hipUser);
        $effectiveCoins = max(0, min($requestedCoins, $availableCoins));
        $amountForOneCoin = $this->amountForOneCoin();
        $originalAmount = (float) ($invoice->total_amount ?? 0);
        $coinsDiscountAmount = min(round($effectiveCoins * $amountForOneCoin, 2), $originalAmount);
        $payableAmount = round(max(0, $originalAmount - $coinsDiscountAmount), 2);

        return [
            'invoice_id' => (int) $invoice->id,
            'coins_available' => $availableCoins,
            'coins_requested' => $requestedCoins,
            'coins_applied' => $effectiveCoins,
            'amount_for_one_coin' => $amountForOneCoin,
            'coins_discount_amount' => $coinsDiscountAmount,
            'original_amount' => $originalAmount,
            'payable_amount' => $payableAmount,
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
            $primaryPersonId = (int) $invoice->primary_person_id;
            $primaryPerson = Persons::find($primaryPersonId);
            if (!$primaryPerson) {
                throw new InvalidArgumentException("Primary person not found for invoice.");
            }

            $hipUser = HIPUser::find($primaryPerson->hip_user_id);
            $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

            // Calculate payable amount after coins
            $coinValue = $this->amountForOneCoin();
            $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
            $walletCoins = $this->resolveAvailableCoins($coinsWallet, $hipUser);
            $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoins));
            $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), (float) $invoice->total_amount);
            $payableAmount = max(0, (float) $invoice->total_amount - $coinsDiscountAmount);

            // Create Razorpay order (amount in paise)
            try {
                $order = $this->razorpayApi->order->create([
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
                'order_id' => $order->id,
                'razorpay_key' => $this->razorpayKeyId,
                'amount' => (int) round($payableAmount * 100), // Return in paise
                'invoice_id' => $invoice->id,
                'transaction_id' => $transactionReference,
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
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            // Prevent duplicate payment completion
            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is already processed.");
            }

            $razorpayPaymentRecord = RazorpayPayment::where('invoice_id', $invoiceId)
                ->where('razorpay_order_id', $razorpayOrderId)
                ->firstOrFail();

            // Verify Razorpay signature
            try {
                $this->razorpayApi->utility->verifyPaymentSignature([
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
            $payment = $this->razorpayApi->payment->fetch($razorpayPaymentId);

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

            // Apply coins logic
            $storedCoinsApplied = isset($razorpayPaymentRecord->coins_applied)
                ? (int) $razorpayPaymentRecord->coins_applied
                : $coinsApplied;

            $coinResult = $this->applyCoinsLogic($invoice, $transaction, $storedCoinsApplied);

            // Update transaction to completed
            $transaction->update([
                'status' => 'completed',
                'payment_method' => $payment->method ?? 'razorpay',
                'discount_amount' => $coinResult['coinsDiscountAmount'],
                'total_amount' => $coinResult['payableAmount'],
            ]);

            // Update invoice to completed
            $invoice->update([
                'status' => 'completed',
                'payment_method' => 'razorpay',
                'coins_applied' => $coinResult['effectiveAppliedCoins'],
                'coins_earned' => $coinResult['coinsEarned'],
                'discount_price' => round((float) ($invoice->discount_price ?? 0) + $coinResult['coinsDiscountAmount'], 2),
            ]);

            $transactionReference = 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);

            Log::info('Razorpay payment completed', [
                'invoice_id' => $invoice->id,
                'razorpay_payment_id' => $razorpayPaymentId,
                'transaction_id' => $transactionReference,
                'amount_paid' => $coinResult['payableAmount'],
                'coins_applied' => $coinResult['effectiveAppliedCoins'],
                'coins_earned' => $coinResult['coinsEarned'],
            ]);

            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'transaction_id' => $transactionReference,
                'status' => 'completed',
                'payment_method' => $payment->method ?? 'razorpay',
                'amount_paid' => $coinResult['payableAmount'],
                'coins_applied' => $coinResult['effectiveAppliedCoins'],
                'coins_earned' => $coinResult['coinsEarned'],
            ];
        });
    }

    /**
     * Apply coins logic to invoice payment (deduct and earn).
     * 
     * @return array{effectiveAppliedCoins: int, coinsDiscountAmount: float, payableAmount: float, coinsEarned: int}
     */
    private function applyCoinsLogic(Invoice $invoice, Transactions $transaction, int $coinsApplied): array
    {
        $primaryPersonId = (int) $invoice->primary_person_id;
        $primaryPerson = Persons::find($primaryPersonId);
        if (!$primaryPerson) {
            throw new InvalidArgumentException("Primary person not found for invoice.");
        }

        $hipUser = HIPUser::find($primaryPerson->hip_user_id);
        $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

        $coinValue = $this->amountForOneCoin();
        $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
        $walletCoinsBefore = $this->resolveAvailableCoins($coinsWallet, $hipUser);
        $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoinsBefore));
        $baseAmount = (float) ($transaction->transaction_amount ?? $invoice->total_amount ?? 0);
        $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), $baseAmount);
        $payableAmount = round(max(0, $baseAmount - $coinsDiscountAmount), 2);
        $coinsEarned = (int) round($payableAmount * 0.01);
        $finalCoinsBalance = max(0, $walletCoinsBefore - $effectiveAppliedCoins) + $coinsEarned;

        // Update wallet
        $coinsWallet->coins = $finalCoinsBalance;
        $coinsWallet->save();

        // Update HIPUser coins_balance if column exists
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
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);

            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is not pending.");
            }

            $serviceTypes = $payload['service_types'] ?? $invoice->service_types ?? [];
            if (is_string($serviceTypes)) {
                $serviceTypes = array_values(array_filter(array_map('trim', explode(',', $serviceTypes))));
            }

            $coinValue = $this->amountForOneCoin();
            $coinsDiscountAmount = 0.0;
            $paidAmount = round(max(0, $originalAmount), 2);
            $coinsEarned = (int) round($paidAmount * 0.01);
            $primaryPersonId = (int) ($payload['primary_person_id'] ?? $invoice->primary_person_id);
            $primaryPerson = Persons::find($primaryPersonId);
            $hipUser = $primaryPerson ? HIPUser::find($primaryPerson->hip_user_id) : null;
            $organizationId = $this->resolveOrganizationId($hipUser, $invoice);

            $effectiveAppliedCoins = 0;
            $walletCoinsBefore = 0;
            if ($primaryPerson) {
                $coinsWallet = $this->resolveCoinsWallet((int) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
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
                $finalCoinsBalance = max(0, $walletCoinsBefore - $effectiveAppliedCoins) + $coinsEarned;
                $coinsWallet->coins = $finalCoinsBalance;
                $coinsWallet->save();
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

            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'transaction_id' => $transactionReference,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'amount_paid' => $paidAmount,
                'coins_applied' => $effectiveAppliedCoins,
                'coins_earned' => $coinsEarned,
            ];
        });
    }
}
