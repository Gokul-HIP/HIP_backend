<?php

namespace Tests\Feature;

use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\User;
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
use Laravel\Sanctum\Sanctum;
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
                'database/migrations/2026_09_04_120000_create_chatbot_sessions_and_messages_tables.php',
                'database/migrations/0001_01_01_000000_create_users_table.php',
            ],
        ];
    }

    private string $actingUserId = '11111111-2222-3333-4444-555555555555';

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
            'automation.debug_chat_memory' => true,
            'automation.debug_chat_payload' => false,
        ]);
        $this->providerSends = [];
        $this->bindNotificationMocks();
        $this->actingAsChatUser($this->actingUserId);
    }

    protected function actingAsChatUser(string $userId): User
    {
        $user = new class extends User
        {
            use \Laravel\Sanctum\HasApiTokens;

            public $incrementing = false;

            protected $keyType = 'string';
        };
        $user->forceFill([
            'id' => $userId,
            'name' => 'Chat Tester',
            'email' => $userId.'@example.com',
        ]);
        $user->exists = true;
        Sanctum::actingAs($user);

        return $user;
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
            ->assertJsonPath('data.model', 'openai/gpt-4o-mini')
            ->assertJsonPath('data.usage.prompt_tokens', 10)
            ->assertJsonPath('data.usage.completion_tokens', 5)
            ->assertJsonPath('data.usage.total_tokens', 15)
            ->assertJsonPath('data.fallback_used', false)
            ->assertJsonPath('data.stream', false)
            ->assertJsonPath('data.trigger_type', 'messageReceived');

        $this->assertNotNull($response->json('data.session_id'));
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
            'session_id' => 'inactive-sess-1',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello'],
            ],
        ])
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', ChatbotWorkflowService::NO_ACTIVE_WORKFLOW_MESSAGE);

        Http::assertNothingSent();
        $this->assertSame(
            0,
            ChatbotMessage::query()->where('session_id', 'inactive-sess-1')->where('role', 'assistant')->count()
        );
    }

    public function test_active_workflow_executes_successfully(): void
    {
        $this->fakeOpenRouterSuccess('Active workflow reply');

        $template = $this->createTemplate('Active system');
        $this->publishChatWorkflow($template->id, [], [
            'status' => WorkflowStatus::Active->value,
        ]);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'active-sess-1',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello active'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reply', 'Active workflow reply');

        Http::assertSentCount(1);
        $this->assertSame(
            1,
            ChatbotMessage::query()->where('session_id', 'active-sess-1')->where('role', 'assistant')->count()
        );
    }

    public function test_inactive_workflow_does_not_execute_openrouter_or_save_assistant(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                $this->openRouterSuccessResponse('SHOULD_NOT_BE_USED'),
                200
            ),
        ]);

        $template = $this->createTemplate('Inactive body');
        $inactive = $this->publishChatWorkflow($template->id, [], [
            'status' => WorkflowStatus::Inactive->value,
            'name' => 'Inactive Chatbot Workflow',
        ]);

        $this->assertSame(WorkflowStatus::Inactive->value, $inactive->fresh()->status);

        $response = $this->postJson('/api/chat/completions', [
            'session_id' => 'inactive-guard-1',
            'messages' => [
                ['role' => 'user', 'content' => 'Should not run'],
            ],
        ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'No active chatbot workflow is configured.',
            ]);

        Http::assertNothingSent();
        $this->assertSame([], $this->providerSends);
        $this->assertDatabaseMissing('chatbot_messages', [
            'session_id' => 'inactive-guard-1',
            'role' => 'assistant',
        ]);
        // User message is also not persisted when resolution fails before save.
        $this->assertDatabaseMissing('chatbot_messages', [
            'session_id' => 'inactive-guard-1',
            'role' => 'user',
        ]);
    }

    public function test_inactive_workflow_is_not_replaced_by_other_trigger_workflows(): void
    {
        Http::fake();

        $template = $this->createTemplate('Chat inactive');
        $this->publishChatWorkflow($template->id, [], [
            'status' => WorkflowStatus::Inactive->value,
        ]);

        // Unrelated active medicine/appointment workflow must not satisfy chatbot lookup.
        $other = Workflow::query()->create([
            'name' => 'Appointment Outbound',
            'status' => WorkflowStatus::Active->value,
            'trigger_type' => 'appointmentBooked',
        ]);
        $version = WorkflowVersion::query()->create([
            'workflow_id' => $other->id,
            'version_number' => 1,
            'definition' => ['nodes' => [], 'edges' => []],
            'compiled_graph' => null,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $other->update(['current_version_id' => $version->id]);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'no-fallback-1',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ])
            ->assertNotFound()
            ->assertJsonPath('message', ChatbotWorkflowService::NO_ACTIVE_WORKFLOW_MESSAGE);

        Http::assertNothingSent();
        $this->assertDatabaseMissing('chatbot_messages', [
            'session_id' => 'no-fallback-1',
            'role' => 'assistant',
        ]);
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
            ->assertJsonPath('message', ChatbotWorkflowService::NO_ACTIVE_WORKFLOW_MESSAGE);

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

        $this->assertDatabaseHas('chatbot_sessions', [
            'session_id' => $sessionId,
            'status' => ChatbotSession::STATUS_ACTIVE,
        ]);
        $this->assertSame(1, ChatbotMessage::query()->where('session_id', $sessionId)->where('role', 'user')->count());
        $this->assertSame(1, ChatbotMessage::query()->where('session_id', $sessionId)->where('role', 'assistant')->count());

        $this->postJson('/api/chat/completions', [
            'session_id' => $sessionId,
            'append_history' => true,
            'messages' => [
                ['role' => 'user', 'content' => 'Second question'],
            ],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $userContents = collect($messages)
                ->where('role', 'user')
                ->pluck('content')
                ->all();

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && is_array($messages)
                && in_array('First question', $userContents, true)
                && in_array('Second question', $userContents, true)
                // Current user message must appear exactly once (no duplication).
                && collect($userContents)->filter(fn ($c) => $c === 'Second question')->count() === 1;
        });

        $this->postJson('/api/chat/session/clear', [
            'session_id' => $sessionId,
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Cache::get('chat_session:'.$sessionId));
        $this->assertSame(
            ChatbotSession::STATUS_ENDED,
            ChatbotSession::query()->where('session_id', $sessionId)->value('status')
        );
    }

    public function test_first_message_creates_session_and_persists_user_and_assistant(): void
    {
        $this->fakeOpenRouterSuccess('Assistant hello');

        $template = $this->createTemplate('System opener');
        // Hospital isolation is exact; organization may be null on the workflow row.
        $this->publishChatWorkflow($template->id, [], [
            'hospital_id' => 3,
        ]);

        $response = $this->postJson('/api/chat/completions', [
            'session_id' => 'persist-1',
            'organization_id' => 7,
            'hospital_id' => 3,
            'messages' => [
                ['role' => 'user', 'content' => 'Hello doctor'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.reply', 'Assistant hello')
            ->assertJsonPath('data.model', 'openai/gpt-4o-mini')
            ->assertJsonPath('data.usage.total_tokens', 15);

        $session = ChatbotSession::query()
            ->where('session_id', 'persist-1')
            ->where('organization_id', 7)
            ->where('hospital_id', 3)
            ->first();

        $this->assertNotNull($session);
        $this->assertSame(ChatbotSession::STATUS_ACTIVE, $session->status);
        $this->assertNotNull($session->last_message_at);

        $user = ChatbotMessage::query()
            ->where('session_id', 'persist-1')
            ->where('role', 'user')
            ->first();
        $assistant = ChatbotMessage::query()
            ->where('session_id', 'persist-1')
            ->where('role', 'assistant')
            ->first();

        $this->assertNotNull($user);
        $this->assertSame('Hello doctor', $user->content);
        $this->assertSame(7, (int) $user->organization_id);
        $this->assertSame(3, (int) $user->hospital_id);

        $this->assertNotNull($assistant);
        $this->assertSame('Assistant hello', $assistant->content);
        $this->assertSame('openai/gpt-4o-mini', $assistant->model);
        $this->assertSame(10, (int) $assistant->prompt_tokens);
        $this->assertSame(5, (int) $assistant->completion_tokens);
        $this->assertSame(15, (int) $assistant->total_tokens);
        $this->assertSame($this->actingUserId, (string) $session->user_id);
        $this->assertSame($this->actingUserId, (string) $user->user_id);
        $this->assertSame($this->actingUserId, (string) $assistant->user_id);
    }

    public function test_retry_does_not_duplicate_user_message(): void
    {
        // Disable OpenRouter fallback so a single 500 fails the request (no second HTTP attempt).
        config(['services.openrouter.fallback_model' => 'openai/gpt-4o-mini']);

        $template = $this->createTemplate('Body');
        $this->publishChatWorkflow($template->id);

        $payload = [
            'session_id' => 'dedup-1',
            'messages' => [
                ['role' => 'user', 'content' => 'Same text'],
            ],
        ];

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push(['error' => 'down'], 500)
                ->push($this->openRouterSuccessResponse('recovered'), 200),
        ]);

        $this->postJson('/api/chat/completions', $payload)->assertStatus(500);

        $this->assertSame(
            1,
            ChatbotMessage::query()->where('session_id', 'dedup-1')->where('role', 'user')->count()
        );
        $this->assertSame(
            0,
            ChatbotMessage::query()->where('session_id', 'dedup-1')->where('role', 'assistant')->count()
        );

        $this->postJson('/api/chat/completions', $payload)
            ->assertOk()
            ->assertJsonPath('data.reply', 'recovered');

        $this->assertSame(
            1,
            ChatbotMessage::query()->where('session_id', 'dedup-1')->where('role', 'user')->where('content', 'Same text')->count()
        );
        $this->assertSame(
            1,
            ChatbotMessage::query()->where('session_id', 'dedup-1')->where('role', 'assistant')->count()
        );
    }

    public function test_session_isolation_by_organization_and_hospital(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::sequence()
                ->push($this->openRouterSuccessResponse('Org1 reply'), 200)
                ->push($this->openRouterSuccessResponse('Org2 reply'), 200),
        ]);

        $template = $this->createTemplate('Scoped');
        // Exact hospital match required when hospital_id is supplied on the request.
        $this->publishChatWorkflow($template->id, [], [
            'organization_id' => null,
            'hospital_id' => 10,
        ]);
        $this->publishChatWorkflow($template->id, [], [
            'organization_id' => null,
            'hospital_id' => 20,
        ]);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'shared-sid',
            'organization_id' => 1,
            'hospital_id' => 10,
            'messages' => [['role' => 'user', 'content' => 'Hello org1']],
        ])->assertOk()->assertJsonPath('data.reply', 'Org1 reply');

        $this->postJson('/api/chat/completions', [
            'session_id' => 'shared-sid',
            'organization_id' => 2,
            'hospital_id' => 20,
            'messages' => [['role' => 'user', 'content' => 'Hello org2']],
        ])->assertOk()->assertJsonPath('data.reply', 'Org2 reply');

        $this->assertSame(
            1,
            ChatbotSession::query()->where('session_id', 'shared-sid')->where('organization_id', 1)->where('hospital_id', 10)->count()
        );
        $this->assertSame(
            1,
            ChatbotSession::query()->where('session_id', 'shared-sid')->where('organization_id', 2)->where('hospital_id', 20)->count()
        );
        $this->assertSame(
            0,
            ChatbotMessage::query()
                ->where('session_id', 'shared-sid')
                ->where('organization_id', 2)
                ->where('content', 'Hello org1')
                ->count()
        );

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $users = collect($messages)->where('role', 'user')->pluck('content');

            if (($users->last() ?? null) !== 'Hello org2') {
                return true;
            }

            return ! $users->contains('Hello org1');
        });
    }

    public function test_ended_session_rejects_further_messages(): void
    {
        $this->fakeOpenRouterSuccess('bye');
        $template = $this->createTemplate('End');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'end-1',
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ])->assertOk();

        $this->postJson('/api/chat/completions', [
            'session_id' => 'end-1',
            'end_conversation' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'ended');

        $this->postJson('/api/chat/completions', [
            'session_id' => 'end-1',
            'messages' => [['role' => 'user', 'content' => 'Again']],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        Http::assertSentCount(1);
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

    public function test_authenticated_uuid_user_id_is_stored_on_session_and_messages(): void
    {
        $uuid = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';
        $this->actingAsChatUser($uuid);
        $this->fakeOpenRouterSuccess('UUID reply');

        $template = $this->createTemplate('You are a hospital assistant for {{chat_message}}');
        $this->publishChatWorkflow($template->id, [
            'prompt' => 'Answer carefully using template context.',
        ]);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'uuid-sess-1',
            'messages' => [['role' => 'user', 'content' => 'Hello UUID']],
        ])
            ->assertOk()
            ->assertJsonPath('data.model', 'openai/gpt-4o-mini')
            ->assertJsonPath('data.usage.prompt_tokens', 10)
            ->assertJsonPath('data.usage.completion_tokens', 5)
            ->assertJsonPath('data.usage.total_tokens', 15)
            ->assertJsonPath('data.fallback_used', false);

        $session = ChatbotSession::query()->where('session_id', 'uuid-sess-1')->first();
        $this->assertNotNull($session);
        $this->assertSame($uuid, (string) $session->user_id);
        $this->assertIsString($session->user_id);

        $this->assertDatabaseHas('chatbot_messages', [
            'session_id' => 'uuid-sess-1',
            'role' => 'user',
            'user_id' => $uuid,
            'content' => 'Hello UUID',
        ]);
        $this->assertDatabaseHas('chatbot_messages', [
            'session_id' => 'uuid-sess-1',
            'role' => 'assistant',
            'user_id' => $uuid,
            'prompt_tokens' => 10,
            'completion_tokens' => 5,
            'total_tokens' => 15,
        ]);

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $roles = array_column($messages, 'role');
            $system = collect($messages)->firstWhere('role', 'system')['content'] ?? '';

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && ($roles[0] ?? null) === 'system'
                && str_contains((string) $system, 'You are a hospital assistant')
                && str_contains((string) $system, 'Answer carefully using template context.')
                && collect($messages)->where('role', 'user')->count() === 1;
        });
    }

    public function test_existing_session_null_user_id_is_backfilled(): void
    {
        $uuid = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
        $this->actingAsChatUser($uuid);

        ChatbotSession::query()->create([
            'session_id' => 'backfill-1',
            'organization_id' => null,
            'hospital_id' => null,
            'user_id' => null,
            'status' => ChatbotSession::STATUS_ACTIVE,
        ]);

        $this->fakeOpenRouterSuccess('backfilled');
        $template = $this->createTemplate('Body');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'backfill-1',
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ])->assertOk();

        $this->assertSame(
            $uuid,
            (string) ChatbotSession::query()->where('session_id', 'backfill-1')->value('user_id')
        );
    }

    public function test_session_owned_by_another_user_is_rejected(): void
    {
        ChatbotSession::query()->create([
            'session_id' => 'owned-1',
            'organization_id' => null,
            'hospital_id' => null,
            'user_id' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            'status' => ChatbotSession::STATUS_ACTIVE,
        ]);

        $this->actingAsChatUser('dddddddd-dddd-4ddd-8ddd-dddddddddddd');
        $template = $this->createTemplate('Body');
        $this->publishChatWorkflow($template->id);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'owned-1',
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        Http::assertNothingSent();
    }

    public function test_openrouter_history_is_chronological_and_excludes_other_sessions(): void
    {
        $this->fakeOpenRouterSuccess('Third reply');
        $template = $this->createTemplate('Care system prompt');
        $this->publishChatWorkflow($template->id, [
            'prompt' => 'Node prompt text',
        ]);

        $this->postJson('/api/chat/completions', [
            'session_id' => 'hist-main',
            'messages' => [['role' => 'user', 'content' => 'First']],
        ])->assertOk();

        // Other session must not leak into OpenRouter history.
        ChatbotMessage::query()->create([
            'session_id' => 'hist-other',
            'organization_id' => null,
            'hospital_id' => null,
            'user_id' => $this->actingUserId,
            'role' => 'user',
            'content' => 'SECRET_OTHER_SESSION',
        ]);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response(
                $this->openRouterSuccessResponse('Second reply'),
                200
            ),
        ]);

        // Seed assistant for first turn already saved; send second user turn.
        $this->postJson('/api/chat/completions', [
            'session_id' => 'hist-main',
            'messages' => [['role' => 'user', 'content' => 'Second']],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];
            $roles = array_column($messages, 'role');
            $contents = array_column($messages, 'content');

            $userCount = collect($messages)->where('role', 'user')->count();
            $secondCount = collect($messages)->where('role', 'user')->where('content', 'Second')->count();

            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && ($roles[0] ?? null) === 'system'
                && str_contains((string) ($contents[0] ?? ''), 'Care system prompt')
                && str_contains((string) ($contents[0] ?? ''), 'Node prompt text')
                && in_array('user', $roles, true)
                && in_array('assistant', $roles, true)
                && in_array('First', $contents, true)
                && in_array('Second', $contents, true)
                && $secondCount === 1
                && $userCount === 2
                && ! in_array('SECRET_OTHER_SESSION', $contents, true)
                // Chronological: First user before its assistant before Second user
                && array_search('First', $contents, true) < array_search('Second', $contents, true);
        });
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
