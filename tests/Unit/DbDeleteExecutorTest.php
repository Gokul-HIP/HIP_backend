<?php

namespace Tests\Unit;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Executors\Actions\DbDeleteExecutor;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\NodeExecutorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class DbDeleteExecutorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        if (! Schema::hasColumn('workflows', 'organization_id')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->unsignedBigInteger('organization_id')->nullable();
            });
        }

        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('persons', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->timestamps();
        });

        DB::table('organizations')->insert([
            'id' => 1,
            'org_name' => 'Org One',
            'org_city' => 'City',
            'org_address' => 'Addr',
            'org_logo' => 'logo.png',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('organizations')->insert([
            'id' => 2,
            'org_name' => 'Org Two',
            'org_city' => 'City',
            'org_address' => 'Addr',
            'org_logo' => 'logo.png',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hospitals')->insert([
            ['id' => 10, 'organization_id' => 1, 'name' => 'Hospital A', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'organization_id' => 2, 'name' => 'Hospital B', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_registry_resolves_db_delete_frontend_node_type(): void
    {
        $registry = app(NodeExecutorRegistry::class);

        $this->assertTrue($registry->has('dbDelete'));
        $this->assertInstanceOf(DbDeleteExecutor::class, $registry->get('dbDelete'));
        $this->assertSame('dbDelete', $registry->get('dbDelete')->type());
    }

    public function test_valid_appointment_delete_continues_workflow(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'p-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment', 'id' => $bookingId]),
            hospitalId: 10,
            organizationId: 1,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
            'appointment_id' => $bookingId,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(0, DB::table('doctor_bookings')->where('id', $bookingId)->count());
        $this->assertSame($bookingId, $execution->fresh()->variables['deleted_record_id'] ?? null);
    }

    public function test_variable_resolution_for_record_id(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'p-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment', 'id' => '{{appointment_id}}']),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
            'appointment_id' => (string) $bookingId,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(0, DB::table('doctor_bookings')->where('id', $bookingId)->count());
    }

    public function test_missing_configuration_fails(): void
    {
        $version = $this->publishDefinition($this->graph(['label' => 'Delete Record']), hospitalId: 10);

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('entity', (string) $execution->fresh()->failure_reason);
    }

    public function test_invalid_entity_fails(): void
    {
        $version = $this->publishDefinition(
            $this->graph(['entity' => 'invoices', 'id' => 1]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('allowlisted', (string) $execution->fresh()->failure_reason);
    }

    public function test_missing_record_fails_without_silent_success(): void
    {
        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment', 'id' => 99999]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('not found', (string) $execution->fresh()->failure_reason);
    }

    public function test_cannot_delete_appointment_from_another_hospital(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 20,
            'patient_id' => 'p-1',
            'name' => 'Other Hospital Booking',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment', 'id' => $bookingId]),
            hospitalId: 10,
            organizationId: 1,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
            'appointment_id' => $bookingId,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame(1, DB::table('doctor_bookings')->where('id', $bookingId)->count());
    }

    public function test_cannot_delete_when_organization_scope_mismatches(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'p-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Workflow claims org 2 but hospital 10 belongs to org 1.
        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment', 'id' => $bookingId]),
            hospitalId: 10,
            organizationId: 2,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame(1, DB::table('doctor_bookings')->where('id', $bookingId)->count());
    }

    public function test_patient_delete_requires_hospital_linked_booking(): void
    {
        DB::table('persons')->insert([
            'id' => 'person-1',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_bookings')->insert([
            'hospital_id' => 10,
            'patient_id' => 'person-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'patient', 'id' => 'person-1']),
            hospitalId: 10,
            organizationId: 1,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
            'patient_id' => 'person-1',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(0, DB::table('persons')->where('id', 'person-1')->count());
    }

    public function test_patient_from_other_hospital_is_not_deleted(): void
    {
        DB::table('persons')->insert([
            'id' => 'person-2',
            'first_name' => 'Other',
            'last_name' => 'Patient',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_bookings')->insert([
            'hospital_id' => 20,
            'patient_id' => 'person-2',
            'name' => 'Other',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'patient', 'id' => 'person-2']),
            hospitalId: 10,
            organizationId: 1,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame(1, DB::table('persons')->where('id', 'person-2')->count());
    }

    public function test_no_duplicate_deletion_on_second_run(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'p-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $definition = $this->graph(['entity' => 'appointment', 'id' => $bookingId]);
        $version = $this->publishDefinition($definition, hospitalId: 10);

        $first = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $first->fresh()->status);

        $version2 = $this->publishDefinition($definition, hospitalId: 10);
        $second = app(WorkflowExecutor::class)->start($version2, 'appointmentBooked', [
            'hospital_id' => 10,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $second->fresh()->status);
        $this->assertStringContainsString('not found', (string) $second->fresh()->failure_reason);
    }

    public function test_context_appointment_id_fallback(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'p-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->graph(['entity' => 'appointment']),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'hospital_id' => 10,
            'appointment_id' => $bookingId,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(0, DB::table('doctor_bookings')->where('id', $bookingId)->count());
    }

    /**
     * @param  array<string, mixed>  $deleteData
     * @return array<string, mixed>
     */
    private function graph(array $deleteData): array
    {
        return [
            'builderVersion' => '1',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['nodeType' => 'appointmentBooked'],
                ],
                [
                    'id' => 'd1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 0],
                    'data' => array_merge(['nodeType' => 'dbDelete', 'label' => 'Delete Record'], $deleteData),
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'd1'],
                ['id' => 'e2', 'source' => 'd1', 'target' => 'e1'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function publishDefinition(
        array $definition,
        ?int $hospitalId = null,
        ?int $organizationId = null,
    ): WorkflowVersion {
        $workflow = Workflow::query()->create([
            'name' => 'DbDelete Test '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => 'appointmentBooked',
            'hospital_id' => $hospitalId,
            'organization_id' => $organizationId,
        ]);

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => 1,
            'definition' => $definition,
            'compiled_graph' => null,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $workflow->update(['current_version_id' => $version->id]);

        return $version->fresh();
    }
}
