<?php

namespace App\Livewire\DoctorAdmin\Appointment;

use App\Livewire\DoctorAdmin\Concerns\ManagesAppointmentHistory;
use App\Livewire\DoctorAdmin\Concerns\ManagesBookFollowUp;
use App\Livewire\DoctorAdmin\Concerns\ManagesPatientProfilePanel;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Support\CurrentDoctor;
use App\Services\DoctorBookingStatusService;
use App\Services\DoctorDashboardReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class MyAppointment extends Component
{
    use WithPagination;
    use ManagesAppointmentHistory;
    use ManagesPatientProfilePanel;
    use ManagesBookFollowUp;

    protected $paginationTheme = 'tailwind';

    public string $activeTab = 'today';

    public string $branchFilter = 'all';

    public string $datePreset = 'today';

    public string $customDate = '';

    public bool $showDateFilter = false;

    public bool $showUpdateModal = false;

    public ?int $updateBookingId = null;

    public string $appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;

    public ?array $modalBooking = null;

    public function mount(): void
    {
        $this->customDate = now()->toDateString();

        $tab = request()->query('tab');
        if (is_string($tab) && in_array($tab, ['today', 'upcoming', 'follow-up', 'completed', 'cancelled'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDatePreset(): void
    {
        $this->resetPage();
    }

    public function updatingCustomDate(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function setDatePreset(string $preset): void
    {
        $this->datePreset = $preset;

        if ($preset === 'today') {
            $this->customDate = now()->toDateString();
        } elseif ($preset === 'tomorrow') {
            $this->customDate = now()->addDay()->toDateString();
        }

        $this->resetPage();
    }

    public function toggleDateFilter(): void
    {
        $this->showDateFilter = ! $this->showDateFilter;
    }

    public function applyCustomDate(): void
    {
        $this->datePreset = 'custom';
        $this->showDateFilter = false;
        $this->resetPage();
    }

    public function openUpdateModal(int $bookingId): void
    {
        $booking = $this->findDoctorBooking($bookingId);

        if (! $booking) {
            return;
        }

        $this->updateBookingId = $booking->id;
        $this->appointmentStatus = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;
        $this->modalBooking = $this->mapBookingCard($booking);
        $this->showUpdateModal = true;
        $this->dispatch('doctor-appt-modal-opened');
    }

    public function closeUpdateModal(): void
    {
        $this->showUpdateModal = false;
        $this->updateBookingId = null;
        $this->modalBooking = null;
        $this->appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;
        $this->dispatch('doctor-appt-modal-closed');
    }

    public function selectAppointmentStatus(string $status): void
    {
        if (! array_key_exists($status, DoctorBooking::appointmentStatusOptions())) {
            return;
        }

        $this->appointmentStatus = $status;
    }

    public function updateAppointmentStatus(DoctorBookingStatusService $statusService): void
    {
        $booking = $this->findDoctorBooking((int) $this->updateBookingId);

        if (! $booking) {
            $this->closeUpdateModal();

            return;
        }

        $statusService->updateAppointmentStatus($booking, $this->appointmentStatus);

        $this->dispatch('toast', type: 'success', message: 'Appointment status updated successfully.');
        $this->closeUpdateModal();
        $this->resetPage();
    }

    protected function doctor(): ?Doctor
    {
        return CurrentDoctor::resolve();
    }

    protected function hospitalIds(Doctor $doctor): array
    {
        return collect($doctor->hospital_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function branches(Doctor $doctor): Collection
    {
        $assignedIds = $this->hospitalIds($doctor);

        $bookingHospitalIds = DoctorBooking::query()
            ->where('doctor_id', $doctor->id)
            ->select('hospital_id')
            ->whereNotNull('hospital_id');

        $bookingBranchIds = DoctorBooking::query()
            ->where('doctor_id', $doctor->id)
            ->select('branch_id as hospital_id')
            ->whereNotNull('branch_id');

        $ids = $bookingHospitalIds
            ->union($bookingBranchIds)
            ->pluck('hospital_id')
            ->map(fn ($id) => (int) $id)
            ->merge($assignedIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Hospital::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function findDoctorBooking(?int $bookingId): ?DoctorBooking
    {
        $doctor = $this->doctor();

        if (! $doctor || ! $bookingId) {
            return null;
        }

        return DoctorBooking::query()
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->find($bookingId);
    }

    protected function filterDate(): Carbon
    {
        return match ($this->datePreset) {
            'tomorrow' => now()->addDay()->startOfDay(),
            'custom' => Carbon::parse($this->customDate ?: now()->toDateString())->startOfDay(),
            default => now()->startOfDay(),
        };
    }

    protected function baseQuery(Doctor $doctor)
    {
        $query = DoctorBooking::query()
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id);

        if ($this->branchFilter !== 'all') {
            $branchId = (int) $this->branchFilter;
            $query->where(function ($scoped) use ($branchId) {
                $scoped->where('hospital_id', $branchId)
                    ->orWhere('branch_id', $branchId);
            });
        }

        $filterDate = $this->filterDate()->toDateString();
        $today = now()->toDateString();

        if ($this->activeTab === 'follow-up') {
            $query->where('is_follow_up', 1)
                ->whereDate('booking_date', $today)
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('status')
                        ->orWhereNotIn('status', ['cancelled', 'completed']);
                })
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('appointment_status')
                        ->orWhereIn('appointment_status', [
                            DoctorBooking::APPOINTMENT_STATUS_NEW,
                            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
                        ]);
                });
        } elseif ($this->activeTab === 'today') {
            $query->whereDate('booking_date', $filterDate)
                ->where('status', 'confirmed')
                ->where(function ($followUpQuery) {
                    $followUpQuery->where('is_follow_up', false)->orWhereNull('is_follow_up');
                })
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('appointment_status')
                        ->orWhereIn('appointment_status', [
                            DoctorBooking::APPOINTMENT_STATUS_NEW,
                            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
                        ]);
                });
        } elseif ($this->activeTab === 'upcoming') {
            $query->whereDate('booking_date', '>', $filterDate)
                ->where('status', 'confirmed')
                ->where(function ($followUpQuery) {
                    $followUpQuery->where('is_follow_up', false)->orWhereNull('is_follow_up');
                })
                ->where(function ($statusQuery) {
                    $statusQuery->whereNull('appointment_status')
                        ->orWhereIn('appointment_status', [
                            DoctorBooking::APPOINTMENT_STATUS_NEW,
                            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
                        ]);
                });
        } elseif ($this->activeTab === 'completed') {
            $query->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_COMPLETED);
        } elseif ($this->activeTab === 'cancelled') {
            $query->where(function ($statusQuery) {
                $statusQuery->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_CANCELLED)
                    ->orWhere('status', 'cancelled');
            });
        }

        return $query->orderBy('booking_date')->orderBy('id');
    }

    protected function mapBookingCard(DoctorBooking $booking): array
    {
        $patient = $booking->patient;
        $patientName = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($patientName === '') {
            $patientName = trim((string) ($booking->name ?: $booking->member?->name ?: 'Patient'));
        }

        $dob = $patient?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $genderRaw = strtolower(trim((string) ($patient?->gender ?? '')));
        $genderLabel = match (true) {
            str_starts_with($genderRaw, 'm') => 'Male',
            str_starts_with($genderRaw, 'f') => 'Female',
            default => '—',
        };
        $gender = strtoupper(substr((string) ($patient?->gender ?? ''), 0, 1)) ?: '—';
        $uhid = $booking->member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');

        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $timeLabel = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first() . ' - ' . $slots->last(),
        };

        $bookingDate = $booking->booking_date;
        $today = now()->toDateString();
        $dayLabel = $bookingDate
            ? ($bookingDate->toDateString() === $today ? 'Today' : $bookingDate->format('D j M'))
            : '—';
        $dateLabel = $bookingDate
            ? $bookingDate->format('d M Y').', '.$timeLabel
            : '—';
        $statusKey = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

        $branchName = $booking->branch?->name
            ?: $booking->hospital?->name
            ?: '—';

        $consultationType = trim((string) ($booking->consultation_type ?: $booking->appointment_type ?: 'In-Person'));

        $avatarUrl = \App\Support\DoctorPatientViewData::resolveAvatarUrl($patient, $booking->member);

        return [
            'id' => $booking->id,
            'patient_name' => $patientName,
            'patient_meta' => ($age ? $age : '—') . '/' . $gender . ' • UHID: ' . $uhid,
            'age' => $age,
            'gender_label' => $genderLabel,
            'uhid' => $uhid,
            'time_label' => $dateLabel,
            'time_only' => $timeLabel,
            'day_label' => $dayLabel,
            'branch' => $branchName,
            'reason' => $booking->reason_of_visit ?: $booking->purpose ?: '—',
            'reason_display' => filled($booking->reason_of_visit ?: $booking->purpose) ? ($booking->reason_of_visit ?: $booking->purpose) : 'Not specified',
            'type' => $consultationType,
            'type_badge' => strtoupper(str_replace([' ', '-'], ' ', $consultationType)),
            'status_label' => $booking->appointmentStatusLabel(),
            'status_class' => $booking->appointmentStatusBadgeClass(),
            'status_key' => $statusKey,
            'avatar_url' => $avatarUrl,
            'initials' => strtoupper(substr($patientName, 0, 1) . substr($patientName, -1, 1)),
            'is_follow_up' => (bool) $booking->is_follow_up,
            'is_online' => $booking->isOnlineConsultation(),
        ];
    }

    public function render()
    {
        $doctor = $this->doctor();
        $branches = $doctor ? $this->branches($doctor) : collect();

        $appointments = $doctor
            ? $this->baseQuery($doctor)->paginate(8)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 8);

        $appointments->setCollection(
            $appointments->getCollection()->map(fn (DoctorBooking $booking) => $this->mapBookingCard($booking))
        );

        $filterDateLabel = $this->filterDate()->format('M d, Y');

        return view('livewire.doctor-admin.appointment.my-appointment', array_merge([
            'appointments' => $appointments,
            'branches' => $branches,
            'filterDateLabel' => $filterDateLabel,
            'kpiCounts' => app(DoctorDashboardReportService::class)->appointmentKpis($doctor),
            'statusOptions' => DoctorBooking::appointmentStatusOptions(),
            'appointmentHistory' => $this->showAppointmentHistoryModal
                ? $this->appointmentHistoryPaginator()->items()
                : [],
            'appointmentHistoryPaginator' => $this->showAppointmentHistoryModal
                ? $this->appointmentHistoryPaginator()
                : null,
        ], $this->followUpFormData()));
    }
}
