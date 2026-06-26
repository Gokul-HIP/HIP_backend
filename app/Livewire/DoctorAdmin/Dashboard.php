<?php

namespace App\Livewire\DoctorAdmin;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Support\CurrentDoctor;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Dashboard extends Component
{
    public function newConsultation(): void
    {
        $this->redirect(route('doctor.my-appointment.index', ['tab' => 'today']));
    }

    public function openNextAppointment(?int $bookingId = null): void
    {
        if (! $bookingId) {
            $this->redirect(route('doctor.my-appointment.index', ['tab' => 'today']));

            return;
        }

        $this->redirect($this->appointmentActionUrl($bookingId));
    }

    public function viewHistory(): void
    {
        $this->redirect(route('doctor.member-profile.member-index'));
    }

    public function viewAppointment(int $bookingId): void
    {
        $this->redirect($this->appointmentActionUrl($bookingId));
    }

    public function render()
    {
        $doctor = CurrentDoctor::resolve();
        $doctorFirstName = $this->doctorFirstName($doctor);
        $doctorLastName = $this->doctorLastName($doctor);
        $todayCount = 0;
        $onlineCount = 0;
        $followUpCount = 0;
        $nextAppointment = null;
        $recentAppointments = collect();
        $nextMinutesLabel = null;

        if ($doctor) {
            $todayBookings = $this->todayBookings($doctor);
            $todayCount = $todayBookings->count();
            $onlineCount = $todayBookings->filter(fn (DoctorBooking $b) => $b->isOnlineConsultation())->count();
            $followUpCount = $this->followUpCountThisWeek($doctor);

            $nextBooking = $this->nextAppointmentToday($doctor, $todayBookings);
            if ($nextBooking) {
                $nextAppointment = $this->mapNextAppointment($nextBooking);
                $nextMinutesLabel = $this->nextMinutesLabel($nextBooking);
            }

            $recentAppointments = $todayBookings
                ->sortBy(fn (DoctorBooking $b) => $this->slotSortValue($b))
                ->take(8)
                ->map(fn (DoctorBooking $b) => $this->mapRecentAppointment($b))
                ->values();
        }

        return view('livewire.doctor-admin.dashboard', [
            'doctorFirstName' => $doctorFirstName,
            'doctorLastName' => $doctorLastName,
            'todayAppointmentsCount' => $todayCount,
            'onlineConsultationsCount' => $onlineCount,
            'followUpPatientsCount' => $followUpCount,
            'nextAppointment' => $nextAppointment,
            'nextMinutesLabel' => $nextMinutesLabel,
            'recentAppointments' => $recentAppointments,
        ]);
    }

