<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Modules\Workflow\Models\WorkflowMessageTemplate;
use App\Modules\Workflow\Services\Runtime\VariableResolver;
use App\Models\Organization;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowTemplateQueryService
{
    public function __construct(
        protected VariableResolver $variableResolver,
    ) {}

    /**
     * List channel message templates (not React Flow blueprints).
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $paginator = WorkflowMessageTemplate::query()
            ->when(isset($filters['organization_id']), fn ($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['channel']), fn ($q) => $q->where('channel', $filters['channel']))
            ->when(isset($filters['category']), fn ($q) => $q->where('category', $filters['category']))
            ->where('is_active', true)
            ->latest('id')
            ->paginate($perPage);

        if (! empty($filters['preview_context']) && is_array($filters['preview_context'])) {
            $paginator->getCollection()->transform(function (WorkflowMessageTemplate $template) use ($filters) {
                $template->setAttribute(
                    'preview',
                    $this->variableResolver->resolve($template->body, $filters['preview_context'])
                );

                return $template;
            });
        }

        return $paginator;
    }

    public function preview(WorkflowMessageTemplate $template, array $context = []): string
    {
        return $this->variableResolver->resolve($template->body, $context);
    }

    /**
     * Persist a channel message template for the Flow Builder "+ Add to Template" flow.
     *
     * Ownership (organization_id) is taken from the authenticated user context only —
     * never from client input. Invalid/missing organization references are stored as null
     * so FK violations never surface as HTTP 500.
     *
     * @param  array<string, mixed>  $data  Validated payload from StoreWorkflowMessageTemplateRequest
     */
    public function create(array $data, ?int $organizationId = null): WorkflowMessageTemplate
    {
        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            $body = trim((string) ($data['message'] ?? ''));
        }

        $status = strtolower(trim((string) ($data['status'] ?? 'active')));
        $isActive = ! in_array($status, ['inactive', 'draft'], true);

        $meta = $this->buildMetaVariables($data);

        $safeOrganizationId = null;
        if ($organizationId !== null && $organizationId > 0) {
            if (Organization::query()->whereKey($organizationId)->exists()) {
                $safeOrganizationId = $organizationId;
            }
        }

        return WorkflowMessageTemplate::query()->create([
            'organization_id' => $safeOrganizationId,
            'name' => trim((string) $data['name']),
            'channel' => strtolower(trim((string) $data['channel'])),
            'category' => isset($data['category']) && is_string($data['category']) && trim($data['category']) !== ''
                ? trim($data['category'])
                : null,
            'locale' => isset($data['locale']) && is_string($data['locale']) && trim($data['locale']) !== ''
                ? trim($data['locale'])
                : 'en',
            'body' => $body,
            'variables' => $meta,
            'is_active' => $isActive,
            'version_number' => 1,
        ]);
    }

    /**
     * Store FE-only extras (subject/title/priority/…) in the existing JSON variables column.
     * Does not add new DB columns.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildMetaVariables(array $data): array
    {
        $meta = [];

        if (isset($data['variables']) && is_array($data['variables'])) {
            $meta = $data['variables'];
        }

        foreach (['subject', 'title', 'priority'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($value === null) {
                continue;
            }
            $trimmed = is_string($value) ? trim($value) : $value;
            if ($trimmed === '' || $trimmed === null) {
                continue;
            }
            $meta[$key] = is_string($trimmed) ? $trimmed : $value;
        }

        if (array_key_exists('temperature', $data) && $data['temperature'] !== null && $data['temperature'] !== '') {
            $meta['temperature'] = (float) $data['temperature'];
        }

        if (array_key_exists('buttons', $data) && $data['buttons'] !== null && $data['buttons'] !== '') {
            $meta['buttons'] = $data['buttons'];
        }

        return $meta;
    }
}
