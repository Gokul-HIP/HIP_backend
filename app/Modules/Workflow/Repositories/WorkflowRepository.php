<?php

namespace App\Modules\Workflow\Repositories;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use Illuminate\Support\Collection;

class WorkflowRepository
{
    public function __construct(
        protected WorkflowCompilerInterface $compiler,
    ) {}

    public function find(int $id): ?Workflow
    {
        return Workflow::query()->find($id);
    }

    /**
     * @return Collection<int, Workflow>
     */
    public function findActiveByTrigger(string $triggerType, ?int $organizationId = null): Collection
    {
        return Workflow::query()
            ->where('status', WorkflowStatus::Active->value)
            ->where(function ($query) use ($triggerType) {
                $query->where('trigger_type', $triggerType)
                    ->orWhereNull('trigger_type');
            })
            ->when($organizationId, fn ($q) => $q->where('organization_id', $organizationId))
            ->with('currentVersion')
            ->get();
    }

    public function publishVersion(
        Workflow $workflow,
        array $definition,
        ?string $publishedBy = null,
        ?string $versionNotes = null
    ): WorkflowVersion {
        $nextVersion = ((int) $workflow->versions()->where('status', 'published')->max('version_number')) + 1;
        if ($nextVersion < 1) {
            $nextVersion = 1;
        }

        $compiled = $this->compiler->compile($definition);

        $version = WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => $nextVersion,
            'definition' => $definition,
            'compiled_graph' => [
                'start_node_id' => $compiled->graph->startNodeId,
                'end_node_ids' => $compiled->graph->endNodeIds,
                'branch_node_ids' => $compiled->graph->branchNodeIds,
                'has_loops' => $compiled->graph->hasLoops,
                'metadata' => $compiled->graph->metadata,
            ],
            'status' => 'published',
            'published_by' => $publishedBy,
            'published_at' => now(),
        ]);

        $workflow->update([
            'current_version_id' => $version->id,
            'status' => WorkflowStatus::Active->value,
        ]);

        return $version;
    }
}
