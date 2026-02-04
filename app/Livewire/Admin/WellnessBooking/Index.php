<?php

namespace App\Livewire\Admin\WellnessBooking;

use Livewire\Component;
use App\Models\WellnessBooking;
use App\Models\WellnessCenters;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\WellnessBookingStatus;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $typeFilter = 'all';
    public $locationFilter = 'all';
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

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
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
        $wellnessBooking = WellnessBooking::find($this->id);
        
        $wellnessBookingStatuses = WellnessBookingStatus::where('wellness_booking_id', $this->id)->get();

        foreach ($wellnessBookingStatuses as $status) {
            $status->delete();
        }
        
        $wellnessBooking->delete();

        $this->dispatch('refreshWellnessBookings');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
    }

    #[On('refreshWellnessBookings')]
    public function refreshWellnessBookings()
    {
        $this->render();
    }

    public function render()
    {
        $query = WellnessBooking::with(['member', 'center'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('center', function ($centerQuery) {
                            $centerQuery->where('centre_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $q->whereHas('center', function ($centerQuery) {
                    $centerQuery->where('centre_type', $this->typeFilter);
                });
            })
            ->when($this->locationFilter !== 'all', function ($q) {
                $q->whereHas('center', function ($centerQuery) {
                    $centerQuery->where('city', $this->locationFilter);
                });
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $wellnessBookings = $query->paginate(10);

        // Get available types from wellness bookings
        $centerIds = WellnessBooking::distinct()->pluck('center_id')->filter();
        $availableCenters = WellnessCenters::whereIn('id', $centerIds)->get();
        
        $availableTypes = $availableCenters->pluck('centre_type')->unique()->filter()->sort()->values();
        $availableLocations = $availableCenters->pluck('city')->unique()->filter()->sort()->values();

        // Calculate statistics
        $totalBookings = WellnessBooking::count();
        $totalPending = WellnessBooking::where('status', 'pending')->count();
        $totalCancellations = WellnessBooking::where('status', 'cancelled')->count();
        $totalCompleted = WellnessBooking::where('status', 'completed')->count();
        $upcomingBookings = WellnessBooking::where('booking_date', '>=', now())
            ->where('booking_date', '<=', now()->addDays(7))
            ->count();

        return view('livewire.admin.wellness-booking.index', [
            'wellnessBookings' => $wellnessBookings,
            'availableTypes' => $availableTypes,
            'availableLocations' => $availableLocations,
            'totalBookings' => $totalBookings,
            'totalPending' => $totalPending,
            'totalCancellations' => $totalCancellations,
            'totalCompleted' => $totalCompleted,
            'upcomingBookings' => $upcomingBookings,
        ]);
    }
}
