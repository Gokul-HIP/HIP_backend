<?php

namespace App\Modules\Workflow\Executors\Flow;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;

class EndExecutor extends AbstractNodeExecutor
{
    public function type(): string
    {
        return 'end';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return NodeExecutionResult::complete();
    }
}
