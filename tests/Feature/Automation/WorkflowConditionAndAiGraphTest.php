<?php

namespace Tests\Feature\Automation;

use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Support\Facades\Http;
use Tests\Support\FrontendNodeCatalog;
use Tests\Support\WorkflowAutomationTestCase;

class WorkflowConditionAndAiGraphTest extends WorkflowAutomationTestCase
{
    public function test_condition_true_branch_reaches_true_end_only(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('c1', 'condition', [
                    'rules' => [
                        'operator' => 'AND',
                        'conditions' => [
                            [
                                'field' => 'gender',
                                'compare' => 'equals',
                                'value' => 'female',
                            ],
                        ],
                    ],
                ]),
                $this->node('true_end', 'end', ['label' => 'True End']),
                $this->node('false_end', 'end', ['label' => 'False End']),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'c1'),
                $this->edge('e2', 'c1', 'true_end', 'true'),
                $this->edge('e3', 'c1', 'false_end', 'false'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext(['patient' => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'gender' => 'female',
                'mobile' => '+919999999999',
            ]])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
    }

    public function test_condition_false_branch_reaches_false_end(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('c1', 'condition', [
                    'rules' => [
                        'operator' => 'AND',
                        'conditions' => [
                            [
                                'field' => 'gender',
                                'compare' => 'equals',
                                'value' => 'male',
                            ],
                        ],
                    ],
                ]),
                $this->node('true_end', 'end'),
                $this->node('false_end', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'c1'),
                $this->edge('e2', 'c1', 'true_end', 'true'),
                $this->edge('e3', 'c1', 'false_end', 'false'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext(['patient' => [
                'first_name' => 'Ada',
                'gender' => 'female',
                'mobile' => '+919999999999',
            ]])
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
    }

    public function test_ai_fe_node_uses_http_fake_and_reaches_end(): void
    {
        Http::fake([
            '*' => Http::response(['summary' => 'Patient summary text'], 200),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'ai', [
                'prompt' => 'Summarize {{patient_name}} at {{hospital_name}}',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $vars = $execution->fresh()->variables ?? [];
        $this->assertArrayHasKey('ai_summary', $vars);
    }

    public function test_send_ai_chat_fe_node_uses_http_fake_and_reaches_end(): void
    {
        config(['services.workflow_ai.endpoint' => 'https://ai.test/workflow']);

        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => 'AI chat for Ada'], 200),
        ]);

        $template = \App\Modules\Workflow\Models\WorkflowMessageTemplate::query()->create([
            'name' => 'AI Chat Template',
            'channel' => 'whatsapp',
            'locale' => 'en',
            'body' => 'Opener for {{patient_name}}',
            'variables' => [],
            'is_active' => true,
            'version_number' => 1,
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendAiChat', [
                'templateId' => $template->id,
                'prompt' => 'Help {{patient_name}}',
                'recipient' => 'patient',
                'temperature' => 0.7,
                'label' => 'Send AI Chat',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('whatsapp', $this->providerSends[0]['channel']);
        $this->assertSame('AI chat for Ada', $this->providerSends[0]['message']);
        $this->assertSame('AI chat for Ada', $execution->fresh()->variables['ai_chat_message'] ?? null);
    }

    public function test_send_ai_voice_fe_node_uses_http_fake_and_reaches_end(): void
    {
        config(['services.workflow_ai_voice.endpoint' => 'https://voice.test/call']);

        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => true,
                'message' => 'queued',
                'call_id' => 'voice-1',
            ], 200),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendAiVoice', [
                'prompt' => 'Hello {{patient_name}}, reminder for appointment {{appointment_id}}.',
                'templateId' => '',
                'recipient' => 'patient',
                'voiceProvider' => 'default',
                'language' => 'en',
                'gender' => 'neutral',
                'retryCount' => 1,
                'label' => 'Send AI Voice Call',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('voice-1', $execution->fresh()->variables['ai_voice_call_id'] ?? null);
        $this->assertNotEmpty($execution->fresh()->variables['ai_voice_script'] ?? null);

        Http::assertSent(fn ($request) => $request->url() === 'https://voice.test/call'
            && ($request['type'] ?? null) === 'ai_voice');
    }

    public function test_send_ai_voice_provider_failure_fails_workflow(): void
    {
        config(['services.workflow_ai_voice.endpoint' => 'https://voice.test/call']);

        Http::fake([
            'https://voice.test/call' => Http::response(['error' => 'down'], 503),
        ]);

        $version = $this->publishDefinition(
            $this->linearGraph('appointmentBooked', 'sendAiVoice', [
                'prompt' => 'Hello {{patient_name}}',
                'templateId' => '',
                'recipient' => 'patient',
                'voiceProvider' => 'default',
                'label' => 'Send AI Voice Call',
            ])
        );

        $execution = app(WorkflowExecutor::class)->start(
            $version,
            'appointmentBooked',
            $this->sampleAppointmentContext()
        );

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertSentCount(1);
    }

    /**
     * @dataProvider notImplementedActionProvider
     */
    public function test_not_implemented_actions_are_skipped_with_reason(string $frontendId, string $reason): void
    {
        $this->markTestSkipped($reason);

        $this->assertContains($frontendId, FrontendNodeCatalog::NOT_IMPLEMENTED_NO_EXECUTOR);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function notImplementedActionProvider(): array
    {
        return [
            ['sendIvr', 'sendIvr has no Laravel executor / NodeType'],
            ['updateAppointment', 'updateAppointment is FE stub only'],
            ['updatePrescription', 'updatePrescription is FE stub only'],
            ['updateMembership', 'updateMembership is FE stub only'],
            ['dbQuery', 'dbQuery has no Laravel executor'],
            ['httpRequest', 'httpRequest FE stub; webhook executor is a different backend-only ID'],
            ['start', 'start is FE-only and stripped before Laravel persistence'],
        ];
    }

    /**
     * @dataProvider notImplementedDomainTriggerProvider
     */
    public function test_not_implemented_domain_triggers_are_skipped(string $frontendId, string $reason): void
    {
        $this->markTestSkipped($reason);
        $this->assertContains($frontendId, FrontendNodeCatalog::NOT_IMPLEMENTED_DOMAIN);
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function notImplementedDomainTriggerProvider(): array
    {
        return [
            ['appointmentReminder', 'Domain dispatch incomplete (contract not_implemented)'],
            ['userPlanExpiry', 'Domain dispatch incomplete (contract not_implemented)'],
            ['familyPackageTierUpdated', 'Domain dispatch incomplete (contract not_implemented)'],
            ['webhookEvent', 'Domain dispatch incomplete (contract not_implemented)'],
            ['apiEvent', 'Domain dispatch incomplete (contract not_implemented)'],
        ];
    }
}