    protected function doctorFirstName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return 'Doctor';
        }

        $parts = preg_split('/\s+/', trim((string) $doctor->name), 2);

        return $parts[0] ?: 'Doctor';
    }

    protected function doctorLastName(?Doctor $doctor): string
    {
        if (! $doctor) {
            return '';
        }

        $parts = preg_split('/\s+/', trim((string) $doctor->name), 2);

        return $parts[1] ?: '';
    }

    protected function hospitalIds(Doctor $doctor): array
    {
        return collect($doctor->hospital_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();
    }

    protected function bookingsQuery(Doctor $doctor)
    {
        $hospitalIds = $this->hospitalIds($doctor);

        $query = DoctorBooking::query()
            ->with(['patient', 'member', 'hospital', 'branch', 'department'])
            ->where('doctor_id', $doctor->id);

        if ($hospitalIds !== []) {
            $query->where(function ($scoped) use ($hospitalIds) {
                $scoped->whereIn('hospital_id', $hospitalIds)
                    ->orWhereIn('branch_id', $hospitalIds);
            });
        }

        return $query;
    }

    protected function todayBookings(Doctor $doctor): Collection
    {
        return $this->bookingsQuery($doctor)
            ->whereDate('booking_date', now()->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('appointment_status')
                    ->orWhere('appointment_status', '!=', DoctorBooking::APPOINTMENT_STATUS_CANCELLED);
            })
            ->orderBy('booking_date')
            ->orderBy('id')
            ->get();
    }

    protected function followUpCountThisWeek(Doctor $doctor): int
    {
        $start = now()->startOfWeek()->toDateString();
        $end = now()->endOfWeek()->toDateString();

        return $this->bookingsQuery($doctor)
            ->where('is_follow_up', true)
            ->whereBetween('booking_date', [$start, $end])
            ->where('status', '!=', 'cancelled')
            ->where(function ($statusQuery) {
                $statusQuery->whereNull('appointment_status')
                    ->orWhere('appointment_status', '!=', DoctorBooking::APPOINTMENT_STATUS_CANCELLED);
            })
            ->count();
    }

    protected function nextAppointmentToday(Doctor $doctor, Collection $todayBookings): ?DoctorBooking
    {
        $active = $todayBookings
            ->filter(function (DoctorBooking $booking) {
                $status = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

                return $status !== DoctorBooking::APPOINTMENT_STATUS_COMPLETED
                    && $booking->status !== 'completed';
            })
            ->sortBy(fn (DoctorBooking $booking) => $this->slotSortValue($booking))
            ->values();

        if ($active->isEmpty()) {
            return null;
        }

        $now = now();

        $upcoming = $active->first(function (DoctorBooking $booking) use ($now) {
            $slot = collect($booking->required_time_slots ?? [])->first();
            if (! $slot) {
                return true;
            }

            try {
                return Carbon::parse($slot)->greaterThanOrEqualTo($now);
            } catch (\Throwable) {
                return true;
            }
        });

        return $upcoming ?? $active->first();
    }

    protected function slotSortValue(DoctorBooking $booking): int
    {
        $slot = collect($booking->required_time_slots ?? [])->first();
        if (! $slot) {
            return PHP_INT_MAX;
        }

        try {
            return Carbon::parse($slot)->timestamp;
        } catch (\Throwable) {
            return PHP_INT_MAX;
        }
    }

    protected function nextMinutesLabel(DoctorBooking $booking): ?string
    {
        $slot = collect($booking->required_time_slots ?? [])->first();
        if (! $slot) {
            return 'Today';
        }

        try {
            $slotTime = Carbon::parse($slot);
            $minutes = (int) now()->diffInMinutes($slotTime, false);

            if ($minutes <= 0) {
                return 'Now';
            }

            if ($minutes < 60) {
                return 'Next in '.$minutes.' min'.($minutes === 1 ? '' : 's');
            }

            $hours = intdiv($minutes, 60);
            $remaining = $minutes % 60;

            return $remaining > 0
                ? 'Next in '.$hours.'h '.$remaining.'m'
                : 'Next in '.$hours.'h';
        } catch (\Throwable) {
            return 'Today';
        }
    }

    protected function patientName(DoctorBooking $booking): string
    {
        $patient = $booking->patient;
        $name = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($booking->name ?: $booking->member?->name ?: 'Patient'));
        }

        return $name;
    }

    protected function patientUhid(DoctorBooking $booking): string
    {
        $patient = $booking->patient;

        return (string) ($booking->member?->hip_id ?: $patient?->id ?: $booking->patient_id ?: '—');
    }

    protected function avatarColor(string $name): string
    {
        $colors = ['#c8102e', '#0da2e7', '#6366f1', '#059669', '#f97316', '#8b5cf6'];
        $index = abs(crc32(strtolower(trim($name)))) % count($colors);

        return $colors[$index];
    }

    protected function resolveAvatarUrl(DoctorBooking $booking): ?string
    {
        $patient = $booking->patient;
        $image = $patient?->image;

        if (filled($image) && Storage::disk('public')->exists('users/'.ltrim((string) $image, '/'))) {
            return asset('storage/users/'.ltrim((string) $image, '/'));
        }

        return null;
    }

    protected function timeLabel(DoctorBooking $booking): string
    {
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();

        return match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' – '.$slots->last(),
        };
    }

    protected function visitMeta(DoctorBooking $booking): array
    {
        if ($booking->isOnlineConsultation()) {
            return [
                'type' => 'Online',
                'icon' => 'fas fa-video',
            ];
        }

        if ($booking->is_follow_up) {
            return [
                'type' => 'Follow-up',
                'icon' => 'fas fa-redo',
            ];
        }

        return [
            'type' => 'In-Clinic',
            'icon' => 'fas fa-hospital',
        ];
    }

    protected function dashboardStatus(DoctorBooking $booking): array
    {
        $status = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;

        if ($booking->status === 'cancelled' || $status === DoctorBooking::APPOINTMENT_STATUS_CANCELLED) {
            return ['label' => 'Cancelled', 'class' => 'cancelled'];
        }

        if ($status === DoctorBooking::APPOINTMENT_STATUS_COMPLETED || $booking->status === 'completed') {
            return ['label' => 'Completed', 'class' => 'completed'];
        }

        if ($status === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN) {
            return ['label' => 'Waiting', 'class' => 'waiting'];
        }

        if ($booking->isOnlineConsultation()) {
            return ['label' => 'Waiting', 'class' => 'waiting'];
        }

        return ['label' => 'Upcoming', 'class' => 'upcoming'];
    }

    protected function actionMeta(DoctorBooking $booking): array
    {
        if ($booking->isOnlineConsultation()) {
            return [
                'label' => 'Start Session',
                'route' => 'online',
            ];
        }

        if ($booking->is_follow_up) {
            return [
                'label' => 'View Follow-up',
                'route' => 'follow-up',
            ];
        }

        return [
            'label' => "View Today's Schedule",
            'route' => 'today',
        ];
    }

    protected function mapNextAppointment(DoctorBooking $booking): array
    {
        $name = $this->patientName($booking);
        $action = $this->actionMeta($booking);
        $branch = $booking->branch?->name ?: $booking->hospital?->name ?: 'Consultation Room';

        return [
            'id' => $booking->id,
            'patient_name' => $name,
            'patient_id' => $this->patientUhid($booking),
            'initials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P',
            'avatar_url' => $this->resolveAvatarUrl($booking),
            'reason' => $booking->reason_of_visit ?: $booking->purpose ?: 'General Consultation',
            'date' => $booking->booking_date?->format('d M Y') ?? 'Today',
            'time_range' => $this->timeLabel($booking),
            'room' => $booking->isOnlineConsultation() ? 'Online Consultation' : $branch,
            'action_label' => $action['label'],
            'action_route' => $action['route'],
            'is_online' => $booking->isOnlineConsultation(),
        ];
    }

    protected function mapRecentAppointment(DoctorBooking $booking): array
    {
        $name = $this->patientName($booking);
        $visit = $this->visitMeta($booking);
        $status = $this->dashboardStatus($booking);
        $department = $booking->department?->name
            ?: $booking->branch?->name
            ?: $booking->hospital?->name
            ?: '—';

        return [
            'id' => $booking->id,
            'patient_name' => $name,
            'initials' => strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P',
            'avatar_color' => $this->avatarColor($name),
            'time' => $this->timeLabel($booking),
            'department' => $department,
            'visit_type' => $visit['type'],
            'visit_icon' => $visit['icon'],
            'status' => $status['label'],
            'status_class' => $status['class'],
            'is_online' => $booking->isOnlineConsultation(),
            'is_follow_up' => (bool) $booking->is_follow_up,
        ];
    }

    protected function findBooking(Doctor $doctor, int $bookingId): ?DoctorBooking
    {
        if ($bookingId <= 0) {
            return null;
        }

        return $this->bookingsQuery($doctor)->find($bookingId);
    }

    protected function appointmentActionUrl(int $bookingId): string
    {
        $doctor = CurrentDoctor::resolve();

        if (! $doctor) {
            return route('doctor.my-appointment.index', ['tab' => 'today']);
        }

        $booking = $this->findBooking($doctor, $bookingId);

        if (! $booking) {
            return route('doctor.my-appointment.index', ['tab' => 'today']);
        }

        if ($booking->isOnlineConsultation()) {
            return route('doctor.online-consultation.index', [
                'search' => $this->patientName($booking),
                'booking' => $booking->id,
            ]);
        }

        if ($booking->is_follow_up) {
            return route('doctor.my-appointment.index', ['tab' => 'follow-up']);
        }

        return route('doctor.my-appointment.index', ['tab' => 'today']);
    }
}
