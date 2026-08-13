<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\HospitalAutomation\Support\TriggerCatalog;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowVersion;
use App\Modules\Workflow\Support\NodeTypeNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkflowBuilderService
{
    public const DRAFT_VERSION_NUMBER = 0;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Workflow::query()
            ->with(['currentVersion:id,workflow_id,version_number,published_at', 'draftVersion:id,workflow_id,version_number,updated_at'])
            ->when(isset($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['trigger_type']), fn ($q) => $q->where('trigger_type', $filters['trigger_type']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = '%'.$filters['search'].'%';
                $q->where('name', 'like', $search);
            })
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Workflow
    {
        $workflow = Workflow::query()
            ->with(['currentVersion', 'draftVersion'])
            ->find($id);

        if (! $workflow) {
            throw new InvalidArgumentException('Workflow not found.');
        }

        return $workflow;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?string $actorId = null): Workflow
    {
        return DB::transaction(function () use ($data, $actorId) {
            $configuration = $this->extractConfiguration($data);
            $triggerType = $this->detectTriggerType($configuration);

            $workflow = Workflow::query()->create([
                'organization_id' => $data['organization_id'] ?? null,
                'name' => $data['name'],
                'status' => WorkflowStatus::Draft->value,
                'trigger_type' => $triggerType,
                'created_by' => $actorId ?? ($data['created_by'] ?? null),
            ]);

            $draft = $this->upsertDraft($workflow, $configuration, $actorId);
            $workflow->update(['draft_version_id' => $draft->id]);

            return $workflow->fresh(['currentVersion', 'draftVersion']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Workflow $workflow, array $data, ?string $actorId = null): Workflow
    {
        return DB::transaction(function () use ($workflow, $data, $actorId) {
            $updates = [];

            if (isset($data['name'])) {
                $updates['name'] = $data['name'];
            }

            if (array_key_exists('organization_id', $data)) {
                $updates['organization_id'] = $data['organization_id'];
            }

            if (isset($data['configuration']) && is_array($data['configuration'])) {
                $updates['trigger_type'] = $this->detectTriggerType($data['configuration']);
                $draft = $this->upsertDraft($workflow, $data['configuration'], $actorId);
                $updates['draft_version_id'] = $draft->id;
            }

            if ($updates !== []) {
                $workflow->update($updates);
            }

            return $workflow->fresh(['currentVersion', 'draftVersion']);
        });
    }

    public function delete(Workflow $workflow): bool
    {
        return (bool) $workflow->delete();
    }

    /**
     * Independent copy: draft status, new id, graph cloned from draft or published version.
     * Does not copy published versions or execution history.
     */
    public function duplicate(Workflow $source, ?string $actorId = null): Workflow
    {
        $source->loadMissing(['draftVersion', 'currentVersion']);

        $configuration = $source->draftVersion?->definition
            ?? $source->currentVersion?->definition;

        if (! is_array($configuration)) {
            throw new InvalidArgumentException('Cannot duplicate workflow without a saved configuration.');
        }

        return $this->create([
            'name' => $source->name.' (Copy)',
            'organization_id' => $source->organization_id,
            'configuration' => $configuration,
            'created_by' => $actorId,
        ], $actorId);
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    protected function upsertDraft(Workflow $workflow, array $configuration, ?string $actorId = null): WorkflowVersion
    {
        $draft = $workflow->draftVersion;

        if ($draft) {
            $draft->update([
                'definition' => $configuration,
                'published_by' => $actorId,
            ]);

            return $draft->fresh();
        }

        return WorkflowVersion::query()->create([
            'workflow_id' => $workflow->id,
            'version_number' => self::DRAFT_VERSION_NUMBER,
            'definition' => $configuration,
            'compiled_graph' => null,
            'status' => 'draft',
            'published_by' => $actorId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractConfiguration(array $data): array
    {
        if (! isset($data['configuration']) || ! is_array($data['configuration'])) {
            throw new InvalidArgumentException('Workflow configuration is required.');
        }

        return $data['configuration'];
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    public function detectTriggerType(array $configuration): ?string
    {
        foreach ($configuration['nodes'] ?? [] as $node) {
            if (! is_array($node)) {
                continue;
            }

            $data = is_array($node['data'] ?? null) ? $node['data'] : [];
            // Always normalize — frontend may send onChatMessage, pharmacyRefillDue, etc.
            $nodeType = NodeTypeNormalizer::normalize((string) ($data['nodeType'] ?? $node['type'] ?? ''));

            if (NodeTypeNormalizer::isTrigger($nodeType)) {
                return $nodeType;
            }
        }

        return null;
    }

    public function moduleFor(Workflow $workflow): ?string
    {
        return $workflow->trigger_type
            ? TriggerCatalog::moduleFor($workflow->trigger_type)
            : null;
    }
}
