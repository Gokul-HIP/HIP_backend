<?php

namespace App\Livewire\HospitalAdmin\Users;

use App\Models\Doctor;
use App\Models\Hospital;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Doctors extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $sortBy = 'name_asc';
    public string $statusFilter = 'all';
    public string $hospitalFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSortBy(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function render()
    {
        $organizationId = Auth::user()->organization_id;
        $hospitalIds = $this->organizationHospitalIds();

        $query = Doctor::query()
            ->where('organization_id', $organizationId);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->hospitalFilter !== 'all') {
            $query->whereJsonContains('hospital_ids', (int) $this->hospitalFilter);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%');
            });
        }

        match ($this->sortBy) {
            'name_desc' => $query->orderBy('name', 'desc'),
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            default => $query->orderBy('name'),
        };

        $totalDoctors = Doctor::query()
            ->where('organization_id', $organizationId)
            ->count();

        $activeDoctors = Doctor::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'active')
            ->count();

        $doctors = $query
            ->paginate(10)
            ->withPath(route('healthcare.doctors.index'));

        $hospitals = Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $doctors->setCollection(
            $doctors->getCollection()->map(function (Doctor $doctor) use ($hospitals) {
                $doctorHospitalNames = collect($doctor->hospital_ids ?? [])
                    ->map(fn ($id) => $hospitals->get((int) $id)?->name)
                    ->filter()
                    ->values();

                $displayName = $doctor->name ?: '-';
                $initials = collect(explode(' ', $displayName))
                    ->filter()
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->take(2)
                    ->implode('');

                return [
                    'id' => $doctor->id,
                    'name' => $displayName,
                    'doctor_id' => 'DOC-' . str_pad((string) $doctor->id, 5, '0', STR_PAD_LEFT),
                    'mobile_number' => $doctor->mobile_number ?: '-',
                    'hospital_name' => $doctorHospitalNames->isNotEmpty() ? $doctorHospitalNames->join(', ') : '-',
                    'qualification' => $doctor->qualification_names ?: '-',
                    'experience' => $doctor->working_since ? $doctor->working_since . ' Years' : '-',
                    'status' => strtolower((string) ($doctor->status ?? 'inactive')),
                    'status_label' => ucfirst((string) ($doctor->status ?? 'inactive')),
                    'created_at' => optional($doctor->created_at)?->format('M d, Y') ?: '-',
                    'initials' => $initials ?: 'DR',
                ];
            })
        );

        return view('livewire.hospital-admin.users.doctors', [
            'doctors' => $doctors,
            'totalDoctors' => $totalDoctors,
            'activeDoctors' => $activeDoctors,
            'availableHospitals' => $hospitals->values(),
            'sortOptions' => [
                'name_asc' => 'Name (A-Z)',
                'name_desc' => 'Name (Z-A)',
                'newest' => 'Newest First',
                'oldest' => 'Oldest First',
            ],
            'statusOptions' => [
                'all' => 'All Status',
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
        ]);
    }
}
