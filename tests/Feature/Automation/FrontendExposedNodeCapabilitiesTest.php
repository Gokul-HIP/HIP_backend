<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

/**
 * Step 4.1: newly executable frontend-exposed nodes on generic graphs
 * (not the 13 named campaign workflows).
 */
class FrontendExposedNodeCapabilitiesTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureDomainTables();
    }

    public function test_trigger_db_query_condition_sms_end(): void
    {
        $patientId = DB::table('persons')->insertGetId([
            'first_name' => 'Riya',
            'last_name' => 'Sharma',
        ]);

        DB::table('doctor_bookings')->insert([
            'id' => 9001,
            'hospital_id' => 10,
            'patient_id' => (string) $patientId,
            'status' => 'confirmed',
        ]);

        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('q1', 'dbQuery', [
                    'query' => 'patient.id == context.patient.id',
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'query_matched == true',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'query-hit',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'q1'),
                $this->edge('e2', 'q1', 'c1'),
                $this->edge('e3', 'c1', 'sms1', 'true'),
                $this->edge('e4', 'c1', 'e2', 'false'),
                $this->edge('e5', 'sms1', 'e1'),
            ],
        ];

        $compiled = app(WorkflowCompilerInterface::class)->compile($definition);
        $this->assertSame('dbQuery', $compiled->graph->nodes['q1']->nodeType);
        $this->assertTrue(app(NodeProcessorRegistry::class)->has('dbQuery'));

        $version = $this->publishDefinition($definition, hospitalId: 10);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'patient' => ['id' => $patientId, 'first_name' => 'Riya', 'last_name' => 'Sharma', 'mobile' => '9999999999'],
                'patient_id' => $patientId,
                'hospital_id' => 10,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertTrue((bool) data_get($execution->fresh()->variables, 'query_matched'));
        $this->assertSame('query-hit', $this->providerSends[0]['message'] ?? null);
    }

    public function test_db_query_rejects_sql_strings(): void
    {
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'dbQuery', [
                'query' => 'SELECT * FROM persons WHERE id = 1',
            ]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('does not accept SQL', (string) $execution->fresh()->failure_reason);
    }

    public function test_trigger_http_request_end(): void
    {
        Http::fake([
            'https://example.com/api' => Http::response(['ok' => true], 200),
        ]);

        $definition = $this->linearGraph('appointmentBooked', 'httpRequest', [
            'url' => 'https://example.com/api',
        ]);

        $compiled = app(WorkflowCompilerInterface::class)->compile($definition);
        $this->assertSame('webhook', $compiled->graph->nodes['a1']->nodeType);

        $validation = app(WorkflowPublishService::class)->validate($definition);
        $this->assertTrue($validation['valid'], json_encode($validation['errors']));

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(200, $execution->fresh()->variables['http_status'] ?? null);
        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/api');
    }

    public function test_webhook_node_still_posts_and_completes(): void
    {
        Http::fake([
            'https://hooks.example.test/run' => Http::response('ok', 200),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'webhook', [
                'url' => 'https://hooks.example.test/run',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_trigger_update_appointment_condition_sms_end(): void
    {
        $bookingId = DB::table('doctor_bookings')->insertGetId([
            'hospital_id' => 10,
            'patient_id' => 'patient-1',
            'status' => 'pending',
        ]);

        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('u1', 'updateAppointment', [
                    'values' => ['status' => 'confirmed'],
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'updated_count > 0',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'appt-updated',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'u1'),
                $this->edge('e2', 'u1', 'c1'),
                $this->edge('e3', 'c1', 'sms1', 'true'),
                $this->edge('e4', 'c1', 'e2', 'false'),
                $this->edge('e5', 'sms1', 'e1'),
            ],
        ];

        $compiled = app(WorkflowCompilerInterface::class)->compile($definition);
        $this->assertSame('databaseUpdate', $compiled->graph->nodes['u1']->nodeType);

        $version = $this->publishDefinition($definition, hospitalId: 10);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'appointment_id' => $bookingId,
                'hospital_id' => 10,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('confirmed', DB::table('doctor_bookings')->where('id', $bookingId)->value('status'));
        $this->assertSame('appt-updated', $this->providerSends[0]['message'] ?? null);
    }

    public function test_trigger_update_prescription_end(): void
    {
        $prescriptionId = DB::table('prescriptions')->insertGetId([
            'hospital_id' => 10,
            'status' => 'draft',
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'updatePrescription', [
                'values' => ['status' => 'sent'],
            ]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'prescription_id' => $prescriptionId,
                'hospital_id' => 10,
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('sent', DB::table('prescriptions')->where('id', $prescriptionId)->value('status'));
        $this->assertSame($prescriptionId, $execution->fresh()->variables['updated_record_id'] ?? null);
    }

    public function test_trigger_update_membership_end(): void
    {
        $membershipId = DB::table('user_family_subscriptions')->insertGetId([
            'hip_user_id' => 'member-1',
            'status' => 'active',
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'updateMembership', [
                'values' => ['status' => 'expired'],
            ]),
            hospitalId: 10,
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'membership_id' => $membershipId,
                'member_id' => 'member-1',
            ])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('expired', DB::table('user_family_subscriptions')->where('id', $membershipId)->value('status'));
    }

    public function test_send_ivr_cannot_be_published(): void
    {
        $definition = $this->linearGraph('appointmentBooked', 'sendIvr', [
            'templateId' => 'tpl_example',
            'recipient' => 'patient',
        ]);

        $validation = app(WorkflowPublishService::class)->validate($definition);

        $this->assertFalse($validation['valid']);
        $this->assertSame('unsupported_node', $validation['errors'][0]['code'] ?? null);
        $this->assertFalse(app(NodeProcessorRegistry::class)->has('sendIvr'));
    }

    public function test_send_ivr_is_not_executable_even_if_version_row_exists(): void
    {
        $definition = $this->linearGraph('appointmentBooked', 'sendIvr', [
            'templateId' => 'tpl_example',
            'recipient' => 'patient',
        ]);

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertStringContainsString('sendIvr', (string) $execution->fresh()->failure_reason);
    }

    protected function ensureDomainTables(): void
    {
        if (! Schema::hasTable('hospitals')) {
            Schema::create('hospitals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('persons')) {
            Schema::create('persons', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('doctor_bookings')) {
            Schema::create('doctor_bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('patient_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('prescriptions')) {
            Schema::create('prescriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_family_subscriptions')) {
            Schema::create('user_family_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->string('hip_user_id')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('organizations') && DB::table('organizations')->where('id', 1)->doesntExist()) {
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

        if (DB::table('hospitals')->where('id', 10)->doesntExist()) {
            DB::table('hospitals')->insert([
                'id' => 10,
                'organization_id' => 1,
                'name' => 'Hospital A',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
