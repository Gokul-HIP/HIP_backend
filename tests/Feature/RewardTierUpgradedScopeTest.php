<?php

namespace Tests\Feature;

use App\Models\RewardTier;
use App\Models\RewardTierConfig;
use App\Modules\Automation\Engine\AutomationEngine;
use App\Modules\Automation\Events\RewardTierUpgraded;
use App\Modules\Automation\Support\PatientBookingScope;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\NodeProcessors\AbstractNodeProcessor;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Repositories\WorkflowRepository;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Services\RewardTierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RewardTierUpgradedScopeTest extends TestCase
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
                'database/migrations/2026_07_21_100000_create_workflows_table.php',
                'database/migrations/2026_07_21_100001_create_workflow_versions_table.php',
                'database/migrations/2026_07_21_100002_create_workflow_executions_table.php',
                'database/migrations/2026_07_21_100003_create_communication_logs_table.php',
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('workflows', 'hospital_id')) {
            Schema::table('workflows', function ($table) {
                $table->unsignedBigInteger('hospital_id')->nullable();
            });
        }

        $this->bootBookingTables();
        $this->stubSendPushNodeProcessor();
    }

    public function test_most_recent_booking_resolves_hospital_and_organization_from_that_hospital(): void
    {
        $this->organization(1);
        $this->organization(2);
        $this->hospital(10, 1);
        $this->hospital(20, 2);

        DB::table('doctor_bookings')->insert([
            'id' => 1,
            'member_id' => 'user-scope',
            'patient_id' => 'person-1',
            'hospital_id' => 10,
            'branch_id' => 10,
            'status' => 'confirmed',
            'created_at' => '2026-10-01 10:00:00',
            'updated_at' => '2026-10-01 10:00:00',
        ]);
        DB::table('diagnostic_test_bookings')->insert([
            'id' => 2,
            'member_id' => 'user-scope',
            'patient_id' => 'person-1',
            'branch_id' => 20,
            'status' => 'confirmed',
            'created_at' => '2026-10-20 10:00:00',
            'updated_at' => '2026-10-20 10:00:00',
        ]);
        DB::table('second_opinions')->insert([
            'id' => 3,
            'member_id' => 'user-scope',
            'patient_id' => 'person-1',
            'branch_id' => 10,
            'status' => 'cancelled',
            'created_at' => '2026-10-22 10:00:00',
            'updated_at' => '2026-10-22 10:00:00',
        ]);

        $scope = PatientBookingScope::forHipUser('user-scope');

        $this->assertNotNull($scope);
        $this->assertSame(20, $scope['hospital_id']);
        $this->assertSame(2, $scope['organization_id']);
        $this->assertSame('diagnostic_test_booking', $scope['source']);
    }

    public function test_upgrade_runs_only_the_matching_hospital_workflow_and_ignores_a_duplicate(): void
    {
        $this->organization(2);
        $this->hospital(20, 2);
        $this->hospital(99, 2);

        DB::table('persons')->insert([
            'id' => 'person-1',
            'hip_user_id' => 'user-scope',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_bookings')->insert([
            'id' => 5,
            'member_id' => 'user-scope',
            'patient_id' => 'person-1',
            'hospital_id' => 20,
            'status' => 'confirmed',
            'created_at' => '2026-10-21 09:00:00',
            'updated_at' => '2026-10-21 09:00:00',
        ]);

        $match = $this->publishWorkflow(2, 20, 'Match');
        $other = $this->publishWorkflow(2, 99, 'Other hospital');

        $this->seedTiers();
        app(RewardTierService::class)->enrollUserOnRegister('user-scope');
        app(RewardTierService::class)->addEarnedCoins('user-scope', 100);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $match->id)->count());
        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $other->id)->count());
        $this->assertSame(20, (int) data_get(WorkflowExecution::query()->where('workflow_id', $match->id)->first()?->context, 'hospital_id'));
        $this->assertSame(2, (int) data_get(WorkflowExecution::query()->where('workflow_id', $match->id)->first()?->context, 'organization_id'));

        $execution = WorkflowExecution::query()->where('workflow_id', $match->id)->first();
        $occurrenceId = data_get($execution?->context, 'event_occurrence_id');
        app(AutomationEngine::class)->handle('rewardTierUpgraded', [
            'hip_user_id' => 'user-scope',
            'hospital_id' => 20,
            'organization_id' => 2,
            'event_occurrence_id' => $occurrenceId,
            'previous_tier_id' => 1,
            'new_tier_id' => 2,
        ]);

        $this->assertSame(1, WorkflowExecution::query()->where('workflow_id', $match->id)->count());
    }

    public function test_patient_without_a_booking_is_not_assigned_a_hospital(): void
    {
        $this->organization(2);
        $this->hospital(20, 2);
        $workflow = $this->publishWorkflow(2, 20, 'Should not run');

        $this->assertNull(PatientBookingScope::forHipUser('user-none'));

        app(AutomationEngine::class)->handle('rewardTierUpgraded', [
            'hip_user_id' => 'user-none',
            'previous_tier_id' => 1,
            'new_tier_id' => 2,
            'event_occurrence_id' => RewardTierUpgraded::occurrenceId('user-none', 1, 2),
        ]);

        $this->assertSame(0, WorkflowExecution::query()->where('workflow_id', $workflow->id)->count());
    }

    private function organization(int $id): void
    {
        DB::table('organizations')->insert([
            'id' => $id,
            'org_name' => 'Org '.$id,
            'org_city' => 'City',
            'org_address' => 'Address',
            'org_logo' => 'logo',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function hospital(int $id, int $organizationId): void
    {
        DB::table('hospitals')->insert([
            'id' => $id,
            'organization_id' => $organizationId,
            'name' => 'Hospital '.$id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function publishWorkflow(int $organizationId, int $hospitalId, string $name): Workflow
    {
        $workflow = Workflow::query()->create([
            'name' => $name,
            'status' => WorkflowStatus::Draft->value,
            'trigger_type' => 'rewardTierUpgraded',
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
        ]);

        app(WorkflowRepository::class)->publishVersion($workflow, [
            'builderVersion' => '1',
            'reactFlowVersion' => '12.x',
            'nodes' => [
                [
                    'id' => 't1',
                    'type' => 'workflow',
                    'position' => ['x' => 0, 'y' => 0],
                    'data' => ['category' => 'triggers', 'nodeType' => 'rewardTierUpgraded', 'label' => 'Tier'],
                ],
                [
                    'id' => 'p1',
                    'type' => 'workflow',
                    'position' => ['x' => 200, 'y' => 0],
                    'data' => ['category' => 'messaging', 'nodeType' => 'sendPush', 'title' => 'Upgraded', 'body' => 'Tier'],
                ],
                [
                    'id' => 'e1',
                    'type' => 'workflow',
                    'position' => ['x' => 400, 'y' => 0],
                    'data' => ['nodeType' => 'end'],
                ],
            ],
            'edges' => [
                ['id' => 'e-t-p', 'source' => 't1', 'target' => 'p1'],
                ['id' => 'e-p-e', 'source' => 'p1', 'target' => 'e1'],
            ],
        ]);

        return $workflow->fresh(['currentVersion']);
    }

    private function seedTiers(): void
    {
        $bronze = RewardTier::query()->create([
            'name' => 'Bronze',
            'min_coins' => 0,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $bronze->id,
            'total_discount_percentage' => 0,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
        $silver = RewardTier::query()->create([
            'name' => 'Silver',
            'min_coins' => 100,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        RewardTierConfig::query()->create([
            'reward_tier_id' => $silver->id,
            'total_discount_percentage' => 5,
            'free_checkup_count' => 0,
            'earned_coins_per_booking' => 0,
        ]);
    }

    private function stubSendPushNodeProcessor(): void
    {
        $registry = $this->app->make(NodeProcessorRegistry::class);
        $dispatcher = $this->app->make(ActionDispatcher::class);

        $registry->register(new class($dispatcher) extends AbstractNodeProcessor
        {
            public function type(): string
            {
                return 'sendPush';
            }

            protected function run(
                ExecutionNode $node,
                WorkflowExecution $execution,
                WorkflowContext $context
            ): NodeExecutionResult {
                return NodeExecutionResult::continue();
            }
        });
    }

    private function bootBookingTables(): void
    {
        Schema::create('hospitals', function ($table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('persons', function ($table) {
            $table->string('id')->primary();
            $table->string('hip_user_id')->nullable();
            $table->timestamps();
        });
        Schema::create('doctor_bookings', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->boolean('is_follow_up')->default(false);
            $table->date('booking_date')->nullable();
            $table->timestamps();
        });
        Schema::create('diagnostic_test_bookings', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('second_opinions', function ($table) {
            $table->id();
            $table->string('member_id')->nullable();
            $table->string('patient_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
        Schema::create('reward_tiers', function ($table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('min_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('reward_tier_configs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('reward_tier_id');
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });
        Schema::create('user_reward_progress', function ($table) {
            $table->id();
            $table->string('hip_user_id')->unique();
            $table->unsignedBigInteger('reward_tier_id')->nullable();
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();
        });
    }
}
