<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Automation\Engine\AutomationFactsBuilder;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidDelayConfiguration;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\DelayScheduler;

class DelayNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        \App\Modules\Workflow\Services\Runtime\ActionDispatcher $actionDispatcher,
        protected DelayScheduler $delayScheduler,
        protected AutomationFactsBuilder $factsBuilder,
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
        $evalContext = new WorkflowContext(
            triggerType: $context->triggerType,
            payload: $this->factsBuilder->enrich($context->payload),
            variables: $context->variables,
        );

        try {
            $delaySeconds = $this->delayScheduler->resolveDelaySeconds($node->data, $evalContext);
        } catch (InvalidDelayConfiguration $e) {
            return NodeExecutionResult::failed($e->getMessage());
        }

        return NodeExecutionResult::wait($delaySeconds);
    }
}
