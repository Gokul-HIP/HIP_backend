<?php

namespace Tests\Feature;

use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowMessageTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_07_21_100004_create_workflow_templates_table.php',
                'database/migrations/2026_07_21_140000_add_category_and_version_to_workflow_templates_table.php',
                'database/migrations/2026_07_29_100000_rename_workflow_templates_to_workflow_message_templates.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Authenticate::class);
    }

    public function test_creates_email_template(): void
    {
        $response = $this->postJson('/api/workflow/templates', [
            'name' => 'Hospital Care Assistant',
            'channel' => 'email',
            'status' => 'active',
            'subject' => 'Appointment Update',
            'body' => 'Hello {{patient_name}}, your appointment is confirmed.',
            'message' => 'Hello {{patient_name}}, your appointment is confirmed.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status_code', 201)
            ->assertJsonPath('data.name', 'Hospital Care Assistant')
            ->assertJsonPath('data.channel', 'email')
            ->assertJsonPath('data.subject', 'Appointment Update')
            ->assertJsonPath('data.body', 'Hello {{patient_name}}, your appointment is confirmed.')
            ->assertJsonPath('data.message', 'Hello {{patient_name}}, your appointment is confirmed.')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_active', true);

        $this->assertNotNull($response->json('data.id'));
        $this->assertDatabaseHas('workflow_message_templates', [
            'name' => 'Hospital Care Assistant',
            'channel' => 'email',
            'is_active' => 1,
        ]);
    }

    public function test_creates_sms_template(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'SMS Appointment Reminder',
            'channel' => 'sms',
            'status' => 'active',
            'message' => 'Hello {{patient_name}}, your appointment is confirmed.',
            'body' => 'Hello {{patient_name}}, your appointment is confirmed.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.body', 'Hello {{patient_name}}, your appointment is confirmed.');
    }

    public function test_creates_whatsapp_template_with_buttons(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'WhatsApp Appointment Reminder',
            'channel' => 'whatsapp',
            'status' => 'active',
            'message' => 'Hello {{patient_name}}, your appointment is confirmed.',
            'body' => 'Hello {{patient_name}}, your appointment is confirmed.',
            'buttons' => "Confirm\nReschedule",
        ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'whatsapp')
            ->assertJsonPath('data.buttons', "Confirm\nReschedule");
    }

    public function test_creates_push_template(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Appointment Reminder Push',
            'channel' => 'push',
            'status' => 'active',
            'title' => 'Appointment Reminder',
            'body' => 'Your appointment is scheduled for {{appointment_date}}.',
            'message' => 'Your appointment is scheduled for {{appointment_date}}.',
            'priority' => 'normal',
        ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'push')
            ->assertJsonPath('data.title', 'Appointment Reminder')
            ->assertJsonPath('data.priority', 'normal');
    }

    public function test_creates_ai_channel_template_for_send_ai_chat_picker(): void
    {
        $response = $this->postJson('/api/workflow/templates', [
            'name' => 'Hospital Care Assistant',
            'channel' => 'ai',
            'status' => 'active',
            'message' => 'You are a helpful hospital care assistant.',
            'body' => 'You are a helpful hospital care assistant.',
            'temperature' => 0.7,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.channel', 'ai')
            ->assertJsonPath('data.temperature', 0.7)
            ->assertJsonPath('data.body', 'You are a helpful hospital care assistant.');

        $id = $response->json('data.id');
        $this->assertTrue(
            WorkflowMessageTemplate::query()->whereKey($id)->where('is_active', true)->exists()
        );
    }

    public function test_creates_voice_channel_template(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Hospital Voice Assistant',
            'channel' => 'voice',
            'status' => 'active',
            'message' => 'You are a helpful hospital voice assistant.',
            'body' => 'You are a helpful hospital voice assistant.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'voice');
    }

    public function test_validates_required_name(): void
    {
        $this->postJson('/api/workflow/templates', [
            'channel' => 'sms',
            'message' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validates_required_channel(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Missing channel',
            'message' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_rejects_unsupported_channel(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Bad channel',
            'channel' => 'telegram',
            'message' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_email_requires_subject(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Email without subject',
            'channel' => 'email',
            'body' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subject']);
    }

    public function test_push_requires_title(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Push without title',
            'channel' => 'push',
            'body' => 'Hello',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_requires_body_or_message(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'Empty content',
            'channel' => 'sms',
            'body' => '',
            'message' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_client_cannot_override_organization_id(): void
    {
        $this->seedOrganization(10);
        $this->seedOrganization(99);

        $user = new class extends Authenticatable
        {
            public $id = 'user-tenant-a';

            public $organization_id = 10;

            public $hospital_id = null;
        };

        $this->actingAs($user);

        $response = $this->postJson('/api/workflow/templates', [
            'name' => 'Owned Template',
            'channel' => 'sms',
            'message' => 'Hello tenant A',
            'organization_id' => 99,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['organization_id']);
    }

    public function test_organization_id_comes_from_authenticated_user(): void
    {
        $this->seedOrganization(10);

        $user = new class extends Authenticatable
        {
            public $id = 'user-tenant-a';

            public $organization_id = 10;

            public $hospital_id = null;
        };

        $this->actingAs($user);

        $response = $this->postJson('/api/workflow/templates', [
            'name' => 'Tenant A Template',
            'channel' => 'sms',
            'message' => 'Hello',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.organization_id', 10);

        $this->assertDatabaseHas('workflow_message_templates', [
            'name' => 'Tenant A Template',
            'organization_id' => 10,
        ]);
    }

    public function test_created_template_appears_in_get_list(): void
    {
        $create = $this->postJson('/api/workflow/templates', [
            'name' => 'Listable SMS',
            'channel' => 'sms',
            'status' => 'active',
            'message' => 'Listed body',
        ])->assertCreated();

        $id = $create->json('data.id');

        $list = $this->getJson('/api/workflow/templates?channel=sms');
        $list->assertOk()
            ->assertJsonPath('status_code', 200);

        $rows = collect($list->json('data.data') ?? $list->json('data') ?? []);
        // Paginated resource: data.data
        if ($rows->isEmpty() && is_array($list->json('data'))) {
            $payload = $list->json('data');
            $rows = collect($payload['data'] ?? []);
        }

        $this->assertTrue(
            $rows->contains(fn ($row) => (int) ($row['id'] ?? 0) === (int) $id),
            'Created template should appear in GET /workflow/templates?channel=sms'
        );
    }

    public function test_get_templates_list_unchanged_shape(): void
    {
        WorkflowMessageTemplate::query()->create([
            'name' => 'Existing',
            'channel' => 'whatsapp',
            'locale' => 'en',
            'body' => 'Hi',
            'variables' => [],
            'is_active' => true,
            'version_number' => 1,
        ]);

        $this->getJson('/api/workflow/templates?channel=whatsapp')
            ->assertOk()
            ->assertJsonPath('message', 'Workflow templates fetched successfully')
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'data' => [
                        ['id', 'name', 'channel', 'body', 'is_active'],
                    ],
                ],
            ]);
    }

    public function test_duplicate_names_are_allowed_when_no_unique_constraint(): void
    {
        // Schema has no unique index on name; do not invent a uniqueness rule.
        $payload = [
            'name' => 'Same Name',
            'channel' => 'sms',
            'message' => 'First',
        ];

        $this->postJson('/api/workflow/templates', $payload)->assertCreated();
        $this->postJson('/api/workflow/templates', array_merge($payload, ['message' => 'Second']))
            ->assertCreated();

        $this->assertSame(2, WorkflowMessageTemplate::query()->where('name', 'Same Name')->count());
    }

    public function test_invalid_user_organization_does_not_500(): void
    {
        $user = new class extends Authenticatable
        {
            public $id = 'user-bad-org';

            public $organization_id = 999999;

            public $hospital_id = null;
        };

        $this->actingAs($user);

        $this->postJson('/api/workflow/templates', [
            'name' => 'Survives Bad Org',
            'channel' => 'sms',
            'message' => 'Hello',
            'body' => 'Hello',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Survives Bad Org')
            ->assertJsonPath('data.organization_id', null);

        $this->assertDatabaseHas('workflow_message_templates', [
            'name' => 'Survives Bad Org',
            'organization_id' => null,
        ]);
    }

    public function test_unauthorized_without_auth_middleware_bypass_returns_401_when_enforced(): void
    {
        // Re-enable auth middleware for this case only.
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $this->withMiddleware(Authenticate::class);

        $this->postJson('/api/workflow/templates', [
            'name' => 'No Auth',
            'channel' => 'sms',
            'message' => 'Hello',
        ])->assertUnauthorized();
    }

    public function test_whatsapp_buttons_array_is_persisted(): void
    {
        $this->postJson('/api/workflow/templates', [
            'name' => 'WA Buttons Array',
            'channel' => 'whatsapp',
            'status' => 'active',
            'message' => 'Hello',
            'body' => 'Hello',
            'buttons' => [
                ['text' => 'Confirm'],
                ['text' => 'Reschedule'],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.channel', 'whatsapp');

        $template = WorkflowMessageTemplate::query()->where('name', 'WA Buttons Array')->first();
        $this->assertNotNull($template);
        $this->assertIsArray($template->variables['buttons'] ?? null);
    }

    protected function seedOrganization(int $id): void
    {
        if (DB::table('organizations')->where('id', $id)->exists()) {
            return;
        }

        DB::table('organizations')->insert([
            'id' => $id,
            'org_name' => "Org {$id}",
            'org_city' => 'City',
            'org_address' => 'Address',
            'org_logo' => 'logo.png',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
