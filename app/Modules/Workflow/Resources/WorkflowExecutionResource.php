<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\Workflow\Models\WorkflowExecution;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowExecution */
class WorkflowExecutionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'workflow_name' => $this->workflow?->name,
            'workflow_version_id' => $this->workflow_version_id,
            'version_number' => $this->version?->version_number,
            'status' => $this->status,
            'trigger_type' => $this->trigger_type,
            'current_node_id' => $this->current_node_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'duration_ms' => $this->duration_ms,
            'failure_reason' => $this->failure_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
