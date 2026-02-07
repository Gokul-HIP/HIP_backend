<?php

namespace App\Livewire\Admin\Caregiver;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CareGiver;

class Index extends Component
{
    
    use WithPagination;
    protected $paginationTheme = 'tailwind';
    public $search = '';
    public $is_active = 'all';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingIsActive()
    {
        $this->resetPage();
    }

    public function render()
    {
        $caregivers = CareGiver::query()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'desc')
            ->paginate(10);
        return view('livewire.admin.caregiver.index', [
            'caregivers' => $caregivers,
        ]);
    }
}
