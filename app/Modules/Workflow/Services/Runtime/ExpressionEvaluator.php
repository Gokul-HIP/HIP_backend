<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Exceptions\InvalidExpressionException;
use Illuminate\Support\Arr;

/**
 * Deterministic evaluator for saved workflow condition expressions.
 * Supports == != < > <= >= && || parentheses, dotted paths, quoted strings,
 * booleans, and numbers. Does not use eval().
 */
class ExpressionEvaluator
{
    /** @var list<array{type: string, value: mixed}> */
    private array $tokens = [];

    private int $pos = 0;

    public function evaluate(string $expression, WorkflowContext $context): bool
    {
        $trimmed = trim($expression);

        if ($trimmed === '') {
            throw new InvalidExpressionException('Expression is empty.');
        }

        $this->tokens = $this->tokenize($trimmed);
        $this->pos = 0;

        if ($this->tokens === []) {
            throw new InvalidExpressionException('Expression is empty.');
        }

        $value = $this->parseOr($context);

        if ($this->current() !== null) {
            throw new InvalidExpressionException('Unexpected token remaining in expression.');
        }

        return $this->isTruthy($value);
    }

    private function parseOr(WorkflowContext $context): mixed
    {
        $left = $this->parseAnd($context);

        while ($this->matchOp('||')) {
            $right = $this->parseAnd($context);
            $left = $this->isTruthy($left) || $this->isTruthy($right);
        }

        return $left;
    }

    private function parseAnd(WorkflowContext $context): mixed
    {
        $left = $this->parseComparison($context);

        while ($this->matchOp('&&')) {
            $right = $this->parseComparison($context);
            $left = $this->isTruthy($left) && $this->isTruthy($right);
        }

        return $left;
    }

    private function parseComparison(WorkflowContext $context): mixed
    {
        $left = $this->parsePrimary($context);
        $op = $this->current();

        if ($op === null || $op['type'] !== 'op' || ! in_array($op['value'], ['==', '!=', '<', '>', '<=', '>='], true)) {
            return $left;
        }

        $this->pos++;
        $right = $this->parsePrimary($context);

        return $this->compare($left, (string) $op['value'], $right);
    }

    private function parsePrimary(WorkflowContext $context): mixed
    {
        $token = $this->current();

        if ($token === null) {
            throw new InvalidExpressionException('Unexpected end of expression.');
        }

        if ($token['type'] === 'op' && $token['value'] === '(') {
            $this->pos++;
            $value = $this->parseOr($context);
            $close = $this->current();

            if ($close === null || $close['type'] !== 'op' || $close['value'] !== ')') {
                throw new InvalidExpressionException('Missing closing parenthesis.');
            }

            $this->pos++;

            return $value;
        }

        $this->pos++;

        return match ($token['type']) {
            'string', 'number', 'bool', 'null' => $token['value'],
            'ident' => $this->resolvePath((string) $token['value'], $context),
            default => throw new InvalidExpressionException('Unexpected token in expression.'),
        };
    }

    private function compare(mixed $left, string $operator, mixed $right): bool
    {
        if (in_array($operator, ['<', '>', '<=', '>='], true)) {
            if ($left === null || $right === null) {
                return false;
            }

            if (! is_numeric($left) || ! is_numeric($right)) {
                return false;
            }

            $l = (float) $left;
            $r = (float) $right;

            return match ($operator) {
                '<' => $l < $r,
                '>' => $l > $r,
                '<=' => $l <= $r,
                '>=' => $l >= $r,
                default => false,
            };
        }

        $equal = $this->valuesEqual($left, $right);

        return $operator === '==' ? $equal : ! $equal;
    }

    private function valuesEqual(mixed $left, mixed $right): bool
    {
        if (is_bool($left) || is_bool($right)) {
            return $this->asBool($left) === $this->asBool($right);
        }

        if ($left === null || $right === null) {
            return $left === $right;
        }

        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left == (float) $right;
        }

