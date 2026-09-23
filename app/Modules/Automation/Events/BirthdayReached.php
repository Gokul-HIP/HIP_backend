<?php

namespace App\Modules\Automation\Events;

use App\Models\Persons;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BirthdayReached implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Persons $patient,
        public ?int $organizationId = null,
        public mixed $hospitalId = null,
    ) {}

    public function triggerType(): string
    {
        return 'birthday';
    }

    public function payload(): array
    {
        return [
            'patient' => $this->patient,
            'patient_id' => $this->patient->id,
            'organization_id' => $this->organizationId,
            'hospital_id' => $this->hospitalId,
        ];
    }
}
