<?php

namespace App\Livewire\DoctorAdmin\Appointment;

use App\Livewire\DoctorAdmin\Concerns\ManagesAppointmentHistory;
use App\Livewire\DoctorAdmin\Concerns\ManagesBookFollowUp;
use App\Livewire\DoctorAdmin\Concerns\ManagesPatientProfilePanel;
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
    use ManagesAppointmentHistory;
    use ManagesPatientProfilePanel;
    use ManagesBookFollowUp;

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

    public string $listTab = 'upcoming';

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

    public function setListTab(string $tab): void
    {
        if (! in_array($tab, ['upcoming', 'completed', 'cancelled'], true)) {
            return;
        }

        $this->listTab = $tab;
        $this->statusFilter = 'all';
        $this->resetPage();
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

    protected function nowTz(): Carbon
    {
        return Carbon::now((string) config('app.timezone'));
    }

    protected function filterDate(): Carbon
    {
        return match ($this->datePreset) {
            'tomorrow' => $this->nowTz()->addDay()->startOfDay(),
            'custom' => Carbon::parse($this->customDate ?: $this->nowTz()->toDateString(), (string) config('app.timezone'))->startOfDay(),
            default => $this->nowTz()->startOfDay(),
        };
    }

    protected function dateRange(): array
    {
        $tz = (string) config('app.timezone');
        $now = Carbon::now($tz);

        return match ($this->datePreset) {
            'tomorrow' => [$now->copy()->addDay()->toDateString(), $now->copy()->addDay()->toDateString()],
            'custom' => [
                Carbon::parse($this->customDate ?: $now->toDateString(), $tz)->toDateString(),
                Carbon::parse($this->customDate ?: $now->toDateString(), $tz)->toDateString(),
            ],
            'next_7' => [$now->toDateString(), $now->copy()->addDays(7)->toDateString()],
            default => [$now->toDateString(), $now->toDateString()],
        };
    }

    protected function doctorScopedQuery(Doctor $doctor)
    {
        $hospitalIds = $this->hospitalIds($doctor);

        $query = DoctorBooking::query()
            ->onlineConsultation()
            ->where('doctor_id', $doctor->id);

        if ($hospitalIds !== []) {
            $query->where(function ($scoped) use ($hospitalIds) {
                $scoped->whereIn('hospital_id', $hospitalIds)
                    ->orWhereIn('branch_id', $hospitalIds);
            });
        }

        return $query;
    }

    protected function applyListFilters($query, Doctor $doctor, bool $includeSearch = true)
    {
        [$start, $end] = $this->dateRange();

        if ($start === $end) {
            $query->whereDate('booking_date', $start);
        } else {
            $query->whereBetween('booking_date', [$start, $end]);
        }

        if ($this->branchFilter !== 'all') {
            $branchId = (int) $this->branchFilter;
            $query->where(function ($scoped) use ($branchId) {
                $scoped->where('hospital_id', $branchId)
                    ->orWhere('branch_id', $branchId);
            });
        }

        $search = trim($this->search);
        if ($includeSearch && $search !== '') {
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

        return $query;
    }

    protected function applyTab($query): void
    {
        if ($this->listTab === 'completed') {
            $query->where(function ($statusQuery) {
                $statusQuery->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_COMPLETED)
                    ->orWhere('status', 'completed');
            });

            return;
        }

        if ($this->listTab === 'cancelled') {
            $query->where(function ($cancelledQuery) {
                $cancelledQuery->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_CANCELLED)
                    ->orWhere('status', 'cancelled');
            });

            return;
        }

        $query->whereRaw($this->activeSql());

        if ($this->statusFilter !== 'all') {
            if ($this->statusFilter === DoctorBooking::APPOINTMENT_STATUS_NEW) {
                $query->where(function ($statusQuery) {
                    $statusQuery->whereNull('appointment_status')
                        ->orWhere('appointment_status', DoctorBooking::APPOINTMENT_STATUS_NEW);
                });
            } elseif (in_array($this->statusFilter, [
                DoctorBooking::APPOINTMENT_STATUS_NEW,
                DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
            ], true)) {
                $query->where('appointment_status', $this->statusFilter);
            }
        }
    }

    protected function baseQuery(Doctor $doctor)
    {
        $query = $this->applyListFilters(
            $this->doctorScopedQuery($doctor)->with(['patient', 'member', 'hospital', 'branch']),
            $doctor
        );

        $this->applyTab($query);

        $slotExpr = "JSON_UNQUOTE(JSON_EXTRACT(required_time_slots, '$[0]'))";

        if (in_array($this->listTab, ['completed', 'cancelled'], true)) {
            return $query->orderByDesc('booking_date')->orderByRaw($slotExpr.' DESC')->orderByDesc('id');
        }

        return $query->orderBy('booking_date')->orderByRaw($slotExpr.' ASC')->orderBy('id');
    }

    protected function tabCounts(Doctor $doctor): array
    {
        $query = $this->applyListFilters($this->doctorScopedQuery($doctor), $doctor);

        $row = $query
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' THEN 1 ELSE 0 END) as upcoming')
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN '.$this->cancelledSql().' THEN 1 ELSE 0 END) as cancelled')
            ->first();

        return [
            'upcoming' => (int) ($row->upcoming ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
        ];
    }

    protected function kpis(Doctor $doctor): array
    {
        $now = $this->nowTz();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $row = $this->doctorScopedQuery($doctor)
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' THEN 1 ELSE 0 END) as waiting')
            ->selectRaw('SUM(CASE WHEN DATE(booking_date) = ? AND '.$this->notCancelledSql().' THEN 1 ELSE 0 END) as today_calls', [$today])
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' AND DATE(booking_date) > ? THEN 1 ELSE 0 END) as upcoming', [$today])
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' AND booking_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as completed', [$monthStart, $monthEnd])
            ->first();

        return [
            'waiting_checked_in' => (int) ($row->waiting ?? 0),
            'today_calls' => (int) ($row->today_calls ?? 0),
            'upcoming' => (int) ($row->upcoming ?? 0),
            'completed' => (int) ($row->completed ?? 0),
        ];
    }

    protected function heroCall(Doctor $doctor): ?array
    {
        $today = $this->nowTz()->toDateString();
        $base = $this->doctorScopedQuery($doctor)->with(['patient', 'member', 'hospital', 'branch']);

        $checkedInToday = $this->soonestBooking(
            (clone $base)->whereDate('booking_date', $today)
                ->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN)
                ->whereRaw($this->activeSql())
        );
        if ($checkedInToday) {
            return $this->mapCallRow($checkedInToday);
        }

        $activeToday = $this->soonestBooking(
            (clone $base)->whereDate('booking_date', $today)->whereRaw($this->activeSql())
        );
        if ($activeToday) {
            return $this->mapCallRow($activeToday);
        }

        $futureActive = $this->soonestBooking(
            (clone $base)->whereDate('booking_date', '>', $today)->whereRaw($this->activeSql())
        );

        return $futureActive ? $this->mapCallRow($futureActive) : null;
    }

    protected function soonestBooking($query): ?DoctorBooking
    {
        $slotExpr = "JSON_UNQUOTE(JSON_EXTRACT(required_time_slots, '$[0]'))";

        return $query->orderBy('booking_date')->orderByRaw($slotExpr.' ASC')->orderBy('id')->first();
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
        $dob = $patient?->dob ?: $booking->member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $genderRaw = strtolower(trim((string) ($patient?->gender ?: $booking->member?->gender ?: '')));
        $genderLabel = match (true) {
            str_starts_with($genderRaw, 'm') => 'Male',
            str_starts_with($genderRaw, 'f') => 'Female',
            default => null,
        };

        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $timeRange = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $bookingDate = $booking->booking_date;
        $today = $this->nowTz()->toDateString();
        $dayKey = $bookingDate?->toDateString() ?: 'unknown';
        $dayLabel = '—';
        if ($bookingDate) {
            $dayLabel = match ($bookingDate->toDateString()) {
                $today => 'Today',
                $this->nowTz()->copy()->addDay()->toDateString() => 'Tomorrow',
                default => $bookingDate->format('D, j M'),
            };
        }

        $timeChip = $timeRange;
        if ($bookingDate && $bookingDate->toDateString() !== $today && $timeRange !== '—') {
            $timeChip = $dayLabel.', '.$timeRange;
        }

        $timeLabel = $bookingDate
            ? $bookingDate->format('d M Y').', '.$timeRange
            : '—';

        $reason = trim((string) ($booking->reason_of_visit ?: $booking->purpose ?: ''));
        $statusKey = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;
        $joinUrl = $this->safeConsultationUrl($booking->online_consultation_link);
        $isCheckedIn = $statusKey === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN;
        $avatarTone = ['primary', 'info', 'success', 'warning'][abs(crc32(strtolower($patientName))) % 4];

        $metaParts = array_values(array_filter([
            $age !== null ? $age.' yrs' : null,
            $genderLabel,
            $uhid !== '—' ? (string) $uhid : null,
            $reason !== '' ? $reason : null,
        ]));

        return [
            'id' => $booking->id,
            'patient_name' => $patientName,
            'uhid' => $uhid,
            'age' => $age,
            'gender_label' => $genderLabel,
            'hero_meta' => implode(' · ', $metaParts) ?: '—',
            'time_range' => $timeRange,
            'time_label' => $timeLabel,
            'time_chip' => $timeChip,
            'day_key' => $dayKey,
            'day_label' => $dayLabel,
            'reason_display' => $reason !== '' ? $reason : 'Not specified',
            'branch' => $booking->branch?->name ?: $booking->hospital?->name ?: '—',
            'is_follow_up' => (bool) $booking->is_follow_up,
            'status_label' => $booking->appointmentStatusLabel(),
            'status_class' => $booking->appointmentStatusBadgeClass(),
            'status_key' => $statusKey,
            'avatar_url' => $this->resolveAvatarUrl($patient, $booking->member),
            'initials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($patientName)), 0, 2)) ?: 'P',
            'avatar_tone' => $avatarTone,
            'consultation_link' => $booking->online_consultation_link,
            'join_url' => $joinUrl,
            'join_label' => $isCheckedIn ? 'Join now' : 'Start call',
            'countdown' => $this->countdownNote($booking),
            'countdown_tone' => $this->countdownTone($booking),
            'hero_pill' => $isCheckedIn ? 'Patient is waiting in the room' : 'Next consultation',
            'hero_state' => $isCheckedIn ? 'Patient waiting' : ($this->countdownNote($booking) ?: 'Scheduled'),
        ];
    }

    protected function countdownNote(DoctorBooking $booking): ?string
    {
        $status = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

        if ($status === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN) {
            return 'Patient waiting';
        }

        $at = $this->slotAt($booking);
        if (! $at) {
            return null;
        }

        $now = $this->nowTz();

        if ($at->greaterThan($now)) {
            $minutes = (int) $now->diffInMinutes($at);
            if ($minutes < 60) {
                return 'Starts in '.$minutes.' min';
            }

            $days = (int) $now->copy()->startOfDay()->diffInDays($at->copy()->startOfDay());
            if ($days >= 1) {
                return 'Starts in '.$days.'d';
            }

            $hours = intdiv($minutes, 60);

            return 'Starts in '.$hours.'h';
        }

        if ($this->isActiveBooking($booking) && $status !== DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN) {
            $late = (int) $at->diffInMinutes($now);

            return 'Late by '.$late.' min';
        }

        return null;
    }

    protected function countdownTone(DoctorBooking $booking): string
    {
        $note = $this->countdownNote($booking);
        if (! $note) {
            return '';
        }

        if (str_starts_with($note, 'Late')) {
            return 'is-late';
        }

        return 'is-soon';
    }

    protected function slotAt(DoctorBooking $booking): ?Carbon
    {
        $slot = collect($booking->required_time_slots ?? [])->filter()->first();
        if (! $slot || ! $booking->booking_date) {
            return null;
        }

        $tz = (string) config('app.timezone');
        $slot = trim((string) $slot);

        try {
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $slot)) {
                return Carbon::parse($slot, $tz);
            }

            return Carbon::parse($booking->booking_date->toDateString().' '.$slot, $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function slotSortValue(DoctorBooking $booking): int
    {
        $at = $this->slotAt($booking);

        return $at ? $at->timestamp : PHP_INT_MAX;
    }

    protected function isActiveBooking(DoctorBooking $booking): bool
    {
        $status = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

        return in_array($status, [
            DoctorBooking::APPOINTMENT_STATUS_NEW,
            DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
        ], true)
            && ! in_array((string) $booking->status, ['cancelled', 'completed'], true)
            && $status !== DoctorBooking::APPOINTMENT_STATUS_COMPLETED
            && $status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;
    }

    protected function safeConsultationUrl(?string $link): ?string
    {
        $link = trim((string) $link);
        if ($link === '') {
            return null;
        }

        if (! filter_var($link, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $link;
    }

    protected function activeSql(): string
    {
        $new = DoctorBooking::APPOINTMENT_STATUS_NEW;
        $checked = DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN;
        $completed = DoctorBooking::APPOINTMENT_STATUS_COMPLETED;
        $cancelled = DoctorBooking::APPOINTMENT_STATUS_CANCELLED;

        return '(
            (appointment_status IS NULL OR appointment_status IN ("'.$new.'","'.$checked.'"))
            AND (status IS NULL OR status NOT IN ("cancelled","completed"))
            AND (appointment_status IS NULL OR appointment_status NOT IN ("'.$completed.'","'.$cancelled.'"))
        )';
    }

    protected function completedSql(): string
    {
        return '(appointment_status = "'.DoctorBooking::APPOINTMENT_STATUS_COMPLETED.'" OR status = "completed")';
    }

    protected function cancelledSql(): string
    {
        return '(appointment_status = "'.DoctorBooking::APPOINTMENT_STATUS_CANCELLED.'" OR status = "cancelled")';
    }

    protected function notCancelledSql(): string
    {
        return '((status IS NULL OR status <> "cancelled") AND (appointment_status IS NULL OR appointment_status <> "'.DoctorBooking::APPOINTMENT_STATUS_CANCELLED.'"))';
    }

    protected function stats(Doctor $doctor): array
    {
        $kpis = $this->kpis($doctor);

        return [
            'ongoing_sessions' => $kpis['waiting_checked_in'],
            'total_appointments' => $kpis['today_calls'],
        ];
    }

    protected function resolveAvatarUrl(?Persons $patient, ?HIPUser $member): ?string
    {
        $candidates = array_filter([
            filled($patient?->image) ? 'users/'.ltrim((string) $patient->image, '/') : null,
            filled($member?->profile_image) ? 'users/'.ltrim((string) $member->profile_image, '/') : null,
        ]);

        foreach ($candidates as $path) {
            if (str_starts_with($path, 'users/http://') || str_starts_with($path, 'users/https://')) {
                continue;
            }

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
        $kpis = $doctor ? $this->kpis($doctor) : [
            'waiting_checked_in' => 0,
            'today_calls' => 0,
            'upcoming' => 0,
            'completed' => 0,
        ];
        $stats = [
            'ongoing_sessions' => $kpis['waiting_checked_in'],
            'total_appointments' => $kpis['today_calls'],
        ];

        $videoCalls = $doctor
            ? $this->baseQuery($doctor)->paginate(10)
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);

        $mapped = $videoCalls->getCollection()->map(fn (DoctorBooking $booking) => $this->mapCallRow($booking));
        $videoCalls->setCollection($mapped);

        $groupedCalls = [];
        foreach ($mapped as $call) {
            $key = $call['day_key'];
            if (! isset($groupedCalls[$key])) {
                $groupedCalls[$key] = [
                    'label' => $call['day_label'],
                    'rows' => [],
                ];
            }
            $groupedCalls[$key]['rows'][] = $call;
        }

        $tabStatusOptions = array_intersect_key(
            DoctorBooking::appointmentStatusOptions(),
            array_flip([
                DoctorBooking::APPOINTMENT_STATUS_NEW,
                DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN,
            ])
        );

        return view('livewire.doctor-admin.appointment.online-consultation', array_merge([
            'videoCalls' => $videoCalls,
            'groupedCalls' => $groupedCalls,
            'branches' => $branches,
            'heroCall' => $doctor ? $this->heroCall($doctor) : null,
            'kpis' => $kpis,
            'tabCounts' => $doctor ? $this->tabCounts($doctor) : ['upcoming' => 0, 'completed' => 0, 'cancelled' => 0],
            'ongoingSessions' => $stats['ongoing_sessions'],
            'totalAppointments' => $stats['total_appointments'],
            'statusOptions' => DoctorBooking::appointmentStatusOptions(),
            'tabStatusOptions' => $tabStatusOptions,
            'appointmentHistory' => $this->showAppointmentHistoryModal
                ? $this->appointmentHistoryPaginator()->items()
                : [],
            'appointmentHistoryPaginator' => $this->showAppointmentHistoryModal
                ? $this->appointmentHistoryPaginator()
                : null,
        ], $this->followUpFormData()));
    }
}
