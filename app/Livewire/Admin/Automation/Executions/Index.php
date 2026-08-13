<?php

namespace App\Livewire\Admin\Automation\Executions;

use App\Modules\Workflow\Models\WorkflowExecution;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $executions = WorkflowExecution::query()
            ->with(['workflow:id,name', 'version:id,version_number'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('id', 'like', $like)
                        ->orWhereHas('workflow', fn ($w) => $w->where('name', 'like', $like));
                });
            })
            ->latest('id')
            ->paginate(15);

        return view('livewire.admin.automation.executions.index', [
            'executions' => $executions,
        ])->extends('layouts.admin')->section('content');
    }
}
