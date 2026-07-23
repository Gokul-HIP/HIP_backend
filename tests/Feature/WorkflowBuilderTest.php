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
                'database/migrations/2025_12_10_111601_create_organizations_table.php',
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel binds middleware by class name, not route alias strings like auth:sanctum.
        $this->withoutMiddleware(Authenticate::class);
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
}
