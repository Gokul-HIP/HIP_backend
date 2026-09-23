<?php

namespace Tests\Unit;

use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\NodeProcessors\SendAiVoiceNodeProcessor;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\AiVoiceCallService;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class SendAiVoiceNodeProcessorTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{channel: string, message: string, recipient?: string|null, call_id?: string|null}> */
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

        config(['services.workflow_ai_voice.endpoint' => 'https://voice.test/call']);
        $this->providerSends = [];
        $this->bindNotificationMocks();
    }

    public function test_registry_resolves_send_ai_voice(): void
    {
        $registry = app(NodeProcessorRegistry::class);

        $this->assertTrue($registry->has('sendAiVoice'));
        $this->assertInstanceOf(SendAiVoiceNodeProcessor::class, $registry->get('sendAiVoice'));
        $this->assertSame('sendAiVoice', $registry->get('sendAiVoice')->type());
    }

    public function test_valid_prompt_only_executes_successfully(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => true,
                'message' => 'Call queued',
                'call_id' => 'call-123',
            ], 200),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello {{patient_name}}, your appointment {{appointment_id}} is confirmed.',
            'templateId' => '',
            'voiceProvider' => 'openai',
            'language' => 'en',
            'voice' => 'alloy',
            'gender' => 'female',
            'retryCount' => 2,
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertCount(1, $this->providerSends);
        $this->assertSame('ai_voice', $this->providerSends[0]['channel']);
        $this->assertSame('+919999999999', $this->providerSends[0]['recipient']);
        $this->assertSame(
            'Hello Ada Lovelace, your appointment 42 is confirmed.',
            $this->providerSends[0]['message']
        );
        $this->assertSame('call-123', $execution->fresh()->variables['ai_voice_call_id'] ?? null);
        $this->assertSame('openai', $execution->fresh()->variables['ai_voice_provider'] ?? null);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://voice.test/call'
                && $request['type'] === 'ai_voice'
                && $request['to'] === '+919999999999'
                && $request['voice_provider'] === 'openai'
                && $request['language'] === 'en'
                && $request['voice'] === 'alloy'
                && $request['gender'] === 'female'
                && (int) $request['retry_count'] === 2
                && str_contains((string) $request['script'], 'Ada Lovelace');
        });
    }

    public function test_valid_template_only_executes_successfully(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => true,
                'call_id' => 'tpl-call',
            ], 200),
        ]);

        $template = $this->createTemplate('Reminder for {{patient_name}} at {{appointment_time}}');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame(
            'Reminder for Ada Lovelace at 10:30 AM',
            $this->providerSends[0]['message']
        );
        $this->assertSame(
            'Reminder for Ada Lovelace at 10:30 AM',
            $execution->fresh()->variables['ai_voice_script'] ?? null
        );
    }

    public function test_missing_recipient_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'recipient' => '',
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
        Http::assertNothingSent();
    }

    public function test_missing_voice_provider_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'voiceProvider' => '',
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_missing_template_and_prompt_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => '',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
        Http::assertNothingSent();
    }

    public function test_inactive_template_fails(): void
    {
        Http::fake();
        $template = $this->createTemplate('Body', active: false);
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_missing_template_id_when_only_template_expected_fails_with_prompt_empty(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(99999, [
            'prompt' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_invalid_retry_count_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
            'retryCount' => 9,
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_custom_recipient_variable_resolution(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response(['success' => true, 'call_id' => 'c1'], 200),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Call script',
            'templateId' => '',
            'recipient' => 'custom',
            'customRecipient' => '{{patient_mobile}}',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('+919999999999', $this->providerSends[0]['recipient']);
    }

    public function test_missing_custom_recipient_fails(): void
    {
        Http::fake();
        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
            'recipient' => 'custom',
            'customRecipient' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_provider_api_failure_fails_workflow(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response(['error' => 'down'], 500),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello {{patient_name}}',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
    }

    public function test_provider_success_false_fails_workflow(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => false,
                'message' => 'rejected by carrier',
            ], 200),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        $this->assertSame([], $this->providerSends);
    }

    public function test_missing_voice_endpoint_fails_closed(): void
    {
        config(['services.workflow_ai_voice.endpoint' => null]);
        Http::fake();

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
        $this->assertSame([], $this->providerSends);
    }

    public function test_empty_template_body_fails(): void
    {
        Http::fake();
        $template = $this->createTemplate('   ');
        $version = $this->publishDefinition($this->graph($template->id, [
            'prompt' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
        Http::assertNothingSent();
        $this->assertSame([], $this->providerSends);
    }

    public function test_channel_manager_provider_failure_fails_execution(): void
    {
        $voice = Mockery::mock(AiVoiceCallService::class);
        $voice->shouldReceive('send')->once()->andReturn([
            'success' => false,
            'response' => 'provider down',
        ]);
        $this->app->instance(AiVoiceCallService::class, $voice);
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Hello',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Failed->value, $execution->fresh()->status);
    }

    public function test_start_send_ai_voice_end_completes(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => true,
                'message' => 'accepted',
                'call_id' => 'flow-1',
            ], 200),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Voice for appointment {{appointment_id}}',
            'templateId' => '',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('flow-1', $this->providerSends[0]['call_id'] ?? null);
    }

    public function test_executor_output_variables_on_success(): void
    {
        Http::fake([
            'https://voice.test/call' => Http::response([
                'success' => true,
                'call_id' => 'out-1',
            ], 200),
        ]);

        $version = $this->publishDefinition($this->graph(null, [
            'prompt' => 'Script text',
            'templateId' => '',
            'language' => 'hi',
            'voiceProvider' => 'azure',
        ]));

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', $this->context());
        $vars = $execution->fresh()->variables ?? [];

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $execution->fresh()->status);
        $this->assertSame('Script text', $vars['ai_voice_script'] ?? null);
        $this->assertSame('azure', $vars['ai_voice_provider'] ?? null);
        $this->assertSame('hi', $vars['ai_voice_language'] ?? null);
        $this->assertSame('out-1', $vars['ai_voice_call_id'] ?? null);
    }

    protected function bindNotificationMocks(): void
    {
        $ok = ['success' => true, 'response' => 'ok'];

        foreach ([
            WhatsAppNotificationService::class,
            SMSNotificationService::class,
            EmailNotificationService::class,
            PushNotificationService::class,
        ] as $class) {
            $mock = Mockery::mock($class);
            $mock->shouldReceive('send')->andReturn($ok)->byDefault();
            $this->app->instance($class, $mock);
        }

        // Use real AiVoiceCallService so Http::fake exercises the adapter,
        // but still record successful ChannelManager dispatches via a spy wrapper.
        $realVoice = new AiVoiceCallService;
        $voice = Mockery::mock(AiVoiceCallService::class)->makePartial();
        $voice->shouldReceive('send')->andReturnUsing(function (?string $phone, string $script, array $options = []) use ($realVoice) {
            $result = $realVoice->send($phone, $script, $options);
            if ($result['success'] ?? false) {
                $this->providerSends[] = [
                    'channel' => 'ai_voice',
                    'message' => $script,
                    'recipient' => $phone,
                    'call_id' => $result['call_id'] ?? null,
                ];
            }

            return $result;
        });

        $this->app->instance(AiVoiceCallService::class, $voice);
        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeProcessorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }

    protected function createTemplate(string $body, bool $active = true): WorkflowMessageTemplate
    {
        return WorkflowMessageTemplate::query()->create([
            'organization_id' => null,
            'name' => 'AI Voice Template '.uniqid(),
            'channel' => 'voice',
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
            'nodeType' => 'sendAiVoice',
            'category' => 'messaging',
            'label' => 'Send AI Voice Call',
            'templateId' => $templateId,
            'prompt' => 'You are calling {{patient_name}}.',
            'recipient' => 'patient',
            'voiceProvider' => 'default',
            'language' => 'en',
            'voice' => '',
            'gender' => 'neutral',
            'retryCount' => 1,
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
            'name' => 'SendAiVoice Test '.uniqid(),
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
