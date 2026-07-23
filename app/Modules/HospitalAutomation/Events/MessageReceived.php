<?php

namespace App\Modules\HospitalAutomation\Events;

use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use Illuminate\Foundation\Events\Dispatchable;

class MessageReceived implements HospitalAutomationEvent
{
    use Dispatchable;

    /** @param  array<string, mixed>  $context */
    public function __construct(public array $context = []) {}

    public function triggerType(): string
    {
        return 'messageReceived';
    }

    public function payload(): array
    {
        return $this->context;
    }
}
