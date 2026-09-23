<?php

namespace Tests\Feature\Automation;

use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Support\Facades\Queue;
use Tests\Support\WorkflowAutomationTestCase;

class SuppressOnAppointmentTest extends WorkflowAutomationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_waiting_execution_cancelled_when_definition_sets_suppress_on_appointment(): void
    {
        $waiting = $this->startWaitingExecution(suppress: true, patientId: 'pat-1', hospitalId: 10);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'patient_id' => 'pat-1',
            'hospital_id' => 10,
            'appointment_id' => 99,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Cancelled->value, $waiting->fresh()->status);
    }

    public function test_waiting_execution_remains_when_suppress_flag_is_false(): void
    {
        $waiting = $this->startWaitingExecution(suppress: false, patientId: 'pat-1', hospitalId: 10);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'patient_id' => 'pat-1',
            'hospital_id' => 10,
            'appointment_id' => 99,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $waiting->fresh()->status);
    }

    public function test_another_patient_waiting_execution_is_unaffected(): void
    {
        $other = $this->startWaitingExecution(suppress: true, patientId: 'pat-other', hospitalId: 10);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'patient_id' => 'pat-1',
            'hospital_id' => 10,
            'appointment_id' => 99,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $other->fresh()->status);
    }

    public function test_another_hospital_waiting_execution_is_unaffected(): void
    {
        $otherHospital = $this->startWaitingExecution(suppress: true, patientId: 'pat-1', hospitalId: 22);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'patient_id' => 'pat-1',
            'hospital_id' => 10,
            'appointment_id' => 99,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $otherHospital->fresh()->status);
    }

    public function test_delayed_job_does_not_resume_cancelled_execution(): void
    {
        $waiting = $this->startWaitingExecution(suppress: true, patientId: 'pat-1', hospitalId: 10);

        app(AutomationEngine::class)->handle('appointmentBooked', [
            'patient_id' => 'pat-1',
            'hospital_id' => 10,
            'appointment_id' => 99,
        ]);

        $this->assertSame(WorkflowExecutionStatus::Cancelled->value, $waiting->fresh()->status);

        $job = new ContinueWorkflowExecutionJob($waiting->id, (string) $waiting->current_node_id);
        $job->handle(app(WorkflowExecutor::class));

        $this->assertSame(WorkflowExecutionStatus::Cancelled->value, $waiting->fresh()->status);
        $this->assertSame([], $this->providerSends);
    }

    private function startWaitingExecution(bool $suppress, string $patientId, int $hospitalId): WorkflowExecution
    {
        $definition = [
            'builderVersion' => '1',
            'suppressOnAppointment' => $suppress,
            'nodes' => [
                $this->node('t1', 'patientRegistered'),
                $this->node('w1', 'wait', [
                    'waitType' => 'duration',
                    'amount' => 2,
                    'unit' => 'days',
                ]),
                $this->node('sms1', 'sendSms', [
                    'recipient' => 'patient',
                    'message' => 'should-not-send-if-cancelled',
                ]),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'w1'),
                $this->edge('e2', 'w1', 'sms1'),
                $this->edge('e3', 'sms1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition, 'patientRegistered');

        return app(WorkflowExecutor::class)->start($version, 'patientRegistered', [
            'patient_id' => $patientId,
            'hospital_id' => $hospitalId,
            'patient_mobile' => '9000000000',
            'patient' => ['first_name' => 'Wait', 'mobile' => '9000000000'],
        ]);
    }
}
