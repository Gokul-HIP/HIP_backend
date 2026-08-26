<?php

namespace App\Modules\HospitalAutomation\Services;

class HospitalAutomationTriggerService
{
    public function __construct(
        protected AutomationEngine $automationEngine,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $triggerType, array $payload = []): void
    {
        $this->automationEngine->handle($triggerType, $payload);
    }
}
