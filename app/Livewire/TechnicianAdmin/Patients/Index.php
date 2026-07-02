<?php

namespace App\Livewire\TechnicianAdmin\Patients;

use App\Models\DiagnosticTestBooking;
use App\Services\TechnicianDiagnosticScopeService;
use App\Support\TechnicianPatientViewData;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $hospitalFilter = 'all';

    public bool $showPatientProfilePanel = false;

    public ?array $profilePatient = null;

    public bool $showBookingHistoryModal = false;

    public ?array $historyPatient = null;

    public array $historyStats = [];

    public Collection $historyBookings;

    public function mount(): void
    {
        $this->historyBookings = collect();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'hospitalFilter']);
        $this->resetPage();
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    protected function findScopedBooking(int $id): ?DiagnosticTestBooking
    {
        return $this->scopeService()->findScopedBooking($id, $this->hospitalFilter);
    }

    protected function patientRows(): Collection
    {
        $scope = $this->scopeService();
        $diagnosticIds = $scope->diagnosticCenterIds($this->hospitalFilter);
        $hospitals = $scope->accessibleHospitals()->keyBy('diagnostic_center_id');

        $bookings = DiagnosticTestBooking::query()
            ->with(['member', 'patient', 'branch', 'diagnosticCenter'])
            ->whereIn('diagnostic_center_id', $diagnosticIds)
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('hip_id', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();

        return $bookings
            ->groupBy(fn (DiagnosticTestBooking $booking) => ($booking->patient_id ?: 'member:' . $booking->member_id))
            ->map(function (Collection $group) use ($hospitals) {
                /** @var DiagnosticTestBooking $latest */
                $latest = $group->first();
                $summary = TechnicianPatientViewData::buildPatientSummary($latest);
                $hospital = $latest->branch ?: $hospitals->get($latest->diagnostic_center_id);

                return array_merge($summary, [
                    'patient_key' => $latest->patient_id ?: ('member:' . $latest->member_id),
                    'patient_name' => $summary['name'],
                    'member_hip_id' => $summary['uhid'],
                    'hospital_name' => $hospital?->name ?: ($latest->diagnosticCenter?->name ?: '-'),
                    'bookings_count' => $group->count(),
                    'last_booking_date' => $latest->booking_date?->format('d M Y') ?: '-',
                    'last_booking_id' => $latest->id,
                    'last_status' => ucfirst((string) ($latest->status ?? 'pending')),
                    'initials' => $summary['initials'],
                ]);
            })
            ->values()
            ->sortByDesc('last_booking_id')
            ->values();
    }

    public function openPatientProfile(int $bookingId): void
    {
        $booking = $this->findScopedBooking($bookingId);

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient profile is unavailable.');

            return;
        }

        $this->profilePatient = TechnicianPatientViewData::buildPatientProfile($booking);
        $this->showPatientProfilePanel = true;
    }

    public function closePatientProfile(): void
    {
        $this->showPatientProfilePanel = false;
        $this->profilePatient = null;
    }

    public function openBookingHistory(int $bookingId): void
    {
        $booking = $this->findScopedBooking($bookingId);

        if (! $booking) {
            return;
        }

        $allBookings = TechnicianPatientViewData::patientBookings($booking);
        $this->historyPatient = TechnicianPatientViewData::buildPatientSummary($booking);
        $this->historyStats = TechnicianPatientViewData::buildHistoryStats($allBookings);
        $this->historyBookings = $allBookings;
        $this->showBookingHistoryModal = true;
    }

    public function closeBookingHistory(): void
    {
        $this->showBookingHistoryModal = false;
        $this->historyPatient = null;
        $this->historyBookings = collect();
    }

    public function render()
    {
        $rows = $this->patientRows();
        $page = $this->getPage();
        $perPage = 10;
        $items = $rows->forPage($page, $perPage)->values();

        $patients = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.technician-admin.patients.index', [
            'patients' => $patients,
            'hospitals' => $this->scopeService()->accessibleHospitals(),
        ]);
    }
}
