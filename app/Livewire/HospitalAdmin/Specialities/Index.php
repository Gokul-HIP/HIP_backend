<?php

namespace App\Livewire\HospitalAdmin\Specialities;

use App\Models\Hospital;
use App\Models\Speciality;
use App\Services\SpecialitieService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $hospitalId;
    public $search = '';
    public $statusFilter = '';
    public $departmentFilter = '';
    public $perPage = 10;

    protected SpecialitieService $specialitieService;

    public function boot(SpecialitieService $specialitieService): void
    {
        $this->specialitieService = $specialitieService;
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

    public function updatingDepartmentFilter(): void
    {
        $this->resetPage();
    }

    public function updateSpecialityStatus(int $specialityId, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $organizationId = Auth::guard('filament')->user()?->organization_id;

        $speciality = Speciality::query()
            ->whereKey($specialityId)
            ->where('hospital_id', $this->hospitalId)
            ->whereHas('hospital', function ($query) use ($organizationId) {
                $query->where('organization_id', $organizationId);
            })
            ->first();

        if (!$speciality) {
            return;
        }

        $speciality->update(['status' => $status]);

        $this->dispatch('toast', type: 'success', message: 'Speciality status updated successfully.');
    }

    public function getSpecialities()
    {
        return $this->specialitieService->searchSpecialities($this->hospitalId, [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'department_category' => $this->departmentFilter,
        ], $this->perPage);
    }

    public function render()
    {
        $hospital = Hospital::findOrFail($this->hospitalId);

        $availableDepartments = Speciality::where('hospital_id', $this->hospitalId)
            ->whereNotNull('department_category')
            ->distinct()
            ->pluck('department_category')
            ->filter()
            ->sort()
            ->values();

        return view('livewire.hospital-admin.specialities.index', [
            'hospital' => $hospital,
            'specialities' => $this->getSpecialities(),
            'availableDepartments' => $availableDepartments,
        ]);
    }
}
