<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\HospitalService;
use App\Models\Organization;

class HospitalIndex extends Component
{   
    public $hospitals;
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
        $this->loadHospitals();
    }

    public function render()
    {
        $organization = Organization::find($this->orgId);
        return view('livewire.admin.organization.hospital.hospital-index', [
            'organization' => $organization
        ]);
    }

    #[On('relodHos')]
    public function relodHos()
    {
        $this->loadHospitals();
        $this->dispatch('relode-hos');
    }

    public function updatedSearch()
    {
        $this->loadHospitals();
    }

    public function updatedStatusFilter()
    {
        $this->loadHospitals();
    }

    public function updatedLocationFilter()
    {
        $this->loadHospitals();
    }

    protected function loadHospitals()
    {
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
            $this->hospitals = $this->hospitalService->getHospitalsByOrganization($this->orgId);
        } else {
            $this->hospitals = $this->hospitalService->searchHospitals($this->orgId, $filters);
        }
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
}
