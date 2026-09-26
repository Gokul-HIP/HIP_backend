<?php

namespace App\Modules\Automation\Listeners;

use App\Modules\Automation\Contracts\HospitalAutomationEvent;
use App\Modules\Automation\TriggerHandlers\HospitalAutomationTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DispatchHospitalAutomationWorkflow implements ShouldQueue
{
    public function __construct(
        protected HospitalAutomationTriggerService $triggerService,
    ) {}

    public function handle(HospitalAutomationEvent $event): void
    {
        $payload = $event->payload();

        Log::info('DispatchHospitalAutomationWorkflow handling', [
            'trigger_type' => $event->triggerType(),
            'appointment_id' => $payload['appointment_id'] ?? null,
            'hospital_id' => $payload['hospital_id'] ?? null,
            'organization_id' => $payload['organization_id'] ?? null,
            'event_occurrence_id' => $payload['event_occurrence_id'] ?? null,
        ]);

        $this->triggerService->dispatch($event->triggerType(), $payload);
    }
}
