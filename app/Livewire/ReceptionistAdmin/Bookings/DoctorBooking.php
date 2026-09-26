<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\Doctor;
use App\Models\DoctorBooking as DoctorBookingModel;
use App\Models\DoctorBookingStatus;
use App\Services\ReceptionistBookingScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DoctorBooking extends Component
{
    use ScopesReceptionistBookings;
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = 'all';

    public string $hospitalFilter = 'all';

    public string $doctorFilter = 'all';

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

    public function updatingDoctorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(ReceptionistBookingScopeService $scope): void
    {
        $this->reset(['search', 'statusFilter', 'doctorFilter', 'dateFilter']);
        $this->hospitalFilter = $scope->defaultHospitalFilter();
        $this->resetPage();
    }

    public function openUpdateStatusModal(int $id): void
    {
        if (! $this->scopeService()->findDoctorBooking($id)) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $id);
    }

    public function openRescheduleModal(int $id): void
    {
        if (! $this->scopeService()->findDoctorBooking($id)) {
            return;
        }

        $this->dispatch('openRescheduleModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (! $this->scopeService()->findDoctorBooking($id)) {
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
        $booking = $this->id ? $this->scopeService()->findDoctorBooking($this->id) : null;

        if (! $booking) {
            $this->closeDeleteBookingModal();

            return;
        }

        DoctorBookingStatus::query()
            ->where('doctor_booking_id', $booking->id)
            ->delete();

        $booking->delete();

        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
        $this->closeDeleteBookingModal();
        $this->dispatch('refreshReceptionistDoctorBookings');
    }

    #[On('refreshReceptionistDoctorBookings')]
    public function refreshReceptionistDoctorBookings(): void
    {
    }

    public function render(ReceptionistBookingScopeService $scope)
    {
        $hospitalIds = $scope->hospitalIds($this->hospitalFilter);
        $hospitals = $scope->accessibleHospitals();

        $baseQuery = DoctorBookingModel::query()->whereIn('hospital_id', $hospitalIds);

        $query = (clone $baseQuery)
            ->with(['member', 'hospital', 'doctor'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->doctorFilter !== 'all', fn ($q) => $q->where('doctor_id', $this->doctorFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('booking_date', $this->dateFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $search = trim($this->search);
                $q->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile_number', 'like', '%' . $search . '%')
                        ->orWhere('id', 'like', '%' . $search . '%')
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('hip_id', 'like', '%' . $search . '%')
                                ->orWhere('mobile_num', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('doctor', fn ($doctorQuery) => $doctorQuery->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc('id');

        $bookings = $query->paginate(10)->withPath(route('receptionist.doctor-bookings.index'));

        $organizationId = Auth::user()->organization_id;

        return view('livewire.receptionist-admin.bookings.doctor-booking', [
            'bookings' => $bookings,
            'hospitals' => $hospitals,
            'availableDoctors' => Doctor::query()
                ->where('organization_id', $organizationId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalBookings' => (clone $baseQuery)->count(),
            'pendingBookings' => (clone $baseQuery)->where('status', 'pending')->count(),
            'cancelledBookings' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'completedBookings' => (clone $baseQuery)->where('status', 'completed')->count(),
        ]);
    }
}
