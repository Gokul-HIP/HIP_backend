<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\HospitalAutomation\Support\TriggerCatalog;
use App\Modules\Workflow\Models\Workflow;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workflow */
class WorkflowResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'module' => $this->trigger_type ? TriggerCatalog::moduleFor($this->trigger_type) : null,
            'trigger_type' => $this->trigger_type,
            'trigger_label' => $this->trigger_type ? TriggerCatalog::labelFor($this->trigger_type) : null,
            'status' => $this->status,
            'organization_id' => $this->organization_id,
            'current_version_number' => $this->currentVersion?->version_number,
            'published_at' => $this->currentVersion?->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
