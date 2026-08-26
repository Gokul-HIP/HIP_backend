<?php

namespace App\Modules\HospitalAutomation\Observers;

use App\Models\DoctorBooking;
use App\Modules\HospitalAutomation\Events\AppointmentBooked;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches AppointmentBooked when a DoctorBooking reaches confirmed status.
 * Does not look up or execute workflows.
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
        $dispatch = function () use ($booking): void {
            AppointmentBooked::dispatch($booking);
        };

        // RefreshDatabase tests wrap work in a transaction that never commits.
        if (app()->runningUnitTests() || DB::transactionLevel() === 0) {
            $dispatch();

            return;
        }

        DB::afterCommit($dispatch);
    }
}
