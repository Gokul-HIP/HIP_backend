<?php

namespace App\Livewire\Admin\StemCellBooking;

use Livewire\Component;
use App\Models\StemCellBooking;
use App\Models\HIPUser;
use App\Models\StemCellBookingStatus;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
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

    public function updatingDateFilter()
    {
        $this->resetPage();
    }

    public function clearDateFilter()
    {
        $this->dateFilter = '';
        $this->resetPage();
    }

    public function openDeleteBookingModal($id)
    {
        $this->id = $id;
        Flux::modal('delete-stem-cell-booking')->show();
    }

    public function closeDeleteBookingModal()
    {
        $this->id = null;
        Flux::modal('delete-stem-cell-booking')->close();
    }

    public function deleteBooking()
    {
        $stemCellBooking = StemCellBooking::find($this->id);
        
        if (!$stemCellBooking) {
            $this->dispatch('toast', type: 'error', message: 'Stem cell booking not found.');
            $this->closeDeleteBookingModal();
            return;
        }
        
        $stemCellBookingStatuses = StemCellBookingStatus::where('stem_cell_booking_id', $this->id)->get();

        foreach ($stemCellBookingStatuses as $status) {
            $status->delete();
        }
        
        $stemCellBooking->delete();

        $this->dispatch('refreshStemCellBookings');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Stem cell booking deleted successfully!');
    }

    #[On('refreshStemCellBookings')]
    public function refreshStemCellBookings()
    {
        // This will trigger a re-render with fresh data
    }

    public function render()
    {
        $query = StemCellBooking::with(['member'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $stemCellBookings = $query->paginate(10);

        // Calculate statistics from the database (not from paginated results)
        $totalBookings = StemCellBooking::count();
        $totalPending = StemCellBooking::where('status', 'pending')->count();
        $totalCancellations = StemCellBooking::where('status', 'cancelled')->count();
        $totalCompleted = StemCellBooking::where('status', 'completed')->count();
        $upcomingBookings = StemCellBooking::where('booking_date', '>=', now())
            ->where('booking_date', '<=', now()->addDays(7))
            ->count();

        return view('livewire.admin.stem-cell-booking.index', [
            'stemCellBookings' => $stemCellBookings,
            'totalBookings' => $totalBookings,
            'totalPending' => $totalPending,
            'totalCancellations' => $totalCancellations,
            'totalCompleted' => $totalCompleted,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }
}
