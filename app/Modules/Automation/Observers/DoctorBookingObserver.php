<?php

namespace App\Modules\Automation\Observers;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\Support\AfterCommit;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches AppointmentBooked when a DoctorBooking reaches confirmed status.
 * Does not look up or execute workflows. Does not fire on delete/soft-delete.
 *
 * AppointmentMissed is dispatched by MissedAppointmentDetector when status
 * becomes missed. This observer only emits AppointmentBooked for confirmed.
 */
class DoctorBookingObserver
{
    public function created(DoctorBooking $booking): void
    {
        if (! $booking->isConfirmed()) {
            Log::info('[appointment-booked] Booking created with non-confirmed status; skipping', [
                'appointment_id' => $booking->id,
                'hospital_id' => $booking->hospital_id,
                'status' => $booking->status,
            ]);

            return;
        }

        Log::info('[appointment-booked] Booking created as confirmed; dispatching', [
            'appointment_id' => $booking->id,
            'hospital_id' => $booking->hospital_id,
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
        AfterCommit::run(fn () => AppointmentBooked::dispatch($booking));
    }
}
