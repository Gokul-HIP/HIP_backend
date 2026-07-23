<?php

namespace App\Modules\Workflow\Executors;

use App\Modules\Workflow\Contracts\NodeExecutorInterface;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;

abstract class AbstractNodeExecutor implements NodeExecutorInterface
{
    public function __construct(
        protected ActionDispatcher $actionDispatcher,
    ) {}

    abstract public function type(): string;

    abstract protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult;

    public function execute(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return $this->actionDispatcher->dispatch(
            $node->nodeType,
            fn () => $this->run($node, $execution, $context)
        );
    }
}
