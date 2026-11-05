<?php

namespace Tests\Feature;

use App\Livewire\Admin\Automation\Workflows\Builder;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Organization;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWorkflowBuilderLivewireTest extends TestCase
{
    use RefreshDatabase;

    private Hospital $hospital1;
    private Hospital $hospital2;
    private Organization $org1;
    private Organization $org2;

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

        if (! Schema::hasTable('healthinpocket_users')) {
            Schema::create('healthinpocket_users', function (Blueprint $table): void {
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

        $this->org1 = Organization::forceCreate([
            'id' => 10,
            'org_name' => 'Org Ten',
            'org_city' => 'City 1',
            'org_address' => 'Addr 1',
            'org_logo' => 'logo1.png',
            'status' => 'active',
        ]);

        $this->org2 = Organization::forceCreate([
            'id' => 20,
            'org_name' => 'Org Twenty',
            'org_city' => 'City 2',
            'org_address' => 'Addr 2',
            'org_logo' => 'logo2.png',
            'status' => 'active',
        ]);

        $this->hospital1 = Hospital::forceCreate([
            'id' => 101,
            'hospital_name' => 'Hospital One',
            'hospital_address' => 'Addr 1',
            'hospital_logo' => 'logo1.png',
            'hospital_admin_name' => 'Admin 1',
            'hospital_admin_contact' => '1111111111',
            'hospital_admin_email' => 'admin1@hosp.com',
            'hospital_admin_address' => 'Admin Addr 1',
            'hospital_admin_longitude' => '0',
            'hospital_admin_latitude' => '0',
            'organization_id' => $this->org1->id,
            'status' => 'active',
        ]);

        $this->hospital2 = Hospital::forceCreate([
            'id' => 202,
            'hospital_name' => 'Hospital Two',
            'hospital_address' => 'Addr 2',
            'hospital_logo' => 'logo2.png',
            'hospital_admin_name' => 'Admin 2',
            'hospital_admin_contact' => '2222222222',
            'hospital_admin_email' => 'admin2@hosp.com',
            'hospital_admin_address' => 'Admin Addr 2',
            'hospital_admin_longitude' => '0',
            'hospital_admin_latitude' => '0',
            'organization_id' => $this->org2->id,
            'status' => 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
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
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 300, 'y' => 100],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'edge-1', 'source' => 't1', 'target' => 'e1'],
            ],
        ];
    }

    public function test_hospital_admin_same_hospital_saves_and_publishes(): void
    {
        $hospitalAdmin = new HIPUser([
            'id' => (string) Str::uuid(),
            'email' => 'hosp1_admin@example.com',
            'hospital_id' => $this->hospital1->id,
            'organization_id' => $this->org1->id,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($hospitalAdmin, 'filament');

        // 1. Create through Livewire
        $createPayload = [
            'name' => 'Hospital Admin Created Workflow',
            'configuration' => $this->validConfiguration(),
            // Even if hospital_id is omitted or passed, it should be forced to hospital1
            'hospital_id' => $this->hospital1->id,
        ];

        Livewire::test(Builder::class)
            ->call('saveFromBuilder', $createPayload);

        $workflow = Workflow::query()->where('name', 'Hospital Admin Created Workflow')->first();
        $this->assertNotNull($workflow);
        $this->assertSame((int) $this->hospital1->id, (int) $workflow->hospital_id);
        $this->assertSame((int) $this->org1->id, (int) $workflow->organization_id);
        $this->assertSame(WorkflowStatus::Draft->value, $workflow->status);

        // 2. Publish through Livewire
        Livewire::test(Builder::class, ['id' => $workflow->id])
            ->call('publishFromBuilder', [
                'name' => 'Hospital Admin Created Workflow',
                'configuration' => $this->validConfiguration(),
            ]);

        $workflow->refresh();
        $this->assertSame(WorkflowStatus::Active->value, $workflow->status);
        $this->assertNotNull($workflow->current_version_id);
    }

    public function test_hospital_admin_tampering_hospital_id_to_another_hospital_is_denied(): void
    {
        $hospitalAdmin = new HIPUser([
            'id' => (string) Str::uuid(),
            'email' => 'hosp1_admin@example.com',
            'hospital_id' => $this->hospital1->id,
            'organization_id' => $this->org1->id,
            'role' => 'hospital_admin',
        ]);

        $this->actingAs($hospitalAdmin, 'filament');

        // Attempt 1: Create tampering hospital_id to hospital2
        Livewire::test(Builder::class)
            ->set('hospitalId', $this->hospital2->id)
            ->call('saveFromBuilder', [
                'name' => 'Illegal Tampered Workflow',
                'hospital_id' => $this->hospital2->id,
                'configuration' => $this->validConfiguration(),
            ]);

        $this->assertNull(Workflow::query()->where('name', 'Illegal Tampered Workflow')->first());

        // Attempt 2: Edit a workflow belonging to hospital2
        $otherWorkflow = app(WorkflowBuilderService::class)->create([
            'name' => 'Original Hospital 2 Workflow',
            'hospital_id' => $this->hospital2->id,
            'organization_id' => $this->org2->id,
            'configuration' => $this->validConfiguration(),
        ]);

        Livewire::test(Builder::class, ['id' => $otherWorkflow->id])
            ->call('saveFromBuilder', [
                'name' => 'Attempted Hack of Hospital 2 Workflow',
                'hospital_id' => $this->hospital2->id,
                'configuration' => $this->validConfiguration(),
            ]);

        $otherWorkflow->refresh();
        $this->assertSame('Original Hospital 2 Workflow', $otherWorkflow->name);

        // Attempt 3: Publish workflow belonging to hospital2
        Livewire::test(Builder::class, ['id' => $otherWorkflow->id])
            ->call('publishFromBuilder', [
                'name' => 'Original Hospital 2 Workflow',
                'configuration' => $this->validConfiguration(),
            ]);

        $otherWorkflow->refresh();
        $this->assertNotSame(WorkflowStatus::Active->value, $otherWorkflow->status);
    }

    public function test_super_admin_allowed_to_save_and_publish_any_hospital(): void
    {
        $superAdmin = new HIPUser([
            'id' => (string) Str::uuid(),
            'email' => 'super_admin@example.com',
            'hospital_id' => null,
            'organization_id' => null,
            'role' => 'super-admin',
        ]);

        $this->actingAs($superAdmin, 'filament');

        // Create workflow for hospital2
        Livewire::test(Builder::class)
            ->call('saveFromBuilder', [
                'name' => 'Super Admin Hospital 2 Workflow',
                'hospital_id' => $this->hospital2->id,
                'configuration' => $this->validConfiguration(),
            ]);

        $workflow = Workflow::query()->where('name', 'Super Admin Hospital 2 Workflow')->first();
        $this->assertNotNull($workflow);
        $this->assertSame((int) $this->hospital2->id, (int) $workflow->hospital_id);

        // Publish workflow
        Livewire::test(Builder::class, ['id' => $workflow->id])
            ->call('publishFromBuilder', [
                'name' => 'Super Admin Hospital 2 Workflow',
                'configuration' => $this->validConfiguration(),
            ]);

        $workflow->refresh();
        $this->assertSame(WorkflowStatus::Active->value, $workflow->status);
    }

    public function test_user_with_neither_hospital_nor_organization_is_denied(): void
    {
        $unscopedUser = new HIPUser([
            'id' => (string) Str::uuid(),
            'email' => 'unscoped_user@example.com',
            'hospital_id' => null,
            'organization_id' => null,
            'role' => 'staff',
        ]);

        $this->actingAs($unscopedUser, 'filament');

        // 1. Create denied
        Livewire::test(Builder::class)
            ->call('saveFromBuilder', [
                'name' => 'Unscoped User Workflow',
                'hospital_id' => $this->hospital1->id,
                'configuration' => $this->validConfiguration(),
            ]);

        $this->assertNull(Workflow::query()->where('name', 'Unscoped User Workflow')->first());

        // 2. Publish denied
        $existingWorkflow = app(WorkflowBuilderService::class)->create([
            'name' => 'Existing Hospital 1 Workflow',
            'hospital_id' => $this->hospital1->id,
            'organization_id' => $this->org1->id,
            'configuration' => $this->validConfiguration(),
        ]);

        Livewire::test(Builder::class, ['id' => $existingWorkflow->id])
            ->call('publishFromBuilder', [
                'name' => 'Existing Hospital 1 Workflow',
                'configuration' => $this->validConfiguration(),
            ]);

        $existingWorkflow->refresh();
        $this->assertNotSame(WorkflowStatus::Active->value, $existingWorkflow->status);
    }
}
