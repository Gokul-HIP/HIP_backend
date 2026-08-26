<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\DTO\CompiledWorkflow;
use App\Modules\Workflow\DTO\ExecutionEdge;
use App\Modules\Workflow\DTO\NodeExecutionResult;
use App\Modules\Workflow\DTO\WorkflowContext;
use App\Modules\Workflow\Enums\WorkflowExecutionStatus;
use App\Modules\Workflow\Jobs\ContinueWorkflowExecutionJob;
use App\Modules\Workflow\Models\WorkflowExecution;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Support\Facades\Log;

class WorkflowExecutor
{
    public function __construct(
        protected NodeExecutorRegistry $registry,
        protected DelayScheduler $delayScheduler,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function start(WorkflowVersion $version, string $triggerType, array $payload = []): WorkflowExecution
    {
        $compiled = $this->compileVersion($version);

        $execution = WorkflowExecution::query()->create([
            'workflow_id' => $version->workflow_id,
            'workflow_version_id' => $version->id,
            'status' => WorkflowExecutionStatus::Running->value,
            'trigger_type' => $triggerType,
            'current_node_id' => $compiled->graph->startNodeId,
            'context' => $payload,
            'variables' => [],
            'started_at' => now(),
        ]);

        $context = new WorkflowContext($triggerType, $payload);

        Log::info('Workflow execution started', [
            'execution_id' => $execution->id,
            'workflow_id' => $version->workflow_id,
            'trigger_type' => $triggerType,
        ]);

        $this->runFromNode($execution, $compiled, $compiled->graph->startNodeId, $context);

        return $execution->fresh();
    }

    public function resume(WorkflowExecution $execution, ?string $nodeId = null): WorkflowExecution
    {
        $execution->loadMissing('version');
        $compiled = $this->compileVersion($execution->version);

        $targetNodeId = $nodeId ?? $execution->current_node_id ?? $compiled->graph->startNodeId;

        $context = new WorkflowContext(
            triggerType: (string) $execution->trigger_type,
            payload: is_array($execution->context) ? $execution->context : [],
            variables: is_array($execution->variables) ? $execution->variables : [],
        );

        $execution->update([
            'status' => WorkflowExecutionStatus::Running->value,
            'current_node_id' => $targetNodeId,
        ]);

        $this->runFromNode($execution->fresh(), $compiled, $targetNodeId, $context);

        return $execution->fresh();
    }

    protected function runFromNode(
        WorkflowExecution $execution,
        CompiledWorkflow $compiled,
        string $nodeId,
        WorkflowContext $context
    ): void {
        $visitedInPass = 0;
        $currentNodeId = $nodeId;

        while ($currentNodeId !== null && $currentNodeId !== '') {
            $visitedInPass++;

            if ($visitedInPass > 100) {
                $this->failExecution($execution, 'Workflow exceeded maximum node traversal limit.');

                return;
            }

            $node = $compiled->graph->node($currentNodeId);

            if (! $node) {
                $this->failExecution($execution, "Node not found: {$currentNodeId}");

                return;
            }

            $execution->update(['current_node_id' => $currentNodeId]);

            try {
                $executor = $this->registry->get($node->nodeType);
                $result = $executor->execute($node, $execution, $context);
            } catch (\Throwable $e) {
                report($e);
                $this->failExecution($execution, $e->getMessage());

                return;
            }

            $execution->update(['variables' => $context->variables]);

            if ($result->status === 'failed') {
                $this->failExecution($execution, $result->message ?? 'Node execution failed');

                return;
            }

            if ($result->status === 'waiting') {
                $this->pauseForDelay($execution, $compiled, $currentNodeId, $result, $context);

                return;
            }

            if ($result->status === 'completed' || NodeTypeNormalizer::isEnd($node->nodeType)) {
                $this->completeExecution($execution);

                return;
            }

            $nextNodeIds = $this->resolveNextNodeIds($compiled, $currentNodeId, $result);

            if ($nextNodeIds === []) {
                $this->completeExecution($execution);

                return;
            }

            if (count($nextNodeIds) === 1) {
                $currentNodeId = $nextNodeIds[0];
                continue;
            }

            foreach (array_slice($nextNodeIds, 1) as $parallelNodeId) {
                ContinueWorkflowExecutionJob::dispatch($execution->id, $parallelNodeId);
            }

            $currentNodeId = $nextNodeIds[0];
        }

        $this->completeExecution($execution);
    }

    /**
     * Pause on a Delay node and schedule resume at the next executable node(s).
     *
     * The Delay node itself must not be the resume target — otherwise resume
     * re-enters DelayExecutor and loops forever.
     */
    protected function pauseForDelay(
        WorkflowExecution $execution,
        CompiledWorkflow $compiled,
        string $delayNodeId,
        NodeExecutionResult $result,
        WorkflowContext $context
    ): void {
        $resumeNodeIds = $this->resolveNextNodeIds($compiled, $delayNodeId, $result);

        if ($resumeNodeIds === []) {
            $this->completeExecution($execution);

            return;
        }

        $primaryResumeNodeId = $resumeNodeIds[0];
        $delaySeconds = $result->delaySeconds ?? 0;

        $execution->update([
            'status' => WorkflowExecutionStatus::Waiting->value,
            'current_node_id' => $primaryResumeNodeId,
        ]);

        foreach ($resumeNodeIds as $resumeNodeId) {
            $this->delayScheduler->scheduleResume(
                $execution->id,
                $resumeNodeId,
                $delaySeconds,
                $context
            );
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveNextNodeIds(
        CompiledWorkflow $compiled,
        string $currentNodeId,
        NodeExecutionResult $result
    ): array {
        if ($result->nextNodeIds !== []) {
            return $result->nextNodeIds;
        }

        $outgoing = $compiled->graph->outgoingEdges($currentNodeId);

        if (isset($result->output['handle'])) {
            $handle = (string) $result->output['handle'];

            $matched = array_values(array_filter(
                $outgoing,
                fn (ExecutionEdge $edge) => ($edge->sourceHandle ?? $handle) === $handle
            ));

            if ($matched !== []) {
                return array_map(fn (ExecutionEdge $edge) => $edge->target, $matched);
            }
        }

        return array_map(
            fn (ExecutionEdge $edge) => $edge->target,
            $outgoing
        );
    }

    protected function compileVersion(WorkflowVersion $version): CompiledWorkflow
    {
        if (is_array($version->compiled_graph) && $version->compiled_graph !== []) {
            return $this->hydrateCompiledWorkflow($version->definition ?? [], $version->compiled_graph);
        }

        return app(\App\Modules\Workflow\Contracts\WorkflowCompilerInterface::class)
            ->compile($version->definition ?? []);
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $cached
     */
    protected function hydrateCompiledWorkflow(array $definition, array $cached): CompiledWorkflow
    {
        return app(\App\Modules\Workflow\Contracts\WorkflowCompilerInterface::class)
            ->compile($definition);
    }

    protected function completeExecution(WorkflowExecution $execution): void
    {
        $startedAt = $execution->started_at;
        $durationMs = $startedAt ? (int) $startedAt->diffInMilliseconds(now()) : null;

        $execution->update([
            'status' => WorkflowExecutionStatus::Completed->value,
            'completed_at' => now(),
            'duration_ms' => $durationMs,
            'current_node_id' => null,
        ]);

        Log::info('Workflow execution completed', [
            'execution_id' => $execution->id,
            'workflow_id' => $execution->workflow_id,
        ]);
    }

    protected function failExecution(WorkflowExecution $execution, string $reason): void
    {
        $startedAt = $execution->started_at;
        $durationMs = $startedAt ? (int) $startedAt->diffInMilliseconds(now()) : null;

        $execution->update([
            'status' => WorkflowExecutionStatus::Failed->value,
            'completed_at' => now(),
            'duration_ms' => $durationMs,
            'failure_reason' => $reason,
        ]);

        Log::warning('Workflow execution failed', [
            'execution_id' => $execution->id,
            'reason' => $reason,
        ]);
    }
}
