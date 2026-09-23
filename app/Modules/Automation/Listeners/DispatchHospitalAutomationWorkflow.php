<?php

namespace App\Modules\Automation\Listeners;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchHospitalAutomationWorkflow implements ShouldQueue
{
    public function __construct(
        protected HospitalAutomationTriggerService $triggerService,
    ) {}

    public function handle(HospitalAutomationEvent $event): void
    {
        $this->triggerService->dispatch($event->triggerType(), $event->payload());
    }
}
