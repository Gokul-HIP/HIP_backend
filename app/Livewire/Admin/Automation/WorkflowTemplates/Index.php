<?php

namespace App\Livewire\Admin\Automation\WorkflowTemplates;

use App\Livewire\Admin\Automation\Concerns\BuildsAutomationEmbedUrl;
use App\Modules\Workflow\Services\Builder\WorkflowTemplateService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Index extends Component
{
    use BuildsAutomationEmbedUrl;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $moduleFilter = 'all';

    public string $statusFilter = 'all';

    public ?int $confirmDeleteId = null;

    public ?string $confirmDeleteName = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingModuleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id, string $name = ''): void
    {
        $this->confirmDeleteId = $id;
        $this->confirmDeleteName = $name;
        Flux::modal('delete-workflow-template')->show();
    }

    public function closeDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->confirmDeleteName = null;
        Flux::modal('delete-workflow-template')->close();
    }

    public function deleteTemplate(WorkflowTemplateService $service): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        try {
            $template = $service->findOrFail($this->confirmDeleteId);
            $service->delete($template);
            Flux::toast(variant: 'success', text: 'Template deleted.');
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        $this->closeDelete();
        $this->resetPage();
    }

    public function duplicate(int $id, WorkflowTemplateService $service): void
    {
        try {
            $template = $service->findOrFail($id);
            $service->duplicate($template, $this->actorId());
            Flux::toast(variant: 'success', text: 'Template duplicated.');
            $this->resetPage();
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render(WorkflowTemplateService $service)
    {
        $filters = [
            'search' => $this->search !== '' ? $this->search : null,
            'module' => $this->moduleFilter !== 'all' ? $this->moduleFilter : null,
            'status' => $this->statusFilter !== 'all' ? $this->statusFilter : null,
        ];

        $templates = $service->list(array_filter($filters, fn ($v) => $v !== null), 10);

        return view('livewire.admin.automation.workflow-templates.index', [
            'templates' => $templates,
            'modules' => [
                'pharmacy' => 'Pharmacy',
                'appointment' => 'Appointment',
                'lab' => 'Lab',
                'billing' => 'Billing',
                'membership' => 'Membership',
                'engagement' => 'Engagement',
                'general' => 'General',
            ],
        ])->extends('layouts.admin')->section('content');
    }
}
