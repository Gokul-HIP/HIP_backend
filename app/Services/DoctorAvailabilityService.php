<?php

namespace App\Services;

use App\Models\DiseaseChatbotBooking;
use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\DoctorBooking;
use App\Models\DoctorReview;
use App\Models\Procedure;
use App\Models\Speciality;
use App\Services\AssignDoctorService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Chatbot doctor availability.
 *
 * Mirrors HomePageController::resolveSlotsForDate weekday matching:
 * - Uses assignment.time_slots JSON {day, start, end} where day is English weekday ("Monday").
 * - Ignores doctor_assignments.day / doctor_assignments.date columns (same as doctorTimeSlots).
 * - Only active assignments for the requested hospital contribute windows.
 *
 * Differences from GET home/doctor-time-slots:
 * - Expands windows into discrete slot starts (slot_minutes), requiring start+duration <= window end.
 * - Applies lead time (now + lead minutes) instead of bare start > now.
 * - Subtracts occupying bookings across all hospitals for the doctor.
 * - Always hospital-scoped for windows (doctorTimeSlots allows null hospital to merge branches).
 */
class DoctorAvailabilityService
{
    public function slotMinutes(): int
    {
        return max(5, (int) config('chatbot.slot_minutes', 30));
    }

    public function leadMinutes(): int
    {
        return max(0, (int) config('chatbot.slot_lead_minutes', 30));
    }

    public function holdMinutes(): int
    {
        return max(0, (int) config(
            'chatbot.payment_hold_minutes',
            config('chatbot.slot_hold_minutes', 15)
        ));
    }

    public function horizonDays(): int
    {
        return max(1, (int) config('chatbot.slot_horizon_days', 14));
    }

