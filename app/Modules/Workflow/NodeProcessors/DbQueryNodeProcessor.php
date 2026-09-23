<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use App\Modules\Workflow\Support\DomainRecordMap;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Constrained read against allowlisted entities. Does not execute SQL from node config.
 *
 * Saved FE fixture uses `query` as an expression-like locator
 * (`patient.id == context.patient.id`), not a SQL string.
 */
class DbQueryNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected VariableResolver $variableResolver,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'dbQuery';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];
        $payload = array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );

        $queryString = trim((string) ($data['query'] ?? ''));
        if ($queryString !== '' && $this->looksLikeSql($queryString)) {
            return NodeExecutionResult::failed(
                'dbQuery does not accept SQL. Use entity/table + where, or an expression locator.'
            );
        }

        $parsed = $this->resolveLocator($data, $payload);
        if ($parsed === null) {
            return NodeExecutionResult::failed(
                'dbQuery requires an allowlisted entity/table and a record locator.'
            );
        }

        [$entityKey, $spec, $recordId] = $parsed;
        $table = $spec['table'];

        if (! Schema::hasTable($table)) {
            return NodeExecutionResult::failed("dbQuery target table is unavailable: {$table}");
        }

        $hospitalId = $this->intFrom($execution->workflow?->hospital_id ?? $payload['hospital_id'] ?? data_get($payload, 'hospital.id'));
        $memberId = $payload['member_id'] ?? data_get($payload, 'member.id') ?? data_get($payload, 'patient.hip_user_id');

        try {
            $query = DB::table($table)->where($spec['idColumn'], $recordId);

            if ($spec['hospitalColumn'] !== null) {
                if ($hospitalId === null) {
                    return NodeExecutionResult::failed('dbQuery requires hospital scope for this entity.');
                }
                $query->where($spec['hospitalColumn'], $hospitalId);
            } elseif ($entityKey === 'patient' || $entityKey === 'persons') {
                if ($hospitalId === null || ! Schema::hasTable('doctor_bookings')) {
                    return NodeExecutionResult::failed('dbQuery requires hospital scope for patient records.');
                }
                $linked = DB::table('doctor_bookings')
                    ->where('patient_id', $recordId)
                    ->where('hospital_id', $hospitalId)
                    ->exists();
                if (! $linked) {
                    $context->setVariable('query_matched', false);
                    $context->setVariable('query_row', null);

                    return NodeExecutionResult::continue();
                }
            } elseif ($spec['memberColumn'] !== null) {
                if ($memberId === null || $memberId === '') {
                    return NodeExecutionResult::failed('dbQuery requires member scope for this entity.');
                }
                $query->where($spec['memberColumn'], $memberId);
            }

            $row = $query->first();
        } catch (\Throwable $e) {
            Log::warning('dbQuery failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        $matched = $row !== null;
        $context->setVariable('query_matched', $matched);
        $context->setVariable('query_row', $row ? (array) $row : null);

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     * @return array{0: string, 1: array<string, mixed>, 2: mixed}|null
     */
    protected function resolveLocator(array $data, array $payload): ?array
    {
        $entityKey = DomainRecordMap::resolveKey(
            (string) ($data['entity'] ?? $data['table'] ?? '')
        );

        $where = is_array($data['where'] ?? null) ? $data['where'] : [];
        $queryString = trim((string) ($data['query'] ?? ''));

        if ($entityKey === null && $queryString !== '') {
            if (! preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\.([A-Za-z_][A-Za-z0-9_]*)\s*==\s*(.+)$/', $queryString, $matches)) {
                return null;
            }
            $entityKey = DomainRecordMap::resolveKey($matches[1]);
            $column = $matches[2];
            $rhs = trim($matches[3]);
            if ($entityKey === null || ! DomainRecordMap::isSafeIdentifier($column)) {
                return null;
            }
            $spec = DomainRecordMap::spec($entityKey);
            if ($spec === null || $column !== $spec['idColumn']) {
                return null;
            }

            return [$entityKey, $spec, $this->resolveRhs($rhs, $payload)];
        }

        if ($entityKey === null) {
            return null;
        }

        $spec = DomainRecordMap::spec($entityKey);
        if ($spec === null) {
            return null;
        }

        if (isset($data['id']) || isset($data['recordId']) || isset($data['record_id'])) {
            return [$entityKey, $spec, $this->resolveScalar($data['id'] ?? $data['recordId'] ?? $data['record_id'], $payload)];
        }

        if ($where !== [] && array_key_exists($spec['idColumn'], $where)) {
            return [$entityKey, $spec, $this->resolveScalar($where[$spec['idColumn']], $payload)];
        }

        foreach ($spec['contextIdKeys'] as $path) {
            $value = data_get($payload, $path);
            if ($value !== null && $value !== '') {
                return [$entityKey, $spec, $value];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveRhs(string $rhs, array $payload): mixed
    {
        $rhs = trim($rhs);
        if (preg_match('/^["\'](.*)["\']$/', $rhs, $quoted)) {
            return $quoted[1];
        }
        if (is_numeric($rhs)) {
            return str_contains($rhs, '.') ? (float) $rhs : (int) $rhs;
        }
        if (str_starts_with($rhs, 'context.')) {
            return data_get($payload, substr($rhs, strlen('context.')));
        }

        return data_get($payload, $rhs);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveScalar(mixed $raw, array $payload): mixed
    {
        if (is_string($raw)) {
            $resolved = trim($this->variableResolver->resolve($raw, $payload));

            return $resolved === '' ? null : $resolved;
        }

        return $raw;
    }

    protected function looksLikeSql(string $query): bool
    {
        return (bool) preg_match('/\b(select|insert|update|delete|drop|alter|truncate|union|exec|execute|into\s+outfile)\b/i', $query);
    }

    protected function intFrom(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
