<?php

namespace App\Livewire\DoctorAdmin\Appointment;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Support\CurrentDoctor;
use App\Services\DoctorBookingStatusService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class OnlineConsultation extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $branchFilter = 'all';

    public string $statusFilter = 'all';

    public string $datePreset = 'today';

    public string $customDate = '';

    public bool $showDateFilter = false;

    public bool $showUpdateModal = false;

    public ?int $updateBookingId = null;

    public string $appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;

    public string $consultationLink = '';

    public ?array $modalBooking = null;

    public function mount(): void
    {
        $this->customDate = now()->toDateString();

        $search = request()->query('search');
        if (is_string($search) && filled(trim($search))) {
            $this->search = trim($search);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDatePreset(): void
    {
        if ($this->datePreset === 'today') {
            $this->customDate = now()->toDateString();
        } elseif ($this->datePreset === 'tomorrow') {
            $this->customDate = now()->addDay()->toDateString();
        }

        $this->resetPage();
    }

    public function applyCustomDate(): void
    {
        $this->datePreset = 'custom';
        $this->showDateFilter = false;
        $this->resetPage();
    }

    public function toggleDateFilter(): void
    {
        $this->showDateFilter = ! $this->showDateFilter;
    }

    public function refreshList(): void
    {
        $this->resetPage();
        $this->dispatch('toast', type: 'success', message: 'Online consultations refreshed.');
    }

    public function openUpdateModal(int $bookingId): void
    {
        $booking = $this->findDoctorBooking($bookingId);

        if (! $booking) {
            return;
        }

        $this->updateBookingId = $booking->id;
        $this->appointmentStatus = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;
        $this->consultationLink = (string) ($booking->online_consultation_link ?? '');
        $this->modalBooking = $this->mapCallRow($booking);
        $this->showUpdateModal = true;
    }

    public function closeUpdateModal(): void
    {
        $this->showUpdateModal = false;
        $this->updateBookingId = null;
        $this->modalBooking = null;
        $this->consultationLink = '';
        $this->appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;
    }

    public function selectAppointmentStatus(string $status): void
    {
        if (! array_key_exists($status, DoctorBooking::appointmentStatusOptions())) {
            return;
        }

        $this->appointmentStatus = $status;
    }

    public function updateAppointment(DoctorBookingStatusService $statusService): void
    {
        $booking = $this->findDoctorBooking((int) $this->updateBookingId);

        if (! $booking) {
            $this->closeUpdateModal();

            return;
        }

        $rules = [
            'consultationLink' => 'nullable|url|max:2048',
        ];

        if ($this->appointmentStatus === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN) {
            $existingLink = trim($this->consultationLink) ?: trim((string) ($booking->online_consultation_link ?? ''));
            if ($existingLink === '') {
                $this->addError('consultationLink', 'Meeting link is required when marking the session as checked-in.');

                return;
            }
        }

        $this->validate($rules);

        $statusService->updateAppointmentStatusWithLink(
            $booking,
            $this->appointmentStatus,
            $this->consultationLink ?: null
        );

        $this->dispatch('toast', type: 'success', message: 'Online consultation updated successfully.');
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
        $hospitalIds = $this->hospitalIds($doctor);

        if ($hospitalIds === []) {
            return collect();
        }

        return Hospital::query()
            ->whereIn('id', $hospitalIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    protected function findDoctorBooking(?int $bookingId): ?DoctorBooking
    {
        $doctor = $this->doctor();

        if (! $doctor || ! $bookingId) {
            return null;
        }

        $hospitalIds = $this->hospitalIds($doctor);

        return DoctorBooking::query()
            ->onlineConsultation()
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->when($hospitalIds !== [], function ($query) use ($hospitalIds) {
                $query->where(function ($scoped) use ($hospitalIds) {
                    $scoped->whereIn('hospital_id', $hospitalIds)
                        ->orWhereIn('branch_id', $hospitalIds);
                });
            })
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
        $hospitalIds = $this->hospitalIds($doctor);
        $filterDate = $this->filterDate()->toDateString();

        $query = DoctorBooking::query()
            ->onlineConsultation()
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('booking_date', $filterDate);

        if ($this->statusFilter === DoctorBooking::APPOINTMENT_STATUS_CANCELLED) {
            $query->where(function ($cancelledQuery) {
                $cancelledQuery->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_CANCELLED)
                    ->orWhere('status', 'cancelled');
            });
        } else {
            $query->where('status', '!=', 'cancelled')
                ->where(function ($cancelledQuery) {
                    $cancelledQuery->whereNull('appointment_status')
                        ->orWhere('appointment_status', '!=', DoctorBooking::APPOINTMENT_STATUS_CANCELLED);
                });
        }

        if ($hospitalIds !== []) {
            $query->where(function ($scoped) use ($hospitalIds) {
                $scoped->whereIn('hospital_id', $hospitalIds)
                    ->orWhereIn('branch_id', $hospitalIds);
            });
        }

        if ($this->branchFilter !== 'all') {
            $branchId = (int) $this->branchFilter;
            $query->where(function ($scoped) use ($branchId) {
                $scoped->where('hospital_id', $branchId)
                    ->orWhere('branch_id', $branchId);
            });
        }

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === DoctorBooking::APPOINTMENT_STATUS_NEW) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('appointment_status')
                        ->orWhere('appointment_status', DoctorBooking::APPOINTMENT_STATUS_NEW);
                });
            } else {
                $query->where('appointment_status', $this->statusFilter);
            }
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('member_id', 'like', '%'.$search.'%')
                    ->orWhere('patient_id', 'like', '%'.$search.'%')
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery
                            ->where('id', 'like', '%'.$search.'%')
                            ->orWhere('hip_id', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%']);
                    })
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery
                            ->where('id', 'like', '%'.$search.'%')
                            ->orWhere('hip_user_id', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%'])
                            ->orWhereHas('hipUser', function ($hipUserQuery) use ($search) {
                                $hipUserQuery
                                    ->where('id', 'like', '%'.$search.'%')
                                    ->orWhere('hip_id', 'like', '%'.$search.'%')
                                    ->orWhere('first_name', 'like', '%'.$search.'%')
                                    ->orWhere('last_name', 'like', '%'.$search.'%')
                                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%']);
                            });
                    });
            });
        }

        return $query->orderBy('booking_date')->orderBy('id');
    }

    protected function mapCallRow(DoctorBooking $booking): array
    {
        $patient = $booking->patient;
        $patientName = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($patientName === '') {
            $patientName = trim((string) ($booking->name ?: $booking->member?->name ?: 'Patient'));
        }

        $uhid = $booking->member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');

        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $timeRange = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $bookingDate = $booking->booking_date;
        $timeLabel = $bookingDate
            ? $bookingDate->format('d M Y').', '.$timeRange
            : '—';

        $avatarUrl = $this->resolveAvatarUrl($patient, $booking->member);

        return [
            'id' => $booking->id,
            'patient_name' => $patientName,
            'uhid' => $uhid,
            'time_range' => $timeRange,
            'time_label' => $timeLabel,
            'status_label' => $booking->onlineCallStatusLabel(),
            'status_class' => $booking->onlineCallStatusClass(),
            'status_key' => $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW,
            'avatar_url' => $avatarUrl,
            'initials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($patientName)), 0, 1)) ?: 'P',
            'consultation_link' => $booking->online_consultation_link,
        ];
    }

    protected function stats(Doctor $doctor): array
    {
        $filterDate = $this->filterDate()->toDateString();
        $hospitalIds = $this->hospitalIds($doctor);

        $statsQuery = DoctorBooking::query()
            ->onlineConsultation()
            ->where('doctor_id', $doctor->id)
            ->whereDate('booking_date', $filterDate)
            ->where('status', '!=', 'cancelled');

        if ($hospitalIds !== []) {
            $statsQuery->where(function ($scoped) use ($hospitalIds) {
                $scoped->whereIn('hospital_id', $hospitalIds)
                    ->orWhereIn('branch_id', $hospitalIds);
            });
        }

        if ($this->branchFilter !== 'all') {
            $branchId = (int) $this->branchFilter;
            $statsQuery->where(function ($scoped) use ($branchId) {
                $scoped->where('hospital_id', $branchId)
                    ->orWhere('branch_id', $branchId);
            });
        }

        return [
            'ongoing_sessions' => (clone $statsQuery)
                ->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN)
                ->count(),
            'total_appointments' => (clone $statsQuery)->count(),
        ];
    }

    protected function resolveAvatarUrl(?Persons $patient, ?HIPUser $member): ?string
    {
        $candidates = array_filter([
            filled($patient?->image) ? 'users/'.ltrim((string) $patient->image, '/') : null,
            filled($member?->profile_image) ? 'users/'.ltrim((string) $member->profile_image, '/') : null,
        ]);

        foreach ($candidates as $path) {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/'.$path);
            }
        }

        return null;
    }

    public function render()
    {
        $doctor = $this->doctor();
        $branches = $doctor ? $this->branches($doctor) : collect();
        $stats = $doctor ? $this->stats($doctor) : ['ongoing_sessions' => 0, 'total_appointments' => 0];

        $videoCalls = $doctor
            ? $this->baseQuery($doctor)->paginate(10)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        $videoCalls->setCollection(
            $videoCalls->getCollection()->map(fn (DoctorBooking $booking) => $this->mapCallRow($booking))
        );

        return view('livewire.doctor-admin.appointment.online-consultation', [
            'videoCalls' => $videoCalls,
            'branches' => $branches,
            'ongoingSessions' => $stats['ongoing_sessions'],
            'totalAppointments' => $stats['total_appointments'],
            'statusOptions' => DoctorBooking::appointmentStatusOptions(),
        ]);
    }
}