        return (string) $left === (string) $right;
    }

    private function asBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return false;
        }

        if (is_string($value) && strcasecmp($value, 'false') === 0) {
            return false;
        }

        return (bool) $value;
    }

    private function isTruthy(mixed $value): bool
    {
        return $this->asBool($value);
    }

    private function resolvePath(string $path, WorkflowContext $context): mixed
    {
        $facts = $context->payload['_facts'] ?? null;

        if (is_array($facts) && Arr::has($facts, $path)) {
            return data_get($facts, $path);
        }

        if (Arr::has($context->payload, $path)) {
            $value = data_get($context->payload, $path);

            if ($this->isEloquentExistsCollision($path, $context->payload)) {
                return data_get($facts, $path);
            }

            return $value;
        }

        if (Arr::has($context->variables, $path)) {
            return data_get($context->variables, $path);
        }

        return data_get($context->payload, $path)
            ?? data_get($context->variables, $path);
    }

    /**
     * Eloquent's `exists` attribute means "row persisted", not the workflow fact.
     *
     * @param  array<string, mixed>  $payload
     */
    private function isEloquentExistsCollision(string $path, array $payload): bool
    {
        if (! str_ends_with($path, '.exists')) {
            return false;
        }

        $parent = substr($path, 0, -strlen('.exists'));
        $parentValue = data_get($payload, $parent);

        return $parentValue instanceof \Illuminate\Database\Eloquent\Model;
    }

    /**
     * @return list<array{type: string, value: mixed}>
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                $i++;
                $buffer = '';

                while ($i < $length && $expression[$i] !== $quote) {
                    if ($expression[$i] === '\\' && $i + 1 < $length) {
                        $buffer .= $expression[$i + 1];
                        $i += 2;
                        continue;
                    }

                    $buffer .= $expression[$i];
                    $i++;
                }

                if ($i >= $length) {
                    throw new InvalidExpressionException('Unterminated string literal.');
                }

                $i++;
                $tokens[] = ['type' => 'string', 'value' => $buffer];
                continue;
            }

            $two = $i + 1 < $length ? $expression[$i].$expression[$i + 1] : $char;

            if (in_array($two, ['==', '!=', '<=', '>=', '&&', '||'], true)) {
                $tokens[] = ['type' => 'op', 'value' => $two];
                $i += 2;
                continue;
            }

            if (in_array($char, ['<', '>', '(', ')'], true)) {
                $tokens[] = ['type' => 'op', 'value' => $char];
                $i++;
                continue;
            }

            if ($char === '-' || ctype_digit($char)) {
                if ($char === '-' && ($i + 1 >= $length || ! ctype_digit($expression[$i + 1]))) {
                    throw new InvalidExpressionException('Unexpected character in expression.');
                }

                $start = $i;
                $i++;

                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $i++;
                }

                $raw = substr($expression, $start, $i - $start);

                if (! is_numeric($raw)) {
                    throw new InvalidExpressionException('Invalid numeric literal.');
                }

                $tokens[] = ['type' => 'number', 'value' => str_contains($raw, '.') ? (float) $raw : (int) $raw];
                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                $i++;

                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_' || $expression[$i] === '.')) {
                    $i++;
                }

                $raw = substr($expression, $start, $i - $start);
                $lower = strtolower($raw);

                if ($lower === 'true' || $lower === 'false') {
                    $tokens[] = ['type' => 'bool', 'value' => $lower === 'true'];
                    continue;
                }

                if ($lower === 'null') {
                    $tokens[] = ['type' => 'null', 'value' => null];
                    continue;
                }

                $tokens[] = ['type' => 'ident', 'value' => $raw];
                continue;
            }

            throw new InvalidExpressionException('Unexpected character in expression.');
        }

        return $tokens;
    }

    /**
     * @return array{type: string, value: mixed}|null
     */
    private function current(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function matchOp(string $op): bool
    {
        $token = $this->current();

        if ($token !== null && $token['type'] === 'op' && $token['value'] === $op) {
            $this->pos++;

            return true;
        }

        return false;
    }
}
