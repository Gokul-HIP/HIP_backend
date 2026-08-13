<?php

namespace App\Modules\Workflow\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WorkflowTemplateCollection extends ResourceCollection
{
    public $collects = WorkflowTemplateResource::class;

    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->map->toArray($request)->values(),
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'path' => $this->resource->path(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
