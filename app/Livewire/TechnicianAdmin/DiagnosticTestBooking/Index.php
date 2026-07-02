<?php

namespace App\Livewire\TechnicianAdmin\DiagnosticTestBooking;

use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Services\TechnicianDiagnosticScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $hospitalFilter = 'all';

    public string $dateFilter = '';

    public ?int $id = null;

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

    public function clearDateFilter(): void
    {
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'hospitalFilter', 'dateFilter']);
        $this->resetPage();
    }

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    public function openUpdateStatusModal(int $id): void
    {
        if (! $this->scopeService()->findScopedBooking($id, $this->hospitalFilter)) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (! $this->scopeService()->findScopedBooking($id, $this->hospitalFilter)) {
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
        $booking = $this->id ? $this->scopeService()->findScopedBooking($this->id, $this->hospitalFilter) : null;

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
        $this->dispatch('refreshDiagnosticTestBookings');
    }

    #[On('refreshDiagnosticTestBookings')]
    public function refreshDiagnosticTestBookings(): void
    {
        // Force rerender.
    }

    public function render()
    {
        $scope = $this->scopeService();
        $hospitals = $scope->accessibleHospitals();
        $diagnosticIds = $scope->diagnosticCenterIds($this->hospitalFilter);

        $baseQuery = DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $diagnosticIds);

        $query = (clone $baseQuery)
            ->with(['member', 'diagnosticCenter', 'branch'])
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%')
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('hip_id', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('diagnosticCenter', function ($diagnosticQuery) use ($search) {
                            $diagnosticQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->orderByDesc('id');

        $bookings = $query->paginate(10);

        $hospitalsByDiagnostic = $hospitals->groupBy('diagnostic_center_id');

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

        return view('livewire.technician-admin.diagnostic-test-booking.index', [
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
