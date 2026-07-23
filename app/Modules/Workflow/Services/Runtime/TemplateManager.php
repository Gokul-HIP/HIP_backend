<?php

namespace App\Modules\Workflow\Services\Runtime;

use App\Modules\Workflow\Models\WorkflowTemplate;

class TemplateManager
{
    public function __construct(
        protected VariableResolver $variableResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function render(?int $templateId, string $channel, array $context, ?string $fallback = null): string
    {
        $template = $templateId
            ? WorkflowTemplate::query()->where('id', $templateId)->where('is_active', true)->first()
            : null;

        if (! $template) {
            $template = WorkflowTemplate::query()
                ->where('channel', $channel)
                ->where('is_active', true)
                ->when(
                    isset($context['organization_id']),
                    fn ($q) => $q->where('organization_id', $context['organization_id'])
                )
                ->latest('id')
                ->first();
        }

        $body = $template?->body ?? $fallback ?? '';

        return $this->variableResolver->resolve($body, $context);
    }
}
