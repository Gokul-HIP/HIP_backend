<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Models\WorkflowTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WorkflowTemplateService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowTemplate::query()
            ->when(isset($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['module']), fn ($q) => $q->where('module', $filters['module']))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->when(isset($filters['trigger_type']), fn ($q) => $q->where('trigger_type', $filters['trigger_type']))
            ->when(isset($filters['search']), function ($q) use ($filters) {
                $search = '%'.$filters['search'].'%';
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', $search)
                        ->orWhere('description', 'like', $search)
                        ->orWhere('slug', 'like', $search);
                });
            })
            ->latest('updated_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): WorkflowTemplate
    {
        $template = WorkflowTemplate::query()->find($id);

        if (! $template) {
            throw new InvalidArgumentException('Workflow template not found.');
        }

        return $template;
    }

    /**
     * Store definition exactly as received — no node/edge transforms.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?string $actorId = null): WorkflowTemplate
    {
        $name = (string) $data['name'];
        $slug = $this->uniqueSlug($data['slug'] ?? $name);

        return WorkflowTemplate::query()->create([
            'organization_id' => $data['organization_id'] ?? null,
            'name' => $name,
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'module' => $data['module'],
            'trigger_type' => $data['trigger_type'] ?? null,
            'trigger_label' => $data['trigger_label'] ?? null,
            'category' => $data['category'] ?? null,
            'definition' => $data['definition'],
            'thumbnail' => $data['thumbnail'] ?? null,
            'status' => $data['status'] ?? WorkflowStatus::Active->value,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkflowTemplate $template, array $data, ?string $actorId = null): WorkflowTemplate
    {
        $updates = [];

        foreach ([
            'organization_id',
            'name',
            'description',
            'module',
            'trigger_type',
            'trigger_label',
            'category',
            'thumbnail',
            'status',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (array_key_exists('definition', $data)) {
            $updates['definition'] = $data['definition'];
        }

        if (array_key_exists('slug', $data) && filled($data['slug'])) {
            $updates['slug'] = $this->uniqueSlug((string) $data['slug'], $template->id);
        } elseif (isset($updates['name']) && $updates['name'] !== $template->name) {
            $updates['slug'] = $this->uniqueSlug((string) $updates['name'], $template->id);
        }

        if ($actorId !== null) {
            $updates['updated_by'] = $actorId;
        }

        if ($updates !== []) {
            $template->update($updates);
        }

        return $template->fresh();
    }

    public function delete(WorkflowTemplate $template): void
    {
        $template->delete();
    }

    public function duplicate(WorkflowTemplate $template, ?string $actorId = null): WorkflowTemplate
    {
        $copyName = $template->name.' (Copy)';

        return WorkflowTemplate::query()->create([
            'organization_id' => $template->organization_id,
            'name' => $copyName,
            'slug' => $this->uniqueSlug($copyName),
            'description' => $template->description,
            'module' => $template->module,
            'trigger_type' => $template->trigger_type,
            'trigger_label' => $template->trigger_label,
            'category' => $template->category,
            'definition' => $template->definition,
            'thumbnail' => $template->thumbnail,
            'status' => $template->status instanceof WorkflowStatus
                ? $template->status->value
                : $template->status,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function preview(WorkflowTemplate $template): array
    {
        return [
            'name' => $template->name,
            'module' => $template->module,
            'trigger' => [
                'type' => $template->trigger_type,
                'label' => $template->trigger_label,
            ],
            'node_count' => $template->nodeCount(),
            'edge_count' => $template->edgeCount(),
            'definition' => $template->definition,
        ];
    }

    public function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        if ($base === '') {
            $base = 'workflow-template';
        }

        $slug = $base;
        $suffix = 1;

        while (
            WorkflowTemplate::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
