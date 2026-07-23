<?php

namespace App\Modules\HospitalAutomation\Listeners;

use App\Modules\HospitalAutomation\Contracts\HospitalAutomationEvent;
use App\Modules\HospitalAutomation\Services\HospitalAutomationTriggerService;
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
