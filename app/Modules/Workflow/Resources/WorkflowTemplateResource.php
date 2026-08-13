<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\Workflow\Models\WorkflowTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowTemplate */
class WorkflowTemplateResource extends JsonResource
{
    protected bool $includeDefinition = true;

    public function withDefinition(bool $include = true): static
    {
        $this->includeDefinition = $include;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'module' => $this->module,
            'trigger_type' => $this->trigger_type,
            'trigger_label' => $this->trigger_label,
            'category' => $this->category,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'thumbnail' => $this->thumbnail,
            'node_count' => $this->nodeCount(),
            'edge_count' => $this->edgeCount(),
            'definition' => $this->when(
                $this->includeDefinition && ! $request->routeIs('workflow-templates.index'),
                $this->definition
            ),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
