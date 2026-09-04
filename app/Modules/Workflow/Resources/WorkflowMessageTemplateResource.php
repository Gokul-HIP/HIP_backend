<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowMessageTemplate */
class WorkflowMessageTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        $variables = is_array($this->variables) ? $this->variables : [];
        $body = $this->body;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel,
            'category' => $this->category,
            'locale' => $this->locale,
            'body' => $body,
            // FE normalizeTemplateItem reads message / subject / title / priority / buttons / status.
            'message' => $body,
            'subject' => $variables['subject'] ?? null,
            'title' => $variables['title'] ?? null,
            'priority' => $variables['priority'] ?? null,
            'buttons' => $variables['buttons'] ?? null,
            'temperature' => $variables['temperature'] ?? null,
            'status' => $this->is_active ? 'active' : 'inactive',
            'variables' => $variables,
            'is_active' => $this->is_active,
            'version_number' => $this->version_number,
            'organization_id' => $this->organization_id,
            'preview' => $this->when(isset($this->preview), $this->preview),
        ];
    }
}
