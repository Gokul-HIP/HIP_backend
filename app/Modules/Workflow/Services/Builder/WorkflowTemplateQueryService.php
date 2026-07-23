<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\Workflow\Models\WorkflowTemplate;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowTemplateQueryService
{
    public function __construct(
        protected VariableResolver $variableResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $paginator = WorkflowTemplate::query()
            ->when(isset($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['channel']), fn ($q) => $q->where('channel', $filters['channel']))
            ->when(isset($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->where('is_active', true)
            ->latest('id')
            ->paginate($perPage);

        if (! empty($filters['preview_context']) && is_array($filters['preview_context'])) {
            $paginator->getCollection()->transform(function (WorkflowTemplate $template) use ($filters) {
                $template->setAttribute(
                    'preview',
                    $this->variableResolver->resolve($template->body, $filters['preview_context'])
                );

                return $template;
            });
        }

        return $paginator;
    }

    public function preview(WorkflowTemplate $template, array $context = []): string
    {
        return $this->variableResolver->resolve($template->body, $context);
    }
}
