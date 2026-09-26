<?php

namespace App\Modules\Automation\Events;

use App\Models\DoctorBooking;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class AppointmentRescheduled implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DoctorBooking $appointment,
        public ?string $previousDate = null,
        public ?string $occurrenceId = null,
    ) {
        $this->occurrenceId = $occurrenceId ?: (string) Str::uuid();
    }

    public function triggerType(): string
    {
        return 'appointmentRescheduled';
    }

    public function payload(): array
    {
        return [
            'appointment' => $this->appointment,
            'appointment_id' => $this->appointment->id,
            'hospital_id' => $this->appointment->hospital_id,
            'previous_date' => $this->previousDate,
            'event_occurrence_id' => $this->occurrenceId,
        ];
    }
}
