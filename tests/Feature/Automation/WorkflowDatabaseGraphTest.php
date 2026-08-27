<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

class WorkflowDatabaseGraphTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('automation_test_records', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

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

        if (DB::table('organizations')->where('id', 1)->doesntExist()) {
            DB::table('organizations')->insert([
                'id' => 1,
                'org_name' => 'Org',
                'org_city' => 'City',
                'org_address' => 'Addr',
                'org_logo' => 'logo.png',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('hospitals')->insert([
            'id' => 10,
            'organization_id' => 1,
            'name' => 'Hospital A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_db_create_fe_node_inserts_row_and_reaches_end(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbCreate', [
                'table' => 'automation_test_records',
                'values' => ['name' => 'created-from-workflow', 'status' => 'new'],
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(1, DB::table('automation_test_records')->where('name', 'created-from-workflow')->count());
        $this->assertNotEmpty($execution->fresh()->variables['created_record_id'] ?? null);
    }

    public function test_db_update_fe_node_updates_row_and_reaches_end(): void
    {
        $id = DB::table('automation_test_records')->insertGetId([
            'name' => 'before',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbUpdate', [
                'table' => 'automation_test_records',
                'where' => ['id' => $id],
                'values' => ['status' => 'closed', 'name' => 'after'],
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $row = DB::table('automation_test_records')->where('id', $id)->first();
        $this->assertSame('closed', $row->status);
        $this->assertSame('after', $row->name);
    }

    public function test_db_delete_fe_node_deletes_appointment_and_reaches_end(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'patient-1',
            'name' => 'Ada',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbDelete', [
                'entity' => 'appointment',
                'recordId' => '{{appointment_id}}',
            ]),
            hospitalId: 10,
            organizationId: 1,
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'appointment_id' => (string) $bookingId,
                'hospital_id' => 10,
                'organization_id' => 1,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(0, DB::table('doctor_bookings')->where('id', $bookingId)->count());
    }

    public function test_db_create_missing_table_fails(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbCreate', [
                'table' => '',
                'values' => ['name' => 'x'],
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
    }

    public function test_db_delete_rejects_arbitrary_entity(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbDelete', [
                'entity' => 'invoices',
                'recordId' => '1',
            ]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext(['hospital_id' => 10])
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('allowlisted', (string) $execution->fresh()->failure_reason);
    }
}
