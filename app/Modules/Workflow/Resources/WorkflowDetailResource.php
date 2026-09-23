<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\Automation\Support\TriggerCatalog;
use App\Modules\Workflow\Models\Workflow;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workflow */
class WorkflowDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        $configuration = $this->draftVersion?->definition
            ?? $this->currentVersion?->definition
            ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'module' => $this->trigger_type ? TriggerCatalog::moduleFor($this->trigger_type) : null,
            'trigger_type' => $this->trigger_type,
            'trigger_label' => $this->trigger_type ? TriggerCatalog::labelFor($this->trigger_type) : null,
            'status' => $this->status,
            'organization_id' => $this->organization_id,
            'hospital_id' => $this->hospital_id,
            'configuration' => $configuration,
            'draft_version' => $this->draftVersion ? [
                'id' => $this->draftVersion->id,
                'version_number' => $this->draftVersion->version_number,
                'status' => $this->draftVersion->status,
                'updated_at' => $this->draftVersion->updated_at?->toIso8601String(),
            ] : null,
            'published_version' => $this->currentVersion ? [
                'id' => $this->currentVersion->id,
                'version_number' => $this->currentVersion->version_number,
                'status' => $this->currentVersion->status,
                'published_at' => $this->currentVersion->published_at?->toIso8601String(),
            ] : null,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
