<?php

namespace App\Livewire\Admin\Automation\Workflows;

use App\Livewire\Admin\Automation\Concerns\BuildsAutomationEmbedUrl;
use App\Modules\Workflow\Services\Builder\WorkflowBuilderService;
use App\Modules\Workflow\Services\Builder\WorkflowPublishService;
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

    public string $statusFilter = 'all';

    public ?int $confirmDeleteId = null;

    public ?string $confirmDeleteName = null;

    public function updatingSearch(): void
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
        Flux::modal('delete-workflow')->show();
    }

    public function closeDelete(): void
    {
        $this->confirmDeleteId = null;
        $this->confirmDeleteName = null;
        Flux::modal('delete-workflow')->close();
    }

    public function deleteWorkflow(WorkflowBuilderService $service): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }

        try {
            $workflow = $service->findOrFail($this->confirmDeleteId);
            $service->delete($workflow);
            Flux::toast(variant: 'success', text: 'Workflow deleted.');
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }

        $this->closeDelete();
        $this->resetPage();
    }

    public function publish(int $id, WorkflowBuilderService $builder, WorkflowPublishService $publisher): void
    {
        try {
            $workflow = $builder->findOrFail($id);
            $publisher->publish($workflow, $this->actorId());
            Flux::toast(variant: 'success', text: 'Workflow published.');
        } catch (Throwable $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function render(WorkflowBuilderService $service)
    {
        $filters = array_filter([
            'search' => $this->search !== '' ? $this->search : null,
            'status' => $this->statusFilter !== 'all' ? $this->statusFilter : null,
        ]);

        return view('livewire.admin.automation.workflows.index', [
            'workflows' => $service->list($filters, 10),
        ])->extends('layouts.admin')->section('content');
    }
}
