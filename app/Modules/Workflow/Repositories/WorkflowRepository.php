<?php

namespace App\Modules\Workflow\Repositories;

use App\Modules\Workflow\Contracts\WorkflowCompilerInterface;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
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
     * Published (active + current_version) workflows for a canonical trigger.
     *
     * Hospital isolation is exact: when $hospitalId is provided, only workflows
     * with that hospital_id are returned. There is no null-hospital / global fallback.
     *
     * @return Collection<int, Workflow>
     */
    public function findPublishedByTrigger(
        string $triggerType,
        ?int $organizationId = null,
        ?int $hospitalId = null
    ): Collection {
        $canonical = NodeTypeNormalizer::normalize($triggerType);

        return Workflow::query()
            ->where('status', WorkflowStatus::Active->value)
            ->whereNotNull('current_version_id')
            ->where('trigger_type', $canonical)
            ->when(
                $organizationId !== null,
                function ($query) use ($organizationId) {
                    $query->where(function ($inner) use ($organizationId) {
                        $inner->where('organization_id', $organizationId)
                            ->orWhereNull('organization_id');
                    });
                },
                function ($query) {
                    $query->whereNull('organization_id');
                }
            )
            ->when(
                $hospitalId !== null,
                fn ($query) => $query->where('hospital_id', $hospitalId),
                fn ($query) => $query->whereNull('hospital_id')
            )
            ->with('currentVersion')
            ->get()
            ->filter(fn (Workflow $workflow) => $workflow->currentVersion !== null)
            ->values();
    }

    /**
     * @return Collection<int, Workflow>
     */
    public function findActiveByTrigger(
        string $triggerType,
        ?int $organizationId = null,
        ?int $hospitalId = null
    ): Collection {
        return $this->findPublishedByTrigger($triggerType, $organizationId, $hospitalId);
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
