<?php

namespace App\Livewire\Admin\Organization\Hospital\Specialitie;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\SpecialitieService;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\Hospital;

class SpecialitieIndex extends Component
{
    use WithPagination;

    public $hospitalId;
    public $speciality_id;
    public $search = '';
    public $statusFilter = '';
    public $departmentFilter = '';
    public $perPage = 10;

    protected $specialitieService;

    public function boot(SpecialitieService $specialitieService)
    {
        $this->specialitieService = $specialitieService;
    }

    public function mount($hospitalId)
    {
        $this->hospitalId = $hospitalId;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingDepartmentFilter()
    {
        $this->resetPage();
    }

    #[On('relodSpe')]
    public function relodSpe()
    {
        $this->dispatch('relode-spe');
    }

    public function edit($id)
    {
        $this->dispatch('edit', $id);
    }

    public function delete($id)
    {
        $this->speciality_id = $id;
        Flux::modal('delete-spe')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-spe')->close();
    }

    public function destroy()
    {
        $this->specialitieService->deleteSpeciality($this->speciality_id);
        Flux::modal('delete-spe')->close();
        $this->relodSpe();
        
        Flux::toast(
            text: 'Speciality deleted successfully!',
            variant: 'success'
        );
    }

    public function view($id)
    {
        // Navigate to view speciality details
        return redirect()->route('admin.organizations.hospital.show', ['id' => $this->hospitalId, 'specialityId' => $id]);
    }

    public function getSpecialities()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'department_category' => $this->departmentFilter,
        ];

        return $this->specialitieService->searchSpecialities(
            $this->hospitalId, 
            $filters, 
            $this->perPage
        );
    }

    public function render()
    {
        $hospital = Hospital::find($this->hospitalId);
        
        return view('livewire.admin.organization.hospital.specialitie.specialitie-index', [
            'specialities' => $this->getSpecialities(),
            'hospital' => $hospital
        ]);
    }
}

