<?php

namespace App\Livewire\Admin\Automation\Workflows;

use App\Livewire\Admin\Automation\Concerns\BuildsAutomationEmbedUrl;
use App\Models\HIPUser;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowHospitalScopeService;
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

    protected function currentUser(): ?HIPUser
    {
        $user = auth('filament')->user() ?? auth()->user();

        return $user instanceof HIPUser ? $user : null;
    }

    public function mount(
        WorkflowBuilderService $service,
        WorkflowHospitalScopeService $scopeService,
        ?int $id = null
    ): void {
        $this->workflowId = $id;
        $route = request()->route()?->getName() ?? '';

        if (str_contains($route, '.view')) {
            $this->viewMode = 'view';
        } elseif ($id) {
            $this->viewMode = 'edit';
        } else {
            $this->viewMode = 'create';
        }

        $user = $this->currentUser();

        if ($id) {
            $workflow = $service->findOrFail($id);
            $this->name = $workflow->name;
            $this->hospitalId = $workflow->hospital_id;
            $version = $workflow->draftVersion ?? $workflow->currentVersion;
            $this->configuration = $version?->definition;
        } else {
            // On create: force hospital admin's hospital from their own hospital_id
            if ($user && $user->hospital_id) {
                $this->hospitalId = (int) $user->hospital_id;
            }
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
    public function saveFromBuilder(
        array $payload,
        WorkflowBuilderService $service,
        WorkflowHospitalScopeService $scopeService
    ): bool {
        if ($this->viewMode === 'view') {
            return false;
        }

        $user = $this->currentUser();

        try {
            if ($this->workflowId) {
                $workflow = $service->findOrFail($this->workflowId);

                // Scope validation: hospital must be taken from stored workflow, tampering denied
                $scope = $scopeService->resolveForUpdate($user, $workflow, [
                    'hospital_id' => $payload['hospital_id'] ?? $this->hospitalId,
                    'organization_id' => $payload['organization_id'] ?? null,
                ]);

                $data = [
                    'name' => $payload['name'] ?? $this->name,
                    'configuration' => $payload['configuration'] ?? $payload['definition'] ?? null,
                    'organization_id' => $scope['organization_id'],
                    'hospital_id' => $scope['hospital_id'],
                ];

                if (! is_array($data['configuration'])) {
                    Flux::toast(variant: 'danger', text: 'Workflow configuration is required.');

                    return false;
                }

                $service->update($workflow, $data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Workflow saved.');

                return true;
            } else {
                // Scope validation: hospital admin forced from their own hospital_id, tampering denied
                $scope = $scopeService->resolveForCreate($user, [
                    'hospital_id' => $payload['hospital_id'] ?? $this->hospitalId,
                    'organization_id' => $payload['organization_id'] ?? null,
                ]);

                $data = [
                    'name' => $payload['name'] ?? $this->name,
                    'configuration' => $payload['configuration'] ?? $payload['definition'] ?? null,
                    'organization_id' => $scope['organization_id'],
                    'hospital_id' => $scope['hospital_id'],
                ];

                if (! is_array($data['configuration'])) {
                    Flux::toast(variant: 'danger', text: 'Workflow configuration is required.');

                    return false;
                }

                $created = $service->create($data, $this->actorId());
                Flux::toast(variant: 'success', text: 'Workflow created.');
                $this->redirect(
                    route('admin.automation.workflows.edit', $created->id),
                    navigate: true
                );

                return true;
            }
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function publishFromBuilder(
        array $payload,
        WorkflowBuilderService $builder,
        WorkflowPublishService $publisher,
        WorkflowHospitalScopeService $scopeService
    ): void {
        if ($this->viewMode === 'view') {
            return;
        }

        try {
            $saved = $this->saveFromBuilder($payload, $builder, $scopeService);
            if (! $saved) {
                return;
            }

            $id = $this->workflowId;
            if (! $id) {
                Flux::toast(variant: 'danger', text: 'Save the workflow before publishing.');

                return;
            }

            $workflow = $builder->findOrFail($id);
            $scopeService->assertHospitalAccess($this->currentUser(), $workflow->hospital_id, $workflow->organization_id);

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
