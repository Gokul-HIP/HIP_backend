<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\HmsPayment;
use App\Models\Invoice;
use App\Models\RewardTierDiscountUsage;
use App\Models\SecondOpinion;
use App\Models\SubscriptionUsageLog;
use App\Models\Transactions;
use App\Models\UserFamilySubscription;
use App\Services\Api\PaymentApiService;
use App\Services\MembershipEntitlementService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Records an HMS payment and settles the HIP booking with the app's payment finalizers. */
class HmsSettlementService
{
    public function __construct(
        private readonly PaymentApiService $payments,
        private readonly MembershipEntitlementService $membership,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    public function verify(HmsIntegration $integration, array $payload): array
    {
        $payment = (array) ($payload['payment'] ?? []);
        $reference = trim((string) ($payment['reference'] ?? ''));
        $status = strtolower(trim((string) ($payment['status'] ?? '')));
        if ($reference === '' || ! in_array($status, ['success', 'failed'], true) || ! is_numeric($payment['amount'] ?? null)) {
            throw new HmsClientException('validation_error', 'payment.reference, payment.status, and payment.amount are required.', 422);
        }
        if ((float) $payment['amount'] < 0) {
            throw new HmsClientException('validation_error', 'payment.amount must be a non-negative number of rupees.', 422);
        }

        $paidAt = null;
        if (filled($payment['paid_at'] ?? null)) {
            try {
                $paidAt = Carbon::parse((string) $payment['paid_at']);
            } catch (\Throwable) {
                throw new HmsClientException('validation_error', 'payment.paid_at must be a date time.', 422);
            }
        }

        try {
            return $this->apply($integration, $payload, $payment, $reference, $status, $paidAt);
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? '') !== '23000' && ! str_contains(strtolower($exception->getMessage()), 'unique')) {
                throw $exception;
            }

            return $this->apply($integration, $payload, $payment, $reference, $status, $paidAt);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $payment
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function apply(
        HmsIntegration $integration,
        array $payload,
        array $payment,
        string $reference,
        string $status,
        ?Carbon $paidAt,
    ): array {
        return DB::transaction(function () use ($integration, $payload, $payment, $reference, $status, $paidAt) {
            $booking = $this->lockedBooking($integration, $payload);
            $existing = HmsPayment::query()
                ->where('integration_id', $integration->getKey())
                ->where('payment_reference', $reference)
                ->lockForUpdate()
                ->first();
            if ($existing && $this->referenceIsAccepted($existing)) {
                return $this->replayAccepted($existing, $booking);
            }

            $amount = round((float) $payment['amount'], 2);

            return $this->decide($integration, $booking, $payload, $reference, $status, $amount, (string) ($payment['method'] ?? ''), $paidAt, $existing);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function status(HmsIntegration $integration, array $payload): array
    {
        $booking = HmsBooking::findForClient($integration->getKey(), $payload);

        $payments = HmsPayment::query()
            ->where('hms_booking_row_id', $booking->id)
            ->orderBy('id')
            ->get();
        $paid = $payments->whereIn('result', [HmsPayment::RESULT_SETTLED, HmsPayment::RESULT_LATE])->last();

        return [
            'hms_booking_id' => $booking->hms_booking_id,
            'booking_id' => (int) $booking->booking_id,
            'booking_type' => $booking->booking_type,
            'state' => $booking->clientState(),
            'expected_amount' => round((float) $booking->expected_amount, 2),
            'hms_payable_amount' => round((float) $booking->hms_payable_amount, 2),
            'paid_amount' => $paid ? round((float) $paid->amount, 2) : null,
            'hold_expires_at' => $booking->hold_expires_at?->toIso8601String(),
            'price_mismatch' => (bool) $booking->price_mismatch,
            'refund' => $booking->refundPayload(),
            'coins_reversed' => (int) ($booking->coins_reversed ?? 0),
            'payments' => $payments->map(fn (HmsPayment $row): array => [
                'reference' => $row->payment_reference,
                'status' => $row->status,
                'amount' => round((float) $row->amount, 2),
                'result' => $row->result,
                'paid_at' => $row->paid_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function decide(
        HmsIntegration $integration,
        HmsBooking $booking,
        array $payload,
        string $reference,
        string $status,
        float $amount,
        string $method,
        ?Carbon $paidAt,
        ?HmsPayment $existing = null,
    ): array {
        $expected = round((float) $booking->expected_amount, 2);
        $matches = abs($amount - $expected) <= 0.01;
        $confirmedAlready = $booking->status === HmsBooking::STATUS_CONFIRMED;

        if ($confirmedAlready && $this->hasSettlement($booking)) {
            throw new HmsClientException('already_paid', 'This booking is already paid.', 409);
        }

        if ($status === 'failed') {
            $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_FAILED, null, $existing);

            return $this->ok($booking, $amount, HmsPayment::RESULT_FAILED);
        }

        if ($confirmedAlready && $expected <= 0.0) {
            if (! $matches) {
                $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_MISMATCH, null, $existing);

                return $this->rejected('amount_mismatch', 'The payment amount does not match the HIP total.', 409, [
                    'expected_amount' => $expected,
                    'received_amount' => $amount,
                ]);
            }

            $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_SETTLED, null, $existing);

            return $this->confirmed($booking, $amount, HmsPayment::RESULT_SETTLED);
        }

        if ($confirmedAlready) {
            throw new HmsClientException('already_paid', 'This booking is already paid.', 409);
        }

        if ($booking->status === HmsBooking::STATUS_CANCELLED) {
            $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_CANCELLED, null, $existing);

            return $this->rejected('booking_cancelled', 'This booking is cancelled.', 409);
        }

        if ($booking->status === HmsBooking::STATUS_EXPIRED) {
            if ($matches && $this->withinLateWindow($booking)) {
                $notes = $this->reinstate($booking);
                $paid = $this->settle($booking, $method !== '' ? $method : 'hms', $amount);
                $booking->status = HmsBooking::STATUS_CONFIRMED;
                $booking->confirmed_at = now();
                $booking->save();
                $this->record($integration, $booking, $payload, $reference, $status, $paid, $method, $paidAt, HmsPayment::RESULT_LATE, $notes, $existing);

                return $this->confirmed($booking, $paid, HmsPayment::RESULT_LATE);
            }

            $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_EXPIRED, null, $existing);

            return $this->rejected('booking_expired', 'This booking has expired. Refund the patient or rebook.', 409);
        }

        if ($booking->status === HmsBooking::STATUS_HELD || ! $matches) {
            $this->record($integration, $booking, $payload, $reference, $status, $amount, $method, $paidAt, HmsPayment::RESULT_MISMATCH, null, $existing);

            return $this->rejected('amount_mismatch', 'The payment amount does not match the HIP total.', 409, [
                'expected_amount' => $expected,
                'received_amount' => $amount,
            ]);
        }

        $paid = $this->settle($booking, $method !== '' ? $method : 'hms', $amount);
        $booking->status = HmsBooking::STATUS_CONFIRMED;
        $booking->confirmed_at = now();
        $booking->save();
        $this->record($integration, $booking, $payload, $reference, $status, $paid, $method, $paidAt, HmsPayment::RESULT_SETTLED, null, $existing);

        return $this->confirmed($booking, $paid, HmsPayment::RESULT_SETTLED);
    }

    private function settle(HmsBooking $booking, string $method, float $amount): float
    {
        $invoice = Invoice::query()->whereKey($booking->invoice_id)->lockForUpdate()->first();
        $transaction = Transactions::query()->whereKey($booking->transaction_id)->lockForUpdate()->first();
        if (! $invoice || ! $transaction) {
            throw new HmsClientException('booking_not_found', 'Booking payment records were not found.', 404);
        }

        $result = $this->payments->completeSettledBookingPayment($invoice, $transaction, $method, $amount);

        return round((float) $result['amount_paid'], 2);
    }

    private function reinstate(HmsBooking $booking): ?string
    {
        $notes = [];
        $this->restoreLocalBooking($booking);
        if ($booking->invoice_id) {
            Invoice::query()->whereKey($booking->invoice_id)->where('status', 'cancelled')->update(['status' => 'pending']);
            Transactions::query()->where('invoice_id', $booking->invoice_id)->where('status', 'cancelled')->update(['status' => 'pending']);
        }

        $usage = $this->usageTypes($booking);
        if ((bool) data_get($booking->pricing_snapshot, 'membership.applied') && $usage['usage_type']) {
            $notes[] = $this->rereserveMembership($booking, $usage['booking_type'], $usage['usage_type']);
        }
        $rewardNote = $this->rereserveReward($booking, $usage['booking_type']);
        if ($rewardNote) {
            $notes[] = $rewardNote;
        }

        $notes = array_values(array_filter($notes));

        return $notes === [] ? null : implode(' ', $notes);
    }

    /**
     * @return array{booking_type: string, usage_type: string|null}
     */
    private function usageTypes(HmsBooking $booking): array
    {
        return match ($booking->booking_type) {
            HmsBooking::TYPE_PACKAGE => ['booking_type' => 'diagnostic_test_booking', 'usage_type' => 'lab_test'],
            HmsBooking::TYPE_SECOND_OPINION => ['booking_type' => 'second_opinion', 'usage_type' => null],
            default => ['booking_type' => 'doctor_booking', 'usage_type' => 'consultation'],
        };
    }

    private function rereserveMembership(HmsBooking $booking, string $bookingType, string $usageType): ?string
    {
        $subscription = UserFamilySubscription::query()
            ->where('hip_user_id', $booking->hip_user_id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
        if (! $subscription) {
            return 'Membership subscription was not active, so usage was not re-reserved.';
        }

        $packageId = $booking->booking_type === HmsBooking::TYPE_PACKAGE
            ? (int) (DiagnosticTestBooking::query()->whereKey($booking->booking_id)->value('package_id') ?? 0)
            : null;

        try {
            $this->membership->reserveUsage((int) $subscription->id, $usageType, (int) $booking->booking_id, $bookingType, $packageId ?: null);
        } catch (\InvalidArgumentException) {
            SubscriptionUsageLog::query()->create([
                'user_family_subscription_id' => $subscription->id,
                'hip_user_id' => $booking->hip_user_id,
                'usage_type' => $usageType,
                'booking_id' => $booking->booking_id,
                'booking_type' => $bookingType,
                'used_at' => now(),
                'notes' => 'membership_reserved',
            ]);

            return 'Membership allowance was exhausted; usage was recorded anyway.';
        }

        return null;
    }

    private function rereserveReward(HmsBooking $booking, string $bookingType): ?string
    {
        $usage = RewardTierDiscountUsage::query()
            ->where('booking_type', $bookingType)
            ->where('booking_id', $booking->booking_id)
            ->first();
        if (! $usage || $usage->status !== 'released') {
            return null;
        }

        $active = RewardTierDiscountUsage::query()
            ->where('hip_user_id', $usage->hip_user_id)
            ->where('reward_tier_id', $usage->reward_tier_id)
            ->where('tier_generation', $usage->tier_generation)
            ->whereIn('status', ['reserved', 'consumed'])
            ->count();
        $limit = (int) (DB::table('reward_tier_configs')->where('reward_tier_id', $usage->reward_tier_id)->value('free_checkup_count') ?? 0);
        $exhausted = $limit > 0 && $active >= $limit;
        $usage->status = 'reserved';
        $usage->save();

        return $exhausted ? 'Reward allowance was exhausted; usage was consumed anyway.' : null;
    }

    private function restoreLocalBooking(HmsBooking $booking): void
    {
        if ($booking->booking_type === HmsBooking::TYPE_DOCTOR) {
            DoctorBooking::query()->whereKey($booking->booking_id)->update([
                'status' => 'pending',
                'payment_status' => 'pending',
                'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            ]);

            return;
        }

        $model = $booking->booking_type === HmsBooking::TYPE_PACKAGE
            ? DiagnosticTestBooking::query()->whereKey($booking->booking_id)
            : SecondOpinion::query()->whereKey($booking->booking_id);
        $model->update([
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    private function withinLateWindow(HmsBooking $booking): bool
    {
        if ($booking->hold_expires_at === null) {
            return false;
        }

        $hours = max(0, (int) config('hms.late_payment_window_hours', 24));

        return now()->lte($booking->hold_expires_at->copy()->addHours($hours));
    }

    private function hasSettlement(HmsBooking $booking): bool
    {
        return HmsPayment::query()
            ->where('hms_booking_row_id', $booking->id)
            ->whereIn('result', [HmsPayment::RESULT_SETTLED, HmsPayment::RESULT_LATE])
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function record(
        HmsIntegration $integration,
        HmsBooking $booking,
        array $payload,
        string $reference,
        string $status,
        float $amount,
        string $method,
        ?Carbon $paidAt,
        string $result,
        ?string $notes,
        ?HmsPayment $existing = null,
    ): void {
        $attributes = [
            'hms_booking_row_id' => $booking->id,
            'status' => $status,
            'amount' => $amount,
            'method' => $method !== '' ? $method : null,
            'paid_at' => $paidAt,
            'result' => $result,
            'payload' => $payload,
            'notes' => $this->notesFor($existing, $notes),
        ];
        if ($existing) {
            $existing->fill($attributes)->save();

            return;
        }

        HmsPayment::query()->create($attributes + [
            'integration_id' => $integration->getKey(),
            'payment_reference' => $reference,
        ]);
    }

    private function notesFor(?HmsPayment $existing, ?string $notes): ?string
    {
        if (! $existing) {
            return $notes;
        }

        $previous = HmsBooking::query()->find($existing->hms_booking_row_id);
        $bookingLabel = $previous?->hms_booking_id ?? ('row '.$existing->hms_booking_row_id);
        $history = 'previous hms_booking_id '.$bookingLabel.' result '.$existing->result;
        $parts = array_values(array_filter(
            [trim((string) $existing->notes), $history, trim((string) $notes)],
            static fn (string $part): bool => $part !== '',
        ));

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function referenceIsAccepted(HmsPayment $payment): bool
    {
        return in_array($payment->result, [HmsPayment::RESULT_SETTLED, HmsPayment::RESULT_LATE], true);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function lockedBooking(HmsIntegration $integration, array $payload): HmsBooking
    {
        $booking = $this->bookingQuery($integration, $payload)->lockForUpdate()->first();
        if (! $booking) {
            throw new HmsClientException('booking_not_found', 'Booking was not found.', 404);
        }

        return $booking;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bookingQuery(HmsIntegration $integration, array $payload)
    {
        $query = HmsBooking::query()->where('integration_id', $integration->getKey());
        $hmsId = trim((string) ($payload['hms_booking_id'] ?? ''));
        if ($hmsId !== '') {
            return $query->where('hms_booking_id', $hmsId);
        }

        $localId = $payload['booking_id'] ?? null;
        $type = (string) ($payload['booking_type'] ?? '');
        if ($localId === null || $localId === '' || ! in_array($type, [HmsBooking::TYPE_DOCTOR, HmsBooking::TYPE_SECOND_OPINION, HmsBooking::TYPE_PACKAGE], true)) {
            throw new HmsClientException('validation_error', 'hms_booking_id or booking_id and booking_type is required.', 422);
        }

        return $query->where('booking_id', (int) $localId)->where('booking_type', $type);
    }

    /**
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function replayAccepted(HmsPayment $payment, HmsBooking $booking): array
    {
        if ((int) $payment->hms_booking_row_id !== (int) $booking->id) {
            throw new HmsClientException(
                'payment_reference_used',
                'This payment reference was already used for another booking.',
                409,
            );
        }

        return [
            'http_status' => 200,
            'body' => [
                'status' => true,
                'result' => HmsPayment::RESULT_DUPLICATE,
                'state' => 'confirmed',
                'booking_id' => (int) $booking->booking_id,
                'paid_amount' => round((float) $payment->amount, 2),
                'expected_amount' => round((float) $booking->expected_amount, 2),
            ],
        ];
    }

    /**
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function confirmed(HmsBooking $booking, float $paid, string $result): array
    {
        return [
            'http_status' => 200,
            'body' => [
                'status' => true,
                'result' => $result,
                'state' => 'confirmed',
                'booking_id' => (int) $booking->booking_id,
                'paid_amount' => $paid,
            ],
        ];
    }

    /**
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function ok(HmsBooking $booking, float $amount, string $result): array
    {
        return [
            'http_status' => 200,
            'body' => [
                'status' => true,
                'result' => $result,
                'state' => $booking->clientState(),
                'booking_id' => (int) $booking->booking_id,
                'paid_amount' => $amount,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array{http_status: int, body: array<string, mixed>}
     */
    private function rejected(string $code, string $message, int $status, array $extra = []): array
    {
        return [
            'http_status' => $status,
            'body' => array_merge([
                'status' => false,
                'code' => $code,
                'message' => $message,
            ], $extra),
        ];
    }
}
