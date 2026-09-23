<?php

namespace App\Modules\Automation\Contracts;

interface HospitalAutomationEvent
{
    public function triggerType(): string;

    /** @return array<string, mixed> */
    public function payload(): array;
}
