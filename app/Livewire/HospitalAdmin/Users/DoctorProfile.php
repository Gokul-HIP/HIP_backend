<?php

namespace App\Livewire\HospitalAdmin\Users;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\DoctorSchedule;
use App\Models\Hospital;
use App\Services\HospitalAdmin\DoctorProfileService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class DoctorProfile extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public int $doctorId;
    public string $tab = 'profile';
    public string $appointmentSearch = '';
    public string $appointmentStatus = 'all';
    public string $appointmentHospital = 'all';
    public string $appointmentDate = '';
    public string $scheduleDate = '';
    public int $scheduleMonth;
    public int $scheduleYear;
    public array $scheduleTimeSlots = [];
    public ?int $editingScheduleId = null;
    public ?int $scheduleIdBeingDeleted = null;
    public string $scheduleDeleteDateLabel = '';

    protected DoctorProfileService $service;

    public function boot(DoctorProfileService $service): void
    {
        $this->service = $service;
    }

    public function mount(int $id): void
    {
        $this->doctorId = $id;
        $this->scheduleMonth = now()->month;
        $this->scheduleYear = now()->year;
        $this->scheduleDate = now()->toDateString();
        $this->scheduleTimeSlots = [$this->emptyTimeSlot()];

        abort_unless($this->getDoctor() !== null, 404);

        $requestedTab = request()->query('tab');
        if (in_array($requestedTab, ['profile', 'appointments', 'hospitals', 'schedules'], true)) {
            $this->tab = $requestedTab;
        }
    }

    public function updatingAppointmentSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAppointmentStatus(): void
    {
        $this->resetPage();
    }

    public function updatingAppointmentHospital(): void
    {
        $this->resetPage();
    }

    public function updatingAppointmentDate(): void
    {
        $this->resetPage();
    }

    public function updatingScheduleDate(string $value): void
    {
        if ($value === '') {
            return;
        }

        $date = Carbon::parse($value);
        $this->scheduleMonth = (int) $date->month;
        $this->scheduleYear = (int) $date->year;
    }

    public function setTab(string $tab): void
    {
        if (!in_array($tab, ['profile', 'appointments', 'hospitals', 'schedules'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function openAddScheduleModal(): void
    {
        $this->editingScheduleId = null;
        $this->scheduleDate = now()->toDateString();
        $this->scheduleMonth = now()->month;
        $this->scheduleYear = now()->year;
        $this->scheduleTimeSlots = [$this->emptyTimeSlot()];
        Flux::modal('add-doctor-schedule')->show();
    }

    public function closeAddScheduleModal(): void
    {
        $this->editingScheduleId = null;
        $this->resetValidation();
        $this->scheduleDate = now()->toDateString();
        $this->scheduleMonth = now()->month;
        $this->scheduleYear = now()->year;
        $this->scheduleTimeSlots = [$this->emptyTimeSlot()];
        Flux::modal('add-doctor-schedule')->close();
    }

    public function addTimeSlot(): void
    {
        $this->scheduleTimeSlots[] = $this->emptyTimeSlot();
    }

    public function removeTimeSlot(int $index): void
    {
        unset($this->scheduleTimeSlots[$index]);
        $this->scheduleTimeSlots = array_values($this->scheduleTimeSlots);

        if ($this->scheduleTimeSlots === []) {
            $this->scheduleTimeSlots = [$this->emptyTimeSlot()];
        }
    }

    public function saveSchedule(): void
    {
        $doctor = $this->getDoctor();
        abort_unless($doctor !== null, 404);

        $validated = $this->validate([
            'scheduleDate' => ['required', 'date'],
            'scheduleTimeSlots' => ['required', 'array', 'min:1'],
            'scheduleTimeSlots.*.from' => ['required', 'date_format:H:i'],
            'scheduleTimeSlots.*.to' => ['required', 'date_format:H:i'],
        ]);

        $normalizedSlots = collect($validated['scheduleTimeSlots'])
            ->map(function (array $slot) {
                return [
                    'from' => $slot['from'],
                    'to' => $slot['to'],
                ];
            })
            ->filter(function (array $slot) {
                return $slot['from'] < $slot['to'];
            })
            ->unique(fn (array $slot) => $slot['from'] . '-' . $slot['to'])
            ->sortBy('from')
            ->values()
            ->all();

        if ($normalizedSlots === []) {
            $this->addError('scheduleTimeSlots', 'Add at least one valid time slot.');
            return;
        }

        DoctorSchedule::query()->updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'schedule_date' => $validated['scheduleDate'],
            ],
            [
                'organization_id' => Auth::user()->organization_id,
                'time_slots' => $normalizedSlots,
            ]
        );

        $this->scheduleMonth = (int) Carbon::parse($validated['scheduleDate'])->month;
        $this->scheduleYear = (int) Carbon::parse($validated['scheduleDate'])->year;

        $this->dispatch('toast', type: 'success', message: 'Doctor schedule saved successfully.');
        $this->closeAddScheduleModal();
    }

    public function copySchedule(int $scheduleId): void
    {
        $schedule = $this->findScopedSchedule($scheduleId);
        if (!$schedule) {
            return;
        }

        $this->editingScheduleId = $schedule->id;
        $this->scheduleDate = $schedule->schedule_date?->toDateString() ?: now()->toDateString();
        $this->scheduleTimeSlots = collect($schedule->time_slots ?? [])
            ->map(fn ($slot) => [
                'from' => (string) ($slot['from'] ?? ''),
                'to' => (string) ($slot['to'] ?? ''),
            ])
            ->values()
            ->all();

        if ($this->scheduleTimeSlots === []) {
            $this->scheduleTimeSlots = [$this->emptyTimeSlot()];
        }

        Flux::modal('add-doctor-schedule')->show();
    }

    public function confirmDeleteSchedule(int $scheduleId): void
    {
        $schedule = $this->findScopedSchedule($scheduleId);
        if (! $schedule) {
            return;
        }

        $this->scheduleIdBeingDeleted = $schedule->id;
        $this->scheduleDeleteDateLabel = $schedule->schedule_date
            ? $schedule->schedule_date->format('d M Y')
            : '';

        Flux::modal('delete-doctor-schedule')->show();
    }

    public function cancelDeleteSchedule(): void
    {
        $this->scheduleIdBeingDeleted = null;
        $this->scheduleDeleteDateLabel = '';

        Flux::modal('delete-doctor-schedule')->close();
    }

    public function deleteSchedule(int $scheduleId): void
    {
        $schedule = $this->findScopedSchedule($scheduleId);
        if (! $schedule) {
            return;
        }

        $schedule->delete();

        $this->scheduleIdBeingDeleted = null;
        $this->scheduleDeleteDateLabel = '';

        Flux::modal('delete-doctor-schedule')->close();

        $this->dispatch('toast', type: 'success', message: 'Doctor schedule deleted successfully.');
    }

    public function openAppointmentStatusModal(int $bookingId): void
    {
        $booking = DoctorBooking::query()
            ->where('doctor_id', $this->doctorId)
            ->whereIn('hospital_id', $this->organizationHospitalIds())
            ->find($bookingId);

        if (!$booking) {
            return;
        }

        $this->dispatch('openUpdateStatusModal', id: $booking->id);
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->scheduleYear, $this->scheduleMonth, 1)->subMonth();
        $this->scheduleMonth = (int) $date->month;
        $this->scheduleYear = (int) $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->scheduleYear, $this->scheduleMonth, 1)->addMonth();
        $this->scheduleMonth = (int) $date->month;
        $this->scheduleYear = (int) $date->year;
    }

    public function clearAppointmentFilters(): void
    {
        $this->reset(['appointmentSearch', 'appointmentStatus', 'appointmentHospital', 'appointmentDate']);
        $this->resetPage();
    }

    protected function organizationHospitalIds(): array
    {
        return $this->service->organizationHospitalIds();
    }

    protected function getDoctor(): ?Doctor
    {
        return $this->service->getDoctor($this->doctorId);
    }

    protected function linkedHospitals(Doctor $doctor): Collection
    {
        return $this->service->linkedHospitals($doctor);
    }

    protected function imageUrl(?string $image): ?string
    {
        return filled($image) ? asset('storage/doctor/' . ltrim($image, '/')) : null;
    }

    protected function emptyTimeSlot(): array
    {
        return [
            'from' => '',
            'to' => '',
        ];
    }

    protected function findScopedSchedule(int $scheduleId): ?DoctorSchedule
    {
        return $this->service->findScopedSchedule($this->doctorId, $scheduleId);
    }

    #[On('refreshDoctorBookings')]
    public function refreshDoctorBookings(): void
    {
        // Rerender after booking status updates.
    }

    public function render(): View
    {
        $doctor = $this->getDoctor();

        abort_unless($doctor !== null, 404);

        $linkedHospitals = $this->linkedHospitals($doctor);
        $hospitalIds = $linkedHospitals->pluck('id')->map(fn ($id) => (int) $id)->all();

        $appointmentQuery = DoctorBooking::query()
            ->with(['member', 'hospital'])
            ->where('doctor_id', $doctor->id)
            ->whereIn('hospital_id', $hospitalIds);

        if ($this->appointmentStatus !== 'all') {
            $appointmentQuery->where('status', $this->appointmentStatus);
        }

        if ($this->appointmentHospital !== 'all') {
            $appointmentQuery->where('hospital_id', (int) $this->appointmentHospital);
        }

        if ($this->appointmentDate !== '') {
            $appointmentQuery->whereDate('booking_date', $this->appointmentDate);
        }

        $search = trim($this->appointmentSearch);
        if ($search !== '') {
            $appointmentQuery->where(function ($query) use ($search) {
                $query
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%')
                    ->orWhere('id', 'like', '%' . $search . '%')
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%' . $search . '%'])
                            ->orWhere('hip_id', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('hospital', function ($hospitalQuery) use ($search) {
                        $hospitalQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $appointments = $appointmentQuery
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'appointmentsPage')
            ->withPath(route('healthcare.doctors.profile', ['id' => $doctor->id]));

        $appointments->setCollection(
            $appointments->getCollection()->map(function (DoctorBooking $booking) {
                $slots = collect($booking->required_time_slots ?? [])->filter()->values();

                return [
                    'id' => $booking->id,
                    'booking_id' => '#BK-' . str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                    'member_name' => $booking->member?->name ?: ($booking->name ?: '-'),
                    'member_meta' => $booking->member?->hip_id ?: '-',
                    'hospital_name' => $booking->hospital?->name ?: '-',
                    'appointment_date' => $booking->booking_date?->format('M d, Y') ?: '-',
                    'time_slot' => $slots->isEmpty() ? '-' : $slots->join(', '),
                    'status' => strtolower((string) ($booking->status ?? 'pending')),
                    'status_label' => ucfirst((string) ($booking->status ?? 'pending')),
                ];
            })
        );

        $statusPillClasses = [
            'pending' => 'bg-amber-50 text-amber-600',
            'confirmed' => 'bg-blue-50 text-blue-600',
            'completed' => 'bg-slate-100 text-slate-600',
            'cancelled' => 'bg-rose-50 text-rose-600',
        ];

        $doctorSchedules = $this->service->getDoctorSchedules(
            $doctor->id,
            $this->scheduleYear,
            $this->scheduleMonth
        );

        return view('livewire.hospital-admin.users.doctor-profile', [
            'doctor' => $doctor,
            'doctorImageUrl' => $this->imageUrl($doctor->doctor_image),
            'doctorIdLabel' => 'DOC-' . str_pad((string) $doctor->id, 5, '0', STR_PAD_LEFT),
            'linkedHospitals' => $linkedHospitals,
            'appointments' => $appointments,
            'appointmentHospitals' => $linkedHospitals,
            'appointmentStatusOptions' => [
                'all' => 'All',
                'confirmed' => 'Confirmed',
                'completed' => 'Completed',
                'pending' => 'Pending',
                'cancelled' => 'Cancelled',
            ],
            'statusPillClasses' => $statusPillClasses,
            'doctorSchedules' => $doctorSchedules,
            'profileStats' => [
                'total_appointments' => DoctorBooking::query()
                    ->where('doctor_id', $doctor->id)
                    ->whereIn('hospital_id', $hospitalIds)
                    ->count(),
                'upcoming_appointments' => DoctorBooking::query()
                    ->where('doctor_id', $doctor->id)
                    ->whereIn('hospital_id', $hospitalIds)
                    ->whereDate('booking_date', '>=', now()->toDateString())
                    ->count(),
                'linked_hospitals' => $linkedHospitals->count(),
            ],
        ]);
    }
}
