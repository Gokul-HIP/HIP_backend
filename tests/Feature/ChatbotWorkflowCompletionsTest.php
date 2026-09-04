<?php

namespace Tests\Feature;

use App\Modules\HospitalAutomation\Services\ChatbotWorkflowService;
use App\Modules\MedicineReminder\Notifications\EmailNotificationService;
use App\Modules\MedicineReminder\Notifications\PushNotificationService;
use App\Modules\MedicineReminder\Notifications\SMSNotificationService;
use App\Modules\MedicineReminder\Notifications\WhatsAppNotificationService;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\ChannelManager;
use App\Modules\Workflow\Services\Runtime\NodeExecutorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * /chat/completions → onChatMessage workflow → SendAiChatExecutor (mocked AI).
 * Does not call real OpenRouter (Http::fake). sendAiChat no longer uses WORKFLOW_AI_ENDPOINT.
 */
class ChatbotWorkflowCompletionsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{channel: string, message: string}> */
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

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        config([
            'services.openrouter.api_key' => 'test-openrouter-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.default_model' => 'openai/gpt-4o-mini',
            'services.openrouter.fallback_model' => 'openrouter/auto',
            'services.workflow_ai.endpoint' => null,
        ]);
        $this->providerSends = [];
        $this->bindNotificationMocks();
    }

    /**
     * @return array<string, mixed>
     */
    protected function openRouterSuccessResponse(string $content): array
    {
        return [
            'id' => 'gen-test',
            'model' => 'openai/gpt-4o-mini',
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => $content]],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
                'total_tokens' => 15,
            ],
        ];
    }

    protected function fakeOpenRouterSuccess(string $content): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                $this->openRouterSuccessResponse($content),
                200
            ),
        ]);
    }

    public function test_chat_completions_discovers_on_chat_message_workflow_and_returns_reply(): void
    {
        // Prove chatbot path uses OpenRouter only — WORKFLOW_AI_ENDPOINT must not be required.
        config([
            'services.workflow_ai.endpoint' => null,
            'services.openrouter.api_key' => 'test-openrouter-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.default_model' => 'openai/gpt-4o-mini',
            'services.openrouter.fallback_model' => 'openrouter/auto',
        ]);

        $openRouterContent = 'Hypertension is high blood pressure.';
        $this->fakeOpenRouterSuccess($openRouterContent);

        $template = $this->createTemplate('Context: {{chat_message}}', channel: 'ai');
        $this->publishChatWorkflow($template->id, [
            'prompt' => 'Answer the user about: {{chat_message}}',
        ]);

        $response = $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'What is hypertension?'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply', $openRouterContent)
            ->assertJsonPath('data.stream', false)
            ->assertJsonPath('data.trigger_type', 'messageReceived');

        $this->assertNotNull($response->json('data.workflow_id'));
        $this->assertNotNull($response->json('data.execution_id'));
        // Response-only chatbot path must not send outbound notifications.
        $this->assertSame([], $this->providerSends);

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $user = collect($messages)->firstWhere('role', 'user')['content'] ?? '';
            $authHeader = $request->header('Authorization');
            $auth = is_array($authHeader) ? (string) ($authHeader[0] ?? '') : (string) $authHeader;

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request->method() === 'POST'
                && str_starts_with($auth, 'Bearer test-openrouter-key')
                && ($request['model'] ?? null) === 'openai/gpt-4o-mini'
                && is_array($request['messages'] ?? null)
                && $user === 'What is hypertension?'
                && (float) ($request['temperature'] ?? -1) === 0.7
                && ($request['stream'] ?? null) === false;
        });
        Http::assertSentCount(1);

        $this->assertSame('ai', $template->fresh()->channel);
    }

    public function test_chatbot_reaches_openrouter_and_maps_choices_content_to_data_reply(): void
    {
        config(['services.workflow_ai.endpoint' => null]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'id' => 'gen-map-test',
                'model' => 'openai/gpt-4o-mini',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Mapped from choices[0].message.content',
                        ],
                    ],
                ],
                'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2],
            ], 200),
        ]);

        $template = $this->createTemplate('You are a care assistant.', channel: 'ai');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Ping'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply', 'Mapped from choices[0].message.content');

        Http::assertSent(fn ($request) => $request->url() === 'https://openrouter.ai/api/v1/chat/completions');
        $this->assertSame([], $this->providerSends);
    }

    public function test_frontend_does_not_need_workflow_id(): void
    {
        $this->fakeOpenRouterSuccess('ok');

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
            // intentionally no workflow_id
        ])->assertOk()->assertJsonPath('success', true);
    }

    public function test_automation_test_workflows_are_excluded(): void
    {
        Http::fake();

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id, [], [
            'source_type' => 'automation_test',
        ]);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
        $this->assertSame([], $this->providerSends);
    }

    public function test_inactive_workflow_is_not_selected(): void
    {
        Http::fake();

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id, [], [
            'status' => WorkflowStatus::Inactive->value,
        ]);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_no_workflow_found_returns_error(): void
    {
        Http::fake();

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['success' => false]);

        Http::assertNothingSent();
    }

    public function test_ai_provider_failure_returns_error_without_fake_reply(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(['error' => 'down'], 500),
        ]);

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertStatus(500)
            ->assertJsonPath('success', false);

        $this->assertSame([], $this->providerSends);
    }

    public function test_empty_ai_response_returns_error(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                $this->openRouterSuccessResponse('   '),
                200
            ),
        ]);

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertStatus(500)
            ->assertJsonPath('success', false);

        $this->assertSame([], $this->providerSends);
    }

    public function test_missing_template_fails_workflow(): void
    {
        Http::fake();

        $this->publishChatWorkflow(null, [
            'templateId' => '',
            'prompt' => 'Hello {{chat_message}}',
        ]);

        $this->postJson('/api/chat/completions', [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertStatus(500)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_session_history_is_passed_and_clear_session_works(): void
    {
        $this->fakeOpenRouterSuccess('Follow-up answer');

        $template = $this->createTemplate('History includes prior turns');
        $this->publishChatWorkflow($template->id, [
            'prompt' => 'Continue the conversation. Latest: {{chat_message}}',
        ]);

        $sessionId = 'sess-workflow-1';

        $this->postJson('/api/chat/completions', [
            'session_id' => $sessionId,
            'append_history' => true,
            'messages' => [
                ['role' => 'user', 'content' => 'First question'],
            ],
        ])->assertOk();

        $cached = Cache::get('chat_session:'.$sessionId);
        $this->assertIsArray($cached);
        $this->assertSame('assistant', $cached[array_key_last($cached)]['role'] ?? null);

        $this->postJson('/api/chat/completions', [
            'session_id' => $sessionId,
            'append_history' => true,
            'messages' => [
                ['role' => 'user', 'content' => 'Second question'],
            ],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && is_array($messages)
                && collect($messages)->contains(fn ($m) => ($m['content'] ?? null) === 'First question')
                && collect($messages)->contains(fn ($m) => ($m['content'] ?? null) === 'Second question');
        });

        $this->postJson('/api/chat/session/clear', [
            'session_id' => $sessionId,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Cache::get('chat_session:'.$sessionId));
    }

    public function test_hospital_scoped_workflow_selection(): void
    {
        $this->fakeOpenRouterSuccess('Hospital 10 reply');

        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id, [], ['hospital_id' => 10]);

        // Wrong hospital → not found (exact hospital isolation, no null fallback when hospital provided)
        $this->postJson('/api/chat/completions', [
            'hospital_id' => 99,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])->assertNotFound();

        $this->postJson('/api/chat/completions', [
            'hospital_id' => 10,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.reply', 'Hospital 10 reply');
    }

    public function test_service_rejects_when_only_automation_test_workflows_exist(): void
    {
        $template = $this->createTemplate('{{chat_message}}');
        $this->publishChatWorkflow($template->id, [], [
            'source_type' => 'automation_test',
        ]);

        $this->expectException(RuntimeException::class);
        app(ChatbotWorkflowService::class)->resolveWorkflow(null, null);
    }

    protected function bindNotificationMocks(): void
    {
        $ok = ['success' => true, 'response' => 'ok'];

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('send')->andReturnUsing(function (?string $mobile, string $message) use ($ok) {
            $this->providerSends[] = ['channel' => 'whatsapp', 'message' => $message];

            return $ok;
        });

        $this->app->instance(WhatsAppNotificationService::class, $whatsApp);
        $this->app->instance(SMSNotificationService::class, Mockery::mock(SMSNotificationService::class));
        $this->app->instance(EmailNotificationService::class, Mockery::mock(EmailNotificationService::class));
        $this->app->instance(PushNotificationService::class, Mockery::mock(PushNotificationService::class));

        $this->app->forgetInstance(ChannelManager::class);
        $this->app->forgetInstance(NodeExecutorRegistry::class);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }

    protected function createTemplate(string $body, string $channel = 'ai'): WorkflowMessageTemplate
    {
        return WorkflowMessageTemplate::query()->create([
            'name' => 'Chatbot Template '.uniqid(),
            'channel' => $channel,
            'locale' => 'en',
            'body' => $body,
            'variables' => [],
            'is_active' => true,
            'version_number' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $nodeExtra
     * @param  array<string, mixed>  $workflowExtra
     */
    protected function publishChatWorkflow(?int $templateId, array $nodeExtra = [], array $workflowExtra = []): Workflow
    {
        $data = array_merge([
            'nodeType' => 'sendAiChat',
            'category' => 'messaging',
            'label' => 'Send AI Chat',
            'templateId' => $templateId,
            'prompt' => 'You are a helpful hospital care assistant. User asked: {{chat_message}}',
            'recipient' => 'patient',
            'temperature' => 0.7,
        ], $nodeExtra);

        if ($templateId === null) {
            $data['templateId'] = $nodeExtra['templateId'] ?? '';
        }

        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['nodeType' => 'onChatMessage'],
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

        $workflow = Workflow::query()->create(array_merge([
            'name' => 'Chatbot Workflow '.uniqid(),
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => 'messageReceived',
        ], $workflowExtra));

        // If status override is inactive, still attach a version so discovery filters by status.
        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => 1,
            'definition' => $definition,
            'compiled_graph' => null,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $workflow->update(['current_version_id' => $version->id]);

        return $workflow->fresh(['currentVersion']);
    }
}
