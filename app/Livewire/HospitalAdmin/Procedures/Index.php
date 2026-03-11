<?php

namespace App\Livewire\HospitalAdmin\Procedures;

use App\Models\Hospital;
use App\Models\Procedure;
use App\Models\ProcedureMaster;
use App\Models\SpecialitiesMaster;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $hospitalId;
    public $search = '';
    public $statusFilter = '';
    public $specialityFilter = '';
    public $perPage = 10;

    protected ProcedureService $procedureService;

    public function boot(ProcedureService $procedureService): void
    {
        $this->procedureService = $procedureService;
    }

    public function mount($hospitalId): void
    {
        $organizationId = Auth::guard('filament')->user()?->organization_id;

        abort_unless(
            Hospital::whereKey($hospitalId)->where('organization_id', $organizationId)->exists(),
            404
        );

        $this->hospitalId = $hospitalId;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSpecialityFilter(): void
    {
        $this->resetPage();
    }

    public function updateProcedureStatus(int $procedureId, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $organizationId = Auth::guard('filament')->user()?->organization_id;

        $procedure = Procedure::query()
            ->whereKey($procedureId)
            ->where('hospital_id', $this->hospitalId)
            ->whereHas('hospital', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->first();

        if (!$procedure) {
            return;
        }

        $procedure->update(['status' => $status]);

        $this->dispatch('toast', type: 'success', message: 'Procedure status updated successfully.');
    }

    public function getProcedures()
    {
        return $this->procedureService->searchProcedures($this->hospitalId, [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'speciality' => $this->specialityFilter,
        ], $this->perPage);
    }

    public function render()
    {
        $hospital = Hospital::findOrFail($this->hospitalId);

        $procedureMasterIds = Procedure::where('hospital_id', $this->hospitalId)
            ->whereNotNull('procedure_master_id')
            ->distinct()
            ->pluck('procedure_master_id')
            ->filter();

        $specialityMasterIds = ProcedureMaster::whereIn('id', $procedureMasterIds)
            ->whereNotNull('speciality_master_id')
            ->distinct()
            ->pluck('speciality_master_id')
            ->filter();

        $availableSpecialityMasters = SpecialitiesMaster::whereIn('id', $specialityMasterIds)
            ->orderBy('name')
            ->get();

        return view('livewire.hospital-admin.procedures.index', [
            'hospital' => $hospital,
            'procedures' => $this->getProcedures(),
            'availableSpecialityMasters' => $availableSpecialityMasters,
        ]);
    }
}
