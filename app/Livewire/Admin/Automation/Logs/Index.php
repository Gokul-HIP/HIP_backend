<?php

namespace App\Livewire\Admin\Automation\Logs;

use App\Modules\Workflow\Models\CommunicationLog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public string $channelFilter = 'all';

    public string $statusFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = CommunicationLog::query()
            ->when($this->channelFilter !== 'all', fn ($q) => $q->where('channel', $this->channelFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $like = '%'.$this->search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('recipient', 'like', $like)
                        ->orWhere('message', 'like', $like)
                        ->orWhere('channel', 'like', $like);
                });
            })
            ->latest('id')
            ->paginate(20);

        return view('livewire.admin.automation.logs.index', [
            'logs' => $logs,
        ])->extends('layouts.admin')->section('content');
    }
}
