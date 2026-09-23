<?php

namespace App\Modules\Automation\Events;

use App\Models\Persons;
use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScheduledEvent implements HospitalAutomationEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Persons $patient,
        public array $context = [],
    ) {}

    public function triggerType(): string
    {
        return 'scheduledEvent';
    }

    public function payload(): array
    {
        return array_merge([
            'patient' => $this->patient,
            'patient_id' => $this->patient->id,
        ], $this->context);
    }
}
