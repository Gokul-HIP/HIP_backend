<?php

namespace App\Services\Api;

use App\Exceptions\ChatbotSlotNoLongerAvailableException;
use App\Models\Coins;
use App\Models\DiseaseChatbotBooking;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Services\ChatbotBookingService;
use App\Services\DoctorBookingStatusService;
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
use App\Services\DoctorNotificationService;
use App\Services\InvoiceFeeCalculator;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use App\Modules\Automation\Support\RewardPointsScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;
use Throwable;

class PaymentApiService
{
    public const SERVER_RECONCILE_SIGNATURE = 'server_reconcile';

    protected NotificationService $notificationService;
    protected ?Api $razorpayApi = null;
    protected string $razorpayKeyId = '';
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

    public function amountForOneCoin(): float
    {
        if ($this->coinAmountValue === null) {
            // $this->coinAmountValue = (float) env('AMOUNT_For_ONE_COIN', env('AMOUNT_FOR_ONE_COIN', 0.10));
            $this->coinAmountValue = (float) app_setting(
                'amount_for_one_coin',
                // config('settings.payment.amount_for_one_coin', 1)
                10
            );
        }

        return $this->coinAmountValue;
    }

    public function serviceChargesPercent(): float
    {
        return app(InvoiceFeeCalculator::class)->serviceChargesPercent();
    }

    public function calculateServiceCharges(float $baseAmount): float
    {
        return app(InvoiceFeeCalculator::class)->serviceCharges($baseAmount);
    }

    public function ensureInvoiceServiceCharges(Invoice $invoice): float
    {
        $existing = round((float) ($invoice->service_charges ?? 0), 2);

        if ($existing > 0) {
            return $existing;
        }

        // Booking invoices already include calculated service charges on total_amount.
        if ($invoice->second_opinion_id || $invoice->doctor_booking_id || $invoice->diagnostic_test_booking_id) {
            return 0.0;
        }

        $subtotal = round((float) ($invoice->amount ?? 0), 2);
        $serviceCharges = $this->calculateServiceCharges($subtotal);

        if ($serviceCharges <= 0) {
            return 0.0;
        }

        $invoice->update(['service_charges' => $serviceCharges]);
        $invoice->service_charges = $serviceCharges;

        return $serviceCharges;
    }

    /**
     * Booking-style Razorpay payload for pending hospital bills.
     *
     * @return array<string, mixed>
     */
    public function resolvePayBillPaymentData(Invoice $invoice, ?int $requestedCoins = null): array
    {
        $this->ensureInvoiceServiceCharges($invoice);
        $invoice->refresh();

        if ($invoice->status !== 'pending') {
            return [
                'requires_payment' => false,
                'invoice_id' => (int) $invoice->id,
                'payment_status' => (string) $invoice->status,
            ];
        }

        if ((float) ($invoice->total_amount ?? 0) <= 0) {
            return [
                'requires_payment' => false,
                'invoice_id' => (int) $invoice->id,
                'payment_status' => 'paid',
            ];
        }

        if ($invoice->transactions()->where('status', 'completed')->exists()) {
            return [
                'requires_payment' => false,
                'invoice_id' => (int) $invoice->id,
                'payment_status' => 'completed',
            ];
        }

        // $razorpayOrder = $this->findExistingPendingRazorpayOrder((int) $invoice->id)
        //     ?? $this->createRazorpayOrder((int) $invoice->id, 0);
        // if ($invoice->pharmacy_order_id) {
        //     $cappedCoins = $this->cappedCoinsForPharmacyInvoice($invoice, max(0, (int) $requestedCoins));
        //     $razorpayOrder = $this->pharmacyRazorpayOrder($invoice, $cappedCoins, $requestedCoins);
        // } else {
        //     $razorpayOrder = $this->findExistingPendingRazorpayOrder((int) $invoice->id)
        //         ?? $this->createRazorpayOrder((int) $invoice->id, 0);
        // }
        if ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id) {
            $razorpayOrder = $this->findExistingPendingRazorpayOrder((int) $invoice->id)
                ?? $this->createRazorpayOrder((int) $invoice->id, 0);
        } elseif ($requestedCoins === null) {
            // No coins_applied query keeps the pending order and its stored coin count.
            $razorpayOrder = $this->findExistingPendingRazorpayOrder((int) $invoice->id)
                ?? $this->pharmacyRazorpayOrder($invoice, 0, null);
        } else {
            // Explicit coins_applied, including 0, replaces the order when the stored count differs.
            $cappedCoins = $this->cappedCoinsForPharmacyInvoice($invoice, max(0, (int) $requestedCoins));
            $razorpayOrder = $this->pharmacyRazorpayOrder($invoice, $cappedCoins, $requestedCoins);
        }

