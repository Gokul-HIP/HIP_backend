<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;

class PrescriptionAddedTriggerNodeProcessor extends AbstractNodeProcessor
{
    public function type(): string
    {
        return 'prescriptionAdded';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return NodeExecutionResult::continue();
    }
}
