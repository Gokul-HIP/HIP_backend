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

class UpdateRecordNodeProcessor extends AbstractNodeProcessor
{
    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected VariableResolver $variableResolver,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'databaseUpdate';
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

        $values = is_array($data['values'] ?? null) ? $data['values'] : [];
        if ($values === []) {
            return NodeExecutionResult::failed('databaseUpdate requires values.');
        }

        $entityKey = $this->resolveEntityKey($data);
        if ($entityKey !== null) {
            return $this->runMappedUpdate($entityKey, $data, $values, $execution, $context, $payload);
        }

        $table = (string) ($data['table'] ?? '');
        $where = is_array($data['where'] ?? null) ? $data['where'] : [];

        if ($table === '') {
            return NodeExecutionResult::failed('databaseUpdate requires table and values.');
        }

        if (! DomainRecordMap::isSafeIdentifier($table)) {
            return NodeExecutionResult::failed('databaseUpdate table name is invalid.');
        }

        $safeValues = $this->sanitizeColumnMap($values, $payload);
        $safeWhere = $this->sanitizeColumnMap($where, $payload);

        if ($safeValues === null || $safeWhere === null) {
            return NodeExecutionResult::failed('databaseUpdate column names must be simple identifiers.');
        }

        try {
            $query = DB::table($table);
            foreach ($safeWhere as $column => $value) {
                $query->where($column, $value);
            }
            $updated = $query->update($safeValues);
        } catch (\Throwable $e) {
            Log::warning('databaseUpdate failed', ['error' => $e->getMessage()]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        $context->setVariable('updated_count', $updated);

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $payload
     */
    protected function runMappedUpdate(
        string $entityKey,
        array $data,
        array $values,
        WorkflowExecution $execution,
        WorkflowContext $context,
        array $payload
    ): NodeExecutionResult {
        $spec = DomainRecordMap::spec($entityKey);
        if ($spec === null) {
            return NodeExecutionResult::failed('databaseUpdate entity is not allowlisted.');
        }

        $table = $spec['table'];
        if (! Schema::hasTable($table)) {
            return NodeExecutionResult::failed("databaseUpdate target table is unavailable: {$table}");
        }

        $safeValues = $this->sanitizeColumnMap($values, $payload);
        if ($safeValues === null || $safeValues === []) {
            return NodeExecutionResult::failed('databaseUpdate values must use simple column identifiers.');
        }

        $recordId = $this->resolveRecordId($data, $spec, $payload);
        if ($recordId === null || $recordId === '') {
            return NodeExecutionResult::failed(
                'databaseUpdate could not identify the target record (id / recordId / where / context).'
            );
        }

        $hospitalId = $this->intFrom($execution->workflow?->hospital_id ?? $payload['hospital_id'] ?? data_get($payload, 'hospital.id'));
        $memberId = $payload['member_id'] ?? data_get($payload, 'member.id') ?? data_get($payload, 'patient.hip_user_id');

        try {
            $query = DB::table($table)->where($spec['idColumn'], $recordId);

            if ($spec['hospitalColumn'] !== null) {
                if ($hospitalId === null) {
                    return NodeExecutionResult::failed('databaseUpdate requires hospital scope for this entity.');
                }
                $query->where($spec['hospitalColumn'], $hospitalId);
            } elseif ($entityKey === 'patient' || $entityKey === 'persons') {
                if ($hospitalId === null || ! Schema::hasTable('doctor_bookings')) {
                    return NodeExecutionResult::failed('databaseUpdate requires hospital scope for patient records.');
                }
                $linked = DB::table('doctor_bookings')
                    ->where('patient_id', $recordId)
                    ->where('hospital_id', $hospitalId)
                    ->exists();
                if (! $linked) {
                    return NodeExecutionResult::failed(
                        'databaseUpdate refused: patient is not within workflow hospital scope.'
                    );
                }
            } elseif ($spec['memberColumn'] !== null) {
                if ($memberId === null || $memberId === '') {
                    return NodeExecutionResult::failed('databaseUpdate requires member scope for this entity.');
                }
                $query->where($spec['memberColumn'], $memberId);
            }

            $updated = $query->update($safeValues);
        } catch (\Throwable $e) {
            Log::warning('databaseUpdate failed', ['error' => $e->getMessage(), 'entity' => $entityKey]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        if ($updated < 1) {
            return NodeExecutionResult::failed(
                'databaseUpdate target record was not found within isolation scope.'
            );
        }

        $context->setVariable('updated_count', $updated);
        $context->setVariable('updated_entity', $entityKey);
        $context->setVariable('updated_record_id', $recordId);

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveEntityKey(array $data): ?string
    {
        foreach ([$data['entity'] ?? null, $data['table'] ?? null, $data['nodeType'] ?? null] as $candidate) {
            $key = DomainRecordMap::resolveKey(is_string($candidate) ? $candidate : null);
            if ($key !== null) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $spec
     * @param  array<string, mixed>  $payload
     */
    protected function resolveRecordId(array $data, array $spec, array $payload): mixed
    {
        if (isset($data['id']) || isset($data['recordId']) || isset($data['record_id'])) {
            return $this->resolveScalar($data['id'] ?? $data['recordId'] ?? $data['record_id'], $payload);
        }

        $where = is_array($data['where'] ?? null) ? $data['where'] : [];
        if ($where !== [] && array_key_exists($spec['idColumn'], $where)) {
            return $this->resolveScalar($where[$spec['idColumn']], $payload);
        }

        foreach ($spec['contextIdKeys'] as $path) {
            $value = data_get($payload, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $map
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    protected function sanitizeColumnMap(array $map, array $payload): ?array
    {
        $safe = [];

        foreach ($map as $column => $value) {
            if (! is_string($column) || ! DomainRecordMap::isSafeIdentifier($column)) {
                return null;
            }
            $safe[$column] = $this->resolveScalar($value, $payload) ?? $value;
        }

        return $safe;
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

    protected function intFrom(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