        return [
            'requires_payment' => true,
            'invoice_id' => (int) $razorpayOrder['invoice_id'],
            'order_id' => (string) $razorpayOrder['order_id'],
            'razorpay_key' => (string) $razorpayOrder['razorpay_key'],
            'amount' => (int) $razorpayOrder['amount'],
            'transaction_id' => (string) $razorpayOrder['transaction_id'],
            'currency' => 'INR',
            'payment_status' => 'pending',
        ];
    }

    /**
     * @return array{success: bool, order_id: string, razorpay_key: string, amount: int, invoice_id: int, transaction_id: string}|null
     */
    private function findExistingPendingRazorpayOrder(int $invoiceId): ?array
    {
        $razorpayPayment = RazorpayPayment::query()
            ->where('invoice_id', $invoiceId)
            ->where('payment_status', 'created')
            ->latest('id')
            ->first();

        if (! $razorpayPayment?->razorpay_order_id) {
            return null;
        }

        $transaction = Transactions::query()
            ->where('invoice_id', $invoiceId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (! $transaction) {
            return null;
        }

        return [
            'success' => true,
            'order_id' => (string) $razorpayPayment->razorpay_order_id,
            'razorpay_key' => (string) $this->razorpayKeyId,
            'amount' => (int) round(((float) $razorpayPayment->amount_paid) * 100),
            'invoice_id' => $invoiceId,
            'transaction_id' => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
        ];
    }

    /**
     * Same rate as every other coin price. The old app_setting/config read is below.
     */
    private function pharmacyCoinRate(): float
    {
        // return (float) app_setting(
        //     'amount_for_one_coin',
        //     config('settings.payment.amount_for_one_coin', 1)
        // );
        return $this->amountForOneCoin();
    }

    /**
     * One pharmacy coin quote: cap, rupee discount, payable rupees, and Razorpay paise.
     *
     * @return array{coins: int, rate: float, discount: float, payable: float, paise: int, total: float}
     */
    public function pharmacyCoinQuote(Invoice $invoice, int $requestedCoins): array
    {
        $rate = $this->pharmacyCoinRate();
        $total = round((float) ($invoice->total_amount ?? 0), 2);
        $coins = $this->cappedCoinsForPharmacyInvoice($invoice, max(0, $requestedCoins));
        $discount = ($coins > 0 && $rate > 0 && $total > 0)
            ? round(min($coins * $rate, $total), 2)
            : 0.0;
        $payable = round(max(0, $total - $discount), 2);

        return [
            'coins' => $coins,
            'rate' => $rate,
            'discount' => $discount,
            'payable' => $payable,
            'paise' => (int) round($payable * 100),
            'total' => $total,
        ];
    }

    /**
     * Same cap as the pharmacy user-coins preview: min(requested, wallet, floor((total - 1) / rate)).
     */
    private function cappedCoinsForPharmacyInvoice(Invoice $invoice, int $requested): int
    {
        $rate = $this->pharmacyCoinRate();
        $total = (float) ($invoice->total_amount ?? 0);
        if ($total <= 0 || $rate <= 0) {
            return 0;
        }

        $person = Persons::query()->find((string) $invoice->primary_person_id);
        $walletPersonId = $person?->parent_id ?: $person?->id;
        $available = 0;
        if ($walletPersonId) {
            $available = (int) (Coins::query()->where('person_id', $walletPersonId)->first()?->coins ?? 0);
        }

        // $maxForBill = (int) floor($total / $rate);
        // Leave at least ₹1 payable. Razorpay rejects an online amount below ₹1.
        $maxForBill = (int) floor(max(0, $total - 1) / $rate);

        return min(max(0, $requested), max(0, $available), max(0, $maxForBill));
    }

    /**
     * Reuse a pending pharmacy Razorpay order only when the stored coin count matches.
     * Transactions can be cancelled. razorpay_payments has no cancelled status in this code.
     *
     * @return array{success: bool, order_id: string, razorpay_key: string, amount: int, invoice_id: int, transaction_id: string}
     */
    private function pharmacyRazorpayOrder(Invoice $invoice, int $cappedCoins, ?int $coinsReceived = null): array
    {
        $existing = $this->findExistingPendingRazorpayOrder((int) $invoice->id);
        $superseded = [];
        $reused = false;
        if ($existing !== null) {
            $payment = RazorpayPayment::query()
                ->where('invoice_id', $invoice->id)
                ->where('razorpay_order_id', $existing['order_id'])
                ->first();
            if ((int) ($payment->coins_applied ?? 0) === $cappedCoins) {
                $reused = true;
                $order = $existing;
            } else {
                $superseded[] = (string) $existing['order_id'];
                Transactions::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
                // Previously the old row stayed payment_status created. Verifying that order_id
                // then completed the newest pending transaction for the full bill and zero coins.
                // failed is not used here: PharmacyRazorpayPaymentObserver treats failed as a failed pharmacy payment.
                RazorpayPayment::query()
                    ->where('invoice_id', $invoice->id)
                    ->where('razorpay_order_id', $existing['order_id'])
                    ->where('payment_status', 'created')
                    ->update(['payment_status' => 'superseded']);
            }
        }

        if (! $reused) {
            $order = $this->createRazorpayOrder((int) $invoice->id, $cappedCoins);
        }

        Log::info('Pharmacy pay bill Razorpay order', [
            'invoice_id' => (int) $invoice->id,
            'pharmacy_order_id' => (int) $invoice->pharmacy_order_id,
            'user_id' => auth()->id(),
            'coins_applied' => $coinsReceived === null ? 'none' : $coinsReceived,
            'capped_coins' => $cappedCoins,
            'order' => $reused ? 'reused' : 'new',
            'razorpay_order_id' => (string) $order['order_id'],
            'amount' => (int) $order['amount'],
            'superseded' => $superseded,
        ]);

        return $order;
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

    private function resolveHospitalForInvoice(Invoice $invoice): ?Hospital
    {
        $invoice->loadMissing(['creator', 'doctorBooking', 'diagnosticTestBooking', 'secondOpinion']);

        if ($invoice->creator?->hospital_id) {
            return Hospital::find($invoice->creator->hospital_id);
        }

        if ($invoice->doctorBooking?->hospital_id) {
            return Hospital::find($invoice->doctorBooking->hospital_id);
        }

        $doctorBooking = $invoice->doctor_booking_id
            ? null
            : DoctorBooking::query()->where('invoice_id', $invoice->id)->first();

        if ($doctorBooking?->hospital_id) {
            return Hospital::find($doctorBooking->hospital_id);
        }

        if ($invoice->diagnosticTestBooking?->branch_id) {
            return Hospital::find($invoice->diagnosticTestBooking->branch_id);
        }

        $diagnosticBooking = $invoice->diagnostic_test_booking_id
            ? null
            : DiagnosticTestBooking::query()->where('invoice_id', $invoice->id)->first();

        if ($diagnosticBooking?->branch_id) {
            return Hospital::find($diagnosticBooking->branch_id);
        }

        if ($invoice->secondOpinion?->branch_id) {
            return Hospital::find($invoice->secondOpinion->branch_id);
        }

        return null;
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
        $isInPatient = filter_var($payload['is_in_patient'] ?? false, FILTER_VALIDATE_BOOLEAN);

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
                'is_in_patient' => $isInPatient,
            ]);

            $suppressPaymentPush = filter_var($payload['suppress_payment_push'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $isInPatient && ! $suppressPaymentPush) {
                $this->sendInvoiceNotification($invoice, false, $deviceId);
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
            'type' => 'payment_request',
            'notification_type' => 'payment_request',
            'source' => 'payment',
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
            ->where('data->type', 'payment_request')
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

        $hospital = $this->resolveHospitalForInvoice($invoice);
        $serviceCharges = $this->ensureInvoiceServiceCharges($invoice);

        $amountForOneCoin = $this->amountForOneCoin();
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];

        return [
            'invoice_id' => (int) $invoice->id,
            'status' => (string) $invoice->status,
            'hospital_name' => (string) ($hospital?->name ?? ''),
            'hospital_type' => (string) ($hospital?->subtitle ?? ''),
            'hospital_logo' => $hospital?->logo ? asset('storage/hospital/'. $hospital->logo) : null,
            'person_id' => (string) $primaryPerson->id,
            'primary_person_id' => (string) $primaryPerson->id,
            'member_name' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
            'member_id' => $person->hipUser?->hip_id ?? $primaryPerson->hipUser?->hip_id ?? null,
            'member_image' => $person->image ? asset('storage/users/'.$person->image):null,
            'service_types' => $serviceTypes,
            'invoice_details' => $invoice->invoice_details ?? [],
            'subtotal' => (float) ($invoice->amount ?? 0),
            'total_gst' => (float) ($invoice->total_gst ?? 0),
            'service_charges' => (float) $serviceCharges,
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
        $existingOrder = $this->findExistingPendingRazorpayOrder($invoiceId);
        if ($existingOrder !== null) {
            Log::info('Reusing existing pending Razorpay order', [
                'invoice_id' => $invoiceId,
                'order_id' => $existingOrder['order_id'],
            ]);

            return $existingOrder;
        }

        Log::info('Razorpay order creation initiated', [
            'invoice_id' => $invoiceId,
            'coins_applied' => $coinsApplied,
        ]);

        return DB::transaction(function () use ($invoiceId, $coinsApplied) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoiceId);
            $this->ensureInvoiceServiceCharges($invoice);
            $invoice->refresh();

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

            // Calculate payable amount after coins.
            // Non-booking invoices use the shared quote. Booking coins stay 0; they were applied at booking.
            $coinValue = $this->amountForOneCoin();
            $coinsWallet = $this->resolveCoinsWallet((string) $primaryPerson->id, $organizationId ? (int) $organizationId : null);
            $walletCoins = $this->resolveAvailableCoins($coinsWallet, $hipUser);
            if ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id) {
                $coinsApplied = 0;
            }
            if ($invoice->pharmacy_order_id || ! ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id)) {
                $quote = $this->pharmacyCoinQuote($invoice, $coinsApplied);
                $effectiveAppliedCoins = $quote['coins'];
                $coinsDiscountAmount = $quote['discount'];
                $payableAmount = $quote['payable'];
            } else {
                // $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoins));
                // $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), (float) $invoice->total_amount);
                // $payableAmount = max(0, (float) $invoice->total_amount - $coinsDiscountAmount);
                $effectiveAppliedCoins = 0;
                $coinsDiscountAmount = 0.0;
                $payableAmount = (float) $invoice->total_amount;
            }
            $amountPaise = (int) round($payableAmount * 100);

            if ($amountPaise < 100) {
                if ($invoice->pharmacy_order_id) {
                    Log::info('Pharmacy pay bill rejected', [
                        'invoice_id' => (int) $invoice->id,
                        'pharmacy_order_id' => (int) $invoice->pharmacy_order_id,
                        'user_id' => auth()->id(),
                        'reason' => 'amount below minimum',
                        'amount' => $amountPaise,
                    ]);
                }

                throw new InvalidArgumentException(
                    'Payment amount is below the minimum allowed for online payment (₹1).'
                );
            }

            // Create Razorpay order (amount in paise)
            try {
                $order = $this->getRazorpayApi()->order->create([
                    'receipt' => 'inv_' . $invoice->id . '_' . strtoupper(Str::random(8)),
                    'amount' => $amountPaise,
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

            $transaction = new Transactions();
            $this->guardUserstamps($transaction, 'transactions');
            $transaction->fill([
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
            $transaction->save();

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
                'amount' => $amountPaise,
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
        $razorpayOrderId   = (string) ($payload['razorpay_order_id'] ?? '');
        $razorpaySignature = (string) ($payload['razorpay_signature'] ?? '');
        $invoiceId         = (int) ($payload['invoice_id'] ?? 0);
        $coinsApplied      = (int) ($payload['coins_applied'] ?? 0);

        if (!$razorpayPaymentId || !$razorpayOrderId || !$razorpaySignature || $invoiceId <= 0) {
            throw new InvalidArgumentException('Missing required payment verification data.');
        }

        $invoice = Invoice::query()->with(['person'])->findOrFail($invoiceId);
        if ($invoice->status !== 'pending') {
            $expiredLink = $this->expiredChatbotLinkForInvoice($invoice);
            if ($expiredLink !== null) {
                return $this->completeExpiredChatbotRazorpayPayment(
                    $invoice,
                    $expiredLink,
                    $payload
                );
            }

            $replay = $this->replayCompletedRazorpayPayment(
                $invoice,
                $razorpayOrderId,
                $razorpayPaymentId,
                $razorpaySignature
            );
            if ($replay !== null) {
                return $replay;
            }

            $duplicate = $this->duplicateCapturedPharmacyPayment(
                $invoice,
                $razorpayOrderId,
                $razorpayPaymentId,
                $razorpaySignature
            );
            if ($duplicate !== null) {
                return $duplicate;
            }

            $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'already processed');

            throw new InvalidArgumentException("Invoice {$invoice->id} is already processed.");
        }

        $razorpayPaymentRecord = RazorpayPayment::where('invoice_id', $invoiceId)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->first();

        if (! $razorpayPaymentRecord) {
            $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'not found');

            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(RazorpayPayment::class);
        }

        if ($invoice->pharmacy_order_id) {
            Log::info('Pharmacy verify payment', [
                'invoice_id' => $invoiceId,
                'pharmacy_order_id' => (int) $invoice->pharmacy_order_id,
                'user_id' => auth()->id(),
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'coins_applied' => $coinsApplied,
                'stored_coins_applied' => (int) ($razorpayPaymentRecord->coins_applied ?? 0),
            ]);
        }

        $rowStatus = (string) $razorpayPaymentRecord->payment_status;
        // A superseded pharmacy row can still be the order the customer paid. Check the
        // signature and the captured payment before rejecting it.
        if (! in_array($rowStatus, ['created', 'superseded'], true)) {
            $this->logPharmacyVerifyRejection(
                $invoice,
                $razorpayOrderId,
                $razorpayPaymentId,
                'no longer payable'
            );

            throw new InvalidArgumentException('This payment order is no longer payable.');
        }

        // A bad signature is the only case that marks this order failed.
        // Any other error here must leave the row payable.
        try {
            $this->getRazorpayApi()->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature'  => $razorpaySignature,
            ]);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed', [
                'invoice_id' => $invoiceId,
                'pharmacy_order_id' => $invoice->pharmacy_order_id ? (int) $invoice->pharmacy_order_id : null,
                'user_id' => auth()->id(),
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'reason' => 'bad signature',
                'error'      => $e->getMessage(),
            ]);

            $this->markRazorpaySignatureFailed($razorpayPaymentRecord, $razorpayPaymentId, $razorpaySignature);

            throw new InvalidArgumentException('Payment verification failed. Invalid signature.');
        } catch (\Exception $e) {
            Log::error('Razorpay signature check could not be completed', [
                'invoice_id' => $invoiceId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw new InvalidArgumentException('Payment verification could not be completed. '.$e->getMessage());
        }

        try {
            $payment = $this->getRazorpayApi()->payment->fetch($razorpayPaymentId);
            $paymentFields = $this->razorpayPaymentFields($payment);
        } catch (Throwable $e) {
            $this->logCapturedPaymentNotRecorded($invoiceId, $razorpayPaymentId, $e, $invoice->pharmacy_order_id ? (int) $invoice->pharmacy_order_id : null);

            throw new InvalidArgumentException('Payment was captured but could not be recorded. It was not marked failed. '.$e->getMessage());
        }

        $honouredAmount = null;
        $gatewayStatus = $paymentFields['status'];
        if ($rowStatus === 'superseded') {
            if ($gatewayStatus !== 'captured') {
                $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'not captured');

                throw new InvalidArgumentException('Payment was not captured.');
            }

            $honouredAmount = $paymentFields['amount'] !== null
                ? round(((int) $paymentFields['amount']) / 100, 2)
                : round((float) ($razorpayPaymentRecord->amount_paid ?? 0), 2);
        } elseif ($invoice->pharmacy_order_id && is_string($gatewayStatus) && $gatewayStatus !== '' && $gatewayStatus !== 'captured') {
            $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'not captured');

            throw new InvalidArgumentException('Payment was not captured.');
        }

        try {
            return $this->recordCapturedRazorpayPayment(
                $paymentFields,
                $razorpayPaymentId,
                $razorpayOrderId,
                $razorpaySignature,
                $invoiceId,
                $coinsApplied,
                $honouredAmount
            );
        } catch (InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'already processed') || str_contains($e->getMessage(), 'no longer payable')) {
                throw $e;
            }

            $this->logCapturedPaymentNotRecorded($invoiceId, $razorpayPaymentId, $e, $invoice->pharmacy_order_id ? (int) $invoice->pharmacy_order_id : null);

            throw new InvalidArgumentException('Payment was captured but could not be recorded. It was not marked failed. '.$e->getMessage());
        } catch (Throwable $e) {
            $this->logCapturedPaymentNotRecorded($invoiceId, $razorpayPaymentId, $e, $invoice->pharmacy_order_id ? (int) $invoice->pharmacy_order_id : null);

            throw new InvalidArgumentException('Payment was captured but could not be recorded. It was not marked failed. '.$e->getMessage());
        }
    }

    /**
     * Chatbot late payment: only when a disease_chatbot_bookings row is expired
     * for this invoice's doctor booking.
     */
    private function expiredChatbotLinkForInvoice(Invoice $invoice): ?DiseaseChatbotBooking
    {
        if (! $invoice->doctor_booking_id) {
            return null;
        }

        if (! Schema::hasTable('disease_chatbot_bookings')) {
            return null;
        }

        return DiseaseChatbotBooking::query()
            ->where('doctor_booking_id', (int) $invoice->doctor_booking_id)
            ->where('status', DiseaseChatbotBooking::STATUS_EXPIRED)
            ->first();
    }

    /**
     * Signature valid + captured after hold expiry: revive if the slot is free,
     * otherwise mark refund_required and throw 409.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function completeExpiredChatbotRazorpayPayment(
        Invoice $invoice,
        DiseaseChatbotBooking $link,
        array $payload
    ): array {
        $razorpayPaymentId = (string) ($payload['razorpay_payment_id'] ?? '');
        $razorpayOrderId = (string) ($payload['razorpay_order_id'] ?? '');
        $razorpaySignature = (string) ($payload['razorpay_signature'] ?? '');
        $invoiceId = (int) $invoice->id;

        $razorpayPaymentRecord = RazorpayPayment::where('invoice_id', $invoiceId)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->first();

        if (! $razorpayPaymentRecord) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(RazorpayPayment::class);
        }

        try {
            $this->getRazorpayApi()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);
        } catch (SignatureVerificationError $e) {
            throw new InvalidArgumentException('Payment verification failed. Invalid signature.');
        }

        try {
            $payment = $this->getRazorpayApi()->payment->fetch($razorpayPaymentId);
            $paymentFields = $this->razorpayPaymentFields($payment);
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Payment was captured but could not be recorded. It was not marked failed. '.$e->getMessage());
        }

        if (($paymentFields['status'] ?? null) !== 'captured') {
            throw new InvalidArgumentException('Payment was not captured.');
        }

        $booking = DoctorBooking::query()->find($link->doctor_booking_id);
        if (! $booking) {
            throw new InvalidArgumentException('Booking not found for expired chatbot payment.');
        }

        $date = $booking->booking_date?->format('Y-m-d') ?? '';
        $slot = is_array($booking->required_time_slots)
            ? (string) ($booking->required_time_slots[0] ?? '')
            : '';
        $hospitalId = (int) ($booking->hospital_id ?? $booking->branch_id ?? 0);
        $doctorId = (string) $booking->doctor_id;

        $refundRequired = false;

        DB::transaction(function () use (
            $invoice,
            $booking,
            $link,
            $razorpayPaymentRecord,
            $doctorId,
            $hospitalId,
            $date,
            $slot,
            &$refundRequired
        ): void {
            // Serialize late-revive vs new bookings for this doctor.
            Doctor::query()->whereKey($doctorId)->lockForUpdate()->first();

            $lockedLink = DiseaseChatbotBooking::query()->whereKey($link->id)->lockForUpdate()->firstOrFail();
            if ($lockedLink->status !== DiseaseChatbotBooking::STATUS_EXPIRED) {
                return;
            }

            $lockedBooking = DoctorBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $lockedInvoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $slotFree = app(ChatbotBookingService::class)->slotIsFree(
                $doctorId,
                $hospitalId,
                $date,
                $slot
            );

            if (! $slotFree) {
                $lockedLink->status = DiseaseChatbotBooking::STATUS_REFUND_REQUIRED;
                $lockedLink->save();
                $refundRequired = true;

                return;
            }

            $lockedBooking->status = DoctorBooking::STATUS_PENDING;
            $lockedBooking->appointment_status = DoctorBooking::APPOINTMENT_STATUS_NEW;
            $lockedBooking->payment_status = 'pending';
            $lockedBooking->save();

            $lockedInvoice->status = 'pending';
            $lockedInvoice->save();

            Transactions::query()
                ->where('invoice_id', $lockedInvoice->id)
                ->where('status', 'cancelled')
                ->update(['status' => 'pending']);

            if (in_array((string) $razorpayPaymentRecord->payment_status, ['superseded', 'created'], true)) {
                RazorpayPayment::query()
                    ->whereKey($razorpayPaymentRecord->id)
                    ->update(['payment_status' => 'created']);
            }

            $lockedLink->status = DiseaseChatbotBooking::STATUS_PENDING_PAYMENT;
            $lockedLink->save();
        });

        if ($refundRequired) {
            Log::critical('Chatbot late payment: slot no longer available, refund required', [
                'invoice_id' => $invoiceId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $razorpayOrderId,
                'doctor_booking_id' => (int) $booking->id,
                'disease_chatbot_booking_id' => (int) $link->id,
            ]);

            throw new ChatbotSlotNoLongerAvailableException(
                'This slot is no longer available. A refund is required.',
                $invoiceId,
                $razorpayPaymentId
            );
        }

        $result = $this->completeRazorpayPayment($payload);

        DiseaseChatbotBooking::query()
            ->whereKey($link->id)
            ->whereIn('status', [
                DiseaseChatbotBooking::STATUS_PENDING_PAYMENT,
                DiseaseChatbotBooking::STATUS_EXPIRED,
            ])
            ->update(['status' => DiseaseChatbotBooking::STATUS_CONFIRMED]);

        return $result;
    }

    /**
     * Same checkout submitted again after the invoice is already completed.
     * Returns the stored result and does not debit or credit coins a second time.
     *
     * @return array{success: bool, invoice_id: int, member_id: ?string, transaction_id: string, status: string, payment_method: string, amount_paid: float, coins_applied: int, coins_earned: int, date: string, time: string}|null
     */
    private function replayCompletedRazorpayPayment(
        Invoice $invoice,
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): ?array {
        if ((string) $invoice->status !== 'completed') {
            return null;
        }

        $razorpayPayment = RazorpayPayment::query()
            ->where('invoice_id', $invoice->id)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->first();

        if (! $razorpayPayment || (string) $razorpayPayment->payment_status !== 'captured') {
            return null;
        }

        $storedPaymentId = (string) ($razorpayPayment->razorpay_payment_id ?? '');
        $storedSignature = (string) ($razorpayPayment->razorpay_signature ?? '');
        if ($storedPaymentId === '' || ! hash_equals($storedPaymentId, $razorpayPaymentId)) {
            return null;
        }

        // Client verify after server reconcile is idempotent when payment id matches.
        $serverReconcile = $storedSignature === ''
            || $storedSignature === self::SERVER_RECONCILE_SIGNATURE;
        if (! $serverReconcile) {
            if ($razorpaySignature === '' || ! hash_equals($storedSignature, $razorpaySignature)) {
                return null;
            }
        } elseif ($razorpaySignature !== '' && $storedSignature === self::SERVER_RECONCILE_SIGNATURE) {
            RazorpayPayment::query()
                ->whereKey($razorpayPayment->id)
                ->update(['razorpay_signature' => $razorpaySignature]);
        }

        $transaction = Transactions::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();
        if (! $transaction) {
            return null;
        }

        $person = $invoice->relationLoaded('person') ? $invoice->person : null;
        $memberId = $person?->hipUser?->hip_id ?? null;

        return [
            'success' => true,
            'invoice_id' => (int) $invoice->id,
            'member_id' => $memberId !== null ? (string) $memberId : null,
            'transaction_id' => 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'status' => 'completed',
            'payment_method' => (string) ($transaction->payment_method ?: 'razorpay'),
            'amount_paid' => (float) $transaction->total_amount,
            'coins_applied' => (int) ($invoice->coins_applied ?? 0),
            'coins_earned' => (int) ($invoice->coins_earned ?? 0),
        ] + $this->completedPaymentStamp($transaction);
    }

    /**
     * Server-side finalize for a gateway-captured payment (chatbot reconcile).
     * Same persistence path as verify-payment after signature checks.
     *
     * @param  array<string, mixed>  $gatewayPayment
     * @return array<string, mixed>
     */
    public function finalizeServerCapturedPayment(
        int $invoiceId,
        string $razorpayOrderId,
        string $razorpayPaymentId,
        array $gatewayPayment
    ): array {
        $paymentFields = $this->razorpayPaymentFieldsFromArray($gatewayPayment);
        if (($paymentFields['status'] ?? null) !== 'captured') {
            throw new InvalidArgumentException('Payment was not captured.');
        }

        $coinsApplied = (int) (RazorpayPayment::query()
            ->where('invoice_id', $invoiceId)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->value('coins_applied') ?? 0);

        return $this->recordCapturedRazorpayPayment(
            $paymentFields,
            $razorpayPaymentId,
            $razorpayOrderId,
            self::SERVER_RECONCILE_SIGNATURE,
            $invoiceId,
            $coinsApplied,
            null
        );
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array{method: ?string, bank: mixed, wallet: mixed, vpa: mixed, card_last4: mixed, card_network: mixed, fee: float, tax: float, status: ?string, amount: ?int}
     */
    private function razorpayPaymentFieldsFromArray(array $payment): array
    {
        $card = $payment['card'] ?? null;
        $cardLast4 = is_array($card) ? ($card['last4'] ?? null) : null;
        $cardNetwork = is_array($card) ? ($card['network'] ?? null) : null;
        $fee = $payment['fee'] ?? null;
        $tax = $payment['tax'] ?? null;
        $method = $payment['method'] ?? null;
        $status = $payment['status'] ?? null;
        $amount = $payment['amount'] ?? null;

        return [
            'method' => is_string($method) && $method !== '' ? $method : null,
            'bank' => $payment['bank'] ?? null,
            'wallet' => $payment['wallet'] ?? null,
            'vpa' => $payment['vpa'] ?? null,
            'card_last4' => $cardLast4,
            'card_network' => $cardNetwork,
            'fee' => $fee !== null ? ((float) $fee) / 100 : 0.0,
            'tax' => $tax !== null ? ((float) $tax) / 100 : 0.0,
            'status' => is_string($status) && $status !== '' ? $status : null,
            'amount' => $amount !== null ? (int) $amount : null,
        ];
    }

    /**
     * Persist a captured Razorpay payment. The caller has already verified the signature
     * and fetched the payment. A failure here rolls this transaction back and does not mark the payment failed.
     *
     * @param  array{method: ?string, bank: mixed, wallet: mixed, vpa: mixed, card_last4: mixed, card_network: mixed, fee: float, tax: float, status: ?string, amount: ?int}  $paymentFields
     * @return array{success: bool, invoice_id: int, transaction_id: string, status: string, payment_method: string, amount_paid: float, coins_applied: int, coins_earned: int, date: string, time: string}
     */
    private function recordCapturedRazorpayPayment(
        array $paymentFields,
        string $razorpayPaymentId,
        string $razorpayOrderId,
        string $razorpaySignature,
        int $invoiceId,
        int $coinsApplied,
        ?float $honouredAmount = null
    ): array {
        return DB::transaction(function () use (
            $paymentFields,
            $razorpayPaymentId,
            $razorpayOrderId,
            $razorpaySignature,
            $invoiceId,
            $coinsApplied,
            $honouredAmount
        ) {
            $invoice = Invoice::lockForUpdate()->with(['person'])->findOrFail($invoiceId);

            if ($invoice->status !== 'pending') {
                throw new InvalidArgumentException("Invoice {$invoice->id} is already processed.");
            }

            $person   = $invoice->person;
            $memberId = $person?->hipUser?->hip_id ?? null;

            $razorpayPaymentRecord = RazorpayPayment::where('invoice_id', $invoiceId)
                ->where('razorpay_order_id', $razorpayOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            $payableStatuses = $honouredAmount !== null ? ['created', 'superseded'] : ['created'];
            if (! in_array((string) $razorpayPaymentRecord->payment_status, $payableStatuses, true)) {
                $this->logPharmacyVerifyRejection(
                    $invoice,
                    $razorpayOrderId,
                    $razorpayPaymentId,
                    'no longer payable'
                );

                throw new InvalidArgumentException('This payment order is no longer payable.');
            }

            $capturedUpdate = [
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature'  => $razorpaySignature,
                'payment_method'      => $paymentFields['method'],
                'bank'                => $paymentFields['bank'],
                'wallet'              => $paymentFields['wallet'],
                'vpa'                 => $paymentFields['vpa'],
                'card_last4'          => $paymentFields['card_last4'],
                'card_network'        => $paymentFields['card_network'],
                'razorpay_fee'        => $paymentFields['fee'],
                'razorpay_tax'        => $paymentFields['tax'],
                'payment_status'      => 'captured',
            ];
            if ($honouredAmount !== null) {
                $capturedUpdate['coins_applied'] = 0;
                $capturedUpdate['amount_paid'] = $honouredAmount;
            }

            $razorpayPaymentRecord->update($this->onlyExistingColumns('razorpay_payments', $capturedUpdate));

            $pendingTransaction = Transactions::where('invoice_id', $invoiceId)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            // The superseded order's own transaction was cancelled when the newer order opened.
            // Complete that one, and close the newer pending order below.
            $transaction = $honouredAmount !== null
                ? (Transactions::where('invoice_id', $invoiceId)->where('status', 'cancelled')->latest('id')->first()
                    ?? $pendingTransaction)
                : $pendingTransaction;

            if (!$transaction) {
                throw new InvalidArgumentException("Pending transaction not found for invoice {$invoiceId}.");
            }

            $storedCoinsApplied = isset($razorpayPaymentRecord->coins_applied)
                ? (int) $razorpayPaymentRecord->coins_applied
                : $coinsApplied;

            $paidAmountOverride = null;
            $isBookingInvoice   = (bool) ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id);

            if ($honouredAmount !== null) {
                // The customer paid the superseded Razorpay order. Record that captured
                // amount and do not debit the coins stored on the newer order.
                $storedCoinsApplied = 0;
                $paidAmountOverride = $honouredAmount;
            } elseif ($isBookingInvoice) {
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

            $this->guardUserstamps($transaction, 'transactions');
            $transaction->update($this->onlyExistingColumns('transactions', [
                'status'          => 'completed',
                'payment_method'  => $paymentFields['method'] ?? 'razorpay',
                'discount_amount' => $coinResult['coinsDiscountAmount'],
                'total_amount'    => $coinResult['payableAmount'],
            ]));

            $doctorBookingResult     = $this->finalizeDoctorBookingAfterPayment($invoice, $transaction);
            $secondOpinionResult     = $this->finalizeSecondOpinionAfterPayment($invoice, $transaction);
            $diagnosticBookingResult = $this->finalizeDiagnosticTestBookingAfterPayment($invoice, $transaction);
            $familyPackageResult     = $this->finalizeFamilyPackageAfterPayment($invoice);

            $invoiceCoinsApplied = $isBookingInvoice
                ? (int) ($invoice->coins_applied ?? 0)
                : (int) $coinResult['effectiveAppliedCoins'];

            $this->guardUserstamps($invoice, 'invoices');
            $invoice->update($this->onlyExistingColumns('invoices', [
                'status'         => 'completed',
                'payment_method' => 'razorpay',
                'coins_applied'  => $invoiceCoinsApplied,
                'coins_earned'   => $coinResult['coinsEarned'],
                'discount_price' => round((float) ($invoice->discount_price ?? 0) + $coinResult['coinsDiscountAmount'], 2),
            ]));

            // Doctor appointment patient confirmation is sent by AppointmentBooked automation.
            // Keep the doctor-facing new-booking notice here.
            if ($invoice->doctor_booking_id) {
                $booking = DoctorBooking::query()->find($invoice->doctor_booking_id);
                if ($booking) {
                    app(DoctorNotificationService::class)->notifyDoctorOfNewBooking($booking);
                }
            }

            $invoice->refresh();
            app(\App\Modules\Automation\Services\InvoiceGeneratedDispatcher::class)->dispatch($invoice);
            app(\App\Modules\Automation\Services\PaymentReceivedDispatcher::class)->dispatch(
                $invoice,
                $transaction,
                [
                    'gateway_payment_id' => $razorpayPaymentId,
                    'currency' => $razorpayPaymentRecord->currency,
                ]
            );

            $transactionReference = 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT);

            if ($honouredAmount !== null) {
                Transactions::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('status', 'pending')
                    ->where('id', '!=', $transaction->id)
                    ->update(['status' => 'cancelled']);

                RazorpayPayment::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('id', '!=', $razorpayPaymentRecord->id)
                    ->where('payment_status', 'created')
                    ->update(['payment_status' => 'superseded']);

                Log::warning('Pharmacy payment on superseded order honoured', [
                    'invoice_id' => (int) $invoice->id,
                    'razorpay_order_id' => $razorpayOrderId,
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'captured_amount' => $honouredAmount,
                ]);
            }

            Log::info('Razorpay payment completed', [
                'invoice_id'          => $invoice->id,
                'pharmacy_order_id'   => $invoice->pharmacy_order_id ? (int) $invoice->pharmacy_order_id : null,
                'user_id'             => auth()->id(),
                'razorpay_order_id'   => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'transaction_id'      => $transactionReference,
                'amount_paid'         => $coinResult['payableAmount'],
                'coins_applied'       => $invoiceCoinsApplied,
                'coins_discount'      => $coinResult['coinsDiscountAmount'],
                'coins_earned'        => $coinResult['coinsEarned'],
                'doctor_booking_id'   => $invoice->doctor_booking_id,
            ]);

            $transaction->refresh();

            return [
                'success'                          => true,
                'invoice_id'                       => (int) $invoice->id,
                'member_id'                        => $memberId !== null ? (string) $memberId : null,
                'transaction_id'                   => (string) $transactionReference,
                'status'                           => 'completed',
                'payment_method'                   => (string) ($paymentFields['method'] ?? 'razorpay'),
                'amount_paid'                      => (float) $coinResult['payableAmount'],
                'coins_applied'                    => (int) $invoiceCoinsApplied,
                'coins_earned'                     => (int) $coinResult['coinsEarned'],
                'doctor_booking_id'                => $doctorBookingResult['doctor_booking_id'] ?? null,
                'booking_payment_status'           => $doctorBookingResult['payment_status'] ?? null,
                'second_opinion_id'                => $secondOpinionResult['second_opinion_id'] ?? null,
                'second_opinion_payment_status'    => $secondOpinionResult['payment_status'] ?? null,
                'diagnostic_test_booking_id'       => $diagnosticBookingResult['diagnostic_test_booking_id'] ?? null,
                'diagnostic_booking_payment_status'=> $diagnosticBookingResult['payment_status'] ?? null,
            ] + $this->completedPaymentStamp($transaction);
        });
    }

    /**
     * Completion clock from transactions.updated_at. That column is written when the
     * transaction is marked completed, before markPaid. transactions and razorpay_payments
     * have no completed_at or paid_at. Shown in Asia/Kolkata.
     *
     * @return array{date_time: string}
     */
    private function completedPaymentStamp(Transactions $transaction): array
    {
        $completedAt = $transaction->updated_at;
        if ($completedAt === null) {
            throw new InvalidArgumentException('Completed transaction has no updated_at.');
        }

        $local = Carbon::parse($completedAt)->timezone('Asia/Kolkata');
        $meridiem = ucfirst(strtolower($local->format('A')));

        return [
            'date_time' => $local->format('d M y') . ',' .$local->format('h.i').' '.$meridiem,
        ];
    }

    /**
     * A later Razorpay capture for another order on an invoice that is already paid.
     * Does not complete the invoice again and does not move coins.
     *
     * @return array{duplicate: bool, invoice_id: int, razorpay_order_id: string, razorpay_payment_id: string, amount: float}|null
     */
    private function duplicateCapturedPharmacyPayment(
        Invoice $invoice,
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $razorpaySignature
    ): ?array {
        if (! $invoice->pharmacy_order_id) {
            return null;
        }

        $row = RazorpayPayment::query()
            ->where('invoice_id', $invoice->id)
            ->where('razorpay_order_id', $razorpayOrderId)
            ->first();
        if (! $row || (string) $row->payment_status === 'captured') {
            return null;
        }

        try {
            $this->getRazorpayApi()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_signature' => $razorpaySignature,
            ]);
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay signature verification failed', [
                'invoice_id' => $invoice->id,
                'pharmacy_order_id' => (int) $invoice->pharmacy_order_id,
                'user_id' => auth()->id(),
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $razorpayPaymentId,
                'reason' => 'bad signature',
                'error' => $e->getMessage(),
            ]);

            throw new InvalidArgumentException('Payment verification failed. Invalid signature.');
        }

        try {
            $payment = $this->getRazorpayApi()->payment->fetch($razorpayPaymentId);
            $paymentFields = $this->razorpayPaymentFields($payment);
        } catch (Throwable $e) {
            $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'not captured');

            throw new InvalidArgumentException('Payment was not captured.');
        }

        if (($paymentFields['status'] ?? null) !== 'captured') {
            $this->logPharmacyVerifyRejection($invoice, $razorpayOrderId, $razorpayPaymentId, 'not captured');

            throw new InvalidArgumentException('Payment was not captured.');
        }

        $amount = $paymentFields['amount'] !== null
            ? round(((int) $paymentFields['amount']) / 100, 2)
            : round((float) ($row->amount_paid ?? 0), 2);

        Log::warning('Duplicate payment on completed pharmacy invoice', [
            'invoice_id' => (int) $invoice->id,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'amount' => $amount,
        ]);

        return [
            'duplicate' => true,
            'invoice_id' => (int) $invoice->id,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'amount' => $amount,
        ];
    }

    /**
     * Commit a signature failure. This is separate from the completion transaction so the
     * failed status is not rolled back by the exception that reports it.
     * A newer still-open Razorpay order keeps its pending transaction.
     */
    private function markRazorpaySignatureFailed(RazorpayPayment $record, string $paymentId, string $signature): void
    {
        DB::transaction(function () use ($record, $paymentId, $signature) {
            $locked = RazorpayPayment::query()->whereKey($record->id)->lockForUpdate()->first();
            if (! $locked || (string) $locked->payment_status !== 'created') {
                return;
            }

            $locked->update($this->onlyExistingColumns('razorpay_payments', [
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'payment_status' => 'failed',
            ]));

            $newerOpen = RazorpayPayment::query()
                ->where('invoice_id', $locked->invoice_id)
                ->where('payment_status', 'created')
                ->where('id', '>', $locked->id)
                ->exists();
            if ($newerOpen) {
                return;
            }

            $transaction = Transactions::query()
                ->where('invoice_id', $locked->invoice_id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();
            if ($transaction) {
                $this->guardUserstamps($transaction, 'transactions');
                $transaction->update($this->onlyExistingColumns('transactions', [
                    'status' => 'failed',
                ]));
            }
        });
    }

    /**
     * Read a Razorpay entity field without touching a missing key.
     * Entity::__get turns an absent key into an ErrorException under Laravel.
     */
    private function razorpayField(object $entity, string $key, mixed $default = null): mixed
    {
        if ($entity instanceof \ArrayAccess && $entity->offsetExists($key)) {
            return $entity[$key];
        }

        if (property_exists($entity, $key)) {
            return $entity->{$key};
        }

        return $default;
    }

    /**
     * @return array{method: ?string, bank: mixed, wallet: mixed, vpa: mixed, card_last4: mixed, card_network: mixed, fee: float, tax: float, status: ?string, amount: ?int}
     */
    private function razorpayPaymentFields(object $payment): array
    {
        $cardId = $this->razorpayField($payment, 'card_id');
        $card = $this->razorpayField($payment, 'card');
        $cardLast4 = null;
        $cardNetwork = null;
        if ($cardId && is_object($card)) {
            $cardLast4 = $this->razorpayField($card, 'last4');
            $cardNetwork = $this->razorpayField($card, 'network');
        }

        $fee = $this->razorpayField($payment, 'fee');
        $tax = $this->razorpayField($payment, 'tax');
        $method = $this->razorpayField($payment, 'method');
        $status = $this->razorpayField($payment, 'status');
        $amount = $this->razorpayField($payment, 'amount');

        return [
            'method' => is_string($method) && $method !== '' ? $method : null,
            'bank' => $this->razorpayField($payment, 'bank'),
            'wallet' => $this->razorpayField($payment, 'wallet'),
            'vpa' => $this->razorpayField($payment, 'vpa'),
            'card_last4' => $cardLast4,
            'card_network' => $cardNetwork,
            'fee' => $fee !== null ? ((float) $fee) / 100 : 0.0,
            'tax' => $tax !== null ? ((float) $tax) / 100 : 0.0,
            'status' => is_string($status) && $status !== '' ? $status : null,
            'amount' => $amount !== null ? (int) $amount : null,
        ];
    }

    private function logCapturedPaymentNotRecorded(int $invoiceId, string $razorpayPaymentId, Throwable $exception, ?int $pharmacyOrderId = null): void
    {
        Log::error('Razorpay payment captured but local completion failed', [
            'invoice_id' => $invoiceId,
            'pharmacy_order_id' => $pharmacyOrderId,
            'user_id' => auth()->id(),
            'razorpay_payment_id' => $razorpayPaymentId,
            'exception' => $exception::class,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    /**
     * Remember a pharmacy coin count for 10 minutes. A later pay-bill or user-coins
     * call for the same invoice with no coins writes a warning.
     */
    public function notePharmacyCoins(int $invoiceId, ?int $coins, string $source, mixed $userId): void
    {
        $key = 'pharmacy-invoice-coins:'.$invoiceId;
        if ($coins !== null && $coins > 0) {
            Cache::put($key, $coins, now()->addMinutes(10));

            return;
        }

        $previous = Cache::get($key);
        if (! is_numeric($previous) || (int) $previous <= 0) {
            return;
        }

        Log::warning('Pharmacy coin count dropped', [
            'invoice_id' => $invoiceId,
            'user_id' => $userId,
            'source' => $source,
            'previous_coins' => (int) $previous,
            'coins' => $coins,
        ]);
    }

    private function logPharmacyVerifyRejection(Invoice $invoice, string $razorpayOrderId, string $razorpayPaymentId, string $reason): void
    {
        if (! $invoice->pharmacy_order_id) {
            return;
        }

        Log::info('Pharmacy verify payment rejected', [
            'invoice_id' => (int) $invoice->id,
            'pharmacy_order_id' => (int) $invoice->pharmacy_order_id,
            'user_id' => auth()->id(),
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => $razorpayPaymentId,
            'reason' => $reason,
        ]);
    }

    /**
     * Userstamps writes created_by and updated_by. Skip that when the column is not on this connection.
     */
    private function guardUserstamps(object $model, string $table): void
    {
        if (! method_exists($model, 'stopUserstamping')) {
            return;
        }

        if (! Schema::hasColumn($table, 'created_by') || ! Schema::hasColumn($table, 'updated_by')) {
            $model->stopUserstamping();
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function onlyExistingColumns(string $table, array $values): array
    {
        $kept = [];
        foreach ($values as $column => $value) {
            if (Schema::hasColumn($table, (string) $column)) {
                $kept[$column] = $value;
            }
        }

        return $kept;
    }

    /**
     * Create a pending invoice for a doctor booking (online payment flow).
     */
    public function createInvoiceForDoctorBooking(DoctorBooking $booking, string $patientPersonId): Invoice
    {
        if ($patientPersonId === '') {
            throw new InvalidArgumentException('Patient person id is required to create an invoice.');
        }

        $patient = Persons::query()->find($patientPersonId);

        if (! $patient && $booking->member_id) {
            $patient = Persons::query()
                ->where('hip_user_id', $booking->member_id)
                ->orderByDesc('is_primary')
                ->first();
        }

        if (! $patient) {
            throw new InvalidArgumentException('Patient profile not found for this booking.');
        }
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
            app(RewardTierService::class)->confirmDiscountUsage('doctor_booking', (int) $booking->id);
            if ($booking->status === 'pending') {
                app(DoctorBookingStatusService::class)->markConfirmed($booking);
            }

            return [
                'doctor_booking_id' => (int) $booking->id,
                'payment_status' => 'paid',
            ];
        }

        $this->deductDoctorBookingCoins($booking);

        app(DoctorBookingStatusService::class)->markConfirmed($booking->fill([
            'payment_status' => 'paid',
            'invoice_id' => $invoice->id,
        ]));
        $booking->save();

        app(\App\Services\MembershipEntitlementService::class)->confirmUsage(
            (int) $booking->id,
            'doctor_booking',
            'consultation'
        );
        app(RewardTierService::class)->confirmDiscountUsage('doctor_booking', (int) $booking->id);

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

        app(CoinsWalletService::class)->debit(
            $coinsWallet,
            $coinsUsed,
            RewardPointsScope::forBookingHospital('redeemed', 'appointment_redemption', $booking->hospital_id ?: ($booking->branch_id ?? null), 'doctor_booking', $booking->id)
        );
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
            app(RewardTierService::class)->confirmDiscountUsage('second_opinion', (int) $booking->id);
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'confirmed']);
            }

            return [
                'second_opinion_id' => (int) $booking->id,
                'payment_status'    => 'paid',
            ];
        }

        $this->deductSecondOpinionCoins($booking);

        $booking->update([
            'payment_status' => 'paid',
            'invoice_id'     => $invoice->id,
            'status'         => 'confirmed',
        ]);
        app(RewardTierService::class)->confirmDiscountUsage('second_opinion', (int) $booking->id);

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

        app(CoinsWalletService::class)->debit(
            $coinsWallet,
            $coinsUsed,
            RewardPointsScope::forBookingHospital('redeemed', 'second_opinion_redemption', $booking->branch_id ?? null, 'second_opinion', $booking->id)
        );
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
            app(RewardTierService::class)->confirmDiscountUsage('diagnostic_test_booking', (int) $booking->id);
            if ($booking->status === 'pending') {
                $booking->update(['status' => 'confirmed']);
            }

            return [
                'diagnostic_test_booking_id' => (int) $booking->id,
                'payment_status'             => 'paid',
            ];
        }

        $this->deductDiagnosticTestBookingCoins($booking);

        $booking->update([
            'payment_status' => 'paid',
            'invoice_id'     => $invoice->id,
            'status'         => 'confirmed',
        ]);

        app(\App\Services\MembershipEntitlementService::class)->confirmUsage(
            (int) $booking->id,
            'diagnostic_test_booking',
            'lab_test'
        );
        app(RewardTierService::class)->confirmDiscountUsage('diagnostic_test_booking', (int) $booking->id);

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

    /**
     * Complete a booking invoice the same way a captured online payment does,
     * without a Razorpay order or signature.
     *
     * @return array{amount_paid: float, coins_earned: int}
     */
    public function completeSettledBookingPayment(Invoice $invoice, Transactions $transaction, string $paymentMethod, float $paidAmount): array
    {
        $coinResult = $this->applyCoinsLogic($invoice, $transaction, 0, $paidAmount);

        $this->guardUserstamps($transaction, 'transactions');
        $transaction->update($this->onlyExistingColumns('transactions', [
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'discount_amount' => $coinResult['coinsDiscountAmount'],
            'total_amount' => $coinResult['payableAmount'],
        ]));

        $this->finalizeDoctorBookingAfterPayment($invoice, $transaction);
        $this->finalizeSecondOpinionAfterPayment($invoice, $transaction);
        $this->finalizeDiagnosticTestBookingAfterPayment($invoice, $transaction);

        $this->guardUserstamps($invoice, 'invoices');
        $invoice->update($this->onlyExistingColumns('invoices', [
            'status' => 'completed',
            'payment_method' => $paymentMethod,
            'coins_applied' => (int) ($invoice->coins_applied ?? 0),
            'coins_earned' => $coinResult['coinsEarned'],
            'discount_price' => round((float) ($invoice->discount_price ?? 0) + $coinResult['coinsDiscountAmount'], 2),
        ]));

        if ($invoice->doctor_booking_id) {
            $booking = DoctorBooking::query()->find($invoice->doctor_booking_id);
            if ($booking) {
                app(DoctorNotificationService::class)->notifyDoctorOfNewBooking($booking);
            }
        }

        $invoice->refresh();
        app(\App\Modules\Automation\Services\InvoiceGeneratedDispatcher::class)->dispatch($invoice);
        app(\App\Modules\Automation\Services\PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction, [
            'currency' => 'INR',
        ]);

        return [
            'amount_paid' => (float) $coinResult['payableAmount'],
            'coins_earned' => (int) $coinResult['coinsEarned'],
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

        app(CoinsWalletService::class)->debit(
            $coinsWallet,
            $coinsUsed,
            RewardPointsScope::forBookingHospital('redeemed', 'package_redemption', $booking->branch_id ?? null, 'diagnostic_test_booking', $booking->id)
        );
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
        } elseif ($invoice->pharmacy_order_id || ! ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id)) {
            $quote = $this->pharmacyCoinQuote($invoice, $coinsApplied);
            $effectiveAppliedCoins = $quote['coins'];
            $coinsDiscountAmount = $quote['discount'];
            $payableAmount = $quote['payable'];
        } else {
            // $effectiveAppliedCoins = max(0, min($coinsApplied, $walletCoinsBefore));
            // $baseAmount = (float) ($transaction->transaction_amount ?? $invoice->total_amount ?? 0);
            // $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), $baseAmount);
            // $payableAmount = round(max(0, $baseAmount - $coinsDiscountAmount), 2);
            $effectiveAppliedCoins = 0;
            $coinsDiscountAmount = 0.0;
            $payableAmount = round(max(0, (float) ($transaction->transaction_amount ?? $invoice->total_amount ?? 0)), 2);
        }

        // Credit 1% of the amount actually paid as HIP coins (same rule as other invoice payments).
        $coinsEarned = (int) round($payableAmount * 0.01);

        $walletService = app(CoinsWalletService::class);
        $redeemed = RewardPointsScope::forInvoice($invoice, 'redeemed', $transaction->id ?? null);
        $earned = RewardPointsScope::forInvoice($invoice, 'earned', $transaction->id ?? null, $coinsEarned);

        if ($effectiveAppliedCoins > 0) {
            $walletService->debit($coinsWallet, $effectiveAppliedCoins, $redeemed);
        }

        if ($coinsEarned > 0) {
            $walletService->creditAfterPayment($coinsWallet, $coinsEarned, $earned);
        }

        // Booking invoices: progress gets 1% of payable + tier earned_coins_per_booking bonus.
        // Non-booking invoices: progress tracks wallet earn only (1%).
        $isBookingInvoice = (bool) (
            $invoice->doctor_booking_id
            || $invoice->second_opinion_id
            || $invoice->diagnostic_test_booking_id
        );
        $progressCoins = $coinsEarned;
        if ($isBookingInvoice && $primaryPerson?->hip_user_id) {
            $progressCoins += app(\App\Services\RewardTierService::class)
                ->earnedCoinsPerBookingBonus((string) $primaryPerson->hip_user_id);
        }
        if ($progressCoins > 0) {
            $this->syncRewardTierProgress($primaryPerson, $progressCoins);
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
     * Keep user_reward_progress in sync with wallet coins (uses primary person's hip_user_id).
     */
    private function syncRewardTierProgress(?Persons $person, int $coinsEarned): void
    {
        if ($coinsEarned <= 0 || ! $person) {
            return;
        }

        $primaryPerson = $this->resolveFamilyPrimaryPerson($person);
        $hipUserId = $primaryPerson?->hip_user_id;

        if (! $hipUserId) {
            return;
        }

        app(RewardTierService::class)->addEarnedCoins((string) $hipUserId, $coinsEarned);
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
        $coinsApplied = null;
        if (array_key_exists('coins_applied', $payload)) {
            $coinsApplied = (int) $payload['coins_applied'];
        }
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
                $requestedCoinsApplied = $coinsApplied;
                if ($requestedCoinsApplied === null) {
                    $requestedCoinsApplied = (int) ($invoice->coins_applied ?? 0);
                }

                if ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id) {
                    // For booking/package invoices, coins_applied has already been accounted for in the invoice amount.
                    $effectiveAppliedCoins = (int) ($invoice->coins_applied ?? 0);
                    $coinsDiscountAmount = 0.0;
                    $paidAmount = round(max(0, $originalAmount), 2);
                    $coinsEarned = (int) round($paidAmount * 0.01);
                } else {
                    $effectiveAppliedCoins = max(0, min($requestedCoinsApplied, $walletCoinsBefore));
                    $coinsDiscountAmount = min(round($effectiveAppliedCoins * $coinValue, 2), $originalAmount);
                    $paidAmount = round(max(0, $originalAmount - $coinsDiscountAmount), 2);
                    $coinsEarned = (int) round($paidAmount * 0.01);
                }
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
                $redeemed = RewardPointsScope::forInvoice($invoice, 'redeemed', $transaction->id ?? null);
                $persistedCoinsEarned = $invoice->coins_earned !== null ? (int) $invoice->coins_earned : null;
                $earned = RewardPointsScope::forInvoice($invoice, 'earned', $transaction->id ?? null, $persistedCoinsEarned);

                if ($effectiveAppliedCoins > 0) {
                    $walletService->debit($coinsWallet, $effectiveAppliedCoins, $redeemed);
                }

                if ($coinsEarned > 0) {
                    $walletService->creditAfterPayment($coinsWallet, $coinsEarned, $earned);
                }

                $isBookingInvoice = (bool) (
                    $invoice->doctor_booking_id
                    || $invoice->second_opinion_id
                    || $invoice->diagnostic_test_booking_id
                );
                $progressCoins = $coinsEarned;
                if ($isBookingInvoice && $primaryPerson?->hip_user_id) {
                    $progressCoins += app(RewardTierService::class)
                        ->earnedCoinsPerBookingBonus((string) $primaryPerson->hip_user_id);
                }
                if ($progressCoins > 0) {
                    $this->syncRewardTierProgress($primaryPerson, $progressCoins);
                }

                $finalCoinsBalance = (int) ($coinsWallet->fresh()->coins ?? 0);
            }

            if ($hipUser && $this->hasCoinsBalanceColumn() && isset($finalCoinsBalance)) {
                $hipUser->coins_balance = $finalCoinsBalance;
                $hipUser->save();
            }

            if ($invoice->doctor_booking_id) {
                $booking = DoctorBooking::query()->find($invoice->doctor_booking_id);
                if ($booking) {
                    app(DoctorNotificationService::class)->notifyDoctorOfNewBooking($booking);
                }
            }

            $this->finalizeFamilyPackageAfterPayment($invoice);

            if (strtolower((string) $status) === 'completed') {
                $invoice->refresh();
                app(\App\Modules\Automation\Services\InvoiceGeneratedDispatcher::class)->dispatch($invoice);
                app(\App\Modules\Automation\Services\PaymentReceivedDispatcher::class)->dispatch($invoice, $transaction);
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

    /**
     * Activate a pending family package subscription when its invoice is paid.
     */
    private function finalizeFamilyPackageAfterPayment(Invoice $invoice): ?array
    {
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];

        if (! in_array('family_package', $serviceTypes, true)) {
            return null;
        }

        try {
            $subscription = app(\App\Services\FamilyPackageService::class)
                ->activateSubscriptionFromInvoice($invoice);

            if (! $subscription) {
                return null;
            }

            return [
                'user_family_subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
            ];
        } catch (\Throwable $e) {
            Log::error('Family package subscription activation failed after payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

