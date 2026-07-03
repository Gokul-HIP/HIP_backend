<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Services\ReceptionistBookingScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DiagnosticBooking extends Component
{
    use ScopesReceptionistBookings;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $hospitalFilter = 'all';

    public string $dateFilter = '';

    public ?int $id = null;

    public function mount(ReceptionistBookingScopeService $scope): void
    {
        $this->hospitalFilter = $scope->defaultHospitalFilter();
    }

    public function updatingSearch(): void
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

    public function openUpdateStatusModal(int $id): void
    {
        if (! $this->scopeService()->findDiagnosticBooking($id)) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (! $this->scopeService()->findDiagnosticBooking($id)) {
            return;
        }

        $this->id = $id;
        Flux::modal('delete-booking')->show();
    }

    public function closeDeleteBookingModal(): void
    {
        $this->id = null;
        Flux::modal('delete-booking')->close();
    }

    public function deleteBooking(): void
    {
        $booking = $this->id ? $this->scopeService()->findDiagnosticBooking($this->id) : null;

        if (! $booking) {
            $this->closeDeleteBookingModal();

            return;
        }

        $booking->load('documents');

        foreach ($booking->documents as $document) {
            if ($document->document_path) {
                Storage::disk('public')->delete($document->document_path);
            }
            $document->delete();
        }

        DiagnosticTestBookingStatus::query()
            ->where('diagnostic_test_booking_id', $booking->id)
            ->delete();

        $booking->delete();

        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
        $this->closeDeleteBookingModal();
        $this->dispatch('refreshReceptionistDiagnosticBookings');
    }

    #[On('refreshReceptionistDiagnosticBookings')]
    public function refreshReceptionistDiagnosticBookings(): void
    {
    }

    protected function scopedQuery(ReceptionistBookingScopeService $scope)
    {
        $hospitalIds = $scope->hospitalIds($this->hospitalFilter);
        $diagnosticIds = $scope->diagnosticCenterIds($this->hospitalFilter);

        return DiagnosticTestBooking::query()
            ->where(function ($query) use ($hospitalIds, $diagnosticIds) {
                $query->whereIn('branch_id', $hospitalIds);
                if ($diagnosticIds !== []) {
                    $query->orWhereIn('diagnostic_center_id', $diagnosticIds);
                }
            });
    }

    public function render(ReceptionistBookingScopeService $scope)
    {
        $hospitals = $scope->accessibleHospitals();
        $hospitalsByDiagnostic = $hospitals->groupBy('diagnostic_center_id');

        $baseQuery = $this->scopedQuery($scope);

        $query = (clone $baseQuery)
            ->with(['member', 'diagnosticCenter', 'branch'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $search = trim($this->search);
                $q->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%')
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('hip_id', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('id');

        $bookings = $query->paginate(10)->withPath(route('receptionist.diagnostic-bookings.index'));

        $allTestIds = $bookings->getCollection()
            ->pluck('test_items')
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $labTests = DiagnosticLabTest::query()
            ->whereIn('id', $allTestIds)
            ->get(['id', 'test_name', 'test_code'])
            ->keyBy('id');

        return view('livewire.receptionist-admin.bookings.diagnostic-booking', [
            'bookings' => $bookings,
            'hospitals' => $hospitals,
            'hospitalsByDiagnostic' => $hospitalsByDiagnostic,
            'labTests' => $labTests,
            'totalBookings' => (clone $baseQuery)->count(),
            'pendingBookings' => (clone $baseQuery)->where('status', 'pending')->count(),
            'cancelledBookings' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'completedBookings' => (clone $baseQuery)->where('status', 'completed')->count(),
        ]);
    }
}
