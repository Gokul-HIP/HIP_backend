<?php

namespace App\Livewire\Admin\Organization\Diagnostic;

use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\DiagnosticService;
use Flux\Flux;
use Livewire\WithPagination;
use App\Models\Organization;

class Diagnostic extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';
    // public $diagnostics;
    public $orgId;
    public $diagnostic_id;
    public $search = '';
    public $statusFilter = 'all';
    public $locationFilter = 'all';

    protected $diagnosticService;

    public function boot(DiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
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

    // public function mount($orgId){

    //     $this->orgId = $orgId;
    //     $this->diagnostics = DiagnosticModal::where("organization_id",$this->orgId)->get();

    // }

    // #[On('relodDia')]
    // public function relodDia(){

    //     $this->diagnostics = DiagnosticModal::where("organization_id",$this->orgId)->get();
    //     $this->dispatch('relode-dia');

    // }
    
    #[On('relodDia')]
    public function render()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'location' => $this->locationFilter,
        ];

        $diagnostics = $this->diagnosticService->getDiagnosticsPaginated($this->orgId, $filters, 10);
        $organization = Organization::find($this->orgId);

        return view('livewire.admin.organization.diagnostic.diagnostic', [
            'diagnostics' => $diagnostics,
            'organization' => $organization
        ]);
    }

    public function delete($id){

       $this->diagnostic_id = $id;
       Flux::modal('delete-diag')->show();
       $this->dispatch('relode-dia');

    }

    public function closeModal()
    {
        Flux::modal('delete-diag')->close();
    }

    public function destroy(){

        $this->diagnosticService->deleteDiagnostic($this->diagnostic_id);
        
        Flux::modal('delete-diag')->close();
        $this->dispatch('relodDia');

    }

    public function edit($id){

        // dd($id);
        $this->dispatch('edit',$id);

    }

}
