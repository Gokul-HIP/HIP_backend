<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\Coins;
use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsIntegration;
use App\Models\HmsMembership;
use App\Models\HmsPayment;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\SecondOpinion;
use App\Models\SubscriptionUsageLog;
use App\Models\Transactions;
use App\Models\UserFamilySubscription;
use App\Models\UserRewardProgress;
use App\Modules\Automation\Support\RewardPointsContext;
use App\Services\CoinsWalletService;
use App\Services\DoctorBookingStatusService;
use App\Services\FamilyPackageService;
use App\Services\MembershipEntitlementService;
use App\Services\NotificationService;
use App\Services\RewardTierService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Cancels an HMS booking or membership. HIP records a refund due and does not send the money back. */
class HmsCancellationService
{
    public function __construct(
        private readonly DoctorBookingStatusService $doctorStatus,
        private readonly MembershipEntitlementService $membership,
        private readonly RewardTierService $rewards,
        private readonly FamilyPackageService $familyPackages,
        private readonly CoinsWalletService $wallets,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    public function cancelBooking(HmsIntegration $integration, array $payload): array
    {
        $input = $this->bookingInput($payload);

        $booking = DB::transaction(function () use ($integration, $input) {
            HmsIntegration::query()->whereKey($integration->getKey())->lockForUpdate()->first();
            $booking = HmsBooking::findForClient($integration->getKey(), $input, true);
            if ($booking->status === HmsBooking::STATUS_CANCELLED || $booking->status === HmsBooking::STATUS_EXPIRED) {
                return $booking;
            }
            if ($this->appointmentCompleted($booking)) {
                throw new HmsClientException('booking_not_cancellable', 'This appointment is already completed.', 409);
            }
            if (in_array($booking->status, [HmsBooking::STATUS_PENDING, HmsBooking::STATUS_HELD], true)) {
                $this->cancelUnpaid($booking);
            } elseif ($booking->status === HmsBooking::STATUS_CONFIRMED) {
                $taken = $this->cancelConfirmed($booking);
                $paid = $this->amountPaid($booking);
                if ($paid > 0) {
                    $booking->refund_status = HmsBooking::REFUND_DUE;
                    $booking->refund_amount = $paid;
                    $booking->coins_reversed = $taken;
                }
            } else {
                throw new HmsClientException('booking_not_cancellable', 'This booking cannot be cancelled.', 409);
            }

            $booking->status = HmsBooking::STATUS_CANCELLED;
            $booking->cancelled_at = $input['cancelled_at'] ?? now();
            $booking->cancel_reason = $input['reason'];
            $booking->save();

            return $booking;
        });

        return ['http_status' => 200, 'body' => $this->bookingBody($booking)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    public function cancelMembership(HmsIntegration $integration, array $payload): array
    {
        $hmsId = trim((string) ($payload['hms_membership_id'] ?? ''));
        if ($hmsId === '') {
            throw new HmsClientException('validation_error', 'hms_membership_id is required.', 422);
        }
        $when = $this->moment($payload['cancelled_at'] ?? null, 'cancelled_at');

        $subscriptionId = DB::transaction(function () use ($integration, $hmsId, $when) {
            HmsIntegration::query()->whereKey($integration->getKey())->lockForUpdate()->first();
            $row = HmsMembership::query()
                ->where('integration_id', $integration->getKey())
                ->where('hms_membership_id', $hmsId)
                ->lockForUpdate()
                ->first();
            if (! $row) {
                throw new HmsClientException('membership_not_found', 'Membership was not found.', 404);
            }
            $subscription = UserFamilySubscription::query()->whereKey($row->subscription_id)->lockForUpdate()->first();
            if (! $subscription) {
                throw new HmsClientException('membership_not_found', 'Membership was not found.', 404);
            }
            if ($subscription->status !== 'cancelled') {
                $this->familyPackages->cancelSubscription((int) $subscription->id);
                $subscription->refresh();
                if ($when) {
                    $subscription->cancelled_at = $when;
                    $subscription->save();
                }
            }
            if ($row->cancelled_at === null) {
                $row->cancelled_at = $subscription->cancelled_at ?? $when ?? now();
                $row->save();
            }

            return (int) $subscription->id;
        });

        return [
            'http_status' => 200,
            'body' => [
                'status' => true,
                'subscription_id' => $subscriptionId,
                'state' => 'cancelled',
            ],
        ];
    }

    private function cancelUnpaid(HmsBooking $hms): void
    {
        $this->cancelLocal($hms, false);
        if ($hms->invoice_id) {
            Invoice::query()->whereKey($hms->invoice_id)->where('status', 'pending')->update(['status' => 'cancelled']);
            Transactions::query()->where('invoice_id', $hms->invoice_id)->where('status', 'pending')->update(['status' => 'cancelled']);
        }
        [$bookingType, $usageType] = $this->usage($hms);
        $this->rewards->releaseDiscountUsage($bookingType, (int) $hms->booking_id);
        if ($usageType) {
            $this->membership->releaseUsage((int) $hms->booking_id, $bookingType, $usageType);
        }
    }

    private function cancelConfirmed(HmsBooking $hms): int
    {
        $invoice = $hms->invoice_id ? Invoice::query()->find($hms->invoice_id) : null;
        $taken = $this->reverseCoins($hms, $invoice);
        $this->cancelLocal($hms, true);
        [$bookingType, $usageType] = $this->usage($hms);
        if ($usageType) {
            SubscriptionUsageLog::query()
                ->where('booking_id', $hms->booking_id)
                ->where('booking_type', $bookingType)
                ->where('usage_type', $usageType)
                ->whereIn('notes', ['membership_reserved', 'membership_consumed'])
                ->delete();
        }
        $this->rewards->releaseDiscountUsage($bookingType, (int) $hms->booking_id);

        return $taken;
    }

    private function cancelLocal(HmsBooking $hms, bool $notify): void
    {
        if ($hms->booking_type === HmsBooking::TYPE_DOCTOR) {
            $booking = DoctorBooking::query()->find($hms->booking_id);
            if ($booking && $booking->status !== 'cancelled') {
                if ($notify) {
                    $this->doctorStatus->updateBookingStatus($booking, 'cancelled', null, 'hms_booking_cancelled');
                } else {
                    $this->doctorStatus->cancelSilently($booking, 'hms_booking_cancelled');
                }
            }

            return;
        }

        if ($hms->booking_type === HmsBooking::TYPE_PACKAGE) {
            $booking = DiagnosticTestBooking::query()->find($hms->booking_id);
            if ($booking && $booking->status !== 'cancelled') {
                $booking->update(['status' => 'cancelled']);
                if ($notify) {
                    $this->notifyPackageCancelled($booking);
                }
            }

            return;
        }

        $booking = SecondOpinion::query()->find($hms->booking_id);
        if ($booking && $booking->status !== 'cancelled') {
            $booking->update(['status' => 'cancelled']);
            if ($notify) {
                $this->notifySecondOpinionCancelled($booking);
            }
        }
    }

    private function notifySecondOpinionCancelled(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');
        $appointmentDate = $booking->preferred_date
            ? $booking->preferred_date->format('d M Y')
            : 'your scheduled date';
        $appointmentTime = is_array($booking->preferred_time_slots) && isset($booking->preferred_time_slots[0])
            ? $booking->preferred_time_slots[0]
            : null;
        $timeText = $appointmentTime ? ' at '.$appointmentTime : '';

        app(NotificationService::class)->notifyUser(
            (string) $booking->member_id,
            'Your second opinion is cancelled!',
            'Your second opinion with Dr. '.($booking->doctor->name ?? 'doctor').' has been cancelled for '.$appointmentDate.$timeText.'.',
            [
                'type' => 'appointment_cancellation',
                'entity_type' => 'doctor',
                'entity_id' => (string) $booking->doctor_id,
                'booking_type' => 'second_opinion',
                'booking_id' => (string) $booking->id,
                'url' => '/booking-history',
                'route' => '/booking-history',
            ]
        );
    }

    private function notifyPackageCancelled(DiagnosticTestBooking $booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $bookingDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        app(NotificationService::class)->notifyUser(
            (string) $booking->member_id,
            'Your Package booking is cancelled!',
            'Your '.$packageLabel.' booking for '.$bookingDate.' has been cancelled.',
            [
                'type' => 'package_cancelled',
                'screen' => 'booking_history',
                'entity_type' => 'diagnostic_center',
                'entity_id' => (string) ($booking->diagnostic_center_id ?? ''),
                'booking_type' => 'diagnostic_package',
                'diagnostic_test_booking_id' => (string) $booking->id,
                'package_type' => (string) ($booking->package_type ?? ''),
                'booking_date' => $booking->booking_date?->format('Y-m-d') ?? '',
                'url' => '/booking-history',
                'route' => '/booking-history',
            ]
        );
    }

    private function reverseCoins(HmsBooking $hms, ?Invoice $invoice): int
    {
        $earned = (int) ($invoice->coins_earned ?? 0);
        $bonus = 0;
        if ($invoice && ($invoice->doctor_booking_id || $invoice->second_opinion_id || $invoice->diagnostic_test_booking_id)) {
            $bonus = $this->rewards->earnedCoinsPerBookingBonus((string) $hms->hip_user_id);
        }
        $progressCoins = $earned + $bonus;
        if ($progressCoins > 0) {
            $progress = UserRewardProgress::query()->where('hip_user_id', $hms->hip_user_id)->lockForUpdate()->first();
            if ($progress) {
                $progress->earned_coins_in_tier = max(0, (int) $progress->earned_coins_in_tier - $progressCoins);
                $progress->total_lifetime_coins = max(0, (int) $progress->total_lifetime_coins - $progressCoins);
                $progress->save();
            }
        }
        if ($earned <= 0) {
            return 0;
        }

        $personId = Persons::query()->where('hip_user_id', $hms->hip_user_id)->value('id');
        $wallet = $personId ? Coins::query()->where('person_id', $personId)->lockForUpdate()->first() : null;
        if (! $wallet) {
            return 0;
        }

        $before = (int) $wallet->coins;
        $this->wallets->debit($wallet, $earned, new RewardPointsContext(
            changeType: 'adjusted',
            source: 'hms_booking_cancelled',
            hospitalId: (int) $hms->hospital_id,
            invoiceId: $invoice?->id,
            transactionId: $hms->transaction_id ? (int) $hms->transaction_id : null,
            bookingSource: $this->usage($hms)[0],
            bookingId: $hms->booking_id,
        ));

        return max(0, $before - (int) $wallet->fresh()->coins);
    }

    private function amountPaid(HmsBooking $booking): float
    {
        $amount = HmsPayment::query()
            ->where('hms_booking_row_id', $booking->id)
            ->whereIn('result', [HmsPayment::RESULT_SETTLED, HmsPayment::RESULT_LATE])
            ->orderByDesc('id')
            ->value('amount');

        return round((float) $amount, 2);
    }

    private function appointmentCompleted(HmsBooking $hms): bool
    {
        if ($hms->booking_type === HmsBooking::TYPE_DOCTOR) {
            $booking = DoctorBooking::query()->find($hms->booking_id);

            return $booking !== null && (
                $booking->status === 'completed'
                || $booking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
            );
        }

        $status = $hms->booking_type === HmsBooking::TYPE_PACKAGE
            ? DiagnosticTestBooking::query()->whereKey($hms->booking_id)->value('status')
            : SecondOpinion::query()->whereKey($hms->booking_id)->value('status');

        return $status === 'completed';
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function usage(HmsBooking $hms): array
    {
        return match ($hms->booking_type) {
            HmsBooking::TYPE_PACKAGE => ['diagnostic_test_booking', 'lab_test'],
            HmsBooking::TYPE_SECOND_OPINION => ['second_opinion', null],
            default => ['doctor_booking', 'consultation'],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function bookingInput(array $payload): array
    {
        $reason = trim((string) ($payload['reason'] ?? ''));
        $payload['reason'] = $reason !== '' ? $reason : null;
        $payload['cancelled_at'] = $this->moment($payload['cancelled_at'] ?? null, 'cancelled_at');

        return $payload;
    }

    private function moment(mixed $value, string $field): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            throw new HmsClientException('validation_error', $field.' must be a date time.', 422);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingBody(HmsBooking $booking): array
    {
        return [
            'status' => true,
            'state' => $booking->clientState(),
            'booking_id' => (int) $booking->booking_id,
            'booking_type' => $booking->booking_type,
            'refund' => $booking->refundPayload(),
            'coins_reversed' => (int) ($booking->coins_reversed ?? 0),
        ];
    }
}
