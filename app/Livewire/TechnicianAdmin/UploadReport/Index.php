<?php

namespace App\Livewire\TechnicianAdmin\UploadReport;

use App\Models\DiagnosticTestBooking;
use App\Services\TechnicianDiagnosticScopeService;
use App\Support\TechnicianPatientViewData;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public int $perPage = 10;

    public string $search = '';

    public string $hospitalFilter = 'all';

    public string $statusFilter = 'all';

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

    public function resetFilters(): void
    {
        $this->reset(['search', 'hospitalFilter', 'statusFilter']);
        $this->resetPage();
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    protected function patientRows(): Collection
    {
        $scope = $this->scopeService();
        $diagnosticIds = $scope->diagnosticCenterIds($this->hospitalFilter);

        $bookings = DiagnosticTestBooking::query()
            ->with(['member', 'patient', 'branch', 'diagnosticCenter'])
            ->whereIn('diagnostic_center_id', $diagnosticIds)
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
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
            ->groupBy(fn (DiagnosticTestBooking $booking) => $booking->patient_id ?: ('member:' . $booking->member_id))
            ->map(function (Collection $group) {
                /** @var DiagnosticTestBooking $latest */
                $latest = $group->first();
                $summary = TechnicianPatientViewData::buildPatientSummary($latest);
                $patient = $latest->patient;
                $member = $latest->member;
                $dob = $patient?->dob ?: $member?->dob;
                $age = $dob ? Carbon::parse($dob)->age : null;

                return array_merge($summary, [
                    'age' => $age,
                    'gender' => ucfirst((string) ($patient?->gender ?: $member?->gender ?: '—')),
                    'last_visit' => $latest->booking_date?->format('d M Y') ?: '—',
                    'hospital_name' => $latest->branch?->name ?: ($latest->diagnosticCenter?->name ?: '—'),
                    'status_label' => ucfirst((string) ($latest->status ?? 'pending')),
                    'avatar_color' => '#1A9FD4',
                ]);
            })
            ->values()
            ->sortByDesc('booking_id')
            ->values();
    }

    public function render()
    {
        $rows = $this->patientRows();
        $page = $this->getPage();
        $items = $rows->forPage($page, $this->perPage)->values();

        $patients = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $rows->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('livewire.technician-admin.upload-report.index', [
            'patients' => $patients,
            'hospitals' => $this->scopeService()->accessibleHospitals(),
            'totalPatients' => $rows->count(),
        ]);
    }
}
