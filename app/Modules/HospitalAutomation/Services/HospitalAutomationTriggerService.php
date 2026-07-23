<?php

namespace App\Modules\HospitalAutomation\Services;

use App\Modules\HospitalAutomation\Support\TriggerCatalog;
use App\Modules\Workflow\Services\Runtime\WorkflowTriggerDispatcher;
use Illuminate\Support\Facades\Log;

class HospitalAutomationTriggerService
{
    public function __construct(
        protected WorkflowTriggerDispatcher $triggerDispatcher,
        protected AutomationContextBuilder $contextBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $triggerType, array $payload = []): void
    {
        if (! in_array($triggerType, TriggerCatalog::types(), true)) {
            Log::warning('Unknown hospital automation trigger', ['trigger_type' => $triggerType]);
        }

        $context = $this->contextBuilder->merge($payload);
        $organizationId = $this->contextBuilder->resolveOrganizationId($context);

        Log::info('Hospital automation workflow dispatch', [
            'trigger_type' => $triggerType,
            'organization_id' => $organizationId,
        ]);

        $this->triggerDispatcher->dispatch($triggerType, $context, $organizationId);

        if (class_exists(\App\Modules\Workflow\Models\WorkflowEventBusLog::class)) {
            try {
                \App\Modules\Workflow\Models\WorkflowEventBusLog::query()->create([
                    'event_name' => $triggerType,
                    'direction' => 'published',
                    'status' => 'processed',
                    'payload' => ['organization_id' => $organizationId],
                ]);
            } catch (\Throwable) {
                //
            }
        }
    }
}
