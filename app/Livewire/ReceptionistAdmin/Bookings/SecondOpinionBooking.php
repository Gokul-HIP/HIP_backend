<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Models\Doctor;
use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Services\ReceptionistBookingScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SecondOpinionBooking extends Component
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
        if (! $this->scopeService()->findSecondOpinion($id)) {
            return;
        }

        $this->dispatch('openSecondOpinionStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (! $this->scopeService()->findSecondOpinion($id)) {
            return;
        }

        $this->id = $id;
        Flux::modal('delete-second-opinion')->show();
    }

    public function closeDeleteBookingModal(): void
    {
        $this->id = null;
        Flux::modal('delete-second-opinion')->close();
    }

    public function deleteBooking(): void
    {
        $booking = $this->id ? $this->scopeService()->findSecondOpinion($this->id) : null;

        if (! $booking) {
            $this->closeDeleteBookingModal();

            return;
        }

        SecondOpinionStatus::query()
            ->where('second_opinion_id', $booking->id)
            ->delete();

        $booking->delete();

        $this->dispatch('toast', type: 'success', message: 'Second opinion booking deleted successfully!');
        $this->closeDeleteBookingModal();
        $this->dispatch('refreshReceptionistSecondOpinionBookings');
    }

    #[On('refreshReceptionistSecondOpinionBookings')]
    public function refreshReceptionistSecondOpinionBookings(): void
    {
    }

    public function render(ReceptionistBookingScopeService $scope)
    {
        $hospitalIds = $scope->hospitalIds($this->hospitalFilter);
        $hospitals = $scope->accessibleHospitals();

        $baseQuery = SecondOpinion::query()->whereIn('branch_id', $hospitalIds);

        $query = (clone $baseQuery)
            ->with(['member', 'branch', 'doctor', 'speciality'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->doctorFilter !== 'all', fn ($q) => $q->where('doctor_id', $this->doctorFilter))
            ->when($this->dateFilter !== '', fn ($q) => $q->whereDate('preferred_date', $this->dateFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $search = trim($this->search);
                $q->where(function ($builder) use ($search) {
                    $builder
                        ->where('patient_name', 'like', '%' . $search . '%')
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

        $bookings = $query->paginate(10)->withPath(route('receptionist.second-opinion-bookings.index'));

        $organizationId = Auth::user()->organization_id;

        return view('livewire.receptionist-admin.bookings.second-opinion-booking', [
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
