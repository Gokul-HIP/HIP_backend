<?php

namespace App\Livewire\Admin\Organization\Pharmacy;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\PharmacyService;
use Livewire\WithPagination;
use App\Models\Organization;

class Pharmacy extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';
    public $orgId;
    // public $pharmacys;
    public $delete_id;
    
    public $search = '';
    public $statusFilter = 'all';

    protected $pharmacyService;

    public function boot(PharmacyService $pharmacyService)
    {
        $this->pharmacyService = $pharmacyService;
    }

    public function mount($orgId)
    {
        $this->orgId = $orgId;
        // $this->loadPharmacies();
    }

    #[On("relodphar")]
    public function render()
    {
        $filters = [];

        if ($this->search !== '') {
            $filters['search'] = $this->search;
        }

        if ($this->statusFilter !== 'all') {
            $filters['status'] = $this->statusFilter;
        }

        $pharmacies = $this->pharmacyService
            ->searchPharmaciesPaginated($this->orgId, $filters, 10);

        $organization = Organization::find($this->orgId);

        return view('livewire.admin.organization.pharmacy.pharmacy', [
            'pharmacys' => $pharmacies,
            'organization' => $organization
        ]);
    }

    // #[On("relodphar")]
    // public function relodphar()
    // {
    //     $this->loadPharmacies();
    //     $this->dispatch('relode-phar');
    // }

    // public function updatedSearch()
    // {
    //     $this->loadPharmacies();
    // }

    // public function updatedStatusFilter()
    // {
    //     $this->loadPharmacies();
    // }

    // protected function loadPharmacies()
    // {
    //     $filters = [];
        
    //     if (!empty($this->search)) {
    //         $filters['search'] = $this->search;
    //     }
        
    //     if ($this->statusFilter !== 'all') {
    //         $filters['status'] = $this->statusFilter;
    //     }

    //     if (empty($filters)) {
    //         $this->pharmacys = $this->pharmacyService->getPharmaciesByOrganization($this->orgId);
    //     } else {
    //         $this->pharmacys = $this->pharmacyService->searchPharmaciesPaginated($this->orgId, $filters);
    //     }
    // }

    public function edit($id)
    {
        $this->dispatch('editpharmacy', $id);
    }

    public function delete($id)
    {
        $this->delete_id = $id;
        Flux::modal('delete-phar')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-phar')->close();
        $this->dispatch('relodphar');
    }

    public function destroy()
    {
        $this->pharmacyService->deletePharmacy($this->delete_id);
        
        Flux::modal('delete-phar')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Pharmacy deleted successfully!'
        );
        $this->resetPage();
        $this->dispatch('relodphar');
    }
}
