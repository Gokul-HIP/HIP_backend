<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Doctor;
use App\Models\DoctorBooking as DoctorBookingModel;
use App\Models\DoctorBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DoctorBooking extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $statusFilter = 'all';
    public string $doctorFilter = 'all';
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

    public function updatingDateFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'doctorFilter', 'dateFilter']);
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

    protected function findScopedBooking(int $id): ?DoctorBookingModel
    {
        return DoctorBookingModel::query()
            ->whereIn('hospital_id', $this->organizationHospitalIds())
            ->find($id);
    }

    public function openUpdateStatusModal(int $id): void
    {
        if (!$this->findScopedBooking($id)) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $id);
    }

    public function openDeleteBookingModal(int $id): void
    {
        if (!$this->findScopedBooking($id)) {
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
        $booking = $this->id ? $this->findScopedBooking($this->id) : null;

        if (!$booking) {
            $this->closeDeleteBookingModal();
            return;
        }

        DoctorBookingStatus::query()
            ->where('doctor_booking_id', $booking->id)
            ->delete();

        $booking->delete();

        $this->dispatch('toast', type: 'success', message: 'Booking deleted successfully!');
        $this->closeDeleteBookingModal();
        $this->dispatch('refreshDoctorBookings');
    }

    #[On('refreshDoctorBookings')]
    public function refreshDoctorBookings(): void
    {
        // Force a rerender after modal actions.
    }

    public function render()
    {
        $organizationId = Auth::user()->organization_id;
        $hospitalIds = $this->organizationHospitalIds();

        $baseScopedQuery = DoctorBookingModel::query()
            ->whereIn('hospital_id', $hospitalIds);

        $query = DoctorBookingModel::query()
            ->with(['member', 'hospital', 'doctor'])
            ->whereIn('hospital_id', $hospitalIds);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->doctorFilter !== 'all') {
            $query->where('doctor_id', (int) $this->doctorFilter);
        }

        if ($this->dateFilter !== '') {
            $query->whereDate('booking_date', $this->dateFilter);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                            ->orWhere('hip_id', 'like', '%' . $search . '%')
                            ->orWhere('mobile_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('hospital', function ($hospitalQuery) use ($search) {
                        $hospitalQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('doctor', function ($doctorQuery) use ($search) {
                        $doctorQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $bookingRows = $query
            ->orderByDesc('id')
            ->paginate(10)
            ->withPath(route('healthcare.doctor.booking'));

        $bookingRows->setCollection(
            $bookingRows->getCollection()->map(function (DoctorBookingModel $booking) {
                $timeSlots = collect($booking->required_time_slots ?? [])->filter()->values();
                $timeLabel = match ($timeSlots->count()) {
                    0 => '-',
                    1 => (string) $timeSlots->first(),
                    default => $timeSlots->first() . ' - ' . $timeSlots->last(),
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
                    'apt_id' => 'APT' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT),
                    'name' => $booking->name ?: '-',
                    'member_name' => $booking->member?->name ?: '-',
                    'member_hip_id' => $booking->member?->hip_id ?: 'N/A',
                    'mobile_number' => $booking->mobile_number ? '+91 ' . $booking->mobile_number : '-',
                    'hospital_name' => $booking->hospital?->name ?: '-',
                    'hospital_meta' => 'HOS' . str_pad((string) ($booking->hospital_id ?? 0), 4, '0', STR_PAD_LEFT),
                    'doctor_name' => $booking->doctor?->name ? 'Dr ' . $booking->doctor->name : '-',
                    'doctor_meta' => 'DOC' . str_pad((string) ($booking->doctor_id ?? 0), 4, '0', STR_PAD_LEFT),
                    'booking_date' => $booking->booking_date?->format('M d, Y') ?: '-',
                    'booking_time' => $timeLabel,
                    'status_label' => ucfirst($status),
                    'status_style' => $statusStyle,
                ];
            })
        );

        $availableDoctors = Doctor::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.hospital-admin.bookings.doctor-booking', [
            'bookingRows' => $bookingRows,
            'availableDoctors' => $availableDoctors,
            'totalBookings' => $baseScopedQuery->count(),
            'pendingBookings' => (clone $baseScopedQuery)->where('status', 'pending')->count(),
            'cancelledBookings' => (clone $baseScopedQuery)->where('status', 'cancelled')->count(),
            'completedBookings' => (clone $baseScopedQuery)->where('status', 'completed')->count(),
            'upcomingBookings' => (clone $baseScopedQuery)
                ->whereBetween('booking_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
        ]);
    }
}
