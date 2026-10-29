<?php

namespace App\Services\Hms;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HmsBooking;
use App\Models\HmsWebhookInbox;
use App\Models\Invoice;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Services\DoctorBookingStatusService;
use App\Services\MembershipEntitlementService;
use App\Services\RewardTierService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HmsBookingExpiryService
{
    public function __construct(
        private readonly DoctorBookingStatusService $doctorStatus,
        private readonly MembershipEntitlementService $membership,
        private readonly RewardTierService $rewards,
    ) {}

    public function expireDue(): int
    {
        $ids = HmsBooking::query()
            ->whereIn('status', [HmsBooking::STATUS_PENDING, HmsBooking::STATUS_HELD])
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->orderBy('id')
            ->pluck('id');

        $expired = 0;
        foreach ($ids as $id) {
            if ($this->expireOne((int) $id)) {
                $expired++;
            }
        }

        return $expired;
    }

    private function expireOne(int $id): bool
    {
        return (bool) DB::transaction(function () use ($id) {
            $hms = HmsBooking::query()->whereKey($id)->lockForUpdate()->first();
            if (! $hms || ! in_array($hms->status, [HmsBooking::STATUS_PENDING, HmsBooking::STATUS_HELD], true)) {
                return false;
            }
            if ($hms->hold_expires_at === null || $hms->hold_expires_at->gte(now())) {
                return false;
            }

            $this->cancelLocalBooking($hms);
            if ($hms->invoice_id) {
                Invoice::query()->whereKey($hms->invoice_id)->where('status', 'pending')->update(['status' => 'cancelled']);
                Transactions::query()->where('invoice_id', $hms->invoice_id)->where('status', 'pending')->update(['status' => 'cancelled']);
            }

            $usage = match ($hms->booking_type) {
                HmsBooking::TYPE_PACKAGE => ['diagnostic_test_booking', 'lab_test'],
                HmsBooking::TYPE_SECOND_OPINION => ['second_opinion', null],
                default => ['doctor_booking', 'consultation'],
            };
            $this->rewards->releaseDiscountUsage($usage[0], (int) $hms->booking_id);
            if ($usage[1]) {
                $this->membership->releaseUsage((int) $hms->booking_id, $usage[0], $usage[1]);
            }

            $hms->status = HmsBooking::STATUS_EXPIRED;
            $hms->save();

            Log::info('[hms-booking] Unpaid hold expired', [
                'hms_booking_id' => $hms->hms_booking_id,
                'integration_id' => $hms->integration_id,
                'booking_type' => $hms->booking_type,
                'booking_id' => $hms->booking_id,
            ]);

            HmsWebhookInbox::query()->create([
                'integration_id' => $hms->integration_id,
                'request_id' => (string) Str::uuid(),
                'event_type' => 'booking.expired',
                'method' => 'POST',
                'path' => 'hms:bookings:expire-unpaid',
                'headers' => [],
                'payload' => [
                    'hms_booking_id' => $hms->hms_booking_id,
                    'booking_type' => $hms->booking_type,
                    'booking_id' => $hms->booking_id,
                    'status' => HmsBooking::STATUS_EXPIRED,
                ],
                'state' => HmsWebhookInbox::STATE_PROCESSED,
                'response_status' => 200,
                'received_at' => now(),
                'processed_at' => now(),
            ]);

            return true;
        });
    }

    private function cancelLocalBooking(HmsBooking $hms): void
    {
        if ($hms->booking_type === HmsBooking::TYPE_DOCTOR) {
            $booking = DoctorBooking::query()->find($hms->booking_id);
            if ($booking && $booking->status !== 'cancelled') {
                $this->doctorStatus->cancelSilently($booking, 'hms_unpaid_expired');
            }

            return;
        }

        $model = $hms->booking_type === HmsBooking::TYPE_PACKAGE
            ? DiagnosticTestBooking::query()->find($hms->booking_id)
            : SecondOpinion::query()->find($hms->booking_id);

        if ($model && $model->status !== 'cancelled') {
            $model->update(['status' => 'cancelled']);
        }
    }
}
