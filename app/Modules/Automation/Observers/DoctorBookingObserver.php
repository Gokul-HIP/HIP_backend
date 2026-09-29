<?php

namespace App\Modules\Automation\Observers;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Support\SafeAutomationDispatch;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches AppointmentBooked when a DoctorBooking is created pending or
 * confirmed, and when status changes to confirmed. Does not look up workflows.
 *
 * Both create-pending and confirm dispatches are existing product semantics
 * (admin confirm is a second AppointmentBooked). Workflows branch on status.
 */
class DoctorBookingObserver
{
    public function created(DoctorBooking $booking): void
    {
        if (! $this->isDispatchStatus($booking->status)) {
            Log::info('[appointment-booked] Booking created with non-dispatch status; skipping', [
                'appointment_id' => $booking->id,
                'hospital_id' => $booking->hospital_id,
                'status' => $booking->status,
            ]);

            return;
        }

        Log::info('[appointment-booked] Booking created; dispatching', [
            'appointment_id' => $booking->id,
            'hospital_id' => $booking->hospital_id,
            'status' => $booking->status,
        ]);

        $this->dispatchAppointmentBooked($booking);
    }

    public function updated(DoctorBooking $booking): void
    {
        if (! $booking->wasChanged('status')) {
            return;
        }

        if (! $booking->isConfirmed()) {
            Log::info('[appointment-booked] Booking status changed but not to confirmed; skipping', [
                'appointment_id' => $booking->id,
                'hospital_id' => $booking->hospital_id,
                'from_status' => $booking->getOriginal('status'),
                'status' => $booking->status,
            ]);

            return;
        }

        Log::info('[appointment-booked] Booking status changed to confirmed; dispatching', [
            'appointment_id' => $booking->id,
            'hospital_id' => $booking->hospital_id,
            'from_status' => $booking->getOriginal('status'),
        ]);

        $this->dispatchAppointmentBooked($booking);
    }

    protected function dispatchAppointmentBooked(DoctorBooking $booking): void
    {
        SafeAutomationDispatch::afterCommit(
            fn () => AppointmentBooked::dispatch($booking),
            'appointment-booked'
        );
    }

    protected function isDispatchStatus(mixed $status): bool
    {
        return in_array($status, [
            DoctorBooking::STATUS_PENDING,
            DoctorBooking::STATUS_CONFIRMED,
        ], true);
    }
}
