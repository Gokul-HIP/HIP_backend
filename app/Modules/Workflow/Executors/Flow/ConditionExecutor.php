<?php

namespace App\Modules\Workflow\Executors\Flow;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Executors\AbstractNodeExecutor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ConditionEngine;

class ConditionExecutor extends AbstractNodeExecutor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected ConditionEngine $conditionEngine,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'condition';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $rules = is_array($node->data['rules'] ?? null)
            ? $node->data['rules']
            : (is_array($node->data['conditions'] ?? null) ? ['operator' => 'AND', 'conditions' => $node->data['conditions']] : []);

        $passed = $this->conditionEngine->evaluate($rules, $context);
        $handle = $passed ? 'true' : 'false';

        return new NodeExecutionResult(
            status: 'branch',
            output: ['handle' => $handle],
        );
    }
}
