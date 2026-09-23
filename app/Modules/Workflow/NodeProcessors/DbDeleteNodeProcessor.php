<?php

namespace App\Modules\Workflow\NodeProcessors;

use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Frontend catalog node: dbDelete.
 *
 * Deletes a single allowlisted hospital record. Does not accept arbitrary SQL
 * or arbitrary table names. Hospital (and organization, when present) isolation
 * is enforced before delete.
 */
class DbDeleteNodeProcessor extends AbstractNodeProcessor
{
    /**
     * Frontend entity / explicit table → physical table + isolation columns.
     *
     * @var array<string, array{table: string, idColumn: string, hospitalColumn: ?string, organizationViaHospital: bool}>
     */
    private const ENTITY_MAP = [
        'appointment' => [
            'table' => 'doctor_bookings',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'organizationViaHospital' => true,
        ],
        'doctor_bookings' => [
            'table' => 'doctor_bookings',
            'idColumn' => 'id',
            'hospitalColumn' => 'hospital_id',
            'organizationViaHospital' => true,
        ],
        'patient' => [
            'table' => 'persons',
            'idColumn' => 'id',
            // persons has no hospital_id; isolation via linked doctor_bookings.
            'hospitalColumn' => null,
            'organizationViaHospital' => true,
        ],
        'persons' => [
            'table' => 'persons',
            'idColumn' => 'id',
            'hospitalColumn' => null,
            'organizationViaHospital' => true,
        ],
    ];

    public function __construct(
        ActionDispatcher $actionDispatcher,
        protected VariableResolver $variableResolver,
    ) {
        parent::__construct($actionDispatcher);
    }

    public function type(): string
    {
        return 'dbDelete';
    }

