<?php

namespace App\Livewire\Admin\CaregiverBooking;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\CaregiverBooking;
use App\Models\CaregiverBookingStatus;
use App\Models\Caregiver;
use App\Models\WellnessCenters;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $status = 'all';
    public $caregiverFilter = 'all';
    public $wellnessCenterFilter = 'all';
    public $caregiverCategoryFilter = 'all';
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

    public function updatingCaregiverFilter()
    {
        $this->resetPage();
    }

    public function updatingWellnessCenterFilter()
    {
        $this->resetPage();
    }

    public function updatingCaregiverCategoryFilter()
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
        $caregiverBooking = CaregiverBooking::find($this->id);

        $caregiverBookingStatuses = CaregiverBookingStatus::where('caregiver_booking_id', $this->id)->get();

        foreach ($caregiverBookingStatuses as $status) {
            $status->delete();
        }

        $caregiverBooking->delete();

        $this->dispatch('refreshCaregiverBookings');
        $this->closeDeleteBookingModal();
        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
    }

    #[On('refreshCaregiverBookings')]
    public function refreshCaregiverBookings()
    {
        $this->render();
    }

    public function render()
    {
        $query = CaregiverBooking::with(['member', 'caregiver', 'wellnessCenter'])
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($memberQuery) {
                            $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('caregiver', function ($caregiverQuery) {
                            $caregiverQuery->where('name', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('wellnessCenter', function ($wellnessCenterQuery) {
                            $wellnessCenterQuery->where('centre_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->status !== 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->caregiverFilter !== 'all', function ($q) {
                $q->where('caregiver_id', $this->caregiverFilter);
            })
            ->when($this->wellnessCenterFilter !== 'all', function ($q) {
                $q->where('wellness_center_id', $this->wellnessCenterFilter);
            })
            ->when($this->caregiverCategoryFilter !== 'all', function ($q) {
                $q->whereHas('caregiver', function ($caregiverQuery) {
                    $caregiverQuery->where('category', $this->caregiverCategoryFilter);
                });
            })
            ->when($this->dateFilter, function ($q) {
                $q->whereDate('booking_date', $this->dateFilter);
            })
            ->orderBy('id', 'desc');

        $caregiverBookings = $query->paginate(10);

        // Calculate statistics from the database (not from paginated results)
        $totalBookings = CaregiverBooking::count();
        $totalPending = CaregiverBooking::where('status', 'pending')->count();
        $totalCancellations = CaregiverBooking::where('status', 'cancelled')->count();
        $totalCompleted = CaregiverBooking::where('status', 'completed')->count();
        $upcomingBookings = CaregiverBooking::where('booking_date', '>=', now())
            ->where('booking_date', '<=', now()->addDays(7))
            ->count();

        $caregiverIds = CaregiverBooking::distinct()->pluck('caregiver_id')->filter();
        $availableCaregivers = Caregiver::whereIn('id', $caregiverIds)->orderBy('name')->get();

        $wellnessCenterIds = CaregiverBooking::distinct()->pluck('wellness_center_id')->filter();
        $availableWellnessCenters = WellnessCenters::whereIn('id', $wellnessCenterIds)->orderBy('centre_name')->get();

        $availableCaregiverCategories = Caregiver::whereIn('id', $caregiverIds)
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return view('livewire.admin.caregiver-booking.index', [
            'caregiverBookings' => $caregiverBookings,
            'totalBookings' => $totalBookings,
            'totalPending' => $totalPending,
            'totalCancellations' => $totalCancellations,
            'totalCompleted' => $totalCompleted,
            'upcomingBookings' => $upcomingBookings,
            'availableCaregivers' => $availableCaregivers,
            'availableWellnessCenters' => $availableWellnessCenters,
            'availableCaregiverCategories' => $availableCaregiverCategories,
        ]);
    }

}