    /**
     * Path (i): active assignments at H whose procedures map to master speciality S,
     * and doctor.status = active.
     *
     * @return Collection<int, Doctor>
     */
    public function eligibleDoctors(int $hospitalId, int $masterSpecialityId): Collection
    {
        $departmentIds = Speciality::query()
            ->where('hospital_id', $hospitalId)
            ->where('speciality_master_id', $masterSpecialityId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($departmentIds === []) {
            return collect();
        }

        $procedureIds = Procedure::query()
            ->whereIn('speciality_id', $departmentIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($procedureIds === []) {
            return collect();
        }

        $procedureLookup = array_fill_keys($procedureIds, true);

        $assignments = DoctorAssignment::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->whereNotNull('time_slots')
            ->get(['id', 'doctor_id', 'hospital_id', 'time_slots', 'procedure_ids', 'status']);

        $doctorIds = [];
        foreach ($assignments as $assignment) {
            $ids = array_map('intval', (array) ($assignment->procedure_ids ?? []));
            $hit = false;
            foreach ($ids as $pid) {
                if (isset($procedureLookup[$pid])) {
                    $hit = true;
                    break;
                }
            }
            if ($hit) {
                $doctorIds[] = (string) $assignment->doctor_id;
            }
        }

        $doctorIds = array_values(array_unique($doctorIds));
        if ($doctorIds === []) {
            return collect();
        }

        return Doctor::query()
            ->whereIn('id', $doctorIds)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Discrete available slot labels for a doctor at a hospital across days.
     *
     * @return array<string, list<string>> keyed by Y-m-d
     */
    public function slotsForDoctor(string $doctorId, int $hospitalId, ?Carbon $fromDate = null, ?int $days = null): array
    {
        $fromDate = ($fromDate ?? Carbon::today('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata')->startOfDay();
        $days = $days ?? $this->horizonDays();
        $toDate = $fromDate->copy()->addDays($days - 1)->endOfDay();

        $assignments = DoctorAssignment::query()
            ->where('doctor_id', $doctorId)
            ->where('hospital_id', $hospitalId)
            ->where('status', 'active')
            ->whereNotNull('time_slots')
            ->get(['id', 'doctor_id', 'hospital_id', 'time_slots', 'status']);

        $bookedByDate = $this->loadBookedMinutesByDate($doctorId, $fromDate->toDateString(), $toDate->toDateString());

        $out = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = $fromDate->copy()->addDays($offset);
            $key = $date->toDateString();
            $times = $this->discreteSlotsForDate($assignments, $date, $bookedByDate[$key] ?? []);
            $out[$key] = $times;
        }

        return $out;
    }

    /**
     * @param  Collection<int, DoctorAssignment>|iterable<int, DoctorAssignment>  $assignments
     * @param  list<int>  $bookedMinutes
     * @return list<string>
     */
    public function discreteSlotsForDate(iterable $assignments, Carbon $date, array $bookedMinutes = []): array
    {
        $dayName = $date->copy()->timezone('Asia/Kolkata')->format('l');
        $slotMinutes = $this->slotMinutes();
        $leadCutoff = Carbon::now('Asia/Kolkata')->addMinutes($this->leadMinutes());
        $isToday = $date->isSameDay(Carbon::today('Asia/Kolkata'));
        $bookedSet = array_fill_keys($bookedMinutes, true);

        $starts = [];
        foreach ($assignments as $assignment) {
            foreach ((array) ($assignment->time_slots ?? []) as $slot) {
                if (($slot['day'] ?? '') !== $dayName) {
                    continue;
                }

                $startLabel = AssignDoctorService::timeToAmPm((string) ($slot['start'] ?? ''));
                $endLabel = ! empty($slot['end'])
                    ? AssignDoctorService::timeToAmPm((string) $slot['end'])
                    : '';
                $startMin = $this->parseToMinutes($startLabel);
                $endMin = $this->parseToMinutes($endLabel);
                if ($startMin === null || $endMin === null || $endMin <= $startMin) {
                    continue;
                }

                for ($cursor = $startMin; $cursor + $slotMinutes <= $endMin; $cursor += $slotMinutes) {
                    if (isset($bookedSet[$cursor])) {
                        continue;
                    }
                    if ($isToday) {
                        $slotAt = $date->copy()->timezone('Asia/Kolkata')->startOfDay()->addMinutes($cursor);
                        if ($slotAt->lt($leadCutoff)) {
                            continue;
                        }
                    }
                    $starts[$cursor] = $this->formatMinutes($cursor);
                }
            }
        }

        ksort($starts, SORT_NUMERIC);

        return array_values($starts);
    }

    /**
     * @return array<string, list<int>> date => minutes-since-midnight
     */
    public function loadBookedMinutesByDate(string $doctorId, string $fromDate, string $toDate): array
    {
        $holdCutoff = Carbon::now('Asia/Kolkata')->subMinutes($this->holdMinutes());

        $rows = DoctorBooking::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('booking_date', '>=', $fromDate)
            ->whereDate('booking_date', '<=', $toDate)
            ->whereIn('status', [
                DoctorBooking::STATUS_PENDING,
                DoctorBooking::STATUS_CONFIRMED,
                DoctorBooking::STATUS_COMPLETED,
                DoctorBooking::STATUS_MISSED,
            ])
            ->get(['id', 'booking_date', 'required_time_slots', 'status', 'created_at']);

        $chatbotPendingIds = [];
        if (Schema::hasTable('disease_chatbot_bookings')) {
            $pendingBookingIds = $rows
                ->where('status', DoctorBooking::STATUS_PENDING)
                ->pluck('id')
                ->all();

            if ($pendingBookingIds !== []) {
                $chatbotPendingIds = DiseaseChatbotBooking::query()
                    ->whereIn('doctor_booking_id', $pendingBookingIds)
                    ->where('status', DiseaseChatbotBooking::STATUS_PENDING_PAYMENT)
                    ->pluck('doctor_booking_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $chatbotPendingIds = array_fill_keys($chatbotPendingIds, true);
            }
        }

        $byDate = [];
        foreach ($rows as $row) {
            if ($row->status === DoctorBooking::STATUS_PENDING) {
                $isChatbotHold = isset($chatbotPendingIds[(int) $row->id]);
                if (! $isChatbotHold) {
                    $created = $row->created_at
                        ? Carbon::parse($row->created_at)->timezone('Asia/Kolkata')
                        : null;
                    // Non-chatbot pending: only block within the hold window.
                    if ($created === null || $created->lt($holdCutoff)) {
                        continue;
                    }
                }
                // Chatbot pending_payment link: keep blocking regardless of hold minutes.
            }

            $dateKey = Carbon::parse($row->booking_date)->toDateString();
            foreach ((array) ($row->required_time_slots ?? []) as $raw) {
                $minutes = $this->parseToMinutes((string) $raw);
                if ($minutes === null) {
                    Log::warning('disease_chatbot.slot_parse_failed', [
                        'booking_id' => $row->id,
                        'reason' => 'unparseable_time_slot',
                    ]);
                    continue;
                }
                $byDate[$dateKey][] = $minutes;
            }
        }

        foreach ($byDate as $date => $mins) {
            $byDate[$date] = array_values(array_unique($mins));
        }

        return $byDate;
    }

    /**
     * Doctors with at least one slot in the horizon, sorted by next_available then rating.
     *
     * @return list<array<string, mixed>>
     */
    public function doctorsWithAvailability(int $hospitalId, int $masterSpecialityId, int $limit = 10): array
    {
        $doctors = $this->eligibleDoctors($hospitalId, $masterSpecialityId);
        if ($doctors->isEmpty()) {
            return [];
        }

        $ratings = DoctorReview::query()
            ->whereIn('doctor_id', $doctors->pluck('id'))
            ->where('status', 'active')
            ->selectRaw('doctor_id, AVG(rating) as avg_rating')
            ->groupBy('doctor_id')
            ->pluck('avg_rating', 'doctor_id');

        $cards = [];
        foreach ($doctors as $doctor) {
            $slots = $this->slotsForDoctor((string) $doctor->id, $hospitalId);
            $next = $this->firstAvailable($slots);
            if ($next === null) {
                continue;
            }

            $rating = $ratings->get($doctor->id);
            $cards[] = [
                'id' => (string) $doctor->id,
                'name' => (string) $doctor->name,
                'photo_url' => $doctor->doctor_image
                    ? url('storage/doctor/'.$doctor->doctor_image)
                    : null,
                'qualification' => $this->qualificationOrNull($doctor),
                'experience' => $this->formatExperience($doctor->working_since),
                'rating' => $rating !== null ? round((float) $rating, 1) : null,
                'consultation_fee' => (float) ($doctor->consultation_fee ?? 0),
                'next_available' => [
                    'date' => $next['date'],
                    'time' => $next['time'],
                    'label' => $this->humanNextAvailableLabel($next['date'], $next['time']),
                ],
                '_sort_ts' => $next['ts'],
                '_sort_rating' => $rating !== null ? (float) $rating : -1.0,
            ];
        }

        usort($cards, function (array $a, array $b) {
            return [$a['_sort_ts'], -$a['_sort_rating'], $a['name']]
                <=> [$b['_sort_ts'], -$b['_sort_rating'], $b['name']];
        });

        return array_map(
            fn (array $row) => collect($row)->except(['_sort_ts', '_sort_rating'])->all(),
            array_slice($cards, 0, $limit)
        );
    }

    /**
     * @param  array<string, list<string>>  $slotsByDate
     * @return list<array{date: string, label: string, slots_count: int, available: bool}>
     */
    public function datePickerItems(array $slotsByDate): array
    {
        $items = [];
        foreach ($slotsByDate as $date => $times) {
            $carbon = Carbon::parse($date, 'Asia/Kolkata');
            $count = count($times);
            $items[] = [
                'date' => $date,
                'label' => $carbon->format('D, j M'),
                'slots_count' => $count,
                'available' => $count > 0,
            ];
        }

        return $items;
    }

    /**
     * @param  list<string>  $times
     * @return list<array{label: string, slots: list<array{value: string, label: string}>}>
     */
    public function hourGroups(array $times): array
    {
        $groups = [
            'Morning' => [],
            'Afternoon' => [],
            'Evening' => [],
        ];

        foreach ($times as $time) {
            $minutes = $this->parseToMinutes($time);
            if ($minutes === null) {
                continue;
            }
            $slot = ['value' => $time, 'label' => $time];
            if ($minutes < 12 * 60) {
                $groups['Morning'][] = $slot;
            } elseif ($minutes < 17 * 60) {
                $groups['Afternoon'][] = $slot;
            } else {
                $groups['Evening'][] = $slot;
            }
        }

        $out = [];
        foreach ($groups as $label => $slots) {
            if ($slots === []) {
                continue;
            }
            $out[] = ['label' => $label, 'slots' => $slots];
        }

        return $out;
    }

    /**
     * @param  array<string, list<string>>  $slotsByDate
     * @return array{date: string, time: string, ts: int}|null
     */
    protected function firstAvailable(array $slotsByDate): ?array
    {
        foreach ($slotsByDate as $date => $times) {
            if ($times === []) {
                continue;
            }
            $time = $times[0];
            $minutes = $this->parseToMinutes($time) ?? 0;

            return [
                'date' => $date,
                'time' => $time,
                'ts' => Carbon::parse($date, 'Asia/Kolkata')->startOfDay()->addMinutes($minutes)->timestamp,
            ];
        }

        return null;
    }

    public function parseToMinutes(?string $time): ?int
    {
        $time = trim((string) $time);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $time, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            $ampm = strtoupper($m[3]);
            if ($h < 1 || $h > 12 || $min > 59) {
                return null;
            }
            if ($ampm === 'AM') {
                if ($h === 12) {
                    $h = 0;
                }
            } elseif ($h !== 12) {
                $h += 12;
            }

            return $h * 60 + $min;
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            if ($h > 23 || $min > 59) {
                return null;
            }

            return $h * 60 + $min;
        }

        return null;
    }

    public function formatMinutes(int $minutes): string
    {
        $minutes = max(0, min(24 * 60 - 1, $minutes));
        $h24 = intdiv($minutes, 60);
        $min = $minutes % 60;
        $ampm = $h24 >= 12 ? 'PM' : 'AM';
        $h12 = $h24 % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }

        return sprintf('%02d:%02d %s', $h12, $min, $ampm);
    }

    protected function formatExperience(?string $workingSince): ?string
    {
        if ($workingSince === null || trim($workingSince) === '') {
            return null;
        }
        if (! preg_match('/\b(19|20)\d{2}\b/', trim($workingSince), $matches)) {
            return null;
        }
        $years = max(0, (int) date('Y') - (int) $matches[0]);

        return $years === 1 ? '1 Yr Exp' : "{$years} Yrs Exp";
    }

    public function humanNextAvailableLabel(string $date, string $time): string
    {
        $day = Carbon::parse($date, 'Asia/Kolkata')->startOfDay();
        $today = Carbon::today('Asia/Kolkata');
        if ($day->equalTo($today)) {
            return 'Today, '.$time;
        }
        if ($day->equalTo($today->copy()->addDay())) {
            return 'Tomorrow, '.$time;
        }

        return $day->format('D, j M').', '.$time;
    }

    protected function qualificationOrNull(Doctor $doctor): ?string
    {
        if (empty($doctor->qualifications)) {
            return null;
        }
        $names = trim((string) ($doctor->qualification_names ?? ''));
        if ($names === '' || $names === '-') {
            return null;
        }

        return $names;
    }

    /**
     * Master specialities from the list that have at least one doctor with a slot.
     *
     * @param  list<array{id: int, name: string}>  $specialities
     * @return list<array{id: int, name: string}>
     */
    public function specialitiesWithAvailability(int $hospitalId, array $specialities): array
    {
        $out = [];
        foreach ($specialities as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($this->doctorsWithAvailability($hospitalId, $id, 1) !== []) {
                $out[] = ['id' => $id, 'name' => (string) ($row['name'] ?? '')];
            }
        }

        return $out;
    }
}
