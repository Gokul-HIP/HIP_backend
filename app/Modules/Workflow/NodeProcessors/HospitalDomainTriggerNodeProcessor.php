<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\NodeProcessors\Contracts\NodeProcessor;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Automation\Engine\AutomationContextBuilder;

/**
 * Generic passthrough trigger executor for hospital domain events.
 * Enriches workflow variables then continues graph traversal.
 */
class HospitalDomainTriggerNodeProcessor implements NodeProcessor
{
    public function __construct(
        private readonly string $triggerType,
        protected ActionDispatcher $actionDispatcher,
        protected AutomationContextBuilder $contextBuilder,
    ) {}

    public function type(): string
    {
        return $this->triggerType;
    }

    public function execute(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return $this->actionDispatcher->dispatch($node->nodeType, function () use ($context, $execution) {
            $merged = $this->contextBuilder->merge($context->payload);
            $variables = app(\App\Modules\Workflow\Services\Runtime\VariableResolver::class)
                ->buildVariables($merged);

            foreach ($variables as $key => $value) {
                $context->setVariable($key, $value);
            }

            $execution->update([
                'patient_id' => $merged['patient_id'] ?? $execution->patient_id,
                'doctor_id' => $merged['doctor_id'] ?? $execution->doctor_id,
                'hospital_id' => $merged['hospital_id'] ?? $execution->hospital_id,
                'organization_id' => $merged['organization_id'] ?? $execution->organization_id,
            ]);

            return NodeExecutionResult::continue();
        });
    }
}