    protected function run(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $data = is_array($node->data) ? $node->data : [];
        $payload = $this->mergedPayload($execution, $context);

        $entityKey = $this->resolveEntityKey($data);
        if ($entityKey === null) {
            return NodeExecutionResult::failed(
                'dbDelete requires entity (patient|appointment) or an allowlisted table.'
            );
        }

        $entity = self::ENTITY_MAP[$entityKey];
        $table = $entity['table'];

        if (! Schema::hasTable($table)) {
            return NodeExecutionResult::failed("dbDelete target table is unavailable: {$table}");
        }

        $recordId = $this->resolveRecordId($data, $entityKey, $payload);
        if ($recordId === null || $recordId === '') {
            return NodeExecutionResult::failed(
                'dbDelete could not identify the target record (id / recordId / where / context).'
            );
        }

        $hospitalId = $this->resolveHospitalId($execution, $payload);
        $organizationId = $this->resolveOrganizationId($execution, $payload);

        if ($entity['hospitalColumn'] !== null && $hospitalId === null) {
            return NodeExecutionResult::failed(
                'dbDelete requires hospital scope for this entity.'
            );
        }

        try {
            $query = DB::table($table)->where($entity['idColumn'], $recordId);

            if ($entity['hospitalColumn'] !== null) {
                $query->where($entity['hospitalColumn'], $hospitalId);
            } else {
                // patient / persons: must be linked to a booking in the workflow hospital.
                if ($hospitalId === null) {
                    return NodeExecutionResult::failed(
                        'dbDelete requires hospital scope for patient records.'
                    );
                }

                if (! $this->patientBelongsToHospital((string) $recordId, (int) $hospitalId, $organizationId)) {
                    return NodeExecutionResult::failed(
                        'dbDelete refused: patient is not within workflow hospital/organization scope.'
                    );
                }
            }

            if (
                $entity['organizationViaHospital']
                && $organizationId !== null
                && $entity['hospitalColumn'] !== null
            ) {
                $allowedHospitalIds = $this->hospitalIdsForOrganization((int) $organizationId);
                if ($allowedHospitalIds === []) {
                    return NodeExecutionResult::failed(
                        'dbDelete refused: organization has no hospitals.'
                    );
                }

                if (! in_array((int) $hospitalId, $allowedHospitalIds, true)) {
                    return NodeExecutionResult::failed(
                        'dbDelete refused: workflow hospital is outside organization scope.'
                    );
                }

                $query->whereIn($entity['hospitalColumn'], $allowedHospitalIds);
            }

            $deleted = $query->delete();
        } catch (\Throwable $e) {
            Log::warning('dbDelete failed', [
                'error' => $e->getMessage(),
                'entity' => $entityKey,
                'record_id' => $recordId,
            ]);

            return NodeExecutionResult::failed($e->getMessage());
        }

        if ($deleted < 1) {
            return NodeExecutionResult::failed(
                'dbDelete target record was not found within hospital/organization scope.'
            );
        }

        if ($deleted > 1) {
            Log::error('dbDelete unexpectedly deleted multiple rows', [
                'entity' => $entityKey,
                'record_id' => $recordId,
                'deleted' => $deleted,
            ]);

            return NodeExecutionResult::failed(
                'dbDelete aborted: unexpected multi-row delete.'
            );
        }

        $context->setVariable('deleted_record_id', $recordId);
        $context->setVariable('deleted_entity', $entityKey);

        return NodeExecutionResult::continue();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function resolveEntityKey(array $data): ?string
    {
        $raw = strtolower(trim((string) ($data['entity'] ?? $data['table'] ?? '')));

        if ($raw === '') {
            return null;
        }

        return array_key_exists($raw, self::ENTITY_MAP) ? $raw : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $payload
     */
    protected function resolveRecordId(array $data, string $entityKey, array $payload): mixed
    {
        if (isset($data['id']) || isset($data['recordId']) || isset($data['record_id'])) {
            $raw = $data['id'] ?? $data['recordId'] ?? $data['record_id'];

            return $this->resolveScalar($raw, $payload);
        }

        $where = is_array($data['where'] ?? null) ? $data['where'] : [];
        if ($where !== []) {
            $idColumn = self::ENTITY_MAP[$entityKey]['idColumn'];
            if (array_key_exists($idColumn, $where)) {
                return $this->resolveScalar($where[$idColumn], $payload);
            }

            // Only id-based where is allowed — no arbitrary multi-column mass deletes.
            return null;
        }

        // Context fallback aligned to FE entity names.
        return match ($entityKey) {
            'appointment', 'doctor_bookings' => $payload['appointment_id']
                ?? data_get($payload, 'appointment.id'),
            'patient', 'persons' => $payload['patient_id']
                ?? data_get($payload, 'patient.id'),
            default => null,
        };
    }

    protected function resolveScalar(mixed $raw, array $payload): mixed
    {
        if (is_string($raw)) {
            $resolved = trim($this->variableResolver->resolve($raw, $payload));

            if ($resolved === '' || preg_match('/^\{\{\s*.+\s*\}\}$/', $resolved)) {
                return null;
            }

            return $resolved;
        }

        if (is_int($raw) || is_float($raw)) {
            return $raw;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function mergedPayload(WorkflowExecution $execution, WorkflowContext $context): array
    {
        return array_merge(
            is_array($execution->context) ? $execution->context : [],
            is_array($context->payload) ? $context->payload : [],
            ['variables' => $context->variables]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveHospitalId(WorkflowExecution $execution, array $payload): ?int
    {
        $workflow = $execution->workflow;
        $raw = $workflow?->hospital_id
            ?? $payload['hospital_id']
            ?? data_get($payload, 'hospital.id')
            ?? data_get($payload, 'appointment.hospital_id');

        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveOrganizationId(WorkflowExecution $execution, array $payload): ?int
    {
        $workflow = $execution->workflow;
        $raw = $workflow?->organization_id
            ?? $payload['organization_id']
            ?? data_get($payload, 'organization.id')
            ?? data_get($payload, 'hospital.organization_id');

        if ($raw === null || $raw === '') {
            return null;
        }

        return (int) $raw;
    }

    /**
     * @return list<int>
     */
    protected function hospitalIdsForOrganization(int $organizationId): array
    {
        if (! Schema::hasTable('hospitals')) {
            return [];
        }

        return DB::table('hospitals')
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function patientBelongsToHospital(string $patientId, int $hospitalId, ?int $organizationId): bool
    {
        if (! Schema::hasTable('doctor_bookings')) {
            return false;
        }

        $query = DB::table('doctor_bookings')
            ->where('patient_id', $patientId)
            ->where('hospital_id', $hospitalId);

        if ($organizationId !== null) {
            $allowedHospitalIds = $this->hospitalIdsForOrganization($organizationId);
            if ($allowedHospitalIds === [] || ! in_array($hospitalId, $allowedHospitalIds, true)) {
                return false;
            }

            $query->whereIn('hospital_id', $allowedHospitalIds);
        }

        return $query->exists();
    }
}
