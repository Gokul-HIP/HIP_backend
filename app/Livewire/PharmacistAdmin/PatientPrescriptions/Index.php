<?php

namespace App\Livewire\PharmacistAdmin\PatientPrescriptions;

use App\Models\Prescription;
use App\Services\PharmacistScopeService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $hospitalFilter = 'all';

    public function mount(PharmacistScopeService $scope): void
    {
        $this->hospitalFilter = $scope->defaultHospitalFilter();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(PharmacistScopeService $scope): void
    {
        $this->search = '';
        $this->hospitalFilter = $scope->defaultHospitalFilter();
        $this->resetPage();
    }

    protected function patientRows(PharmacistScopeService $scope): Collection
    {
        $search = trim($this->search);
        $hospitalIds = $scope->hospitalIds($this->hospitalFilter);
        $hospitals = $scope->hospitalsKeyed();

        if ($hospitalIds === []) {
            return collect();
        }

        $prescriptions = Prescription::query()
            ->with(['patient', 'member', 'doctor', 'hospital'])
            ->whereIn('hospital_id', $hospitalIds)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->whereHas('patient', function ($patientQuery) use ($search) {
                            $patientQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%']);
                        })
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('hip_id', 'like', '%' . $search . '%')
                                ->orWhere('mobile_num', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return $prescriptions
            ->groupBy(fn (Prescription $rx) => $this->patientKey($rx))
            ->map(function (Collection $group) use ($hospitals) {
                /** @var Prescription $latest */
                $latest = $group->first();
                $patient = $latest->patient;
                $member = $latest->member;

                $name = $patient
                    ? trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? ''))
                    : ($member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : '—');

                return [
                    'patient_key' => $this->patientKey($latest),
                    'patient_id' => $latest->patient_id,
                    'member_id' => $latest->member_id,
                    'name' => $name !== '' ? $name : '—',
                    'member_hip_id' => $member?->hip_id ?: '—',
                    'mobile' => $patient?->mobile_num
                        ? '+91 ' . $patient->mobile_num
                        : ($member?->mobile_num ? '+91 ' . $member->mobile_num : '—'),
                    'hospital_name' => $latest->hospital?->name ?: ($hospitals->get($latest->hospital_id)?->name ?? '—'),
                    'prescriptions_count' => $group->count(),
                    'last_prescription_date' => $latest->created_at?->format('d M Y') ?: '—',
                    'last_doctor' => $latest->doctor?->name ? 'Dr ' . $latest->doctor->name : '—',
                    'last_status' => ucfirst((string) ($latest->status ?? 'draft')),
                    'latest_prescription_id' => $latest->id,
                    'initials' => $this->initials($name),
                ];
            })
            ->sortByDesc('latest_prescription_id')
            ->values();
    }

    protected function patientKey(Prescription $prescription): string
    {
        if (filled($prescription->patient_id)) {
            return 'patient:' . $prescription->patient_id;
        }

        if (filled($prescription->member_id)) {
            return 'member:' . $prescription->member_id;
        }

        return 'rx:' . $prescription->id;
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');

        return $letters !== '' ? $letters : '?';
    }

    public function render(PharmacistScopeService $scope)
    {
        $rows = $this->patientRows($scope);
        $page = $this->getPage();
        $perPage = 10;
        $items = $rows->forPage($page, $perPage)->values();

        $patients = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => route('pharmacist.patient-prescriptions.index'), 'query' => request()->query()]
        );

        return view('livewire.pharmacist-admin.patient-prescriptions.index', [
            'patients' => $patients,
            'hospitals' => $scope->accessibleHospitals(),
        ]);
    }
}
