<?php

namespace App\Modules\Automation\Events;

use App\Models\DoctorBooking;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentRescheduled implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public DoctorBooking $appointment,
        public ?string $previousDate = null,
    ) {}

    public function triggerType(): string
    {
        return 'appointmentRescheduled';
    }

    public function payload(): array
    {
        return [
            'appointment' => $this->appointment,
            'previous_date' => $this->previousDate,
        ];
    }
}
