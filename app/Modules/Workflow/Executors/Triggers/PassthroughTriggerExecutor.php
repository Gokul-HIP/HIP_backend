<?php

namespace App\Modules\Workflow\Executors\Triggers;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Log;

/**
 * Default handler for trigger nodes that only start the graph (no side effects).
 * Registered for every TriggerCatalog type that lacks a specialized executor.
 */
class PassthroughTriggerExecutor extends AbstractNodeExecutor
{
    public function __construct(
        protected string $triggerType,
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return $this->triggerType;
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        Log::info('Workflow trigger node executed', [
            'trigger_type' => $this->triggerType,
            'execution_id' => $execution->id,
            'node_id' => $node->id,
        ]);

        return NodeExecutionResult::continue();
    }
}
