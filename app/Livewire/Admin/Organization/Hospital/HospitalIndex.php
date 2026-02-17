<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\HospitalService;
use App\Models\Organization;
use App\Models\Hospital;

class HospitalIndex extends Component
{   
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $hospital_id;
    public $orgId;
    
    public $search = '';
    public $statusFilter = 'all';
    public $locationFilter = 'all';

    protected $hospitalService;

    public function boot(HospitalService $hospitalService)
    {
        $this->hospitalService = $hospitalService;
    }

    public function mount($orgId)
    {
        $this->orgId = $orgId;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $organization = Organization::find($this->orgId);
        
        $filters = [];
        
        if (!empty($this->search)) {
            $filters['search'] = $this->search;
        }
        
        if ($this->statusFilter !== 'all') {
            $filters['status'] = $this->statusFilter;
        }

        if ($this->locationFilter !== 'all') {
            $filters['location'] = $this->locationFilter;
        }

        if (empty($filters)) {
            $hospitals = $this->hospitalService->getHospitalsByOrganizationPaginated($this->orgId, 10);
        } else {
            $hospitals = $this->hospitalService->searchHospitalsPaginated($this->orgId, $filters, 10);
        }
        
        // Get total and active counts for display
        $allHospitals = Hospital::where('organization_id', $this->orgId)->get();
        $totalCount = $allHospitals->count();
        $activeCount = $allHospitals->where('status', 'active')->count();
        
        return view('livewire.admin.organization.hospital.hospital-index', [
            'organization' => $organization,
            'hospitals' => $hospitals,
            'totalCount' => $totalCount,
            'activeCount' => $activeCount
        ]);
    }

    #[On('relodHos')]
    public function relodHos()
    {
        $this->resetPage();
        $this->dispatch('relode-hos');
    }

    public function delete($id)
    {
        $this->hospital_id = $id;
        Flux::modal('delete-hos')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-hos')->close();
    }

    public function destroy()
    {
        $this->hospitalService->deleteHospital($this->hospital_id);
        
        Flux::modal('delete-hos')->close();
        $this->relodHos();
    }

    public function edit($id)
    {
        $this->dispatch('edit', $id);
    }

    public function createUser($id)
    {
        $this->dispatch('createUser', $id);
        Flux::modal('create-user')->show();
    }
}
