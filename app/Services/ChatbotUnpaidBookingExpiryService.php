<?php

namespace App\Services;

use App\Models\DiseaseChatbotBooking;
use App\Models\DoctorBooking;
use App\Models\Invoice;
use App\Models\RazorpayPayment;
use App\Models\Transactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatbotUnpaidBookingExpiryService
{
    public function __construct(
        protected DoctorBookingStatusService $bookingStatus,
    ) {}

    /**
     * @return array{expired: int, skipped_paid: int, skipped_in_flight: int, skipped_other: int}
     */
    public function expireDue(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $counts = [
            'expired' => 0,
            'skipped_paid' => 0,
            'skipped_in_flight' => 0,
            'skipped_other' => 0,
        ];

        DiseaseChatbotBooking::query()
            ->where('status', DiseaseChatbotBooking::STATUS_PENDING_PAYMENT)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->orderBy('id')
            ->chunkById(50, function ($rows) use (&$counts): void {
                foreach ($rows as $link) {
                    $result = $this->expireOne($link);
                    $counts[$result] = ($counts[$result] ?? 0) + 1;
                }
            });

        return $counts;
    }

    /**
     * @return 'expired'|'skipped_paid'|'skipped_in_flight'|'skipped_other'
     */
    public function expireOne(DiseaseChatbotBooking $link): string
    {
        return DB::transaction(function () use ($link) {
            /** @var DiseaseChatbotBooking|null $locked */
            $locked = DiseaseChatbotBooking::query()
                ->whereKey($link->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== DiseaseChatbotBooking::STATUS_PENDING_PAYMENT) {
                return 'skipped_other';
            }

            if ($locked->expires_at === null || $locked->expires_at->gte(now())) {
                return 'skipped_other';
            }

            /** @var DoctorBooking|null $booking */
            $booking = DoctorBooking::query()
                ->whereKey($locked->doctor_booking_id)
                ->lockForUpdate()
                ->first();

            if (! $booking) {
                $locked->status = DiseaseChatbotBooking::STATUS_EXPIRED;
                $locked->save();

                return 'expired';
            }

            // Re-read after lock: already paid/confirmed → mark link confirmed.
            if (
                $booking->payment_status === 'paid'
                || $booking->status === DoctorBooking::STATUS_CONFIRMED
            ) {
                $locked->status = DiseaseChatbotBooking::STATUS_CONFIRMED;
                $locked->save();

                return 'skipped_paid';
            }

            $invoiceId = $locked->invoice_id ?: $booking->invoice_id;
            if ($invoiceId) {
                $inFlight = RazorpayPayment::query()
                    ->where('invoice_id', $invoiceId)
                    ->whereIn('payment_status', ['authorized', 'captured'])
                    ->exists();

                if ($inFlight) {
                    return 'skipped_in_flight';
                }
            }

            // Cancel booking without patient push / AppointmentCancelled.
            $this->bookingStatus->cancelSilently($booking->fresh(), 'chatbot_unpaid_expired');

            if ($invoiceId) {
                /** @var Invoice|null $invoice */
                $invoice = Invoice::query()->whereKey($invoiceId)->lockForUpdate()->first();
                if ($invoice && (string) $invoice->status === 'pending') {
                    $invoice->status = 'cancelled';
                    $invoice->save();
                }

                Transactions::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);

                RazorpayPayment::query()
                    ->where('invoice_id', $invoiceId)
                    ->where('payment_status', 'created')
                    ->update(['payment_status' => 'superseded']);
            }

            $locked->status = DiseaseChatbotBooking::STATUS_EXPIRED;
            $locked->save();

            Log::info('Chatbot unpaid booking expired', [
                'disease_chatbot_booking_id' => $locked->id,
                'doctor_booking_id' => $locked->doctor_booking_id,
                'invoice_id' => $invoiceId,
            ]);

            return 'expired';
        });
    }
}
