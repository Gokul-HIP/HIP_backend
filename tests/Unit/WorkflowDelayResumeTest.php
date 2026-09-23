<?php

namespace Tests\Unit;

use App\Modules\Workflow\NodeProcessors\Contracts\NodeProcessor;
use App\Modules\Workflow\DTO\ExecutionNode;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\NodeProcessors\ConditionNodeProcessor;
use App\Modules\Workflow\NodeProcessors\DelayNodeProcessor;
use App\Modules\Workflow\NodeProcessors\EndNodeProcessor;
use App\Modules\Workflow\NodeProcessors\AppointmentBookedTriggerNodeProcessor;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Services\Runtime\ActionDispatcher;
use App\Modules\Workflow\Services\Runtime\ConditionEngine;
use App\Modules\Workflow\Services\Runtime\DelayScheduler;
use App\Modules\Workflow\NodeProcessorRegistry;
use App\Modules\Workflow\Services\Runtime\WorkflowExecutor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WorkflowDelayResumeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, int> */
    private array $executionCounts = [];

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
                'database/migrations/2026_07_21_130000_add_draft_version_id_to_workflows_table.php',
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->executionCounts = [];
        Queue::fake();
        $this->bindTestRuntime();
    }

    public function test_delay_schedules_resume_at_next_node_not_delay_itself(): void
    {
        $version = $this->publishDefinition($this->linearDelayDefinition());

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_email' => 'patient@example.com',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $this->assertSame('push1', $execution->current_node_id);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);
        $this->assertSame(1, $this->executionCounts['email'] ?? 0);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, function (ContinueWorkflowExecutionJob $job) use ($execution) {
            return $job->executionId === $execution->id && $job->nodeId === 'push1';
        });

        Queue::assertNotPushed(ContinueWorkflowExecutionJob::class, function (ContinueWorkflowExecutionJob $job) {
            return $job->nodeId === 'delay1';
        });
    }

    public function test_frontend_wait_node_id_resolves_to_delay_executor_at_runtime(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('email1', 'sendEmail'),
                $this->node('wait1', 'wait', ['type' => 'minutes', 'value' => 5]),
                $this->node('push1', 'sendPush'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'email1'),
                $this->edge('e2', 'email1', 'wait1'),
                $this->edge('e3', 'wait1', 'push1'),
                $this->edge('e4', 'push1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);

        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked', [
            'patient_email' => 'patient@example.com',
        ]);

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $this->assertSame('push1', $execution->current_node_id);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0, 'FE wait must invoke DelayNodeProcessor');
        $this->assertSame(1, $this->executionCounts['email'] ?? 0);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, function (ContinueWorkflowExecutionJob $job) use ($execution) {
            return $job->executionId === $execution->id && $job->nodeId === 'push1';
        });
    }

    public function test_delay_executes_only_once_and_workflow_completes_after_resume(): void
    {
        $version = $this->publishDefinition($this->linearDelayDefinition());
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start($version, 'appointmentBooked');

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);

        $resumed = $executor->resume($execution->fresh(), 'push1');

        $this->assertSame(WorkflowExecutionStatus::Completed->value, $resumed->status);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);
        $this->assertSame(1, $this->executionCounts['email'] ?? 0);
        $this->assertSame(1, $this->executionCounts['push'] ?? 0);
        $this->assertSame(1, $this->executionCounts['end'] ?? 0);
    }

    public function test_multiple_sequential_delays_resume_to_next_nodes(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('d1', 'delay', ['type' => 'seconds', 'value' => 1]),
                $this->node('email1', 'sendEmail'),
                $this->node('d2', 'delay', ['type' => 'seconds', 'value' => 2]),
                $this->node('push1', 'sendPush'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'd1'),
                $this->edge('e2', 'd1', 'email1'),
                $this->edge('e3', 'email1', 'd2'),
                $this->edge('e4', 'd2', 'push1'),
                $this->edge('e5', 'push1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start($version, 'appointmentBooked');
        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $this->assertSame('email1', $execution->current_node_id);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, fn ($job) => $job->nodeId === 'email1');

        $afterFirst = $executor->resume($execution->fresh(), 'email1');
        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $afterFirst->status);
        $this->assertSame('push1', $afterFirst->current_node_id);
        $this->assertSame(2, $this->executionCounts['delay'] ?? 0);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, fn ($job) => $job->nodeId === 'push1');

        $completed = $executor->resume($afterFirst->fresh(), 'push1');
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame(2, $this->executionCounts['delay'] ?? 0);
        $this->assertSame(1, $this->executionCounts['push'] ?? 0);
    }

    public function test_delay_followed_by_condition_resumes_at_condition(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('d1', 'delay', ['type' => 'seconds', 'value' => 1]),
                $this->node('c1', 'condition', [
                    'rules' => [],
                ]),
                $this->node('push1', 'sendPush'),
                $this->node('email1', 'sendEmail'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'd1'),
                $this->edge('e2', 'd1', 'c1'),
                $this->edge('e3', 'c1', 'push1', 'true'),
                $this->edge('e4', 'c1', 'email1', 'false'),
                $this->edge('e5', 'push1', 'e1'),
                $this->edge('e6', 'email1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $executor = app(WorkflowExecutor::class);

        $execution = $executor->start($version, 'appointmentBooked');
        $this->assertSame('c1', $execution->current_node_id);

        Queue::assertPushed(ContinueWorkflowExecutionJob::class, fn ($job) => $job->nodeId === 'c1');

        $completed = $executor->resume($execution->fresh(), 'c1');
        $this->assertSame(WorkflowExecutionStatus::Completed->value, $completed->status);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);
        $this->assertSame(1, $this->executionCounts['condition'] ?? 0);
        $this->assertSame(1, $this->executionCounts['push'] ?? 0);
        $this->assertSame(0, $this->executionCounts['email'] ?? 0);
    }

    public function test_delay_followed_by_parallel_branches_schedules_all_resume_targets(): void
    {
        $definition = [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('d1', 'delay', ['type' => 'seconds', 'value' => 5]),
                $this->node('push1', 'sendPush'),
                $this->node('email1', 'sendEmail'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'd1'),
                $this->edge('e2', 'd1', 'push1'),
                $this->edge('e3', 'd1', 'email1'),
                $this->edge('e4', 'push1', 'e1'),
                $this->edge('e5', 'email1', 'e1'),
            ],
        ];

        $version = $this->publishDefinition($definition);
        $execution = app(WorkflowExecutor::class)->start($version, 'appointmentBooked');

        $this->assertSame(WorkflowExecutionStatus::Waiting->value, $execution->status);
        $this->assertSame('push1', $execution->current_node_id);

        $pushedNodeIds = [];
        Queue::assertPushed(ContinueWorkflowExecutionJob::class, function (ContinueWorkflowExecutionJob $job) use ($execution, &$pushedNodeIds) {
            if ($job->executionId === $execution->id) {
                $pushedNodeIds[] = $job->nodeId;
            }

            return true;
        });

        sort($pushedNodeIds);
        $this->assertSame(['email1', 'push1'], $pushedNodeIds);
        $this->assertSame(1, $this->executionCounts['delay'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function linearDelayDefinition(): array
    {
        return [
            'builderVersion' => '1',
            'nodes' => [
                $this->node('t1', 'appointmentBooked'),
                $this->node('email1', 'sendEmail'),
                $this->node('delay1', 'delay', ['type' => 'minutes', 'value' => 5]),
                $this->node('push1', 'sendPush'),
                $this->node('e1', 'end'),
            ],
            'edges' => [
                $this->edge('e1', 't1', 'email1'),
                $this->edge('e2', 'email1', 'delay1'),
                $this->edge('e3', 'delay1', 'push1'),
                $this->edge('e4', 'push1', 'e1'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function publishDefinition(array $definition): WorkflowVersion
    {
        $workflow = Workflow::query()->create([
            'name' => 'Delay Resume Test '.uniqid(),
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
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function node(string $id, string $nodeType, array $extra = []): array
    {
        return [
            'id' => $id,
            'type' => 'workflow',
            'position' => ['x' => 0, 'y' => 0],
            'data' => array_merge(['nodeType' => $nodeType], $extra),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function edge(string $id, string $source, string $target, ?string $sourceHandle = null): array
    {
        $edge = [
            'id' => $id,
            'source' => $source,
            'target' => $target,
        ];

        if ($sourceHandle !== null) {
            $edge['sourceHandle'] = $sourceHandle;
        }

        return $edge;
    }

    private function bindTestRuntime(): void
    {
        $dispatcher = app(ActionDispatcher::class);
        $registry = new NodeProcessorRegistry;

        $registry->register(new CountingExecutor(
            new AppointmentBookedTriggerNodeProcessor($dispatcher),
            $this->executionCounts,
            'trigger'
        ));
        $registry->register(new CountingExecutor(
            new DelayNodeProcessor($dispatcher, app(DelayScheduler::class), app(\App\Modules\Automation\Engine\AutomationFactsBuilder::class)),
            $this->executionCounts,
            'delay'
        ));
        $registry->register(new CountingExecutor(
            new ConditionNodeProcessor($dispatcher, app(ConditionEngine::class), app(\App\Modules\Automation\Engine\AutomationFactsBuilder::class)),
            $this->executionCounts,
            'condition'
        ));
        $registry->register(new CountingExecutor(
            new EndNodeProcessor($dispatcher),
            $this->executionCounts,
            'end'
        ));
        $registry->register(new CountingExecutor(
            new StubContinueExecutor('sendEmail'),
            $this->executionCounts,
            'email'
        ));
        $registry->register(new CountingExecutor(
            new StubContinueExecutor('sendPush'),
            $this->executionCounts,
            'push'
        ));

        $this->app->instance(NodeProcessorRegistry::class, $registry);
        $this->app->forgetInstance(WorkflowExecutor::class);
    }
}

class StubContinueExecutor implements NodeProcessor
{
    public function __construct(private string $type) {}

    public function type(): string
    {
        return $this->type;
    }

    public function execute(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        return NodeExecutionResult::continue();
    }
}

class CountingExecutor implements NodeProcessor
{
    /**
     * @param  array<string, int>  $counts
     */
    public function __construct(
        private NodeProcessor $inner,
        private array &$counts,
        private string $key,
    ) {}

    public function type(): string
    {
        return $this->inner->type();
    }

    public function execute(
        ExecutionNode $node,
        WorkflowExecution $execution,
        WorkflowContext $context
    ): NodeExecutionResult {
        $this->counts[$this->key] = ($this->counts[$this->key] ?? 0) + 1;

        return $this->inner->execute($node, $execution, $context);
    }
}
