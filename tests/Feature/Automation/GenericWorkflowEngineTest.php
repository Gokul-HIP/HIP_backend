<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\WorkflowAutomationTestCase;

/**
 * NEW graphs composed from the existing node contract — not copies of the 13 reference campaigns.
 */
class GenericWorkflowEngineTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_graph_a_trigger_sms_end_uses_message_field_and_variables(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'patientRegistered'),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'Hello {{patient_name}} from {{hospital_name}}',
                    'templateId' => '',
                ]),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'sms1'),
                $this->edge('e2', 'sms1', 'e1'),
            ],
        ];

        $compiled = app(WorkflowCompilerInterface::class)->compile($definition);
        $this->assertSame('t1', $compiled->graph->startNodeId);

        $version = $this->publishDefinition($definition, 'patientRegistered');
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'patientRegistered',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertNotEmpty($this->providerSends);
        $this->assertSame('sms', $this->providerSends[0]['channel']);
        $this->assertStringContainsString('Ada Lovelace', $this->providerSends[0]['message']);
        $this->assertStringContainsString('Sunrise Hospital', $this->providerSends[0]['message']);
    }

    public function test_graph_b_wait_duration_then_condition_then_sms(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'patientRegistered'),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 2,
                    'unit' => 'hours',
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'appointment.exists == false',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'Still open',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'w1'),
                $this->edge('e2', 'w1', 'c1'),
                $this->edge('e3', 'c1', 'sms1', 'true'),
                $this->edge('e4', 'c1', 'e2', 'false'),
                $this->edge('e5', 'sms1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition, 'patientRegistered');
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start($version, 'patientRegistered', [
            'patient_id' => 'patient-b',
            'hospital_id' => 10,
            'patient_mobile' => '9999999999',
            'patient' => ['first_name' => 'Pat', 'last_name' => 'B', 'mobile' => '9999999999'],
            'hospital' => ['id' => 10, 'name' => 'Clinic'],
            'appointment' => ['exists' => false],
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        Queue::assertPushed(ContinueWorkflowExecutionJob::class);

        $completed = $executor->resume($execution->fresh(), $execution->current_node_id);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame('sms', $this->providerSends[0]['channel'] ?? null);
        $this->assertSame('Still open', $this->providerSends[0]['message'] ?? null);
    }

    public function test_graph_c_condition_true_sms_false_whatsapp(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('c1', 'condition', [
                    'expression' => 'patient.age >= 60',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'senior-sms',
                ]),
                $this->node('wa1', 'sendWhatsApp', [
                    'recipient' => 'patient',
                    'message' => 'under-60-wa',
                ]),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'c1'),
                $this->edge('e2', 'c1', 'sms1', 'true'),
                $this->edge('e3', 'c1', 'wa1', 'false'),
                $this->edge('e4', 'sms1', 'e1'),
                $this->edge('e5', 'wa1', 'e1'),
            ],
        ];

        $versionTrue = $this->publishDefinition($definition, 'appointmentBooked');
        $trueRun = app(WorkflowExecutor::class)->start(
            $versionTrue,
            'appointmentBooked',
            $this->sampleAppointmentContext(['patient' => [
                'id' => 'p-c1',
                'first_name' => 'Pat',
                'age' => 72,
                'mobile' => '9999999999',
            ]])
        );
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $trueRun->status);
        $this->assertSame('sms', $this->providerSends[0]['channel']);
        $this->assertSame('senior-sms', $this->providerSends[0]['message']);

        $this->providerSends = [];
        $versionFalse = $this->publishDefinition($definition, 'appointmentBooked');
        $falseRun = app(WorkflowExecutor::class)->start(
            $versionFalse,
            'appointmentBooked',
            $this->sampleAppointmentContext(['patient' => [
                'id' => 'p-c2',
                'first_name' => 'Pat',
                'age' => 22,
                'mobile' => '9999999999',
            ]])
        );
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $falseRun->status);
        $this->assertSame('whatsapp', $this->providerSends[0]['channel']);
        $this->assertSame('under-60-wa', $this->providerSends[0]['message']);
    }

    public function test_graph_d_wait_update_condition_sms(): void
    {
        Schema::create('generic_engine_records', function (Blueprint $table) {
            $table->id();
            $table->string('flag')->nullable();
        });
        DB::table('generic_engine_records')->insert(['id' => 1, 'flag' => 'pending']);

        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 1,
                    'unit' => 'seconds',
                ]),
                $this->node('u1', 'updateRecord', [
                    'table' => 'generic_engine_records',
                    'where' => ['id' => 1],
                    'values' => ['flag' => 'done'],
                ]),
                $this->node('c1', 'condition', [
                    'expression' => 'patient.age > 0',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'updated',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'w1'),
                $this->edge('e2', 'w1', 'u1'),
                $this->edge('e3', 'u1', 'c1'),
                $this->edge('e4', 'c1', 'sms1', 'true'),
                $this->edge('e5', 'c1', 'e2', 'false'),
                $this->edge('e6', 'sms1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $executor = app(WorkflowExecutor::class);
        $execution = $executor->start($version, 'appointmentBooked', $this->sampleAppointmentContext());
        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);

        $completed = $executor->resume($execution->fresh(), $execution->current_node_id);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame('done', DB::table('generic_engine_records')->where('id', 1)->value('flag'));
        $this->assertSame('updated', $this->providerSends[0]['message'] ?? null);
    }

    public function test_graph_e_condition_wait_condition_sms(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('c1', 'condition', [
                    'expression' => 'patient.gender == "female"',
                ]),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 1,
                    'unit' => 'minutes',
                ]),
                $this->node('c2', 'condition', [
                    'expression' => 'patient.age >= 18',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'adult-female',
                ]),
                $this->node('e1', 'end'),
                $this->node('e2', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'c1'),
                $this->edge('e2', 'c1', 'w1', 'true'),
                $this->edge('e3', 'c1', 'e2', 'false'),
                $this->edge('e4', 'w1', 'c2'),
                $this->edge('e5', 'c2', 'sms1', 'true'),
                $this->edge('e6', 'c2', 'e2', 'false'),
                $this->edge('e7', 'sms1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $executor = app(WorkflowExecutor::class);
        $execution = $executor->start($version, 'appointmentBooked', $this->sampleAppointmentContext([
            'patient' => [
                'first_name' => 'Ada',
                'gender' => 'female',
                'age' => 35,
                'mobile' => '9999999999',
            ],
        ]));

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $completed = $executor->resume($execution->fresh(), $execution->current_node_id);
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame('adult-female', $this->providerSends[0]['message'] ?? null);
    }

    public function test_push_uses_title_and_body_fields(): void
    {
        $definition = $this->linearGraph('appointmentBooked', 'sendPush', [
            'title' => 'Ping {{hospital_name}}',
            'body' => 'Hi {{patient_name}}',
        ]);

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->status);
        $this->assertSame('push', $this->providerSends[0]['channel']);
        $this->assertSame('Hi Ada Lovelace', $this->providerSends[0]['message']);
        $this->assertSame('Ping Sunrise Hospital', $this->providerSends[0]['subject']);
    }

    public function test_caregiver_recipient_uses_context_contact(): void
    {
        $definition = $this->linearGraph('appointmentBooked', 'sendWhatsApp', [
            'recipient' => 'caregiver',
            'message' => 'For {{patient_name}}',
        ]);

        $version = $this->publishDefinition($definition);
        app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext([
                'caregiver_contact' => '9888877777',
            ])
        );

        $this->assertSame('whatsapp', $this->providerSends[0]['channel']);
        $this->assertSame('9888877777', $this->providerSends[0]['recipient']);
        $this->assertStringContainsString('Ada Lovelace', $this->providerSends[0]['message']);
    }
}
