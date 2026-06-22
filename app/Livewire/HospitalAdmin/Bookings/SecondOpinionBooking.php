<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Doctor;
use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SecondOpinionBooking extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $statusFilter = 'all';
    public string $doctorFilter = 'all';
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

    public function updatingDoctorFilter(): void
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

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'doctorFilter', 'hospitalFilter', 'dateFilter']);
        $this->resetPage();
    }

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function findScopedBooking(int $id): ?SecondOpinion
    {
        return SecondOpinion::query()
            ->whereIn('branch_id', $this->organizationHospitalIds())
            ->find($id);
    }

    public function openUpdateStatusModal(int $id): void
    {
        if (! $this->findScopedBooking($id)) {
            return;
        }

        $this->dispatch('openHealthcareSecondOpinionStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (! $this->findScopedBooking($id)) {
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
        $booking = $this->id ? $this->findScopedBooking($this->id) : null;

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
        $this->dispatch('refreshHealthcareSecondOpinions');
    }

    #[On('refreshHealthcareSecondOpinions')]
    public function refreshHealthcareSecondOpinions(): void
    {
        // Force rerender after modal actions.
    }

    public function render()
    {
        $organizationId = Auth::user()->organization_id;
        $hospitalIds = $this->organizationHospitalIds();

        $baseScopedQuery = SecondOpinion::query()
            ->whereIn('branch_id', $hospitalIds);

        $query = SecondOpinion::query()
            ->with(['member', 'branch', 'doctor', 'speciality'])
            ->whereIn('branch_id', $hospitalIds);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->doctorFilter !== 'all') {
            $query->where('doctor_id', (int) $this->doctorFilter);
        }

        if ($this->hospitalFilter !== 'all') {
            $query->where('branch_id', (int) $this->hospitalFilter);
        }

        if ($this->dateFilter !== '') {
            $query->whereDate('preferred_date', $this->dateFilter);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('patient_name', 'like', '%' . $search . '%')
                    ->orWhere('diagnosis', 'like', '%' . $search . '%')
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                            ->orWhere('hip_id', 'like', '%' . $search . '%')
                            ->orWhere('mobile_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('doctor', function ($doctorQuery) use ($search) {
                        $doctorQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('speciality', function ($specialityQuery) use ($search) {
                        $specialityQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $secondOpinions = $query
            ->orderByDesc('id')
            ->paginate(10)
            ->withPath(route('healthcare.second-opinion.booking'));

        $bookingRows = $secondOpinions->getCollection()->map(function (SecondOpinion $booking) {
            $slots = collect($booking->preferred_time_slots ?? [])->filter()->values();
            $timeLabel = match ($slots->count()) {
                0 => '-',
                1 => (string) $slots->first(),
                default => $slots->first() . ' - ' . $slots->last(),
            };

            $status = strtolower((string) ($booking->status ?? 'pending'));
            $statusStyle = match ($status) {
                'confirmed' => 'background:#DCFCE7; color:#16A34A;',
                'completed' => 'background:#DBEAFE; color:#2563EB;',
                'cancelled' => 'background:#FEE2E2; color:#DC2626;',
                default => 'background:#FEF3C7; color:#D97706;',
            };

            return [
                'id' => $booking->id,
                'apt_id' => 'SO' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                'patient_name' => $booking->patient_name ?: '-',
                'relationship' => $booking->relationship ? ucfirst($booking->relationship) : null,
                'member_name' => $booking->member?->name ?: '-',
                'member_hip_id' => $booking->member?->hip_id ?: 'N/A',
                'hospital_name' => $booking->branch?->name ?: '-',
                'doctor_name' => $booking->doctor?->name ? 'Dr ' . $booking->doctor->name : '-',
                'speciality_name' => $booking->speciality?->name ?: '-',
                'preferred_date' => $booking->preferred_date?->format('M d, Y') ?: '-',
                'preferred_time' => $timeLabel,
                'payment_mode_label' => $booking->payment_mode_label,
                'status_label' => ucfirst($status),
                'status_style' => $statusStyle,
            ];
        });

        $secondOpinions->setCollection($bookingRows);

        return view('livewire.hospital-admin.bookings.second-opinion-booking', [
            'bookingRows' => $secondOpinions,
            'availableDoctors' => Doctor::query()
                ->where('organization_id', $organizationId)
                ->orderBy('name')
                ->get(['id', 'name']),
            'availableHospitals' => Hospital::query()
                ->whereIn('id', $hospitalIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalBookings' => $baseScopedQuery->count(),
            'pendingBookings' => (clone $baseScopedQuery)->where('status', 'pending')->count(),
            'cancelledBookings' => (clone $baseScopedQuery)->where('status', 'cancelled')->count(),
            'completedBookings' => (clone $baseScopedQuery)->where('status', 'completed')->count(),
            'upcomingBookings' => (clone $baseScopedQuery)
                ->whereBetween('preferred_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
        ]);
    }
}
