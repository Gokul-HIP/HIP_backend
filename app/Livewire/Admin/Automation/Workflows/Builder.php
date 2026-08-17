<?php

namespace App\Livewire\Admin\Automation\Workflows;

use App\Livewire\Admin\Automation\Concerns\BuildsAutomationEmbedUrl;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
use Flux\Flux;
use Livewire\Component;
use Throwable;

/**
 * Shared React Flow host for Workflows — Workflow Mode (Save + Publish).
 */
class Builder extends Component
{
    use BuildsAutomationEmbedUrl;

    public string $viewMode = 'create';

    public ?int $workflowId = null;

    public ?int $hospitalId = null;

    public string $name = 'Hospital workflow';

    /** @var array<string, mixed>|null */
    public ?array $configuration = null;

    public string $embedUrl = '';

    public function mount(WorkflowBuilderService $service, ?int $id = null): void
    {
        $this->workflowId = $id;
        $route = request()->route()?->getName() ?? '';

        if (str_contains($route, '.view')) {
            $this->viewMode = 'view';
        } elseif ($id) {
            $this->viewMode = 'edit';
        } else {
            $this->viewMode = 'create';
        }

        if ($id) {
            $workflow = $service->findOrFail($id);
            $this->name = $workflow->name;
            $this->hospitalId = $workflow->hospital_id;
            $version = $workflow->draftVersion ?? $workflow->currentVersion;
            $this->configuration = $version?->definition;
        }

        $query = [
            'mode' => 'workflow',
            'readOnly' => $this->viewMode === 'view' ? '1' : '0',
        ];
        if ($this->workflowId) {
            $query['workflow_id'] = $this->workflowId;
        }
        $this->embedUrl = $this->automationEmbedUrl('/embed/workflow-builder', $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveFromBuilder(array $payload, WorkflowBuilderService $service): void
    {
        if ($this->viewMode === 'view') {
            return;
        }

        $data = [
            'name' => $payload['name'] ?? $this->name,
            'configuration' => $payload['configuration'] ?? $payload['definition'] ?? null,
            'organization_id' => $payload['organization_id'] ?? null,
            'hospital_id' => $payload['hospital_id'] ?? $this->hospitalId,
        ];

        if (! is_array($data['configuration'])) {
            Flux::toast(variant: 'danger', text: 'Workflow configuration is required.');

            return;
        }

        try {
            if ($this->workflowId) {
                $workflow = $service->findOrFail($this->workflowId);
                $service->update($workflow, $data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Workflow saved.');
            } else {
                $created = $service->create($data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Workflow created.');
                $this->redirect(
                    route('admin.automation.workflows.edit', $created->id),
                    navigate: true
                );
            }
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishFromBuilder(
        array $payload,
        WorkflowBuilderService $builder,
        WorkflowPublishService $publisher
    ): void {
        if ($this->viewMode === 'view') {
            return;
        }

        try {
            $this->saveFromBuilder($payload, $builder);

            $id = $this->workflowId;
            if (! $id) {
                Flux::toast(variant: 'danger', text: 'Save the workflow before publishing.');

                return;
            }

            $workflow = $builder->findOrFail($id);
            $publisher->publish($workflow, $this->actorId());
            Flux::toast(variant: 'success', text: 'Workflow published.');
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.automation.workflows.builder', [
            'readOnly' => $this->viewMode === 'view',
            'pageTitle' => match ($this->viewMode) {
                'view' => 'View Workflow',
                'edit' => 'Edit Workflow',
                default => 'Create Workflow',
            },
            'initialPayload' => [
                'name' => $this->name,
                'configuration' => $this->configuration,
                'mode' => 'workflow',
                'viewMode' => $this->viewMode,
                'workflowId' => $this->workflowId,
                'hospital_id' => $this->hospitalId,
            ],
        ])->extends('layouts.admin')->section('content');
    }
}
