<?php

namespace App\Modules\Workflow\Executors\Triggers;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Log;

class ScheduledEventTriggerExecutor extends AbstractNodeExecutor
{
    public function type(): string
    {
        return 'scheduledEvent';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        Log::info('ScheduledEvent trigger executed', [
            'execution_id' => $execution->id,
            'node_id' => $node->id,
        ]);

        return NodeExecutionResult::continue();
    }
}
