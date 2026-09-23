<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Support\Facades\Log;

class AppointmentBookedTriggerNodeProcessor extends AbstractNodeProcessor
{
    public function type(): string
    {
        return 'appointmentBooked';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        Log::info('AppointmentBooked trigger executed', [
            'execution_id' => $execution->id,
            'node_id' => $node->id,
        ]);

        return NodeExecutionResult::continue();
    }
}
