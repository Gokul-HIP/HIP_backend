<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Automation\Engine\AutomationFactsBuilder;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidExpressionException;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ConditionEngine;

class ConditionNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected ConditionEngine $conditionEngine,
        protected AutomationFactsBuilder $factsBuilder,
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
        $evalContext = new WorkflowContext(
            triggerType: $context->triggerType,
            payload: $this->factsBuilder->enrich($context->payload),
            variables: $context->variables,
        );

        $expression = trim((string) ($node->data['expression'] ?? ''));

        try {
            if ($expression !== '') {
                $passed = $this->conditionEngine->evaluateExpression($expression, $evalContext);
            } else {
                $rules = is_array($node->data['rules'] ?? null)
                    ? $node->data['rules']
                    : (is_array($node->data['conditions'] ?? null)
                        ? ['operator' => 'AND', 'conditions' => $node->data['conditions']]
                        : []);

                $passed = $this->conditionEngine->evaluate($rules, $evalContext);
            }
        } catch (InvalidExpressionException $e) {
            return NodeExecutionResult::failed($e->getMessage());
        }

        $handle = $passed ? 'true' : 'false';

        return new NodeExecutionResult(
            status: 'branch',
            output: ['handle' => $handle],
        );
    }
}
