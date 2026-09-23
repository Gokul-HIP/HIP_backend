<?php

namespace App\Modules\Automation\Listeners;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Queued listener for AppointmentBooked.
 *
 * Queue safety: when this job runs the event's DoctorBooking is already
 * re-fetched from the database by Laravel's SerializesModels.  We reload
 * it here with full relations so we build context from the live DB row,
 * not from the in-memory state at dispatch time.
 */
class AppointmentBookedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected HospitalAutomationTriggerService $triggerService,
    ) {}

    public function handle(AppointmentBooked $event): void
    {
        $bookingId = $event->appointment->id;

        // Reload from DB with all relations needed by AutomationContextBuilder.
        $booking = DoctorBooking::query()
            ->with(['doctor', 'hospital.organization', 'patient', 'department'])
            ->find($bookingId);

        if (! $booking) {
            Log::warning('[appointment-booked] Listener: booking not found; aborting', [
                'appointment_id' => $bookingId,
            ]);

            return;
        }

        // Queue safety: only run the workflow if the booking is still confirmed.
        if (! $booking->isConfirmed()) {
            Log::info('[appointment-booked] Listener: booking is no longer confirmed; aborting', [
                'appointment_id' => $bookingId,
                'current_status' => $booking->status,
            ]);

            return;
        }

        if (! $booking->hospital_id) {
            Log::warning('[appointment-booked] Listener: booking has no hospital_id; aborting', [
                'appointment_id' => $bookingId,
            ]);

            return;
        }

        $this->triggerService->dispatch('appointmentBooked', [
            'appointment' => $booking,
        ]);
    }
}
