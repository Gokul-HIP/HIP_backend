<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DoctorDashboardReportService
{
    public const RANGES = [1, 3, 6, 9];

    public function build(?Doctor $doctor, int $range): array
    {
        $range = in_array($range, self::RANGES, true) ? $range : 3;
        $tz = (string) config('app.timezone');
        $now = Carbon::now($tz);
        $today = $now->toDateString();
        [$periodStart, $periodEnd] = $this->periodBounds($now, $range);
        $periodLabel = $this->periodLabel($range);

        $empty = $this->emptyReport($range, $periodLabel, $today);

        if (! $doctor) {
            return $empty;
        }

        $weekStart = $now->copy()->startOfWeek()->toDateString();
        $weekEnd = $now->copy()->endOfWeek()->toDateString();
        $upcomingEnd = $now->copy()->addDays(7)->toDateString();

        $kpis = $this->kpiQuery($doctor, $today, $weekStart, $weekEnd, $upcomingEnd, $periodStart, $periodEnd);
        $buckets = $this->buckets($now, $range, $periodStart, $periodEnd);
        $grouped = $this->groupedPeriodQuery($doctor, $periodStart, $periodEnd, $range === 1);

        $series = [];
        $table = [];
        $totals = [
            'booked' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'follow_ups' => 0,
            'online' => 0,
            'clinic' => 0,
        ];

        foreach ($buckets as $bucket) {
            $row = $grouped->get($bucket['key'], [
                'completed' => 0,
                'cancelled' => 0,
                'follow_ups' => 0,
                'online' => 0,
                'clinic' => 0,
            ]);

            $completed = (int) ($row['completed'] ?? 0);
            $cancelled = (int) ($row['cancelled'] ?? 0);
            $followUps = (int) ($row['follow_ups'] ?? 0);
            $online = (int) ($row['online'] ?? 0);
            $clinic = (int) ($row['clinic'] ?? 0);
            $booked = $completed + $cancelled + $followUps;
            $pct = $booked > 0 ? round(($completed / $booked) * 100) : 0;

            $series[] = [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'completed' => $completed,
                'follow_up' => $followUps,
                'cancelled' => $cancelled,
                'online' => $online,
                'clinic' => $clinic,
            ];

            $table[] = [
                'period' => $bucket['label'],
                'booked' => $booked,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'follow_ups' => $followUps,
                'online' => $online,
                'completion_pct' => $pct,
            ];

            $totals['booked'] += $booked;
            $totals['completed'] += $completed;
            $totals['cancelled'] += $cancelled;
            $totals['follow_ups'] += $followUps;
            $totals['online'] += $online;
            $totals['clinic'] += $clinic;
        }

        $totals['completion_pct'] = $totals['booked'] > 0
            ? round(($totals['completed'] / $totals['booked']) * 100)
            : 0;

        $donut = [
            ['key' => 'completed', 'label' => 'Completed', 'value' => $totals['completed']],
            ['key' => 'follow_up', 'label' => 'Follow-up', 'value' => $totals['follow_ups']],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => $totals['cancelled']],
        ];
        $donutTotal = max(0, $totals['completed'] + $totals['follow_ups'] + $totals['cancelled']);
        foreach ($donut as &$slice) {
            $slice['pct'] = $donutTotal > 0 ? round(($slice['value'] / $donutTotal) * 100) : 0;
        }
        unset($slice);

        return [
            'range' => $range,
            'period_label' => $periodLabel,
            'greeting' => $this->greeting($now),
            'doctor_name' => trim((string) $doctor->name) ?: 'Doctor',
            'today' => $today,
            'kpis' => [
                'today' => (int) ($kpis->today ?? 0),
                'follow_up' => (int) ($kpis->follow_up ?? 0),
                'upcoming' => (int) ($kpis->upcoming ?? 0),
                'completed' => (int) $this->scoped($doctor, assignedHospitalsOnly: false)
                    ->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_COMPLETED)
                    ->count(),
                'cancelled' => (int) ($kpis->cancelled ?? 0),
            ],
            'series' => $series,
            'donut' => $donut,
            'donut_total' => $donutTotal,
            'table' => $table,
            'totals' => $totals,
            'today_schedule' => $this->todaySchedule($doctor, $today),
            'empty' => $donutTotal === 0 && ((int) ($kpis->today ?? 0) === 0),
        ];
    }

    public function appointmentKpis(?Doctor $doctor): array
    {
        $empty = [
            'today' => 0,
            'follow-up' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        if (! $doctor) {
            return $empty;
        }

        $tz = (string) config('app.timezone');
        $now = Carbon::now($tz);
        $today = $now->toDateString();

        $row = $this->scoped($doctor, assignedHospitalsOnly: false)
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' AND DATE(booking_date) = ? THEN 1 ELSE 0 END) as today', [$today])
            ->selectRaw(
                'SUM(CASE WHEN '.$this->followUpSql().' AND DATE(booking_date) = ? THEN 1 ELSE 0 END) as follow_up',
                [$today]
            )
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' AND DATE(booking_date) > ? THEN 1 ELSE 0 END) as upcoming', [$today])
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN '.$this->cancelledSql().' THEN 1 ELSE 0 END) as cancelled')
            ->first();

        return [
            'today' => (int) ($row->today ?? 0),
            'follow-up' => (int) ($row->follow_up ?? 0),
            'upcoming' => (int) ($row->upcoming ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'cancelled' => (int) ($row->cancelled ?? 0),
        ];
    }

    protected function kpiQuery(
        Doctor $doctor,
        string $today,
        string $weekStart,
        string $weekEnd,
        string $upcomingEnd,
        string $periodStart,
        string $periodEnd
    ) {
        return $this->scoped($doctor)
            ->selectRaw('SUM(CASE WHEN '.$this->activeSql().' AND booking_date = ? THEN 1 ELSE 0 END) as today', [$today])
            ->selectRaw(
                'SUM(CASE WHEN '.$this->activeSql().' AND is_follow_up = 1 AND booking_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as follow_up',
                [$weekStart, $weekEnd]
            )
            ->selectRaw(
                'SUM(CASE WHEN '.$this->activeSql().' AND booking_date > ? AND booking_date <= ? THEN 1 ELSE 0 END) as upcoming',
                [$today, $upcomingEnd]
            )
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' THEN 1 ELSE 0 END) as completed')
            ->selectRaw(
                'SUM(CASE WHEN '.$this->cancelledSql().' AND booking_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as cancelled',
                [$periodStart, $periodEnd]
            )
            ->first();
    }

    protected function groupedPeriodQuery(Doctor $doctor, string $periodStart, string $periodEnd, bool $byWeek): Collection
    {
        $bucketExpr = $byWeek
            ? 'YEARWEEK(booking_date, 3)'
            : "DATE_FORMAT(booking_date, '%Y-%m')";

        $rows = $this->scoped($doctor)
            ->whereBetween('booking_date', [$periodStart, $periodEnd])
            ->selectRaw($bucketExpr.' as bucket')
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' THEN 1 ELSE 0 END) as completed')
            ->selectRaw('SUM(CASE WHEN '.$this->cancelledSql().' THEN 1 ELSE 0 END) as cancelled')
            ->selectRaw('SUM(CASE WHEN is_follow_up = 1 THEN 1 ELSE 0 END) as follow_ups')
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' AND '.$this->onlineSql().' THEN 1 ELSE 0 END) as online')
            ->selectRaw('SUM(CASE WHEN '.$this->completedSql().' AND NOT ('.$this->onlineSql().') THEN 1 ELSE 0 END) as clinic')
            ->groupByRaw($bucketExpr)
            ->get()
            ->keyBy(fn ($row) => (string) $row->bucket);

        return $rows->map(fn ($row) => [
            'completed' => (int) $row->completed,
            'cancelled' => (int) $row->cancelled,
            'follow_ups' => (int) $row->follow_ups,
            'online' => (int) $row->online,
            'clinic' => (int) $row->clinic,
        ]);
    }

    protected function todaySchedule(Doctor $doctor, string $today): array
    {
        return $this->scoped($doctor)
            ->with(['patient', 'member', 'hospital', 'branch'])
            ->where('booking_date', $today)
            ->where($this->notCancelledCompleted())
            ->orderBy('booking_date')
            ->orderBy('id')
            ->limit(12)
            ->get()
            ->map(function (DoctorBooking $booking) {
                $name = $this->patientName($booking);
                $slots = collect($booking->required_time_slots ?? [])->filter()->values();
                $time = match ($slots->count()) {
                    0 => '—',
                    1 => (string) $slots->first(),
                    default => $slots->first().' – '.$slots->last(),
                };

                return [
                    'id' => $booking->id,
                    'time' => $time,
                    'patient_name' => $name,
                    'reason' => $booking->reason_of_visit ?: $booking->purpose ?: 'Not specified',
                    'mode' => $booking->isOnlineConsultation() ? 'Online' : 'Clinic',
                    'is_online' => $booking->isOnlineConsultation(),
                ];
            })
            ->values()
            ->all();
    }

    protected function buckets(CarbonInterface $now, int $range, string $periodStart, string $periodEnd): array
    {
        $start = Carbon::parse($periodStart, $now->timezone);
        $end = Carbon::parse($periodEnd, $now->timezone);

        if ($range === 1) {
            $buckets = [];
            $cursor = $start->copy()->startOfWeek();
            while ($cursor->lte($end)) {
                $weekEnd = $cursor->copy()->endOfWeek();
                $clipStart = $cursor->copy()->max($start);
                $clipEnd = $weekEnd->copy()->min($end);
                if ($clipStart->lte($clipEnd)) {
                    $buckets[] = [
                        'key' => (string) $clipStart->isoWeekYear().str_pad((string) $clipStart->isoWeek(), 2, '0', STR_PAD_LEFT),
                        'label' => $clipStart->format('j M').' – '.$clipEnd->format('j M'),
                    ];
                }
                $cursor->addWeek();
            }

            return $buckets;
        }

        $buckets = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $buckets[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
            ];
            $cursor->addMonth();
        }

        return $buckets;
    }

    protected function periodBounds(CarbonInterface $now, int $range): array
    {
        $end = $now->copy()->subMonthNoOverflow()->endOfMonth();
        $start = $range === 1
            ? $end->copy()->startOfMonth()
            : $end->copy()->subMonthsNoOverflow($range - 1)->startOfMonth();

        return [$start->toDateString(), $end->toDateString()];
    }

    protected function periodLabel(int $range): string
    {
        return match ($range) {
            1 => 'Last month',
            6 => '6 months',
            9 => '9 months',
            default => '3 months',
        };
    }

    protected function greeting(CarbonInterface $now): string
    {
        $hour = (int) $now->format('G');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }

    protected function scoped(Doctor $doctor, bool $assignedHospitalsOnly = true): Builder
    {
        $query = DoctorBooking::query()->where('doctor_id', $doctor->id);

        if (! $assignedHospitalsOnly) {
            return $query;
        }

        $hospitalIds = collect($doctor->hospital_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if ($hospitalIds !== []) {
            $query->where(function ($scoped) use ($hospitalIds) {
                $scoped->whereIn('hospital_id', $hospitalIds)
                    ->orWhereIn('branch_id', $hospitalIds);
            });
        }

        return $query;
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
        $completed = DoctorBooking::APPOINTMENT_STATUS_COMPLETED;

        return '(appointment_status = "'.$completed.'")';
    }

    protected function historyCompletedSql(string $today): string
    {
        $cancelled = DoctorBooking::APPOINTMENT_STATUS_CANCELLED;

        return '('.$this->completedSql().' OR (
            booking_date < "'.$today.'"
            AND (status IS NULL OR status <> "cancelled")
            AND (appointment_status IS NULL OR appointment_status <> "'.$cancelled.'")
        ))';
    }

    protected function cancelledSql(): string
    {
        $cancelled = DoctorBooking::APPOINTMENT_STATUS_CANCELLED;

        return '(appointment_status = "'.$cancelled.'" OR status = "cancelled")';
    }

    protected function followUpSql(): string
    {
        return '('.$this->activeSql().' AND is_follow_up = 1)';
    }

    protected function onlineSql(): string
    {
        return '(
            LOWER(COALESCE(consultation_type, "")) LIKE "%online%"
            OR LOWER(COALESCE(consultation_type, "")) LIKE "%video%"
            OR LOWER(COALESCE(appointment_type, "")) LIKE "%online%"
            OR LOWER(COALESCE(appointment_type, "")) LIKE "%video%"
        )';
    }

    protected function notCancelledCompleted(): \Closure
    {
        return function ($query) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhereNotIn('status', ['cancelled', 'completed']);
            })->where(function ($appointmentQuery) {
                $appointmentQuery->whereNull('appointment_status')
                    ->orWhereNotIn('appointment_status', [
                        DoctorBooking::APPOINTMENT_STATUS_COMPLETED,
                        DoctorBooking::APPOINTMENT_STATUS_CANCELLED,
                    ]);
            });
        };
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

    protected function emptyReport(int $range, string $periodLabel, string $today): array
    {
        return [
            'range' => $range,
            'period_label' => $periodLabel,
            'greeting' => $this->greeting(Carbon::now(config('app.timezone'))),
            'doctor_name' => 'Doctor',
            'today' => $today,
            'kpis' => [
                'today' => 0,
                'follow_up' => 0,
                'upcoming' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ],
            'series' => [],
            'donut' => [
                ['key' => 'completed', 'label' => 'Completed', 'value' => 0, 'pct' => 0],
                ['key' => 'follow_up', 'label' => 'Follow-up', 'value' => 0, 'pct' => 0],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'value' => 0, 'pct' => 0],
            ],
            'donut_total' => 0,
            'table' => [],
            'totals' => [
                'booked' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'follow_ups' => 0,
                'online' => 0,
                'clinic' => 0,
                'completion_pct' => 0,
            ],
            'today_schedule' => [],
            'empty' => true,
        ];
    }
}
