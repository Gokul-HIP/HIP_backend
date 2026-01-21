<?php

namespace App\Livewire\Admin\Organization\Hospital\Procedure;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ProcedureService;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\Hospital;

class ProcedureIndex extends Component
{
    use WithPagination;

    public $hospitalId;
    public $procedure_id;
    public $search = '';
    public $statusFilter = '';
    public $specialityFilter = '';
    public $perPage = 10;

    protected $procedureService;
    protected string $paginationTheme = 'tailwind';

    public function boot(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    public function updatingPerPage()
    {
        $this->resetPage();
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

    public function updatingSpecialityFilter()
    {
        $this->resetPage();
    }

    #[On('reloadProcedures')]
    public function reloadProcedures()
    {
        $this->dispatch('reload-procedures');
    }

    public function edit($id)
    {
        $this->dispatch('edit', $id);
    }

    public function delete($id)
    {
        $this->procedure_id = $id;
        Flux::modal('delete-procedure')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-procedure')->close();
    }

    public function destroy()
    {
        $this->procedureService->deleteProcedure($this->procedure_id);
        Flux::modal('delete-procedure')->close();
        $this->reloadProcedures();
        
        Flux::toast(
            text: 'Procedure deleted successfully!',
            variant: 'success'
        );
    }

    public function getProcedures()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'speciality' => $this->specialityFilter,
        ];

        return $this->procedureService->searchProcedures(
            $this->hospitalId, 
            $filters, 
            $this->perPage
        );
    }

    public function render()
    {
        $hospital = Hospital::find($this->hospitalId);
        
        return view('livewire.admin.organization.hospital.procedure.procedure-index', [
            'procedures' => $this->getProcedures(),
            'hospital' => $hospital
        ]);
    }
}

