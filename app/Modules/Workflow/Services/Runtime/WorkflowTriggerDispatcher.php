<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\Repositories\WorkflowRepository;
use Illuminate\Support\Facades\Log;

class WorkflowTriggerDispatcher
{
    public function __construct(
        protected WorkflowRepository $workflowRepository,
        protected WorkflowExecutor $workflowExecutor,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $triggerType, array $payload, ?int $organizationId = null): void
    {
        $workflows = $this->workflowRepository->findActiveByTrigger($triggerType, $organizationId);

        foreach ($workflows as $workflow) {
            $version = $workflow->currentVersion;

            if (! $version) {
                Log::warning('Active workflow missing published version', [
                    'workflow_id' => $workflow->id,
                    'trigger_type' => $triggerType,
                ]);
                continue;
            }

            $this->workflowExecutor->start($version, $triggerType, $payload);
        }
    }
}
