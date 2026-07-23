<?php

namespace App\Modules\Workflow\Resources;

use App\Modules\Workflow\Models\WorkflowTemplate;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowTemplate */
class WorkflowTemplateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'channel' => $this->channel,
            'category' => $this->category,
            'locale' => $this->locale,
            'body' => $this->body,
            'variables' => $this->variables,
            'is_active' => $this->is_active,
            'version_number' => $this->version_number,
            'preview' => $this->when(isset($this->preview), $this->preview),
        ];
    }
}
