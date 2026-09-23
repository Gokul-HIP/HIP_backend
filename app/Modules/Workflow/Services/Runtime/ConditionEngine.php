<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidExpressionException;

class ConditionEngine
{
    public function __construct(
        protected ExpressionEvaluator $expressionEvaluator,
    ) {}

    /**
     * Evaluate a saved JEXL-like expression against runtime context.
     *
     * @throws InvalidExpressionException
     */
    public function evaluateExpression(string $expression, WorkflowContext $context): bool
    {
        return $this->expressionEvaluator->evaluate($expression, $context);
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function evaluate(array $rules, WorkflowContext $context): bool
    {
        if ($rules === []) {
            return true;
        }

        $operator = strtoupper((string) ($rules['operator'] ?? 'AND'));
        $conditions = $rules['conditions'] ?? $rules['rules'] ?? [];

        if (! is_array($conditions)) {
            return $this->evaluateLeaf($rules, $context);
        }

        if ($operator === 'NOT') {
            $first = $conditions[0] ?? $rules;

            return ! $this->evaluate(is_array($first) ? $first : $rules, $context);
        }

        $results = array_map(
            fn ($condition) => is_array($condition)
                ? $this->evaluate($condition, $context)
                : false,
            $conditions
        );

        return $operator === 'OR'
            ? in_array(true, $results, true)
            : ! in_array(false, $results, true);
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    protected function evaluateLeaf(array $rule, WorkflowContext $context): bool
    {
        $field = (string) ($rule['field'] ?? $rule['attribute'] ?? '');
        $operator = (string) ($rule['compare'] ?? $rule['operator'] ?? 'equals');
        $expected = $rule['value'] ?? null;

        $actual = $this->resolveField($field, $context);

        return match ($operator) {
            'equals', 'eq', '==' => $actual == $expected,
            'not_equals', 'neq', '!=' => $actual != $expected,
            'greater_than', 'gt', '>' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
            'greater_than_or_equal', 'gte', '>=' => is_numeric($actual) && is_numeric($expected) && $actual >= $expected,
            'less_than', 'lt', '<' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
            'less_than_or_equal', 'lte', '<=' => is_numeric($actual) && is_numeric($expected) && $actual <= $expected,
            'contains' => is_string($actual) && is_string($expected) && str_contains(strtolower($actual), strtolower($expected)),
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'exists' => $actual !== null && $actual !== '',
            default => false,
        };
    }

    protected function resolveField(string $field, WorkflowContext $context): mixed
    {
        $normalized = strtolower($field);

        return match ($normalized) {
            'age', 'patient.age' => data_get($context->payload, 'patient.age')
                ?? data_get($context->payload, 'prescription.patient.age'),
            'gender', 'patient.gender' => data_get($context->payload, 'patient.gender')
                ?? data_get($context->payload, 'prescription.patient.gender'),
            'disease', 'patient.disease' => data_get($context->payload, 'patient.disease'),
            'membership', 'member.type' => data_get($context->payload, 'member.type')
                ?? data_get($context->payload, 'prescription.member.type'),
            'payment', 'payment.status' => data_get($context->payload, 'payment.status'),
            'language', 'patient.language' => data_get($context->payload, 'patient.language'),
            'segment', 'patient.segment' => data_get($context->payload, 'patient.segment'),
            default => data_get($context->payload, $field)
                ?? data_get($context->variables, $field),
        };
    }
}
