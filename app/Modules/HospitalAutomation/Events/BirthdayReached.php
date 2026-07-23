<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Models\Persons;
use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BirthdayReached implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Persons $patient) {}

    public function triggerType(): string
    {
        return 'birthday';
    }

    public function payload(): array
    {
        return ['patient' => $this->patient];
    }
}
