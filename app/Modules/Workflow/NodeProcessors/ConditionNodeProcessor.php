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
use Illuminate\Support\Facades\Log;

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

        $prefix = match ($evalContext->triggerType) {
            'appointmentBooked' => '[appointment-booked]',
            'labTestOrdered' => '[lab-test-ordered]',
            default => null,
        };

        if ($prefix !== null) {
            Log::info($prefix.' condition evaluated', [
                'workflow_execution_id' => $execution->id,
                'workflow_id' => $execution->workflow_id,
                'trigger_type' => $evalContext->triggerType,
                'expression' => $expression !== '' ? $expression : 'rules',
                'result' => $passed,
                'handle' => $handle,
                'appointment_status' => data_get($evalContext->payload, '_facts.appointment.status'),
                'appointment_booking_type' => data_get($evalContext->payload, '_facts.appointment.booking_type'),
                'order_status' => data_get($evalContext->payload, 'order.status'),
                'payment_status' => data_get($evalContext->payload, 'payment.status'),
                'payment_is_pay_by_hospital' => data_get($evalContext->payload, 'payment.is_pay_by_hospital'),
                'hospital_id' => $evalContext->payload['hospital_id'] ?? null,
                'organization_id' => $evalContext->payload['organization_id'] ?? null,
            ]);
        }

        return new NodeExecutionResult(
            status: 'branch',
            output: ['handle' => $handle],
        );
    }
}
