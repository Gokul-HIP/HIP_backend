<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Executors\Actions\SendAiChatExecutor;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\NodeExecutorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SendAiChatExecutorTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{channel: string, message: string, recipient?: string|null, subject?: string|null}> */
    private array $providerSends = [];

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
                'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
                'database/migrations/2026_07_21_100004_create_workflow_templates_table.php',
                'database/migrations/2026_07_21_140000_add_category_and_version_to_workflow_templates_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
                'database/migrations/2026_07_29_100000_rename_workflow_templates_to_workflow_message_templates.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.workflow_ai.endpoint' => 'https://ai.test/workflow']);
        $this->providerSends = [];
        $this->bindNotificationMocks();
    }

    public function test_registry_resolves_send_ai_chat(): void
    {
        $registry = app(NodeExecutorRegistry::class);

        $this->assertTrue($registry->has('sendAiChat'));
        $this->assertInstanceOf(SendAiChatExecutor::class, $registry->get('sendAiChat'));
        $this->assertSame('sendAiChat', $registry->get('sendAiChat')->type());
    }

    public function test_valid_send_ai_chat_executes_successfully(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response([
                'message' => 'Hello Ada, your chat is ready.',
            ], 200),
        ]);

        $template = $this->createTemplate('Chat opener for {{patient_name}} about {{appointment_id}}');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'You are helping {{patient_name}}.',
            'temperature' => 0.5,
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('whatsapp', $this->providerSends[0]['channel']);
        $this->assertSame('Hello Ada, your chat is ready.', $this->providerSends[0]['message']);
        // ChannelManager resolves logical "patient" before calling the provider.
        $this->assertSame('+919999999999', $this->providerSends[0]['recipient']);
        $this->assertSame(
            'Hello Ada, your chat is ready.',
            $execution->fresh()->variables['ai_chat_message'] ?? null
        );

        Http::assertSent(function ($request) {
            return $request->url() === 'https://ai.test/workflow'
                && $request['prompt'] === 'You are helping Ada Lovelace.'
                && $request['template'] === 'Chat opener for Ada Lovelace about 42'
                && $request['type'] === 'ai_chat'
                && (float) $request['temperature'] === 0.5;
        });
    }

    public function test_default_temperature_is_zero_point_seven(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => 'ok'], 200),
        ]);

        $template = $this->createTemplate('Body {{patient_name}}');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Assist {{patient_name}}',
            // temperature omitted
        ]));

        app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        Http::assertSent(fn ($request) => (float) $request['temperature'] === 0.7);
    }

    public function test_invalid_temperature_fails(): void
    {
        Http::fake();

        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
            'temperature' => 3.5,
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
        Http::assertNothingSent();
    }

    public function test_missing_prompt_fails(): void
    {
        Http::fake();
        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
        Http::assertNothingSent();
    }

    public function test_missing_template_id_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
        Http::assertNothingSent();
    }

    public function test_invalid_template_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(99999, [
            'prompt' => 'Hello',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_inactive_template_fails(): void
    {
        Http::fake();
        $template = $this->createTemplate('Body', active: false);
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_ai_provider_failure_fails_workflow(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['error' => 'down'], 500),
        ]);

        $template = $this->createTemplate('Body {{patient_name}}');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello {{patient_name}}',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
    }

    public function test_empty_ai_message_is_not_sent(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => '   '], 200),
        ]);

        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
    }

    public function test_missing_ai_endpoint_fails(): void
    {
        config(['services.workflow_ai.endpoint' => null]);
        Http::fake();

        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
        $this->assertSame([], $this->providerSends);
    }

    public function test_no_duplicate_send(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => 'Once'], 200),
        ]);

        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
        ]));

        app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertCount(1, $this->providerSends);
        Http::assertSentCount(1);
    }

    public function test_appointment_booked_to_send_ai_chat_to_end_completes(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['summary' => 'Chat ready'], 200),
        ]);

        $template = $this->createTemplate('Template {{appointment_date}} {{appointment_time}}');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Help with appointment {{appointment_id}}',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('Chat ready', $this->providerSends[0]['message']);
    }

    public function test_channel_delivery_failure_fails_execution(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => 'Ready'], 200),
        ]);

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')->once()->andReturn([
            'success' => false,
            'response' => 'provider down',
        ]);
        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeExecutorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        $template = $this->createTemplate('Body');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
    }

    public function test_email_template_channel_uses_channel_manager(): void
    {
        Http::fake([
            'https://ai.test/workflow' => Http::response(['message' => 'Email chat'], 200),
        ]);

        $template = $this->createTemplate('Email body', channel: 'email');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => 'Hello {{patient_name}}',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('email', $this->providerSends[0]['channel']);
        $this->assertSame('ada@example.com', $this->providerSends[0]['recipient']);
        $this->assertSame('Email chat', $this->providerSends[0]['message']);
    }

    protected function bindNotificationMocks(): void
    {
        $ok = ['success' => true, 'response' => 'ok'];

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) use ($ok) {
            $this->providerSends[] = [
                'channel' => 'whatsapp',
                'message' => $message,
                'recipient' => $mobile,
            ];

            return $ok;
        });

        $sms = Mockery::mock(SMSNotificationService::class);
        $sms->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) use ($ok) {
            $this->providerSends[] = [
                'channel' => 'sms',
                'message' => $message,
                'recipient' => $mobile,
            ];

            return $ok;
        });

        $email = Mockery::mock(EmailNotificationService::class);
        $email->shouldReceive('send')->andReturnUsing(function (?string $to, string $subject, string $message) use ($ok) {
            $this->providerSends[] = [
                'channel' => 'email',
                'message' => $message,
                'subject' => $subject,
                'recipient' => $to,
            ];

            return $ok;
        });

        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('send')->andReturnUsing(function ($memberId, string $title, string $message) use ($ok) {
            $this->providerSends[] = [
                'channel' => 'push',
                'message' => $message,
                'subject' => $title,
                'recipient' => is_scalar($memberId) ? (string) $memberId : null,
            ];

            return $ok;
        });

        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->instance(SMSNotificationService::class, $sms);
        $this->app->instance(EmailNotificationService::class, $email);
        $this->app->instance(PushNotificationService::class, $push);

        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeExecutorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }

    protected function createTemplate(string $body, string $channel = 'whatsapp', bool $active = true): WorkflowMessageTemplate
    {
        return WorkflowMessageTemplate::query()->create([
            'organization_id' => null,
            'name' => 'AI Chat Template '.uniqid(),
            'channel' => $channel,
            'category' => 'general',
            'locale' => 'en',
            'body' => $body,
            'variables' => [],
            'is_active' => $active,
            'version_number' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function graph(?int $templateId, array $extra = []): array
    {
        $data = array_merge([
            'nodeType' => 'sendAiChat',
            'category' => 'messaging',
            'label' => 'Send AI Chat',
            'templateId' => $templateId,
            'prompt' => 'You are a helpful hospital care assistant.',
            'recipient' => 'patient',
            'temperature' => 0.7,
        ], $extra);

        if (array_key_exists('templateId', $extra) && $extra['templateId'] === '') {
            $data['templateId'] = '';
        } elseif ($templateId !== null) {
            $data['templateId'] = $templateId;
        }

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
                    'id' => 'a1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => $data,
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 400, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'a1'],
                ['id' => 'e2', 'source' => 'a1', 'target' => 'e1'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function publishDefinition(array $definition): WorkflowVersion
    {
        $workflow = Workflow::query()->create([
            'name' => 'SendAiChat Test '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => 'appointmentBooked',
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

    /**
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        return [
            'patient' => [
                'id' => 'patient-1',
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'mobile' => '+919999999999',
                'email' => 'ada@example.com',
            ],
            'doctor' => ['id' => 'doctor-1', 'name' => 'Dr Green'],
            'hospital' => ['id' => 10, 'name' => 'Sunrise Hospital'],
            'appointment_id' => 42,
            'appointment_date' => '01 Sep 2026',
            'appointment_time' => '10:30 AM',
            'patient_id' => 'patient-1',
            'patient_mobile' => '+919999999999',
            'patient_email' => 'ada@example.com',
            'member_id' => 'member-1',
            'hospital_id' => 10,
        ];
    }
}
