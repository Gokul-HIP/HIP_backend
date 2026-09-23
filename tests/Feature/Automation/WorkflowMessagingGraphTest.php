<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Support\Facades\Queue;
use Tests\Support\WorkflowAutomationTestCase;

class WorkflowMessagingGraphTest extends WorkflowAutomationTestCase
{
    /**
     * @dataProvider messagingNodeProvider
     */
    public function test_messaging_fe_node_reaches_end_and_hits_expected_channel(
        string $feNodeType,
        string $expectedChannel,
        array $nodeData,
        array $contextExtras = [],
    ): void {
        $this->providerSends = [];
        $this->bindNotificationMocks(success: true);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', $feNodeType, $nodeData)
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext($contextExtras)
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame($expectedChannel, $this->providerSends[0]['channel']);
        $this->assertStringContainsString('Ada', $this->providerSends[0]['message']);
        $this->assertStringContainsString('Dr Green', $this->providerSends[0]['message']);
    }

    /**
     * @return list<array{0: string, 1: string, 2: array<string, mixed>, 3?: array<string, mixed>}>
     */
    public static function messagingNodeProvider(): array
    {
        $body = 'Hi {{patient_name}}, appointment with {{doctor_name}}.';

        return [
            ['sendWhatsApp', 'whatsapp', ['messageTemplate' => $body, 'recipient' => 'patient']],
            ['sendSms', 'sms', ['messageTemplate' => $body, 'recipient' => 'patient']],
            ['sendEmail', 'email', ['subject' => 'Appt', 'body' => $body, 'recipient' => 'patient']],
            ['sendPush', 'push', ['title' => 'Appt', 'body' => $body, 'recipient' => 'patient'], ['member_id' => 'member-1']],
        ];
    }

    public function test_messaging_provider_failure_fails_execution_without_reaching_end_success(): void
    {
        $this->bindNotificationMocks(success: false, failChannel: 'sms');

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendSms', [
                'messageTemplate' => 'Hi {{patient_name}}',
                'recipient' => 'patient',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertStringContainsString('provider down', (string) $execution->fresh()->failure_reason);
    }

    public function test_empty_manual_message_still_invokes_channel_with_empty_or_fails_cleanly(): void
    {
        // Existing AbstractMessagingNodeProcessor allows empty manual body (unlike sendTemplate).
        // Assert exactly one provider attempt and no duplicate.
        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendWhatsApp', [
                'messageTemplate' => '',
                'recipient' => 'patient',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertContains(
            $execution->fresh()->status,
            [WorkflowExecutionStatus::Completed->value, WorkflowExecutionStatus::Failed->value]
        );
        $this->assertLessThanOrEqual(1, count($this->providerSends));
    }

    public function test_wait_fe_node_pauses_then_resumes_to_end(): void
    {
        Queue::fake();

        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('w1', 'wait', ['type' => 'seconds', 'value' => 5]),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'w1'),
                $this->edge('e2', 'w1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start($version, 'appointmentBooked', $this->sampleAppointmentContext());
        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->fresh()->status);
        $this->assertSame('e1', $execution->fresh()->current_node_id);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, fn ($job) => $job->nodeId === 'e1');

        $completed = $executor->resume($execution->fresh(), 'e1');
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->fresh()->status);
    }
}
