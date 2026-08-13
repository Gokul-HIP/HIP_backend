<?php

namespace Tests\Feature;

use App\Modules\Workflow\Models\WorkflowTemplate;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTemplateTest extends TestCase
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
                'database/migrations/2026_07_29_100001_create_workflow_blueprint_templates_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(Authenticate::class);
    }

    /** @return array<string, mixed> */
    private function sampleDefinition(): array
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
                    'data' => ['nodeType' => 'prescriptionAdded'],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 400, 'y' => 100],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 't1', 'target' => 'e1'],
            ],
        ];
    }

    public function test_store_preserves_definition_exactly(): void
    {
        $definition = $this->sampleDefinition();

        $response = $this->postJson('/api/admin/workflow-templates', [
            'name' => 'Medicine Reminder',
            'description' => 'Reminder Workflow',
            'module' => 'pharmacy',
            'trigger_type' => 'prescriptionAdded',
            'trigger_label' => 'Prescription Added',
            'status' => 'active',
            'definition' => $definition,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Medicine Reminder')
            ->assertJsonPath('data.module', 'pharmacy')
            ->assertJsonPath('data.node_count', 2)
            ->assertJsonPath('data.definition.nodes.0.id', 't1')
            ->assertJsonPath('data.definition.nodes.0.position.x', 100);

        $this->assertDatabaseHas('workflow_templates', [
            'name' => 'Medicine Reminder',
            'module' => 'pharmacy',
            'slug' => 'medicine-reminder',
        ]);
    }

    public function test_duplicate_appends_copy_and_unique_slug(): void
    {
        $template = WorkflowTemplate::query()->create([
            'name' => 'Medicine Reminder',
            'slug' => 'medicine-reminder',
            'module' => 'pharmacy',
            'trigger_type' => 'prescriptionAdded',
            'status' => 'active',
            'definition' => $this->sampleDefinition(),
        ]);

        $response = $this->postJson("/api/admin/workflow-templates/{$template->id}/duplicate");

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Medicine Reminder (Copy)')
            ->assertJsonPath('data.slug', 'medicine-reminder-copy');
    }

    public function test_preview_returns_counts_and_definition(): void
    {
        $template = WorkflowTemplate::query()->create([
            'name' => 'Medicine Reminder',
            'slug' => 'medicine-reminder',
            'module' => 'pharmacy',
            'trigger_type' => 'prescriptionAdded',
            'trigger_label' => 'Prescription Added',
            'status' => 'active',
            'definition' => $this->sampleDefinition(),
        ]);

        $this->getJson("/api/workflow-templates/{$template->id}/preview")
            ->assertOk()
            ->assertJsonPath('data.name', 'Medicine Reminder')
            ->assertJsonPath('data.node_count', 2)
            ->assertJsonPath('data.edge_count', 1)
            ->assertJsonPath('data.trigger.type', 'prescriptionAdded');
    }

    public function test_soft_delete(): void
    {
        $template = WorkflowTemplate::query()->create([
            'name' => 'Medicine Reminder',
            'slug' => 'medicine-reminder',
            'module' => 'pharmacy',
            'status' => 'active',
            'definition' => $this->sampleDefinition(),
        ]);

        $this->deleteJson("/api/admin/workflow-templates/{$template->id}")
            ->assertOk();

        $this->assertSoftDeleted('workflow_templates', ['id' => $template->id]);
    }
}
