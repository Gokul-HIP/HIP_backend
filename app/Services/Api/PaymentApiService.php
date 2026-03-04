<?php

namespace App\Services\Api;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\Transactions;
use App\Models\UserDevice;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentApiService
{
    protected NotificationService $notificationService;
    private ?bool $hasCoinsBalanceColumn = null;
    private ?float $coinAmountValue = null;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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
            $coinsBalance = (int) ($coinsWallet->coins ?? 0);
            if ($coinsBalance <= 0 && $this->hasCoinsBalanceColumn()) {
                $coinsBalance = (int) ($hipUser->coins_balance ?? 0);
            }
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
        $coinsBalance = (int) ($coinsWallet->coins ?? 0);
        if ($coinsBalance <= 0 && $this->hasCoinsBalanceColumn()) {
            $coinsBalance = (int) ($hipUser->coins_balance ?? 0);
        }

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

        $availableCoins = (int) ($coinsWallet->coins ?? 0);
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
                $walletCoinsBefore = (int) ($coinsWallet->coins ?? 0);
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
            ]);

            if ($transaction->status === 'completed' && isset($coinsWallet)) {
                $coinsWallet->coins = max(0, $walletCoinsBefore - $effectiveAppliedCoins) + $coinsEarned;
                $coinsWallet->save();
            }

            if ($hipUser && $this->hasCoinsBalanceColumn()) {
                if ($effectiveAppliedCoins > 0) {
                    $hipUser->coins_balance = max(0, (int) ($hipUser->coins_balance ?? 0) - $effectiveAppliedCoins);
                }
                $hipUser->coins_balance = (int) ($hipUser->coins_balance ?? 0) + $coinsEarned;
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
