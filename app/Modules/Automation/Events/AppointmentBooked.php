<?php

namespace App\Modules\Automation\Events;

use App\Models\DoctorBooking;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised when a DoctorBooking is created as pending or confirmed, and when
 * status later changes to confirmed. Workflows distinguish those states with
 * JEXL (appointment.status, payment.is_pay_by_hospital). Occurrence includes
 * lifecycle status so pending then confirmed are not collapsed by alreadyStarted().
 */
class AppointmentBooked implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DoctorBooking $appointment,
        public ?string $occurrenceId = null,
        public ?string $eventStatus = null,
    ) {
        $this->eventStatus = strtolower((string) ($eventStatus ?: $appointment->status));
        $this->occurrenceId = $occurrenceId ?: self::occurrenceIdFor($appointment);
    }

    public static function occurrenceIdFor(DoctorBooking $booking): string
    {
        return 'appointment-booked:booking:'.$booking->id.':status:'.strtolower((string) $booking->status);
    }

    public static function statusFromOccurrenceId(?string $occurrenceId): ?string
    {
        if (! is_string($occurrenceId) || $occurrenceId === '') {
            return null;
        }

        if (preg_match('/:status:([a-z0-9_]+)$/', $occurrenceId, $matches) !== 1) {
            return null;
        }

        return strtolower($matches[1]);
    }

    public function triggerType(): string
    {
        return 'appointmentBooked';
    }

    public function payload(): array
    {
        return [
            'appointment' => $this->appointment,
            'appointment_id' => $this->appointment->id,
            'hospital_id' => $this->appointment->hospital_id,
            'event_occurrence_id' => $this->occurrenceId,
            'meta' => [
                'booking_type' => 'doctor',
                'booking_id' => (string) $this->appointment->id,
            ],
        ];
    }
}
