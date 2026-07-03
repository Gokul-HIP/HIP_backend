<?php

namespace App\Livewire\ReceptionistAdmin\Patients;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\SecondOpinion;
use App\Services\ReceptionistBookingScopeService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $hospitalFilter = 'all';

    public string $statusFilter = 'all';

    public string $dateFilter = '';

    public function mount(ReceptionistBookingScopeService $scope): void
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(ReceptionistBookingScopeService $scope): void
    {
        $this->reset(['search', 'statusFilter', 'dateFilter']);
        $this->hospitalFilter = $scope->defaultHospitalFilter();
        $this->resetPage();
    }

    protected function patientRows(ReceptionistBookingScopeService $scope): Collection
    {
        $hospitalIds = $scope->hospitalIds($this->hospitalFilter);
        $diagnosticIds = $scope->diagnosticCenterIds($this->hospitalFilter);
        $hospitals = $scope->hospitalsKeyed();
        $search = trim($this->search);

        $records = collect();

        $doctorBookings = DoctorBooking::query()
            ->with(['member', 'hospital', 'patient'])
            ->whereIn('hospital_id', $hospitalIds)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhereHas('member', fn ($m) => $m->where('hip_id', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%'));
                });
            })
            ->get();

        foreach ($doctorBookings as $booking) {
            $records->push($this->normalizeDoctorBooking($booking, $hospitals));
        }

        $secondOpinions = SecondOpinion::query()
            ->with(['member', 'branch'])
            ->whereIn('branch_id', $hospitalIds)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('preferred_date', $this->dateFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($builder) use ($search) {
                    $builder->where('patient_name', 'like', '%' . $search . '%')
                        ->orWhereHas('member', fn ($m) => $m->where('hip_id', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile_num', 'like', '%' . $search . '%'));
                });
            })
            ->get();

        foreach ($secondOpinions as $booking) {
            $records->push($this->normalizeSecondOpinion($booking, $hospitals));
        }

        $diagnosticBookings = DiagnosticTestBooking::query()
            ->with(['member', 'branch', 'patient', 'diagnosticCenter'])
            ->where(function ($query) use ($hospitalIds, $diagnosticIds) {
                $query->whereIn('branch_id', $hospitalIds);
                if ($diagnosticIds !== []) {
                    $query->orWhereIn('diagnostic_center_id', $diagnosticIds);
                }
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhereHas('member', fn ($m) => $m->where('hip_id', 'like', '%' . $search . '%')
                            ->orWhere('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%'));
                });
            })
            ->get();

        foreach ($diagnosticBookings as $booking) {
            $records->push($this->normalizeDiagnosticBooking($booking, $hospitals));
        }

        return $records
            ->groupBy('patient_key')
            ->map(function (Collection $group) {
                $latest = $group->sortByDesc('sort_id')->first();

                return [
                    'patient_key' => $latest['patient_key'],
                    'name' => $latest['name'],
                    'member_hip_id' => $latest['member_hip_id'],
                    'mobile' => $latest['mobile'],
                    'hospital_name' => $latest['hospital_name'],
                    'bookings_count' => $group->count(),
                    'booking_types' => $group->pluck('booking_type')->unique()->values()->all(),
                    'last_booking_date' => $latest['booking_date_label'],
                    'last_status' => $latest['status_label'],
                    'initials' => $this->initials($latest['name']),
                    'sort_id' => $latest['sort_id'],
                ];
            })
            ->sortByDesc('sort_id')
            ->values();
    }

    protected function normalizeDoctorBooking(DoctorBooking $booking, Collection $hospitals): array
    {
        $name = trim($booking->name ?: ($booking->patient
            ? trim(($booking->patient->first_name ?? '') . ' ' . ($booking->patient->last_name ?? ''))
            : ($booking->member?->name ?? '')));

        return [
            'patient_key' => $this->patientKey($booking->patient_id, $booking->member_id, $name, $booking->mobile_number),
            'name' => $name !== '' ? $name : '—',
            'member_hip_id' => $booking->member?->hip_id ?: '—',
            'mobile' => $booking->mobile_number ? '+91 ' . $booking->mobile_number : ($booking->member?->mobile_num ?: '—'),
            'hospital_name' => $booking->hospital?->name ?: ($hospitals->get($booking->hospital_id)?->name ?? '—'),
            'booking_type' => 'Doctor',
            'booking_date_label' => $booking->booking_date?->format('d M Y') ?: '—',
            'status_label' => ucfirst((string) ($booking->status ?? 'pending')),
            'sort_id' => $booking->id,
        ];
    }

    protected function normalizeSecondOpinion(SecondOpinion $booking, Collection $hospitals): array
    {
        $name = trim($booking->patient_name ?: ($booking->member?->name ?? ''));

        return [
            'patient_key' => $this->patientKey($booking->patient_id, $booking->member_id, $name, $booking->member?->mobile_num),
            'name' => $name !== '' ? $name : '—',
            'member_hip_id' => $booking->member?->hip_id ?: '—',
            'mobile' => $booking->member?->mobile_num ? '+91 ' . $booking->member->mobile_num : '—',
            'hospital_name' => $booking->branch?->name ?: ($hospitals->get($booking->branch_id)?->name ?? '—'),
            'booking_type' => 'Second Opinion',
            'booking_date_label' => $booking->preferred_date?->format('d M Y') ?: '—',
            'status_label' => ucfirst((string) ($booking->status ?? 'pending')),
            'sort_id' => $booking->id,
        ];
    }

    protected function normalizeDiagnosticBooking(DiagnosticTestBooking $booking, Collection $hospitals): array
    {
        $name = trim($booking->name ?: ($booking->patient
            ? trim(($booking->patient->first_name ?? '') . ' ' . ($booking->patient->last_name ?? ''))
            : ($booking->member?->name ?? '')));

        return [
            'patient_key' => $this->patientKey($booking->patient_id, $booking->member_id, $name, $booking->mobile_number),
            'name' => $name !== '' ? $name : '—',
            'member_hip_id' => $booking->member?->hip_id ?: '—',
            'mobile' => $booking->mobile_number ? '+91 ' . $booking->mobile_number : ($booking->member?->mobile_num ?: '—'),
            'hospital_name' => $booking->branch?->name
                ?: ($hospitals->get($booking->branch_id)?->name
                ?: ($booking->diagnosticCenter?->name ?? '—')),
            'booking_type' => 'Diagnostic',
            'booking_date_label' => $booking->booking_date?->format('d M Y') ?: '—',
            'status_label' => ucfirst((string) ($booking->status ?? 'pending')),
            'sort_id' => $booking->id,
        ];
    }

    protected function patientKey(int|string|null $patientId, int|string|null $memberId, string $name, ?string $mobile): string
    {
        if (filled($patientId)) {
            return 'patient:' . $patientId;
        }

        if (filled($memberId)) {
            return 'member:' . $memberId;
        }

        return 'guest:' . md5(strtolower(trim($name)) . '|' . preg_replace('/\D/', '', (string) $mobile));
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($parts)->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('');

        return $letters !== '' ? $letters : '?';
    }

    public function render(ReceptionistBookingScopeService $scope)
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
            ['path' => route('receptionist.patients.index'), 'query' => request()->query()]
        );

        return view('livewire.receptionist-admin.patients.index', [
            'patients' => $patients,
            'hospitals' => $scope->accessibleHospitals(),
        ]);
    }
}
