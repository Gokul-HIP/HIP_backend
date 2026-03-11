<?php

namespace App\Livewire\HospitalAdmin\Hospitals;

use App\Models\Hospital;
use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $locationFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLocationFilter(): void
    {
        $this->resetPage();
    }

    public function updateHospitalStatus(int $hospitalId, string $status): void
    {
        if (!in_array($status, ['active', 'inactive'], true)) {
            return;
        }

        $organizationId = Auth::guard('filament')->user()?->organization_id;

        $hospital = Hospital::whereKey($hospitalId)
            ->where('organization_id', $organizationId)
            ->first();

        if (!$hospital) {
            return;
        }

        $hospital->update(['status' => $status]);

        $this->dispatch('toast', type: 'success', message: 'Hospital status updated successfully.');
    }

    public function render()
    {
        $organizationId = Auth::guard('filament')->user()?->organization_id;
        $organization = $organizationId ? Organization::find($organizationId) : null;

        $hospitalQuery = Hospital::query()
            ->where('organization_id', $organizationId)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%')
                        ->orWhere('address', 'like', '%' . $this->search . '%')
                        ->orWhere('admin_email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->locationFilter !== 'all', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('city', 'like', '%' . $this->locationFilter . '%')
                        ->orWhere('area', 'like', '%' . $this->locationFilter . '%')
                        ->orWhere('address', 'like', '%' . $this->locationFilter . '%');
                });
            })
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            });

        $hospitals = (clone $hospitalQuery)
            ->orderBy('name')
            ->paginate(10);

        $allHospitals = Hospital::query()
            ->where('organization_id', $organizationId)
            ->get();

        return view('livewire.hospital-admin.hospitals.index', [
            'organization' => $organization,
            'hospitals' => $hospitals,
            'totalCount' => $allHospitals->count(),
            'activeCount' => $allHospitals->where('status', 'active')->count(),
            'totalHospitals' => $allHospitals->count(),
            'activeHospitals' => $allHospitals->where('status', 'active')->count(),
            'inactiveHospitals' => $allHospitals->where('status', 'inactive')->count(),
        ]);
    }
}
