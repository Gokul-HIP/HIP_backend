<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Models\DoctorBooking;
use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCancelled implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public DoctorBooking $appointment) {}

    public function triggerType(): string
    {
        return 'appointmentCancelled';
    }

    public function payload(): array
    {
        return ['appointment' => $this->appointment];
    }
}
