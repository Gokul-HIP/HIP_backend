<?php

namespace App\Livewire\Admin\DiagnosticTestBooking;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Diagnostic;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public $search = '';
    public $status = 'all';
    public $testTypeFilter = 'all';
    public $diagnosticFilter = 'all';
    public $dateFilter = '';
    public $id;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingTestTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingDiagnosticFilter()
    {
        $this->resetPage();
    }

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilter()
    {
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->dispatch('openUpdateStatusModal', id: $id);
        $this->render();
    }

    public function openDeleteBookingModal($id)
    {
        $this->id = $id;
        Flux::modal('delete-booking')->show();
    }

    public function closeDeleteBookingModal()
    {
        $this->id = null;
        Flux::modal('delete-booking')->close();
        $this->render();
    }

    public function deleteBooking()
    {
        $booking = DiagnosticTestBooking::find($this->id);

        $bookingStatuses = DiagnosticTestBookingStatus::where('diagnostic_test_booking_id', $this->id)->get();

        foreach ($bookingStatuses as $status) {
            $status->delete();
        }

        $booking->delete();

        $this->dispatch('refreshDiagnosticTestBookings');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
    }

    #[On('refreshDiagnosticTestBookings')]
    public function refreshDiagnosticTestBookings()
    {
        $this->render();
    }

    public function render()
    {
        $query = DiagnosticTestBooking::with(['member', 'diagnosticCenter'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('diagnosticCenter', function ($diagnosticQuery) {
                            $diagnosticQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->testTypeFilter !== 'all', function ($q) {
                $q->where('test_type', $this->testTypeFilter);
            })
            ->when($this->diagnosticFilter !== 'all', function ($q) {
                $q->where('diagnostic_center_id', $this->diagnosticFilter);
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $diagnosticTestBookings = $query->paginate(10);

        $diagnosticIds = DiagnosticTestBooking::distinct()->pluck('diagnostic_center_id')->filter();
        $availableDiagnostics = Diagnostic::whereIn('id', $diagnosticIds)->orderBy('name')->get();

        return view('livewire.admin.diagnostic-test-booking.index', [
            'diagnosticTestBookings' => $diagnosticTestBookings,
            'availableDiagnostics' => $availableDiagnostics,
        ]);
    }
}
