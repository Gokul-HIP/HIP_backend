<?php

namespace App\Livewire\TechnicianAdmin\Patients;

use App\Models\DiagnosticTestBooking;
use App\Services\TechnicianDiagnosticScopeService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $hospitalFilter = 'all';

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

    /**
     * @return Collection<int, array<string, mixed>>
     */
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
                $hospital = $latest->branch ?: $hospitals->get($latest->diagnostic_center_id);

                return [
                    'patient_key' => $latest->patient_id ?: ('member:' . $latest->member_id),
                    'patient_name' => $latest->name ?: ($latest->patient?->first_name . ' ' . $latest->patient?->last_name),
                    'member_hip_id' => $latest->member?->hip_id ?: 'N/A',
                    'mobile' => $latest->mobile_number ? '+91 ' . $latest->mobile_number : '-',
                    'hospital_name' => $hospital?->name ?: ($latest->diagnosticCenter?->name ?: '-'),
                    'bookings_count' => $group->count(),
                    'last_booking_date' => $latest->booking_date?->format('d M Y') ?: '-',
                    'last_booking_id' => $latest->id,
                    'last_status' => ucfirst((string) ($latest->status ?? 'pending')),
                ];
            })
            ->values()
            ->sortByDesc('last_booking_id')
            ->values();
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
