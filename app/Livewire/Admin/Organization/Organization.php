<?php

namespace App\Livewire\Admin\Organization;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Organization as OrganizationModel;

class Organization extends Component
{
    use WithPagination;

    public string $search   = '';
    public string $location = 'all';
    public string $type     = 'all';
    public string $status   = 'all';

    #[On("relode-org")]
    public function relodeOrg()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = OrganizationModel::query();

        if (!empty($this->search)) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name',   'like', "%{$s}%")
                  ->orWhere('city', 'like', "%{$s}%");
            });
        }

        if ($this->location !== 'all') {
            $query->where('city', 'like', "%{$this->location}%");
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        $organizations = $query->latest()->paginate(10);

        $totalCount  = OrganizationModel::count();
        $activeCount = OrganizationModel::where('status', 'active')->count();

        return view('livewire.admin.organization.organization', [
            'organization' => $organizations,
            'totalCount'   => $totalCount,
            'activeCount'  => $activeCount,
        ]);
    }
}