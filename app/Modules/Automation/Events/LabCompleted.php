<?php

namespace App\Modules\Automation\Events;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

class LabCompleted implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = []) {}

    public function triggerType(): string
    {
        return 'labCompleted';
    }

    public function payload(): array
    {
        return $this->context;
    }
}
