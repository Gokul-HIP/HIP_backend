<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Models\Persons;
use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatientRegistered implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Persons $patient, public ?int $organizationId = null) {}

    public function triggerType(): string
    {
        return 'patientRegistered';
    }

    public function payload(): array
    {
        return [
            'patient' => $this->patient,
            'organization_id' => $this->organizationId,
        ];
    }
}
