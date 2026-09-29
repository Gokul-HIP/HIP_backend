<?php

namespace App\Modules\Automation\Listeners;

use App\Models\DoctorBooking;
use App\Modules\Automation\Events\AppointmentBooked;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Queued listener for DoctorBooking AppointmentBooked.
 *
 * Does not require confirmed. Pay-by-Hospital pending and later confirmation
 * both reach AutomationEngine; published JEXL decides the notification.
 *
 * Stale-queue protection compares the status captured on the event
 * (occurrence / eventStatus) with the live DoctorBooking row. It does not
 * branch on payment method.
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
        $eventStatus = $this->capturedEventStatus($event);

        $booking = DoctorBooking::query()
            ->with(['doctor', 'hospital.organization', 'patient', 'department'])
            ->find($bookingId);

        if (! $booking) {
            Log::warning('[appointment-booked] Listener: booking not found; aborting', [
                'appointment_id' => $bookingId,
                'event_status' => $eventStatus,
            ]);

            return;
        }

        $currentStatus = strtolower((string) $booking->status);

        Log::info('[appointment-booked] Listener evaluating event', [
            'appointment_id' => $bookingId,
            'event_status' => $eventStatus,
            'current_status' => $currentStatus,
        ]);

        if ($eventStatus !== $currentStatus) {
            Log::info('[appointment-booked] Stale event skipped', [
                'appointment_id' => $bookingId,
                'event_status' => $eventStatus,
                'current_status' => $currentStatus,
            ]);

            return;
        }

        if (! in_array($currentStatus, [
            DoctorBooking::STATUS_PENDING,
            DoctorBooking::STATUS_CONFIRMED,
        ], true)) {
            Log::info('[appointment-booked] Stale event skipped', [
                'appointment_id' => $bookingId,
                'event_status' => $eventStatus,
                'current_status' => $currentStatus,
                'reason' => 'status_not_pending_or_confirmed',
            ]);

            return;
        }

        if (! $booking->hospital_id) {
            Log::warning('[appointment-booked] Listener: booking has no hospital_id; aborting', [
                'appointment_id' => $bookingId,
            ]);

            return;
        }

        Log::info('[appointment-booked] Listener dispatching automation', [
            'appointment_id' => $bookingId,
            'event_status' => $eventStatus,
            'current_status' => $currentStatus,
        ]);

        $this->triggerService->dispatch('appointmentBooked', [
            'appointment' => $booking,
            'appointment_id' => $booking->id,
            'hospital_id' => $booking->hospital_id,
            'event_occurrence_id' => $event->occurrenceId ?: AppointmentBooked::occurrenceIdFor($booking),
            'meta' => [
                'booking_type' => 'doctor',
                'booking_id' => (string) $booking->id,
            ],
        ]);
    }

    protected function capturedEventStatus(AppointmentBooked $event): string
    {
        if (filled($event->eventStatus ?? null)) {
            return strtolower((string) $event->eventStatus);
        }

        $fromOccurrence = AppointmentBooked::statusFromOccurrenceId($event->occurrenceId);
        if ($fromOccurrence !== null) {
            return $fromOccurrence;
        }

        return strtolower((string) ($event->appointment->status ?? ''));
    }
}
