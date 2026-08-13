<?php

namespace App\Livewire\Admin\Automation\WorkflowTemplates;

use App\Livewire\Admin\Automation\Concerns\BuildsAutomationEmbedUrl;
use App\Modules\Workflow\Enums\WorkflowStatus;
use App\Modules\Workflow\Services\Builder\WorkflowTemplateService;
use Flux\Flux;
use Livewire\Component;
use Throwable;

/**
 * Shared React Flow host for Workflow Templates.
 * mode: create | edit | preview — Template Mode (Save Template, no Publish).
 */
class Builder extends Component
{
    use BuildsAutomationEmbedUrl;

    public string $viewMode = 'create';

    public ?int $templateId = null;

    public string $name = 'Untitled Template';

    public ?string $description = null;

    public string $module = 'pharmacy';

    public ?string $triggerType = null;

    public ?string $triggerLabel = null;

    public string $status = 'active';

    /** @var array<string, mixed>|null */
    public ?array $definition = null;

    public string $embedUrl = '';

    public function mount(WorkflowTemplateService $service, ?int $id = null): void
    {
        $this->templateId = $id;
        $route = request()->route()?->getName() ?? '';

        if (str_ends_with($route, '.preview')) {
            $this->viewMode = 'preview';
        } elseif ($id) {
            $this->viewMode = 'edit';
        } else {
            $this->viewMode = 'create';
        }

        if ($id) {
            $template = $service->findOrFail($id);
            $this->name = $template->name;
            $this->description = $template->description;
            $this->module = $template->module;
            $this->triggerType = $template->trigger_type;
            $this->triggerLabel = $template->trigger_label;
            $this->status = $template->status instanceof WorkflowStatus
                ? $template->status->value
                : (string) $template->status;
            $this->definition = $template->definition;
        }

        $this->embedUrl = $this->buildEmbedUrl();
    }

    protected function buildEmbedUrl(): string
    {
        $query = [
            'mode' => 'template',
            'readOnly' => $this->viewMode === 'preview' ? '1' : '0',
        ];

        // Omit empty ids so the React builder stays in create mode (Start node).
        if ($this->templateId) {
            $query['template_id'] = $this->templateId;
        }

        return $this->automationEmbedUrl('/embed/workflow-builder', $query);
    }

    /**
     * Called from the embedded React Flow builder via postMessage → Alpine → Livewire.
     *
     * @param  array<string, mixed>  $payload
     */
    public function saveFromBuilder(array $payload, WorkflowTemplateService $service): void
    {
        if ($this->viewMode === 'preview') {
            return;
        }

        $data = [
            'name' => $payload['name'] ?? $this->name,
            'description' => $payload['description'] ?? $this->description,
            'module' => $payload['module'] ?? $this->module,
            'trigger_type' => $payload['trigger_type'] ?? $this->triggerType,
            'trigger_label' => $payload['trigger_label'] ?? $this->triggerLabel,
            'status' => $payload['status'] ?? $this->status,
            'definition' => $payload['definition'] ?? null,
            'organization_id' => $payload['organization_id'] ?? null,
        ];

        if (! is_array($data['definition'])) {
            Flux::toast(variant: 'danger', text: 'Definition (React Flow JSON) is required.');

            return;
        }

        try {
            if ($this->templateId) {
                $template = $service->findOrFail($this->templateId);
                $service->update($template, $data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Template saved.');
                $this->definition = $data['definition'];
                $this->name = $data['name'];
            } else {
                $created = $service->create($data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Template created.');
                $this->redirect(
                    route('admin.automation.workflow-templates.edit', $created->id),
                    navigate: true
                );
            }
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.automation.workflow-templates.builder', [
            'readOnly' => $this->viewMode === 'preview',
            'pageTitle' => match ($this->viewMode) {
                'preview' => 'Preview Workflow Template',
                'edit' => 'Edit Workflow Template',
                default => 'Create Workflow Template',
            },
            'initialPayload' => [
                'name' => $this->name,
                'description' => $this->description,
                'module' => $this->module,
                'trigger_type' => $this->triggerType,
                'trigger_label' => $this->triggerLabel,
                'status' => $this->status,
                'definition' => $this->definition,
                'mode' => 'template',
                'viewMode' => $this->viewMode,
                'templateId' => $this->templateId,
            ],
        ])->extends('layouts.admin')->section('content');
    }
}
