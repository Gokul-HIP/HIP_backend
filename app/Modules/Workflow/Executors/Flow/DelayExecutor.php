<?php

namespace App\Modules\Workflow\Executors\Flow;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\DelayScheduler;

class DelayExecutor extends AbstractNodeExecutor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected DelayScheduler $delayScheduler,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'delay';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $delaySeconds = $this->delayScheduler->resolveDelaySeconds($node->data);

        return NodeExecutionResult::wait($delaySeconds);
    }
}
