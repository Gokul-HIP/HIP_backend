<?php

namespace Tests\Feature;

use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowBuilderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Only migrate tables required for Builder Connect tests (avoids MySQL-only data migrations on SQLite).
     *
     * @return array<string, mixed>
     */
    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/2025_11_21_014030_create_permission_tables.php',
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2025_12_10_111709_create_hospitals_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
                'database/migrations/2026_08_17_100000_add_hospital_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel binds middleware by class name, not route alias strings like auth:sanctum.
        $this->withoutMiddleware(Authenticate::class);

        if (! \Illuminate\Support\Facades\Schema::hasTable('healthinpocket_users')) {
            \Illuminate\Support\Facades\Schema::create('healthinpocket_users', function ($table) {
                $table->uuid('id')->primary();
                $table->string('email')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->unsignedBigInteger('hospital_id')->nullable();
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->string('role')->nullable();
                $table->timestamps();
            });
        }
    }

    /** @return array<string, mixed> */
    private function validConfiguration(): array
    {
        return [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 100],
                    'data' => ['nodeType' => 'appointmentBooked'],
                ],
                [
                    'id' => 'd1',
                    'type' => 'workflow',
                    'position' => ['x' => 300, 'y' => 100],
                    'data' => ['nodeType' => 'delay', 'type' => 'minutes', 'value' => 5],
                ],
                [
                    'id' => 'w1',
                    'type' => 'workflow',
                    'position' => ['x' => 500, 'y' => 100],
                    'data' => [
                        'nodeType' => 'sendWhatsApp',
                        'messageTemplate' => 'Hi {{PatientName}}, your appointment is confirmed.',
                    ],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 700, 'y' => 100],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 't1', 'target' => 'd1'],
                ['id' => 'edge-2', 'source' => 'd1', 'target' => 'w1'],
                ['id' => 'edge-3', 'source' => 'w1', 'target' => 'e1'],
            ],
        ];
    }

    public function test_create_save_publish_round_trip(): void
    {
        $configuration = $this->validConfiguration();

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Appointment Booked Confirmation',
            'configuration' => $configuration,
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Appointment Booked Confirmation')
            ->assertJsonPath('data.trigger_type', 'appointmentBooked')
            ->assertJsonPath('data.status', WorkflowStatus::Draft->value)
            ->assertJsonPath('data.configuration.nodes.0.id', 't1');

        $workflowId = $createResponse->json('data.id');

        $showResponse = $this->getJson("/api/workflows/{$workflowId}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.configuration.edges.2.target', 'e1');

        $updatedConfiguration = $configuration;
        $updatedConfiguration['nodes'][2]['data']['messageTemplate'] = 'Updated template';

        $updateResponse = $this->putJson("/api/workflows/{$workflowId}", [
            'name' => 'Appointment Booked Confirmation v2',
            'configuration' => $updatedConfiguration,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Appointment Booked Confirmation v2')
            ->assertJsonPath('data.configuration.nodes.2.data.messageTemplate', 'Updated template');

        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish");

        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.workflow_id', $workflowId)
            ->assertJsonPath('data.version_number', 1)
            ->assertJsonPath('data.validation.valid', true);

        $workflow = Workflow::query()->with(['currentVersion', 'draftVersion'])->findOrFail($workflowId);
        $this->assertSame(WorkflowStatus::Active->value, $workflow->status);
        $this->assertNotNull($workflow->currentVersion);
        $this->assertSame(1, $workflow->currentVersion->version_number);
        $this->assertSame('published', $workflow->currentVersion->status);
    }

    public function test_frontend_on_chat_message_saves_and_publishes(): void
    {
        $configuration = [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'nodes' => [
                [
                    'id' => 'onChatMessage_0q3wx4e',
                    'type' => 'workflow',
                    'position' => ['x' => 256, 'y' => 240],
                    'data' => [
                        'category' => 'triggers',
                        'nodeType' => 'onChatMessage',
                        'label' => 'On Message Received',
                        'status' => 'draft',
                    ],
                ],
                [
                    'id' => 'end_1',
                    'type' => 'workflow',
                    'position' => ['x' => 500, 'y' => 240],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 'onChatMessage_0q3wx4e', 'target' => 'end_1'],
            ],
        ];

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Chat Message Workflow',
            'configuration' => $configuration,
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.trigger_type', 'messageReceived')
            ->assertJsonPath('data.trigger_label', 'On Message Received');

        $workflowId = $createResponse->json('data.id');

        $this->postJson("/api/workflows/{$workflowId}/publish")
            ->assertStatus(200)
            ->assertJsonPath('data.validation.valid', true);

        $workflow = Workflow::query()->findOrFail($workflowId);
        $this->assertSame('messageReceived', $workflow->trigger_type);
        $this->assertSame(WorkflowStatus::Active->value, $workflow->status);
    }

    public function test_publish_rejects_invalid_workflow(): void
    {
        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Invalid Workflow',
            'configuration' => $this->validConfiguration(),
        ]);

        $createResponse->assertStatus(201);

        $workflowId = $createResponse->json('data.id');

        // Save passes request validation (non-empty edges) but publish fails (no end node).
        $this->putJson("/api/workflows/{$workflowId}", [
            'configuration' => [
                'builderVersion' => '1',
                'reactFlowVersion' => '12.x',
                'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
                'nodes' => [
                    [
                        'id' => 't1',
                        'type' => 'workflow',
                        'position' => ['x' => 0, 'y' => 0],
                        'data' => ['nodeType' => 'appointmentBooked'],
                    ],
                    [
                        'id' => 'd1',
                        'type' => 'workflow',
                        'position' => ['x' => 200, 'y' => 0],
                        'data' => ['nodeType' => 'delay', 'type' => 'minutes', 'value' => 5],
                    ],
                ],
                'edges' => [
                    ['id' => 'edge-1', 'source' => 't1', 'target' => 'd1'],
                ],
            ],
        ])->assertStatus(200);

        $this->postJson("/api/workflows/{$workflowId}/publish")
            ->assertStatus(422)
            ->assertJsonFragment(['status_code' => 422]);
    }

    public function test_triggers_and_variables_endpoints(): void
    {
        $triggersResponse = $this->getJson('/api/workflow/triggers');
        $triggersResponse->assertStatus(200);

        $triggerTypes = collect($triggersResponse->json('data.triggers'))->pluck('type');
        $this->assertTrue($triggerTypes->contains('appointmentBooked'));
        $this->assertTrue($triggerTypes->contains('webhookEvent'));

        $variablesResponse = $this->getJson('/api/workflow/variables?trigger=appointmentBooked');
        $variablesResponse->assertStatus(200)
            ->assertJsonPath('data.trigger', 'appointmentBooked');

        $groups = $variablesResponse->json('data.groups');
        $this->assertArrayHasKey('patient', $groups);
        $this->assertArrayHasKey('appointment', $groups);
    }

    public function test_list_and_delete_workflow(): void
    {
        $this->postJson('/api/workflows', [
            'name' => 'List Test Workflow',
            'configuration' => $this->validConfiguration(),
        ])->assertStatus(201);

        $this->getJson('/api/workflows')
            ->assertStatus(200)
            ->assertJsonPath('data.data.0.name', 'List Test Workflow');

        $workflowId = Workflow::query()->value('id');

        $this->deleteJson("/api/workflows/{$workflowId}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('workflows', ['id' => $workflowId]);
    }

    public function test_duplicate_creates_independent_draft_copy(): void
    {
        $configuration = $this->validConfiguration();

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Appointment Booked Confirmation',
            'configuration' => $configuration,
        ]);

        $createResponse->assertStatus(201);
        $sourceId = $createResponse->json('data.id');

        $this->postJson("/api/workflows/{$sourceId}/publish")->assertStatus(200);

        $duplicateResponse = $this->postJson("/api/workflows/{$sourceId}/duplicate");

        $duplicateResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Appointment Booked Confirmation (Copy)')
            ->assertJsonPath('data.status', WorkflowStatus::Draft->value)
            ->assertJsonPath('data.trigger_type', 'appointmentBooked')
            ->assertJsonPath('data.hospital_id', null)
            ->assertJsonPath('data.configuration.nodes.0.id', 't1');

        $copyId = $duplicateResponse->json('data.id');
        $this->assertNotNull($copyId);
        $this->assertNotSame($sourceId, $copyId);

        $source = Workflow::query()->findOrFail($sourceId);
        $copy = Workflow::query()->with(['draftVersion', 'currentVersion'])->findOrFail($copyId);

        $this->assertSame(WorkflowStatus::Active->value, $source->status);
        $this->assertSame(WorkflowStatus::Draft->value, $copy->status);
        $this->assertNull($copy->current_version_id);
        $this->assertNotNull($copy->draftVersion);
        $this->assertSame(
            $source->draftVersion?->definition ?? $source->currentVersion?->definition,
            $copy->draftVersion->definition
        );
    }

    public function test_duplicate_unknown_workflow_returns_404(): void
    {
        $this->postJson('/api/workflows/999999/duplicate')
            ->assertStatus(404)
            ->assertJsonPath('message', 'Workflow not found.');
    }

    public function test_unavailable_triggers_have_available_false_in_catalog(): void
    {
        $response = $this->getJson('/api/workflow/triggers');
        $response->assertStatus(200);

        $triggers = collect($response->json('data.triggers'))->keyBy('type');

        // Available trigger
        $this->assertTrue($triggers->get('appointmentBooked')['available'] ?? false);

        // Unavailable triggers from config
        $unavailable = config('workflow.unavailable_triggers', []);
        foreach ($unavailable as $type) {
            if ($triggers->has($type)) {
                $this->assertFalse(
                    $triggers->get($type)['available'],
                    "Trigger {$type} should have available: false"
                );
            }
        }
    }

    public function test_unavailable_trigger_allows_saving_draft(): void
    {
        $configuration = [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 100],
                    'data' => ['nodeType' => 'webhookEvent'],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 500, 'y' => 100],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 't1', 'target' => 'e1'],
            ],
        ];

        // Saving draft stays allowed
        $response = $this->postJson('/api/workflows', [
            'name' => 'Draft with Webhook Event',
            'configuration' => $configuration,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', WorkflowStatus::Draft->value);
    }

    public function test_unavailable_trigger_blocks_publishing_with_422(): void
    {
        $configuration = [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1],
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 100, 'y' => 100],
                    'data' => ['nodeType' => 'webhookEvent'],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 500, 'y' => 100],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 't1', 'target' => 'e1'],
            ],
        ];

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Unavailable Trigger Publish Test',
            'configuration' => $configuration,
        ]);
        $workflowId = $createResponse->json('data.id');

        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish");
        $publishResponse->assertStatus(422)
            ->assertJsonPath('status_code', 422)
            ->assertJsonPath('message', 'This trigger is not available yet');
    }

    private function createTestHospital(int $orgId = 1): \App\Models\Hospital
    {
        $org = \App\Models\Organization::query()->find($orgId) ?? \App\Models\Organization::forceCreate([
            'id' => $orgId,
            'org_name' => "Organization {$orgId}",
            'org_city' => 'City',
            'org_address' => 'Address',
            'org_logo' => 'logo.png',
            'status' => 'active',
        ]);

        return \App\Models\Hospital::forceCreate([
            'hospital_name' => "Hospital for Org {$orgId}",
            'hospital_address' => 'Hospital Address',
            'hospital_logo' => 'hospital_logo.png',
            'hospital_admin_name' => 'Admin',
            'hospital_admin_contact' => '9876543210',
            'hospital_admin_email' => "admin{$orgId}@hospital.com",
            'hospital_admin_address' => 'Hospital Admin Address',
            'hospital_admin_longitude' => '0',
            'hospital_admin_latitude' => '0',
            'organization_id' => $org->id,
            'status' => 'active',
        ]);
    }

    public function test_hospital_scope_mismatch_with_workflow_organization_returns_422(): void
    {
        $hospital1 = $this->createTestHospital(101);
        $hospital2 = $this->createTestHospital(102);

        $configuration = $this->validConfiguration();

        // Attempting to create a workflow with hospital_id belonging to org 101, but workflow org 102
        $response = $this->postJson('/api/workflows', [
            'name' => 'Mismatched Org Hospital Workflow',
            'organization_id' => 102,
            'hospital_id' => $hospital1->id,
            'configuration' => $configuration,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Hospital does not belong to the workflow organization.');
    }

    public function test_hospital_admin_same_organization_allows_creating_and_publishing(): void
    {
        $hospital = $this->createTestHospital(201);

        $adminUser = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'hosp_admin@example.com',
            'organization_id' => 201,
            'hospital_id' => $hospital->id,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($adminUser);

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Authorized Hospital Workflow',
            'organization_id' => 201,
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);

        $createResponse->assertStatus(201);
        $workflowId = $createResponse->json('data.id');

        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish");
        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.workflow_id', $workflowId);
    }

    public function test_hospital_admin_other_organization_returns_403(): void
    {
        $hospital = $this->createTestHospital(301);

        $otherAdmin = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'other_admin@example.com',
            'organization_id' => 999, // Different organization
            'hospital_id' => 9999,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($otherAdmin);

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Unauthorized Hospital Workflow',
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);

        $createResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');
    }

    public function test_super_admin_can_access_any_hospital(): void
    {
        $hospital = $this->createTestHospital(401);

        $superAdmin = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'super@example.com',
            'role' => 'super-admin',
        ]);

        $this->actingAs($superAdmin);

        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Super Admin Hospital Workflow',
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);

        $createResponse->assertStatus(201);
        $workflowId = $createResponse->json('data.id');

        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish");
        $publishResponse->assertStatus(200);
    }

    public function test_hospitals_endpoint_filters_for_scoped_hospital_admin(): void
    {
        $hospital1 = $this->createTestHospital(501);
        $hospital2 = $this->createTestHospital(502);

        $scopedAdmin = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'scoped_admin@example.com',
            'hospital_id' => $hospital1->id,
            'organization_id' => 501,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($scopedAdmin);

        $response = $this->getJson('/api/hospitals');
        $response->assertStatus(200);

        $ids = collect($response->json('data.data'))->pluck('id');
        $this->assertTrue($ids->contains($hospital1->id));
        $this->assertFalse($ids->contains($hospital2->id));
    }

    public function test_hospital_admin_same_hospital_creates_updates_and_publishes_successfully(): void
    {
        $hospital = $this->createTestHospital(601);

        $adminUser = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'admin_hosp601@example.com',
            'organization_id' => 601,
            'hospital_id' => $hospital->id,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($adminUser);

        // 1. Create
        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Initial Workflow',
            'hospital_id' => $hospital->id,
            'organization_id' => 601,
            'configuration' => $this->validConfiguration(),
        ]);
        $createResponse->assertStatus(201);
        $workflowId = (int) $createResponse->json('data.id');

        // 2. Update
        $updateResponse = $this->putJson("/api/workflows/{$workflowId}", [
            'name' => 'Renamed Workflow by Hospital Admin',
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Renamed Workflow by Hospital Admin');

        // 3. Publish
        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish", [
            'hospital_id' => $hospital->id,
            'version_notes' => 'Published by hospital admin',
        ]);
        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.workflow_id', $workflowId);
    }

    public function test_hospital_admin_other_hospital_is_denied_on_create_update_and_publish(): void
    {
        $hospitalAllowed = $this->createTestHospital(701);
        $hospitalForbidden = $this->createTestHospital(702);

        $adminUser = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'admin701@example.com',
            'organization_id' => 701,
            'hospital_id' => $hospitalAllowed->id,
            'role' => 'hospital_admin',
        ]);

        // Workflow belonging to forbidden hospital 702
        $forbiddenWorkflow = app(\App\Modules\Workflow\Services\Builder\WorkflowBuilderService::class)->create([
            'name' => 'Other Hospital Workflow',
            'hospital_id' => $hospitalForbidden->id,
            'organization_id' => 702,
            'configuration' => $this->validConfiguration(),
        ]);

        $this->actingAs($adminUser);

        // 1. Create targeting other hospital -> 403
        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Illegal Workflow',
            'hospital_id' => $hospitalForbidden->id,
            'organization_id' => 702,
            'configuration' => $this->validConfiguration(),
        ]);
        $createResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');

        // 2. Update targeting other hospital -> 403
        $updateResponse = $this->putJson("/api/workflows/{$forbiddenWorkflow->id}", [
            'name' => 'Attempted Illegal Update',
            'hospital_id' => $hospitalForbidden->id,
            'configuration' => $this->validConfiguration(),
        ]);
        $updateResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');

        // 3. Publish targeting other hospital -> 403
        $publishResponse = $this->postJson("/api/workflows/{$forbiddenWorkflow->id}/publish", [
            'hospital_id' => $hospitalForbidden->id,
        ]);
        $publishResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');
    }

    public function test_super_admin_creates_updates_and_publishes_any_hospital(): void
    {
        $hospital = $this->createTestHospital(801);

        $superAdmin = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'superadmin@example.com',
            'hospital_id' => null,
            'organization_id' => null,
            'role' => 'super-admin',
        ]);

        $this->actingAs($superAdmin);

        // 1. Create
        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Super Admin Workflow',
            'hospital_id' => $hospital->id,
            'organization_id' => 801,
            'configuration' => $this->validConfiguration(),
        ]);
        $createResponse->assertStatus(201);
        $workflowId = (int) $createResponse->json('data.id');

        // 2. Update
        $updateResponse = $this->putJson("/api/workflows/{$workflowId}", [
            'name' => 'Updated by Super Admin',
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);
        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated by Super Admin');

        // 3. Publish
        $publishResponse = $this->postJson("/api/workflows/{$workflowId}/publish", [
            'hospital_id' => $hospital->id,
        ]);
        $publishResponse->assertStatus(200)
            ->assertJsonPath('data.workflow_id', $workflowId);
    }

    public function test_user_without_hospital_or_organization_is_denied_with_403(): void
    {
        $hospital = $this->createTestHospital(901);

        $unscopedUser = new \App\Models\HIPUser([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'email' => 'unscoped@example.com',
            'hospital_id' => null,
            'organization_id' => null,
            'role' => 'staff',
        ]);

        $existingWorkflow = app(\App\Modules\Workflow\Services\Builder\WorkflowBuilderService::class)->create([
            'name' => 'Hospital 901 Workflow',
            'hospital_id' => $hospital->id,
            'organization_id' => 901,
            'configuration' => $this->validConfiguration(),
        ]);

        $this->actingAs($unscopedUser);

        // 1. Create targeting a hospital -> 403
        $createResponse = $this->postJson('/api/workflows', [
            'name' => 'Unscoped Creation Attempt',
            'hospital_id' => $hospital->id,
            'organization_id' => 901,
            'configuration' => $this->validConfiguration(),
        ]);
        $createResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');

        // 2. Update targeting a hospital -> 403
        $updateResponse = $this->putJson("/api/workflows/{$existingWorkflow->id}", [
            'name' => 'Unscoped Update Attempt',
            'hospital_id' => $hospital->id,
            'configuration' => $this->validConfiguration(),
        ]);
        $updateResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');

        // 3. Publish targeting a hospital -> 403
        $publishResponse = $this->postJson("/api/workflows/{$existingWorkflow->id}/publish", [
            'hospital_id' => $hospital->id,
        ]);
        $publishResponse->assertStatus(403)
            ->assertJsonPath('message', 'You are not authorized to manage workflows for this hospital.');
    }

    public function test_unavailable_triggers_drift_matches_frontend_definitions(): void
    {
        $backendUnavailable = config('workflow.unavailable_triggers', []);
        $this->assertNotEmpty($backendUnavailable);

        // Candidate paths for frontend shared.js (WSL path and Windows path)
        $candidates = [
            '/mnt/c/xampp/htdocs/HIP_Automation/src/components/flow/config/triggers/shared.js',
            'C:/xampp/htdocs/HIP_Automation/src/components/flow/config/triggers/shared.js',
        ];

        $frontendPath = null;
        foreach ($candidates as $cand) {
            if (file_exists($cand)) {
                $frontendPath = $cand;
                break;
            }
        }

        if ($frontendPath !== null) {
            $contents = file_get_contents($frontendPath);
            foreach ($backendUnavailable as $triggerType) {
                $this->assertStringContainsString(
                    "'{$triggerType}'",
                    str_replace('"', "'", $contents),
                    "Frontend shared.js UNAVAILABLE_TRIGGERS must contain backend trigger '{$triggerType}'"
                );
            }
        } else {
            // If running in isolated CI without frontend mounted, verify all canonical items are present
            $expected = [
                'labReportReady',
                'medicineRefillDue',
                'membershipExpiry',
                'procedureCompleted',
                'campaignTriggered',
                'labCompleted',
                'userPlanExpiry',
                'familyPackageTierUpdated',
                'webhookEvent',
            ];
            foreach ($expected as $exp) {
                $this->assertContains($exp, $backendUnavailable);
            }
        }
    }
}
