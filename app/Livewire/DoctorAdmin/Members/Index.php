<?php

namespace App\Livewire\DoctorAdmin\Members;

use App\Livewire\DoctorAdmin\Concerns\ManagesAppointmentHistory;
use App\Livewire\DoctorAdmin\Concerns\ManagesBookFollowUp;
use App\Livewire\DoctorAdmin\Concerns\ManagesPatientProfilePanel;
use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Persons;
use App\Services\DoctorBookingStatusService;
use App\Support\CurrentDoctor;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    use ManagesAppointmentHistory;
    use ManagesPatientProfilePanel;
    use ManagesBookFollowUp;

    protected $paginationTheme = 'tailwind';

    public int $perPage = 10;

    public string $search = '';

    public string $branchFilter = 'all';

    public string $visitTypeFilter = 'all';

    public string $statusFilter = 'all';

    public bool $showUpdateModal = false;

    public ?int $updateBookingId = null;

    public string $appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;

    public ?array $modalBooking = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVisitTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->branchFilter = 'all';
        $this->visitTypeFilter = 'all';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function removeTag(string $key): void
    {
        match ($key) {
            'search' => $this->search = '',
            'branch' => $this->branchFilter = 'all',
            'visit_type' => $this->visitTypeFilter = 'all',
            'status' => $this->statusFilter = 'all',
            default => null,
        };

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
    }

    public function closeUpdateModal(): void
    {
        $this->showUpdateModal = false;
        $this->updateBookingId = null;
        $this->modalBooking = null;
        $this->appointmentStatus = DoctorBooking::APPOINTMENT_STATUS_NEW;
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

    public function exportList(): void
    {
        $this->dispatch('toast', type: 'info', message: 'Export is not available yet.');
    }

    public function openRegisterModal(): void
    {
        $this->dispatch('toast', type: 'info', message: 'Patient registration is not available yet.');
    }

    public function showPatientDocuments(): void
    {
        $this->dispatch('toast', type: 'info', message: 'Documents view is coming soon.');
    }

    public function getActiveFilterTagsProperty(): array
    {
        $tags = [];

        if (trim($this->search) !== '') {
            $tags[] = ['key' => 'search', 'label' => 'Search: '.trim($this->search)];
        }

        if ($this->branchFilter !== 'all') {
            $doctor = $this->doctor();
            $branchName = $doctor
                ? ($this->branches($doctor)->firstWhere('id', (int) $this->branchFilter)?->name ?? 'Selected')
                : 'Selected';
            $tags[] = ['key' => 'branch', 'label' => 'Branch: '.$branchName];
        }

        if ($this->visitTypeFilter !== 'all') {
            $labels = [
                'new' => 'New',
                'follow-up' => 'Follow-up',
                'online' => 'Online',
            ];
            $tags[] = [
                'key' => 'visit_type',
                'label' => 'Visit Type: '.($labels[$this->visitTypeFilter] ?? ucfirst($this->visitTypeFilter)),
            ];
        }

        if ($this->statusFilter !== 'all') {
            $tags[] = ['key' => 'status', 'label' => 'Status: '.ucfirst($this->statusFilter)];
        }

        return $tags;
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
            ->with(['patient.hipUser', 'member', 'hospital', 'branch'])
            ->where('doctor_id', $doctor->id)
            ->when($hospitalIds !== [], function ($query) use ($hospitalIds) {
                $query->where(function ($scoped) use ($hospitalIds) {
                    $scoped->whereIn('hospital_id', $hospitalIds)
                        ->orWhereIn('branch_id', $hospitalIds);
                });
            })
            ->find($bookingId);
    }

    protected function baseBookingQuery(Doctor $doctor)
    {
        $hospitalIds = $this->hospitalIds($doctor);

        $query = DoctorBooking::query()
            ->with(['patient.hipUser', 'member'])
            ->where('doctor_id', $doctor->id);

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

        if ($this->visitTypeFilter === 'follow-up') {
            $query->where('is_follow_up', true);
        } elseif ($this->visitTypeFilter === 'online') {
            $query->onlineConsultation();
        } elseif ($this->visitTypeFilter === 'new') {
            $query->where('is_follow_up', false)
                ->where(function ($builder) {
                    $builder->where(function ($onlineScope) {
                        $onlineScope
                            ->whereRaw('LOWER(COALESCE(consultation_type, "")) NOT LIKE ?', ['%online%'])
                            ->whereRaw('LOWER(COALESCE(consultation_type, "")) NOT LIKE ?', ['%video%'])
                            ->whereRaw('LOWER(COALESCE(appointment_type, "")) NOT LIKE ?', ['%online%'])
                            ->whereRaw('LOWER(COALESCE(appointment_type, "")) NOT LIKE ?', ['%video%']);
                    });
                });
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('member_id', 'like', '%'.$search.'%')
                    ->orWhere('patient_id', 'like', '%'.$search.'%')
                    ->orWhere('mobile_number', 'like', '%'.$search.'%')
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery
                            ->where('id', 'like', '%'.$search.'%')
                            ->orWhere('hip_id', 'like', '%'.$search.'%')
                            ->orWhere('mobile_num', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%']);
                    })
                    ->orWhereHas('patient', function ($patientQuery) use ($search) {
                        $patientQuery
                            ->where('id', 'like', '%'.$search.'%')
                            ->orWhere('hip_user_id', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%'])
                            ->orWhereHas('hipUser', function ($hipUserQuery) use ($search) {
                                $hipUserQuery
                                    ->where('id', 'like', '%'.$search.'%')
                                    ->orWhere('hip_id', 'like', '%'.$search.'%')
                                    ->orWhere('mobile_num', 'like', '%'.$search.'%')
                                    ->orWhere('first_name', 'like', '%'.$search.'%')
                                    ->orWhere('last_name', 'like', '%'.$search.'%')
                                    ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) like ?", ['%'.$search.'%']);
                            });
                    });
            });
        }

        return $query;
    }

    protected function patientCollection(Doctor $doctor): Collection
    {
        return $this->baseBookingQuery($doctor)
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (DoctorBooking $booking) => (string) ($booking->patient_id ?: $booking->member_id ?: 'booking-'.$booking->id))
            ->map(fn (Collection $bookings) => $this->mapPatientRow($bookings))
            ->when($this->statusFilter !== 'all', fn (Collection $patients) => $patients->where('status_key', $this->statusFilter))
            ->sortByDesc('sort_key')
            ->values();
    }

    protected function mapPatientRow(Collection $bookings): array
    {
        $today = now()->startOfDay();
        $sorted = $bookings->sortByDesc(fn (DoctorBooking $booking) => $booking->booking_date?->timestamp ?? 0);
        $latest = $sorted->first();

        $patient = $latest->patient;
        $member = $latest->member;

        $name = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($latest->name ?: $member?->name ?: 'Patient'));
        }

        $uhid = $member?->hip_id ?: ($patient?->id ?? $latest->patient_id ?? '—');
        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: '—'));
        $mobile = $latest->mobile_number
            ?: $patient?->mobile
            ?: $member?->mobile_num
            ?: '—';

        $pastBookings = $bookings->filter(function (DoctorBooking $booking) use ($today) {
            return $booking->booking_date
                && $booking->booking_date->lt($today)
                && $booking->status !== 'cancelled'
                && $booking->appointment_status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;
        });

        $upcomingBookings = $bookings->filter(function (DoctorBooking $booking) use ($today) {
            if (! $booking->booking_date || $booking->booking_date->lt($today)) {
                return false;
            }

            if ($booking->status === 'cancelled') {
                return false;
            }

            if ($booking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_CANCELLED) {
                return false;
            }

            return true;
        });

        $lastVisitBooking = $pastBookings->sortByDesc(fn (DoctorBooking $booking) => $booking->booking_date?->timestamp ?? 0)->first();
        $nextBooking = $upcomingBookings->sortBy(fn (DoctorBooking $booking) => $booking->booking_date?->timestamp ?? PHP_INT_MAX)->first();
        $displayBooking = $nextBooking ?: $latest;
        $actionBooking = $nextBooking ?: $latest;

        $visitType = $this->resolveVisitType($displayBooking);
        $patientStatus = $this->resolvePatientStatus($bookings, $nextBooking, $lastVisitBooking);
        $avatarUrl = $this->resolveAvatarUrl($patient, $member);
        $initials = strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P';

        return [
            'id' => $latest->patient_id ?: $latest->member_id ?: $latest->id,
            'booking_id' => $actionBooking->id,
            'name' => $name,
            'uhid' => $uhid,
            'age' => $age,
            'gender' => $gender,
            'mobile' => $mobile,
            'last_visit' => $lastVisitBooking?->booking_date?->format('d M Y') ?? '—',
            'next_appointment' => $nextBooking?->booking_date?->format('d M Y') ?? '—',
            'visit_type' => $visitType['label'],
            'visit_type_class' => $visitType['class'],
            'status' => $patientStatus['label'],
            'status_key' => $patientStatus['key'],
            'status_class' => $patientStatus['class'],
            'avatar_url' => $avatarUrl,
            'initials' => $initials,
            'avatar_color' => $this->avatarColor($name),
            'sort_key' => $nextBooking?->booking_date?->timestamp ?? $latest->booking_date?->timestamp ?? 0,
        ];
    }

    protected function resolvePatientStatus(
        Collection $bookings,
        ?DoctorBooking $nextBooking,
        ?DoctorBooking $lastVisitBooking
    ): array {
        $today = now()->startOfDay();

        if ($nextBooking) {
            if ($nextBooking->booking_date?->isToday()
                || $nextBooking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN) {
                return ['key' => 'active', 'label' => 'Active', 'class' => 'active'];
            }

            if ($nextBooking->booking_date && $nextBooking->booking_date->gt($today)) {
                return ['key' => 'upcoming', 'label' => 'Upcoming', 'class' => 'upcoming'];
            }
        }

        if ($lastVisitBooking
            && (
                $lastVisitBooking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
                || $lastVisitBooking->status === 'completed'
            )) {
            return ['key' => 'completed', 'label' => 'Completed', 'class' => 'completed'];
        }

        $hasAnyBooking = $bookings->isNotEmpty();

        return [
            'key' => 'inactive',
            'label' => $hasAnyBooking ? 'Inactive' : 'Inactive',
            'class' => 'inactive',
        ];
    }

    protected function resolveVisitType(DoctorBooking $booking): array
    {
        if ($booking->isOnlineConsultation()) {
            return ['label' => 'Online', 'class' => 'visit-online'];
        }

        if ($booking->is_follow_up) {
            return ['label' => 'Follow-up', 'class' => 'visit-follow-up'];
        }

        return ['label' => 'New', 'class' => 'visit-new'];
    }

    protected function avatarColor(string $name): string
    {
        $colors = ['#c8102e', '#0da2e7', '#059669', '#7c3aed', '#e09b1a', '#0369a1'];

        return $colors[crc32($name) % count($colors)];
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
        $gender = strtoupper(substr((string) ($patient?->gender ?? ''), 0, 1)) ?: '—';
        $uhid = $booking->member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');

        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $timeLabel = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $bookingDate = $booking->booking_date;
        $dateLabel = '—';

        if ($bookingDate) {
            if ($bookingDate->isToday()) {
                $dateLabel = 'Today, '.$timeLabel;
            } elseif ($bookingDate->isTomorrow()) {
                $dateLabel = 'Tomorrow, '.$timeLabel;
            } else {
                $dateLabel = $bookingDate->format('M d, Y').', '.$timeLabel;
            }
        }

        $branchName = $booking->branch?->name
            ?: $booking->hospital?->name
            ?: '—';

        $consultationType = trim((string) ($booking->consultation_type ?: $booking->appointment_type ?: 'In-Person'));

        $avatarUrl = filled($patient?->image)
            ? asset('storage/users/'.ltrim((string) $patient->image, '/'))
            : null;

        return [
            'id' => $booking->id,
            'patient_name' => $patientName,
            'patient_meta' => ($age ? $age : '—').'/'.$gender.' • UHID: '.$uhid,
            'uhid' => $uhid,
            'time_label' => $dateLabel,
            'time_only' => $timeLabel,
            'branch' => $branchName,
            'type_badge' => strtoupper(str_replace([' ', '-'], ' ', $consultationType)),
            'avatar_url' => $avatarUrl,
            'initials' => strtoupper(substr($patientName, 0, 1).substr($patientName, -1, 1)),
        ];
    }

    protected function stats(Doctor $doctor): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();

        $bookings = DoctorBooking::query()
            ->with(['patient', 'member'])
            ->where('doctor_id', $doctor->id)
            ->when($this->hospitalIds($doctor) !== [], function ($query) use ($doctor) {
                $hospitalIds = $this->hospitalIds($doctor);
                $query->where(function ($scoped) use ($hospitalIds) {
                    $scoped->whereIn('hospital_id', $hospitalIds)
                        ->orWhereIn('branch_id', $hospitalIds);
                });
            })
            ->get();

        $grouped = $bookings->groupBy(fn (DoctorBooking $booking) => (string) ($booking->patient_id ?: $booking->member_id ?: 'booking-'.$booking->id));

        $todayPatients = $bookings
            ->filter(fn (DoctorBooking $booking) => $booking->booking_date?->toDateString() === $today)
            ->groupBy(fn (DoctorBooking $booking) => (string) ($booking->patient_id ?: $booking->member_id ?: 'booking-'.$booking->id))
            ->count();

        $followUpPatients = $grouped
            ->filter(fn (Collection $items) => $items->contains(fn (DoctorBooking $booking) => (bool) $booking->is_follow_up))
            ->count();

        $newThisMonth = $grouped
            ->filter(function (Collection $items) use ($monthStart) {
                $firstBooking = $items->sortBy(fn (DoctorBooking $booking) => $booking->booking_date?->timestamp ?? PHP_INT_MAX)->first();

                return $firstBooking?->booking_date
                    && $firstBooking->booking_date->greaterThanOrEqualTo($monthStart)
                    && ! $firstBooking->is_follow_up;
            })
            ->count();

        return [
            'my_patients' => $grouped->count(),
            'today_patients' => $todayPatients,
            'follow_up' => $followUpPatients,
            'new_this_month' => $newThisMonth,
        ];
    }

    public function render()
    {
        $doctor = $this->doctor();
        $branches = $doctor ? $this->branches($doctor) : collect();
        $stats = $doctor
            ? $this->stats($doctor)
            : ['my_patients' => 0, 'today_patients' => 0, 'follow_up' => 0, 'new_this_month' => 0];

        if (! $doctor) {
            $patients = new LengthAwarePaginator([], 0, $this->perPage);
        } else {
            $collection = $this->patientCollection($doctor);
            $page = $this->getPage();
            $total = $collection->count();
            $items = $collection->forPage($page, $this->perPage)->values();

            $patients = new LengthAwarePaginator(
                $items,
                $total,
                $this->perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
        }

        return view('livewire.doctor-admin.members.index', array_merge([
            'branches' => $branches,
            'patients' => $patients,
            'activeFilterTags' => $this->activeFilterTags,
            'myPatientsCount' => $stats['my_patients'],
            'todayPatientsCount' => $stats['today_patients'],
            'followUpCount' => $stats['follow_up'],
            'newThisMonthCount' => $stats['new_this_month'],
            'totalPatients' => $patients->total(),
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
