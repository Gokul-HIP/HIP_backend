<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Persons;
use App\Models\Coins;
use App\Models\Doctor;
use App\Models\SpecialitiesMaster;
use App\Models\MasterQualification;
use App\Models\Hospital;
use App\Models\DoctorAssignment;
use App\Models\Procedure;
use App\Models\DoctorReview;
use App\Services\AssignDoctorService;
use App\Services\Api\HospitalApiService;
use App\Services\Api\BookingApiService;
use App\Models\SecondOpinion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\LocationMaster;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Disease;
use App\Models\DiagnosticPackage;
use App\Models\DiseaseDepartment;
use App\Models\DiseasePackage;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class HomePageController extends Controller
{
    public function __construct(
        protected HospitalApiService $hospitalApiService,
        protected BookingApiService $bookingApiService
    ) {}

    private function resolveHospitalCoordinates(Hospital $hospital): ?array
    {
        $lat = $hospital->admin_latitude;
        $lng = $hospital->admin_longitude;

        if (is_numeric($lat) && is_numeric($lng)
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180) {
            return [(float) $lat, (float) $lng];
        }

        $location = $hospital->location;
        if ($location && is_numeric($location->latitude) && is_numeric($location->longitude)) {
            return [(float) $location->latitude, (float) $location->longitude];
        }

        return null;
    }

    private function findNearestArea($areas, float $lat, float $lng): ?LocationMaster
    {
        return $areas->sortBy(fn (LocationMaster $area) => $this->distanceKm(
            $lat,
            $lng,
            (float) $area->latitude,
            (float) $area->longitude
        ))->first();
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function formatDoctorExperience(?string $workingSince): ?string
    {
        if ($workingSince === null || trim($workingSince) === '') {
            return null;
        }

        if (!preg_match('/\b(19|20)\d{2}\b/', trim($workingSince), $matches)) {
            return null;
        }

        $startYear = (int) $matches[0];
        $currentYear = (int) date('Y');
        $years = max(0, $currentYear - $startYear);

        if ($years === 1) {
            return '1 Yr Exp';
        }

        return "{$years} Yrs Exp";
    }

    private function ageFromDob($dob): ?int
    {
        if (empty($dob)) {
            return null;
        }

        try {
            return Carbon::parse($dob)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function branchRequiredResponse()
    {
        return response()->json([
            'status'  => 422,
            'message' => 'Please select the branch',
            'data'    => [],
            'count'   => 0,
        ], 422);
    }

    private function resolveHomepageHospitalId(Request $request, $user): int
    {
        // Match doctor-list API: explicit hospital_id in query wins when provided.
        if ($request->filled('hospital_id')) {
            return (int) $request->hospital_id;
        }

        if ($user->preferred_branch_id) {
            return (int) $user->preferred_branch_id;
        }

        return 0;
    }

    private function scopeDoctorsForHospital($query, int $hospitalId)
    {
        $hospitalAsNumber = json_encode($hospitalId);
        $hospitalAsString = json_encode((string) $hospitalId);

        return $query->where(function ($q) use ($hospitalId, $hospitalAsNumber, $hospitalAsString) {
            $q->where(function ($inner) use ($hospitalAsNumber, $hospitalAsString) {
                $inner->whereNotNull('hospital_ids')
                    ->whereRaw('JSON_VALID(hospital_ids) = 1')
                    ->whereRaw(
                        '(JSON_CONTAINS(hospital_ids, ?) OR JSON_CONTAINS(hospital_ids, ?))',
                        [$hospitalAsNumber, $hospitalAsString]
                    );
            })->orWhere(function ($inner) use ($hospitalAsNumber, $hospitalAsString) {
                $inner->whereNotNull('assigned_hospital')
                    ->whereRaw('JSON_VALID(assigned_hospital) = 1')
                    ->whereRaw(
                        '(JSON_CONTAINS(assigned_hospital, ?) OR JSON_CONTAINS(assigned_hospital, ?))',
                        [$hospitalAsNumber, $hospitalAsString]
                    );
            })->orWhereHas('assignments', function ($assignment) use ($hospitalId) {
                $assignment->where('hospital_id', $hospitalId)->where('status', 'active');
            });
        });
    }

    private function scopeDoctorsForSpeciality($query, int $specialityId)
    {
        $specialityAsNumber = json_encode($specialityId);
        $specialityAsString = json_encode((string) $specialityId);

        return $query->where(function ($q) use ($specialityAsNumber, $specialityAsString) {
            $q->where(function ($inner) use ($specialityAsNumber, $specialityAsString) {
                $inner->whereRaw('JSON_VALID(speciality) = 1')
                    ->whereRaw(
                        '(JSON_CONTAINS(speciality, ?) OR JSON_CONTAINS(speciality, ?))',
                        [$specialityAsNumber, $specialityAsString]
                    );
            });
        });
    }

    /** Match only the doctor profile speciality (same field used for speciality_names). */
    private function scopeDoctorsForProfileSpeciality($query, int $specialityId)
    {
        $specialityAsNumber = json_encode($specialityId);
        $specialityAsString = json_encode((string) $specialityId);

        return $query
            ->whereRaw('JSON_VALID(speciality) = 1')
            ->whereRaw(
                '(JSON_CONTAINS(speciality, ?) OR JSON_CONTAINS(speciality, ?))',
                [$specialityAsNumber, $specialityAsString]
            );
    }

    private function scopeDoctorsForDisease($query, int $diseaseId)
    {
        $diseaseAsNumber = json_encode($diseaseId);
        $diseaseAsString = json_encode((string) $diseaseId);

        return $query->where(function ($q) use ($diseaseAsNumber, $diseaseAsString) {
            $q->whereNotNull('assigned_diseases')
                ->whereRaw('JSON_VALID(assigned_diseases) = 1')
                ->whereRaw(
                    '(JSON_CONTAINS(assigned_diseases, ?) OR JSON_CONTAINS(assigned_diseases, ?))',
                    [$diseaseAsNumber, $diseaseAsString]
                );
        });
    }

    private function applyDoctorSearch($query, string $search, ?int $hospitalId = null)
    {
        $searchLike = '%' . $search . '%';

        $specialityIds = SpecialitiesMaster::query()
            ->where('status', 'active')
            ->where('name', 'like', $searchLike)
            ->pluck('id');

        $qualificationIds = MasterQualification::query()
            ->where('name', 'like', $searchLike)
            ->pluck('id');

        $procedureIds = Procedure::query()
            ->where('status', 'active')
            ->where('procedure_name', 'like', $searchLike)
            ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId))
            ->pluck('id');

        return $query->where(function ($q) use ($searchLike, $specialityIds, $qualificationIds, $procedureIds, $hospitalId) {
            $q->where('name', 'like', $searchLike);

            foreach ($specialityIds as $specialityId) {
                $q->orWhere(function ($inner) use ($specialityId) {
                    $this->scopeDoctorsForProfileSpeciality($inner, (int) $specialityId);
                });
            }

            foreach ($qualificationIds as $qualificationId) {
                $asNumber = json_encode((int) $qualificationId);
                $asString = json_encode((string) $qualificationId);

                $q->orWhere(function ($inner) use ($asNumber, $asString) {
                    $inner->whereRaw('JSON_VALID(qualifications) = 1')
                        ->whereRaw(
                            '(JSON_CONTAINS(qualifications, ?) OR JSON_CONTAINS(qualifications, ?))',
                            [$asNumber, $asString]
                        );
                });
            }

            foreach ($procedureIds as $procedureId) {
                $asNumber = json_encode((int) $procedureId);
                $asString = json_encode((string) $procedureId);

                $q->orWhere(function ($inner) use ($asNumber, $asString) {
                    $inner->whereRaw('JSON_VALID(assigned_procedure) = 1')
                        ->whereRaw(
                            '(JSON_CONTAINS(assigned_procedure, ?) OR JSON_CONTAINS(assigned_procedure, ?))',
                            [$asNumber, $asString]
                        );
                })->orWhereHas('assignments', function ($assignment) use ($asNumber, $asString, $hospitalId) {
                    $assignment->where('status', 'active')
                        ->whereRaw(
                            '(JSON_CONTAINS(procedure_ids, ?) OR JSON_CONTAINS(procedure_ids, ?))',
                            [$asNumber, $asString]
                        );

                    if ($hospitalId) {
                        $assignment->where('hospital_id', $hospitalId);
                    }
                });
            }
        });
    }

    private function applyDoctorSort($query, string $sort)
    {
        return match ($sort) {
            'name_desc' => $query->orderByDesc('name'),
            'rating_desc' => $query->orderByDesc('rating_avg')->orderBy('name'),
            'rating_asc' => $query->orderBy('rating_avg')->orderBy('name'),
            'experience_desc' => $query->orderBy('working_since')->orderBy('name'),
            'experience_asc' => $query->orderByDesc('working_since')->orderBy('name'),
            'newest' => $query->orderByDesc('created_at')->orderBy('name'),
            'oldest' => $query->orderBy('created_at')->orderBy('name'),
            'availability' => $query
                ->orderByDesc('has_upcoming_assignment')
                ->orderBy('name'),
            default => $query->orderBy('name'),
        };
    }

    private function resolveNextSlotDateTime(string $dayName, string $startTime): ?Carbon
    {
        try {
            $targetDayOfWeek = Carbon::parse($dayName)->dayOfWeek;
            $normalizedStart = AssignDoctorService::timeToAmPm($startTime);
            $time24 = AssignDoctorService::amPmToTime($normalizedStart);

            if (!preg_match('/^(\d{1,2}):(\d{2})/', $time24, $parts)) {
                return null;
            }

            $now = Carbon::now();

            for ($offset = 0; $offset < 14; $offset++) {
                $date = $now->copy()->startOfDay()->addDays($offset);

                if ($date->dayOfWeek !== $targetDayOfWeek) {
                    continue;
                }

                $slotDateTime = $date->copy()->setTime((int) $parts[1], (int) $parts[2], 0);

                if ($slotDateTime->greaterThan($now)) {
                    return $slotDateTime;
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function formatSlotDayLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isTomorrow()) {
            return 'Tomorrow';
        }

        return $date->format('D, d M');
    }

    private function formatNextSlotLabel(Carbon $date, string $time): string
    {
        return $this->formatSlotDayLabel($date) . ', ' . $time;
    }

    private function resolveNextSlotFromAssignments($assignments): ?array
    {
        $candidates = [];

        foreach ($assignments as $assignment) {
            foreach ((array) ($assignment->time_slots ?? []) as $slot) {
                $day = $slot['day'] ?? null;
                $start = $slot['start'] ?? null;
                $end = $slot['end'] ?? null;

                if (!$day || !$start) {
                    continue;
                }

                $slotDateTime = $this->resolveNextSlotDateTime($day, $start);

                if (!$slotDateTime) {
                    continue;
                }

                $candidates[] = [
                    'datetime'    => $slotDateTime,
                    'day'         => $day,
                    'date'        => $slotDateTime->toDateString(),
                    'start'       => AssignDoctorService::timeToAmPm($start),
                    'end'         => $end ? AssignDoctorService::timeToAmPm($end) : null,
                    'hospital_id' => $assignment->hospital_id,
                ];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a['datetime']->timestamp <=> $b['datetime']->timestamp);

        $next = $candidates[0];
        $labelDay = $this->formatSlotDayLabel($next['datetime']);

        return [
            'day'         => $next['day'],
            'date'        => $next['date'],
            'start'       => $next['start'],
            'end'         => $next['end'],
            'label'       => "{$labelDay}, {$next['start']}",
            'hospital_id' => $next['hospital_id'],
        ];
    }

    private function resolveProcedureNames($assignments, $procedureMap): array
    {
        $ids = collect($assignments)
            ->flatMap(fn ($assignment) => (array) ($assignment->procedure_ids ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return $ids->map(fn ($id) => $procedureMap[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function resolveDoctorHospitalIds(Doctor $doctor, $assignments): array
    {
        return collect()
            ->merge((array) ($doctor->hospital_ids ?? []))
            ->merge((array) ($doctor->assigned_hospital ?? []))
            ->merge($assignments->pluck('hospital_id'))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function buildDoctorBranches(array $hospitalIds): array
    {
        if ($hospitalIds === []) {
            return [];
        }

        $areas = LocationMaster::query()
            ->select('id', 'area', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return Hospital::query()
            ->where('status', 'active')
            ->whereIn('id', $hospitalIds)
            ->select('id', 'name', 'logo', 'location_id', 'admin_latitude', 'admin_longitude', 'address', 'admin_contact')
            ->with('location:id,area,latitude,longitude')
            ->orderBy('name')
            ->get()
            ->map(function (Hospital $hospital) use ($areas) {
                $coordinates = $this->resolveHospitalCoordinates($hospital);

                if (!$coordinates) {
                    return null;
                }

                $areaName = $hospital->location?->area
                    ?? $this->findNearestArea($areas, $coordinates[0], $coordinates[1])?->area;

                return [
                    'hospital_id'   => $hospital->id,
                    'hospital_name' => $hospital->name,
                    'branch_name'   => $areaName ? "{$areaName} Branch" : null,
                    'area'          => $areaName,
                    'address'       => $hospital->address ?? $areaName,
                    'contact'       => $hospital->admin_contact,
                    'google_map_link' => sprintf(
                        'https://www.google.com/maps/search/?api=1&query=%s,%s',
                        $coordinates[0],
                        $coordinates[1]
                    ),
                    'logo'          => $hospital->logo
                        ? url('storage/hospital/' . $hospital->logo)
                        : null,
                ];
            })
            ->filter()
            ->sortBy('branch_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function buildAvailabilityCalendar($assignments, ?int $hospitalId = null, int $days = 14): array
    {
        $weekDays = collect();

        foreach ($assignments as $assignment) {
            if ($hospitalId && (int) $assignment->hospital_id !== $hospitalId) {
                continue;
            }

            foreach ((array) ($assignment->time_slots ?? []) as $slot) {
                $day = $slot['day'] ?? null;
                if ($day) {
                    $weekDays->push(Carbon::parse($day)->dayOfWeek);
                }
            }
        }

        $weekDays = $weekDays->unique()->values();

        $calendar = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $date = Carbon::today()->addDays($offset);
            $calendar[] = [
                'date'         => $date->toDateString(),
                'day'          => $date->format('l'),
                'day_short'    => strtoupper($date->format('D')),
                'day_number'   => (int) $date->format('d'),
                'month'        => strtoupper($date->format('M')),
                'is_available' => $weekDays->contains($date->dayOfWeek),
            ];
        }

        return $calendar;
    }

    private function resolveSelectedDate(Request $request, array $calendar): Carbon
    {
        if ($request->filled('date')) {
            return Carbon::parse($request->date)->startOfDay();
        }

        $firstAvailable = collect($calendar)->firstWhere('is_available', true);

        return $firstAvailable
            ? Carbon::parse($firstAvailable['date'])->startOfDay()
            : Carbon::today();
    }

    private function resolveSlotsForDate($assignments, Carbon $date, ?int $hospitalId = null): array
    {
        $dayName = $date->format('l');
        $now = Carbon::now();
        $slots = [];

        foreach ($assignments as $assignment) {
            if ($hospitalId && (int) $assignment->hospital_id !== $hospitalId) {
                continue;
            }

            foreach ((array) ($assignment->time_slots ?? []) as $slot) {
                if (($slot['day'] ?? '') !== $dayName) {
                    continue;
                }

                $start = AssignDoctorService::timeToAmPm($slot['start'] ?? '');
                $end = !empty($slot['end']) ? AssignDoctorService::timeToAmPm($slot['end']) : null;
                $time24 = AssignDoctorService::amPmToTime($start);

                if (!preg_match('/^(\d{1,2}):(\d{2})/', $time24, $parts)) {
                    continue;
                }

                $slotDateTime = $date->copy()->setTime((int) $parts[1], (int) $parts[2], 0);

                $slots[] = [
                    'start'       => $start,
                    'end'         => $end,
                    'label'       => $start,
                    'available'   => $slotDateTime->greaterThan($now),
                    'hospital_id' => $assignment->hospital_id,
                    'sort_time'   => (int) $parts[1] * 60 + (int) $parts[2],
                ];
            }
        }

        return collect($slots)
            ->unique(fn ($slot) => $slot['start'] . '|' . $slot['hospital_id'])
            ->sortBy('sort_time')
            ->values()
            ->map(fn ($slot) => collect($slot)->except('sort_time')->all())
            ->all();
    }

    /**
     * Build next available slots grouped by date/day.
     * Returns only available date/day/time slots.
     */
    private function buildNextAvailableSlotsByDay($assignments, ?int $hospitalId = null, int $days = 14, int $limit = 7): array
    {
        $result = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = Carbon::today()->addDays($offset);
            $dateSlots = $this->resolveSlotsForDate($assignments, $date, $hospitalId);
            $availableSlots = collect($dateSlots)
                ->filter(fn ($slot) => (bool) ($slot['available'] ?? false))
                ->map(fn ($slot) => [
                    'start' => $slot['start'] ?? null,
                    'end' => $slot['end'] ?? null,
                ])
                ->values()
                ->all();

            if ($availableSlots === []) {
                continue;
            }

            $result[] = [
                'date' => $date->toDateString(),
                'day' => strtoupper($date->format('D')),
                'day_number' => (int) $date->format('d'),
                'time_slots' => $availableSlots,
            ];

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    /**
     * Full weekly schedule showing available day + branch + time slots.
     */
    private function buildFullWeeklyScheduleByBranch($assignments, array $branches, ?int $hospitalId = null): array
    {
        $branchLookup = collect($branches)->keyBy(fn ($b) => (int) ($b['hospital_id'] ?? 0));
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $weekly = [];
        foreach ($weekdays as $weekday) {
            $weekly[$weekday] = [];
        }

        foreach ($assignments as $assignment) {
            $assignmentHospitalId = (int) ($assignment->hospital_id ?? 0);
            if ($hospitalId && $assignmentHospitalId !== $hospitalId) {
                continue;
            }

            foreach ((array) ($assignment->time_slots ?? []) as $slot) {
                $slotDay = $slot['day'] ?? null;
                if (! $slotDay) {
                    continue;
                }

                $weekday = Carbon::parse($slotDay)->format('l');
                if (! isset($weekly[$weekday])) {
                    continue;
                }

                $start = AssignDoctorService::timeToAmPm($slot['start'] ?? '');
                $end = ! empty($slot['end']) ? AssignDoctorService::timeToAmPm($slot['end']) : null;
                if ($start === '') {
                    continue;
                }

                $branchInfo = $branchLookup->get($assignmentHospitalId);
                $branchName = $branchInfo['branch_name']
                    ?? $branchInfo['hospital_name']
                    ?? ('Branch ' . $assignmentHospitalId);

                if (! isset($weekly[$weekday][$assignmentHospitalId])) {
                    $weekly[$weekday][$assignmentHospitalId] = [
                        'branch_id' => $assignmentHospitalId ?: null,
                        'branch_name' => $branchName,
                        'time_slots' => [],
                    ];
                }

                $weekly[$weekday][$assignmentHospitalId]['time_slots'][] = [
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        return collect($weekdays)
            ->map(function (string $weekday) use ($weekly) {
                $branchesForDay = array_values($weekly[$weekday] ?? []);
                $branchesForDay = array_map(function (array $branch) {
                    $seen = [];
                    $timeSlots = array_values(array_filter($branch['time_slots'], function (array $slot) use (&$seen) {
                        $key = ($slot['start'] ?? '') . '|' . ($slot['end'] ?? '');
                        if (isset($seen[$key])) {
                            return false;
                        }
                        $seen[$key] = true;
                        return true;
                    }));

                    usort($timeSlots, function (array $a, array $b) {
                        $aStart = AssignDoctorService::amPmToTime((string) ($a['start'] ?? ''));
                        $bStart = AssignDoctorService::amPmToTime((string) ($b['start'] ?? ''));
                        return strcmp($aStart, $bStart);
                    });

                    $firstSlot = $timeSlots[0] ?? null;
                    $lastSlot = ! empty($timeSlots)
                        ? $timeSlots[count($timeSlots) - 1]
                        : null;

                    $rangeStart = $firstSlot['start'] ?? null;
                    $rangeEnd = $lastSlot['end'] ?? ($lastSlot['start'] ?? null);

                    $branch['time'] = ($rangeStart && $rangeEnd)
                        ? ($rangeStart . ' - ' . $rangeEnd)
                        : null;
                    unset($branch['time_slots']);

                    return $branch;
                }, $branchesForDay);

                $branchesForDay = array_values(array_filter(
                    $branchesForDay,
                    fn (array $branch) => ! empty($branch['time'])
                ));

                if ($branchesForDay === []) {
                    return null;
                }

                return [
                    'day' => strtoupper(substr($weekday, 0, 3)),
                    'branches' => $branchesForDay,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function buildReviewsSummary(string $doctorId): array
    {
        $baseQuery = DoctorReview::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'active');

        $total = (clone $baseQuery)->count();
        $average = $total > 0
            ? round((float) ((clone $baseQuery)->avg('rating') ?? 0), 1)
            : 0.0;

        $distribution = [];
        $breakdown = [];

        for ($star = 5; $star >= 1; $star--) {
            $count = (clone $baseQuery)->where('rating', $star)->count();
            $percentage = $total > 0
                ? round(($count / $total) * 100, 1)
                : 0.0;

            $breakdown[(string) $star] = $count;
            $distribution[] = [
                'stars' => $star,
                'count' => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'average_rating' => $average,
            'total_reviews' => $total,
            'rating_distribution' => $distribution,
            'rating_breakdown' => $breakdown,
        ];
    }

    private function formatDoctorCard($doctor, ?int $hospitalId = null, $procedureMap = null): array
    {
        $rating = $doctor->rating_avg !== null
            ? (string) round((float) $doctor->rating_avg, 1)
            : '0';

        $assignments = $doctor->relationLoaded('assignments')
            ? $doctor->assignments
            : collect();

        if ($hospitalId) {
            $assignments = $assignments->where('hospital_id', $hospitalId);
        }

        $assignments = $assignments->where('status', 'active')->values();
        $nextSlot = $this->resolveNextSlotFromAssignments($assignments);
        $procedureNames = $procedureMap
            ? $this->resolveProcedureNames($assignments, $procedureMap)
            : [];

        return [
            'id'                  => $doctor->id,
            'name'                => $doctor->name,
            'doctor_image'        => $doctor->doctor_image
                ? url('storage/doctor/' . $doctor->doctor_image)
                : null,
            'qualification_names' => $doctor->qualification_names,
            'speciality_names'    => $doctor->speciality_names,
            'consultation_fee'    => $doctor->consultation_fee,
            'experience'          => $this->formatDoctorExperience($doctor->working_since),
            'rating'              => $rating,
            'review_count'        => (int) ($doctor->reviews_count ?? 0),
            'available_today'     => $nextSlot !== null && ($nextSlot['date'] ?? null) === today()->toDateString(),
            // 'procedure_names'     => $procedureNames,
            // 'next_slot'           => $nextSlot,
            'next_slot_label'     => $nextSlot['label'] ?? null,
        ];
    }

    private function personProfileImageUrl(?string $filename): ?string
    {
        return $filename ? url('storage/person/' . $filename) : null;
    }
    public function hospitalLocations()
    {
        try {
            $areas = LocationMaster::query()
                ->select('id', 'area', 'latitude', 'longitude')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            $hospitals = Hospital::query()
                ->where('status', 'active')
                ->select('id', 'name', 'logo', 'location_id', 'admin_latitude', 'admin_longitude', 'admin_contact')
                ->with('location:id,area,latitude,longitude')
                ->orderBy('name')
                ->get();

            $data = $hospitals->map(function (Hospital $hospital) use ($areas) {
                $coordinates = $this->resolveHospitalCoordinates($hospital);

                if (!$coordinates) {
                    return null;
                }

                [$latitude, $longitude] = $coordinates;

                $areaName = $hospital->location?->area
                    ?? $this->findNearestArea($areas, $latitude, $longitude)?->area;

                return [
                    'hospital_id'   => $hospital->id,
                    // 'hospital_name' => $hospital->name,
                    'branch_name'   => $areaName ? "{$areaName}" : null,
                    'area'          => $areaName,
                    'contact'       => $hospital->admin_contact ?? null,
                    // 'latitude'      => $latitude,
                    // 'longitude'     => $longitude,
                    // 'logo'          => $hospital->logo
                    //     ? url('storage/hospital/' . $hospital->logo)
                    //     : null,
                ];
            })
                ->filter()
                ->sortBy('branch_name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();

            return response()->json([
                'status'  => 200,
                'message' => 'Hospital locations fetched successfully',
                'data'    => $data,
                'count'   => $data->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching hospital locations', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching hospital locations',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }


    public function userCoins(Request $request){

        $request->validate([
            'consultation_fee' => 'nullable|numeric|min:0',
            'coins_used' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();

        if(!$user){
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $coinsData = $this->fetchUserCoinsData($user);

        if ($coinsData === null) {
            return response()->json([
                'status' => 404,
                'message' => 'Person not found',
            ], 404);
        }

        $availableCoins = (int) ($coinsData['coins'] ?? 0);

        // If frontend only needs balance (toggle OFF / initial render), return coins only.
        if (! $request->filled('consultation_fee')) {
            return response()->json([
                'status' => 200,
                'message' => 'Coins fetched successfully',
                'data' => [
                    'coins' => $availableCoins,
                ],
            ], 200);
        }

        $consultationFee = (float) $request->consultation_fee;
        $coinsUsed = min((int) $request->input('coins_used', 0), $availableCoins);

        // Coin value + service charge are dynamic settings with config fallback.
        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );
        $serviceCharge = (float) app_setting(
            'service_charges',
            config('settings.fees.service_charges', config('services.service_charges_percent', 0))
        );

        $coinsValue = round($coinsUsed * $amountForOneCoin, 2);
        $totalDiscount = round(min($coinsValue, $consultationFee), 2);
        $amountAfterDiscount = round(max(0, $consultationFee - $totalDiscount), 2);
        $totalAmount = round($amountAfterDiscount + $serviceCharge, 2);
        $remainingCoins = max(0, $availableCoins - $coinsUsed);

        return response()->json([
            'status' => 200,
            'message' => 'Coins summary fetched successfully',
            'data' => [
                // 'coins' => $availableCoins,
                'coins_used' => $coinsUsed,
                'remaining_coins' => $remainingCoins,
                // 'amount_for_one_coin' => $amountForOneCoin,
                // 'coins_value' => $coinsValue,
                'consultation_fee' => $consultationFee,
                'total_discount' => $totalDiscount,
                // 'amount_after_discount' => $amountAfterDiscount,
                'service_charge' => $serviceCharge,
                'total_amount' => $totalAmount,
            ],
        ], 200);

    }

    /**
     * Package booking coins preview — same response shape as userCoins.
     * Without type + package_id: coins balance only (like userCoins without consultation_fee).
     * With type + package_id: pricing summary; consultation_fee from package (like userCoins with consultation_fee).
     */
    public function packageCoins(Request $request)
    {
        $request->validate([
            'type'       => 'nullable|string|in:diagnostic,disease',
            'package_id' => 'nullable|integer|min:1',
            'coins_used' => 'nullable|integer|min:0',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $coinsData = $this->fetchUserCoinsData($user);

        if ($coinsData === null) {
            return response()->json([
                'status'  => 404,
                'message' => 'Person not found',
            ], 404);
        }

        $availableCoins = (int) ($coinsData['coins'] ?? 0);

        if (! $request->filled('type') || ! $request->filled('package_id')) {
            return response()->json([
                'status'  => 200,
                'message' => 'Coins fetched successfully',
                'data'    => [
                    'coins' => $availableCoins,
                ],
            ], 200);
        }

        $package = $this->resolvePackageForCoins((string) $request->type, (int) $request->package_id);

        if (! $package) {
            return response()->json([
                'status'  => 404,
                'message' => 'Package not found or inactive',
            ], 404);
        }

        $consultationFee = $this->packageConsultationFee($package);
        $coinsUsed = min((int) $request->input('coins_used', 0), $availableCoins);

        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );
        $serviceCharge = (float) app_setting(
            'service_charges',
            config('settings.fees.service_charges', config('services.service_charges_percent', 0))
        );

        $coinsValue = round($coinsUsed * $amountForOneCoin, 2);
        $totalDiscount = round(min($coinsValue, $consultationFee), 2);
        $amountAfterDiscount = round(max(0, $consultationFee - $totalDiscount), 2);
        $totalAmount = round($amountAfterDiscount + $serviceCharge, 2);
        $remainingCoins = max(0, $availableCoins - $coinsUsed);

        return response()->json([
            'status'  => 200,
            'message' => 'Coins summary fetched successfully',
            'data'    => [
                'coins_used'       => $coinsUsed,
                'remaining_coins'  => $remainingCoins,
                'package_fee' => $consultationFee,
                'coin_discount'   => $totalDiscount,
                'service_charge'    => $serviceCharge,
                'total_amount'      => $totalAmount,
            ],
        ], 200);
    }

    private function packageConsultationFee(DiagnosticPackage|DiseasePackage $package): float
    {
        $price = (float) ($package->price ?? 0);
        $discount = (float) ($package->discount ?? 0);

        return round($price - ($price * ($discount / 100)), 2);
    }

    private function resolvePackageForCoins(string $type, int $packageId): DiagnosticPackage|DiseasePackage|null
    {
        $query = $type === 'disease'
            ? DiseasePackage::query()
            : DiagnosticPackage::query();

        $package = $query->where('id', $packageId)->first();

        if (! $package || ($package->status ?? null) !== 'active') {
            return null;
        }

        return $package;
    }

    public function doctorSpecialities(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'search'      => 'nullable|string|max:255',
        ]);

        try {
            $search = $request->filled('search') ? trim((string) $request->search) : null;
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;
            $result = $this->fetchDoctorSpecialitiesData($hospitalId, $search);

            return response()->json([
                'status'  => 200,
                'message' => $result['count'] > 0
                    ? 'Doctor specialities fetched successfully'
                    : 'No doctor specialities found',
                'data'    => $result['data'],
                'count'   => $result['count'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctor specialities', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching doctor specialities',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

    public function doctorList(Request $request)
    {
        if (!$request->filled('hospital_id')) {
            return $this->branchRequiredResponse();
        }

        $request->validate([
            'hospital_id' => 'required|integer|exists:hospitals,id',
            'per_page'    => 'nullable|integer|min:1|max:50',
            'page'        => 'nullable|integer|min:1',
        ]);

        try {
            $result = $this->fetchDoctorListData(
                (int) $request->hospital_id,
                (int) ($request->per_page ?? 10),
                (int) ($request->page ?? 1)
            );

            return response()->json([
                'status'       => 200,
                'message'      => $result['total'] > 0
                    ? 'Doctors fetched successfully'
                    : 'No doctors found',
                'data'         => $result['data'],
                'count'        => $result['count'],
                'current_page' => $result['current_page'],
                'last_page'    => $result['last_page'],
                'per_page'     => $result['per_page'],
                'total'        => $result['total'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctor list', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching doctor list',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

    public function hospitalBranches(){

        try {
            $result = $this->fetchHospitalBranchesData();

            return response()->json([
                'status'  => 200,
                'message' => 'Hospital locations fetched successfully',
                'data'    => $result['data'],
                'count'   => $result['count'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching hospital locations', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching hospital locations',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }

    }

    public function doctors(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'speciality_id' => 'nullable|integer|exists:specialities_masters,id',
            'search'      => 'nullable|string|max:255',
            'sort'        => 'nullable|string|in:name_asc,name_desc,rating_desc,rating_asc,experience_desc,experience_asc,availability,newest,oldest',
            'per_page'    => 'nullable|integer|min:1|max:50',
            'page'        => 'nullable|integer|min:1',
        ]);

        try {
            $perPage = (int) ($request->per_page ?? 10);
            $sort    = $request->input('sort', 'name_asc');
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;
            $specialityId = $request->filled('speciality_id') ? (int) $request->speciality_id : null;

            $query = Doctor::query()
                ->select(
                    'id',
                    'name',
                    'doctor_image',
                    'qualifications',
                    'speciality',
                    'consultation_fee',
                    'working_since',
                    'created_at'
                )
                ->where('status', 'active')
                ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                    $q->where('status', 'active');
                }], 'rating')
                ->withCount(['doctorReviews as reviews_count' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->withExists(['assignments as has_upcoming_assignment' => function ($q) use ($hospitalId) {
                    $q->where('status', 'active')
                        ->whereNotNull('time_slots')
                        ->whereRaw('JSON_LENGTH(time_slots) > 0');

                    if ($hospitalId) {
                        $q->where('hospital_id', $hospitalId);
                    }
                }])
                ->with(['assignments' => function ($q) use ($hospitalId) {
                    $q->where('status', 'active')
                        ->whereNotNull('time_slots')
                        ->when($hospitalId, fn ($inner) => $inner->where('hospital_id', $hospitalId))
                        ->select('id', 'doctor_id', 'hospital_id', 'time_slots', 'procedure_ids', 'day', 'date', 'status');
                }]);

            if ($hospitalId) {
                $this->scopeDoctorsForHospital($query, $hospitalId);
            }

            if ($specialityId) {
                $this->scopeDoctorsForSpeciality($query, $specialityId);
            }

            if ($request->filled('search') && strlen(trim($request->search)) >= 2) {
                $this->applyDoctorSearch($query, trim($request->search), $hospitalId);
            }

            $this->applyDoctorSort($query, $sort);

            $doctors = $query->paginate($perPage);

            if ($doctors->isEmpty()) {
                return response()->json([
                    'status'       => 200,
                    'message'      => 'No doctors found',
                    'data'         => [],
                    'count'        => 0,
                    'total'        => 0,
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => $perPage,
                ], 200);
            }

            $procedureIds = $doctors->getCollection()
                ->flatMap(fn ($doctor) => $doctor->assignments->flatMap(
                    fn ($assignment) => (array) ($assignment->procedure_ids ?? [])
                ))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $serviceCharges = (float) config('services.service_charges_percent');

            $procedureMap = Procedure::query()
                ->where('status', 'active')
                ->whereIn('id', $procedureIds)
                ->pluck('procedure_name', 'id');

            return response()->json([
                'status'       => 200,
                'message'      => 'Doctors fetched successfully',
                // 'service_charges' => $serviceCharges,
                'data'         => $doctors->getCollection()
                    ->map(fn ($doctor) => $this->formatDoctorCard($doctor, $hospitalId, $procedureMap))
                    ->values(),
                'count'        => $doctors->count(),
                'total'        => $doctors->total(),
                'current_page' => $doctors->currentPage(),
                'last_page'    => $doctors->lastPage(),
                'per_page'     => $doctors->perPage(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctors', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching doctors',
                'data'    => [],
                'count'   => 0,
                'total'   => 0,
            ], 500);
        }
    }

    public function doctorDetails(Request $request)
    {
        $request->validate([
            'doctor_id'   => 'required|uuid|exists:doctors,id',
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
        ]);

        try {
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;

            $doctor = Doctor::query()
                ->where('id', $request->doctor_id)
                ->where('status', 'active')
                ->with(['assignments' => function ($q) use ($hospitalId) {
                    $q->where('status', 'active')
                        ->whereNotNull('time_slots')
                        ->when($hospitalId, fn ($inner) => $inner->where('hospital_id', $hospitalId))
                        ->select('id', 'doctor_id', 'hospital_id', 'time_slots', 'procedure_ids', 'day', 'date', 'status');
                }])
                ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                    $q->where('status', 'active');
                }], 'rating')
                ->withCount(['doctorReviews as reviews_count' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->first();

            if (!$doctor) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Doctor not found',
                    'data'    => [],
                ], 404);
            }

            $assignments = $doctor->assignments;
            $hospitalIds = $this->resolveDoctorHospitalIds($doctor, $assignments);

            if ($hospitalId) {
                $hospitalIds = array_values(array_intersect($hospitalIds, [$hospitalId]));
            }

            $procedureIds = $assignments
                ->flatMap(fn ($assignment) => (array) ($assignment->procedure_ids ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $procedureMap = Procedure::query()
                ->where('status', 'active')
                ->whereIn('id', $procedureIds)
                ->pluck('procedure_name', 'id');

            $specialityIds = collect((array) ($doctor->speciality ?? []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $specialities = SpecialitiesMaster::query()
                ->where('status', 'active')
                ->whereIn('id', $specialityIds)
                ->select('id', 'name', 'description', 'display_image')
                ->orderBy('name')
                ->get()
                ->map(fn (SpecialitiesMaster $speciality) => [
                    'id'          => $speciality->id,
                    'name'        => $speciality->name,
                    'description' => $speciality->description,
                    'icon'        => $speciality->display_image
                        ? url('storage/speciality/' . basename($speciality->display_image))
                        : null,
                ])
                ->values();

            $specializations = collect($specialities->pluck('name'))
                ->merge($this->resolveProcedureNames($assignments, $procedureMap))
                ->unique()
                ->values()
                ->all();

            $calendar = $this->buildAvailabilityCalendar($assignments, $hospitalId);
            $nextSlot = $this->resolveNextSlotFromAssignments($assignments);
            $branches = $this->buildDoctorBranches($hospitalIds);
            $nextAvailableSlots = $this->buildNextAvailableSlotsByDay($assignments, $hospitalId, 14, 7);
            $fullWeeklySchedule = $this->buildFullWeeklyScheduleByBranch($assignments, $branches, $hospitalId);
            $reviewsSummary = $this->buildReviewsSummary($doctor->id);

            $reviews = DoctorReview::query()
                ->where('doctor_id', $doctor->id)
                ->where('status', 'active')
                ->with('member:id,first_name,last_name,profile_image')
                ->latest()
                ->limit(10)
                ->get()
                ->map(function (DoctorReview $review) {
                    $member = $review->member;

                    return [
                        'id'             => $review->id,
                        'reviewer_name'  => $member
                            ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))
                            : 'Anonymous',
                        'reviewer_image' => $member && $member->profile_image
                            ? url('storage/users/' . $member->profile_image)
                            : null,
                        'comment'        => $review->review,
                        'rating'         => (string) $review->rating,
                        'created_at'     => $review->created_at?->format('d M Y') ?? '',
                    ];
                })
                ->values();

            $rating = (string) ($reviewsSummary['average_rating'] ?? 0);

            return response()->json([
                'status'  => 200,
                'message' => 'Doctor details fetched successfully',
                'data'    => [
                    'id'                  => $doctor->id,
                    'name'                => $doctor->name,
                    'doctor_image'        => $doctor->doctor_image
                        ? url('storage/doctor/' . $doctor->doctor_image)
                        : null,
                    'qualification_names' => $doctor->qualification_names,
                    'consultation_fee'    => (float) $doctor->consultation_fee,
                    'speciality_names'    => $doctor->speciality_names,
                    // 'specialities'        => $specialities,
                    'specializations'     => $specializations,
                    'experience'          => $this->formatDoctorExperience($doctor->working_since),
                    'about'               => $doctor->about_doctor,
                    'rating'              => $rating,
                    'review_count'        => (int) ($reviewsSummary['total_reviews'] ?? 0),
                    // 'available_today'     => (bool) (collect($calendar)->firstWhere('date', today()->toDateString())['is_available'] ?? false),
                    'branches'            => $branches,
                    'next_slot'           => $nextSlot,
                    'next_available_slots' => $nextAvailableSlots,
                    'full_weekly_schedule_calendar' => $fullWeeklySchedule,
                    'reviews_summary'     => $reviewsSummary,
                    'reviews'             => $reviews,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctor details', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching doctor details',
                'data'    => [],
            ], 500);
        }
    }

    private function formatDiagnosticPackageRow($package, ?object $hospital = null, ?object $diagnostic = null, string $packageType = 'diagnostic'): array
    {
        $discount = (float) ($package->discount ?? 0);
        $price = (float) ($package->price ?? 0);

        $row = [
            'id'                     => $package->id,
            'package_type'           => $packageType,
            'name'                   => $package->name,
            'price'                  => $price,
            'coins_earn'           => (int) round($price * 0.01),
            'package_discount'       => $discount . '%',
            'package_discount_price' => (float) ($price - ($price * ($discount / 100))),
            'package_price_discounted' => (float) ($price - ($price - ($price * ($discount / 100)))),
            'weight'                 => (float) ($package->weight ?? 0),
            'is_home_service'        => (bool) ($package->is_home_service ?? false),
            'lab_tests'              => $package->lab_tests_list,
        ];

        if ($packageType === 'disease') {
            $row['disease_id'] = $package->disease_id ?? null;
        }

        return $row;
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mapHospitalPackagesResult(array $result, string $packageType): \Illuminate\Support\Collection
    {
        $packages = $result['packages'];

        if ($result['scoped']) {
            $hospital   = $result['hospital'];
            $diagnostic = $result['diagnostic'];

            return collect($packages->items())
                ->map(fn ($package) => $this->formatDiagnosticPackageRow($package, $hospital, $diagnostic, $packageType))
                ->values();
        }

        $diagnostics           = $result['diagnostics'];
        $hospitalsByDiagnostic = $result['hospitalsByDiagnostic'];

        return collect($packages->items())->map(function ($package) use ($diagnostics, $hospitalsByDiagnostic, $packageType) {
            $diagnostic = $diagnostics->get($package->diagnostic_id);
            $hospital   = $hospitalsByDiagnostic->get($package->diagnostic_id)?->first();

            return $this->formatDiagnosticPackageRow($package, $hospital, $diagnostic, $packageType);
        })->values();
    }

    private function diagnosticLogoUrl(?string $logo): ?string
    {
        return $logo ? url('storage/diagnostic/' . $logo) : null;
    }

    /**
     * Diagnostic packages for home screen.
     * Optional hospital_id (or id): filters via hospitals.diagnostic_center_id.
     * Without hospital_id: returns packages from all linked diagnostic centers.
     */
    public function hospitalDiagnosticPackages(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'page'        => 'nullable|integer|min:1',
            'page_limit'  => 'nullable|integer|min:1|max:50',
            'per_page'    => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;
            if ($hospitalId !== null && $hospitalId <= 0) {
                $hospitalId = null;
            }
            $page       = (int) ($request->page ?? 1);
            $pageLimit  = (int) ($request->page_limit ?? $request->per_page ?? env('PAGELIMIT', 10));

            $result = $this->hospitalApiService->getHospitalDiagnosticPackages($hospitalId, $page, $pageLimit);
            $diseaseResult = $this->hospitalApiService->getHospitalDiseasePackages($hospitalId, $page, $pageLimit);

            if ($hospitalId !== null && $result === null) {
                return response()->json([
                    'status'                 => 404,
                    'message'                => 'Hospital or diagnostic center not found',
                    'hospital_id'            => $hospitalId,
                    'hospital_name'          => null,
                    'diagnostic_id'          => null,
                    'diagnostic_image'       => null,
                    'diagnostic_name'        => null,
                    'data'                   => [],
                    'total'                  => 0,
                    'page'                   => $page,
                    'page_limit'             => $pageLimit,
                    'count'                  => 0,
                    'disease_packages'       => [],
                    'disease_packages_total' => 0,
                    'disease_packages_count' => 0,
                ], 404);
            }

            $packages       = $result['packages'];
            $data           = $this->mapHospitalPackagesResult($result, 'diagnostic');
            $diseasePackages = $diseaseResult
                ? $this->mapHospitalPackagesResult($diseaseResult, 'disease')
                : collect();

            $contextHospital   = $result['scoped'] ? $result['hospital'] : null;
            $contextDiagnostic = $result['scoped'] ? $result['diagnostic'] : null;

            $hasAny = $data->isNotEmpty() || $diseasePackages->isNotEmpty();
            $serviceCharge = (float) app_setting('service_charges', config('settings.fees.service_charges', config('services.service_charges_percent', 0)));

            return response()->json([
                'status'                 => 200,
                'message'                => $hasAny ? 'Diagnostic packages fetched successfully' : 'No packages found',
                // 'hospital_id'            => $contextHospital?->id,
                // 'hospital_name'          => $contextHospital?->name,
                // 'diagnostic_id'          => $contextDiagnostic?->id,
                // 'diagnostic_image'       => $this->diagnosticLogoUrl($contextDiagnostic?->logo),
                // 'diagnostic_name'        => $contextDiagnostic?->name,
                'service_charge'         => $serviceCharge,
                'data'                   => $data,
                'total'                  => $packages->total(),
                'page'                   => $packages->currentPage(),
                'page_limit'             => $packages->perPage(),
                'count'                  => $data->count(),
                'disease_packages'       => $diseasePackages,
                'disease_packages_total' => $diseaseResult ? $diseaseResult['packages']->total() : 0,
                'disease_packages_count' => $diseasePackages->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching diagnostic packages', ['error' => $e->getMessage()]);

            return response()->json([
                'status'                 => 500,
                'message'                => 'Error fetching diagnostic packages',
                // 'hospital_id'            => null,
                // 'hospital_name'          => null,
                // 'diagnostic_id'          => null,
                // 'diagnostic_image'       => null,
                // 'diagnostic_name'        => null,
                'service_charge'         => 0,
                'data'                   => [],
                'total'                  => 0,
                'page'                   => 1,
                'page_limit'             => (int) env('PAGELIMIT', 10),
                'count'                  => 0,
                'disease_packages'       => [],
                'disease_packages_total' => 0,
                'disease_packages_count' => 0,
            ], 500);
        }
    }

    private function buildBookingHistoryFamilyMembers(HIPUser $user): array
    {
        $members = [];

        $primaryPerson = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first();

        $selfName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        $members[] = [
            'id'           => $user->id,
            'patient_id'   => $primaryPerson?->id,
            'name'         => $selfName !== '' ? $selfName : 'Self',
            'relationship' => 'Self',
            'label'        => 'Self',
        ];

        if (! $primaryPerson) {
            return $members;
        }

        $dependents = Persons::query()
            ->where('parent_id', $primaryPerson->id)
            ->where('id', '!=', $primaryPerson->id)
            ->orderBy('first_name')
            ->get();

        foreach ($dependents as $dependent) {
            $relationship = $dependent->relationship
                ?? ucfirst(strtolower((string) ($dependent->gender === 'Female' ? 'Mother' : 'Father')));

            $members[] = [
                'id'           => $dependent->id,
                'patient_id'   => $dependent->id,
                'name'         => trim(($dependent->first_name ?? '') . ' ' . ($dependent->last_name ?? '')),
                'relationship' => $relationship,
                'label'        => $relationship,
            ];
        }

        return $members;
    }

    private function applyBookingHistoryPatientFilter(Builder $query, HIPUser $user, ?string $patientId, ?string $relationship): Builder
    {
        if (! $patientId && ! $relationship) {
            return $query;
        }

        $primaryPersonId = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->value('id');

        $normalizedRelationship = $relationship ? ucfirst(strtolower($relationship)) : null;

        if ($normalizedRelationship === 'Self' || $patientId === $user->id || $patientId === $primaryPersonId) {
            return $query->where(function (Builder $q) use ($primaryPersonId) {
                $q->where('relationship', 'Self')
                    ->orWhere('patient_id', $primaryPersonId)
                    ->when($primaryPersonId === null, fn (Builder $inner) => $inner->orWhereNull('patient_id'));
            });
        }

        if ($patientId) {
            return $query->where('patient_id', $patientId);
        }

        return $query->where('relationship', $normalizedRelationship);
    }

    private function bookingHistoryBaseQuery(HIPUser $user, ?string $patientId = null, ?string $relationship = null): Builder
    {
        $query = DoctorBooking::query()->where('member_id', $user->id);

        return $this->applyBookingHistoryPatientFilter($query, $user, $patientId, $relationship);
    }

    private function getBookingHistorySummary(HIPUser $user, ?string $patientId = null, ?string $relationship = null): array
    {
        $base = $this->bookingHistoryBaseQuery($user, $patientId, $relationship);

        $upcoming = (clone $base)
            ->whereIn('status', ['confirmed'])
            ->whereDate('booking_date', '>=', now()->toDateString())
            ->count();

        $completed = (clone $base)->where('status', 'completed')->count();
        $cancelled = (clone $base)->where('status', 'cancelled')->count();

        return [
            'upcoming'  => $upcoming,
            'completed' => $completed,
            'cancelled' => $cancelled,
        ];
    }

    private function formatBookingHistoryItem(DoctorBooking $booking): array
    {
        $doctor = $booking->doctor;
        $branch = $booking->branch ?? $booking->hospital;
        $department = $booking->department;

        $timeSlots = is_array($booking->required_time_slots) ? $booking->required_time_slots : [];
        $appointmentTime = $timeSlots[0] ?? null;

        $locationParts = array_filter([
            $branch?->area,
            $branch?->name,
        ]);

        return [
            'id'                => $booking->id,
            // 'status'            => strtoupper((string) $booking->status),
            'doctor_id'         => $booking->doctor_id,
            'doctor_name'       => $doctor?->name,
            'doctor_image'      => $doctor?->doctor_image
                ? url('storage/doctor/' . $doctor->doctor_image)
                : null,
            'department_id'     => $booking->department_id,
            'department_name'   => $department?->name,
            'patient_name'      => $booking->name,
            'relationship'      => $booking->relationship,
            'appointment_date'  => $booking->booking_date
                ? Carbon::parse($booking->booking_date)->format('d M Y')
                : null,
            'appointment_time'  => $appointmentTime,
            // 'time_slots'        => $timeSlots,
            'branch_id'         => $booking->branch_id ?? $booking->hospital_id,
            'branch_name'       => $branch?->name,
            'location'          => implode(', ', $locationParts) ?: $branch?->address,
            // 'appointment_type'  => $booking->appointment_type ?? $booking->consultation_type,
            // 'reason_of_visit'   => $booking->reason_of_visit,
            // 'message'           => $booking->message,
            // 'booking_date'      => $booking->booking_date?->format('Y-m-d'),
        ];
    }

    /**
     * Doctor appointment booking history for home screen (tabs + family filter + search).
     */
    public function bookingHistory(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthenticated',
                'data'    => [],
            ], 401);
        }

        $request->merge([
            'patient_id' => $request->input('patient_id', $request->input('member_id')),
        ]);

        $request->validate([
            'type'         => 'required|string|in:upcoming,completed,cancelled',
            'patient_id'   => 'nullable|uuid',
            'relationship' => 'nullable|string|max:50',
            'search'       => 'nullable|string|max:255',
            'page'         => 'nullable|integer|min:1',
            'per_page'     => 'nullable|integer|min:1|max:50',
            'page_limit'   => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $page      = (int) ($request->page ?? 1);
            $pageLimit = (int) ($request->page_limit ?? $request->per_page ?? env('PAGELIMIT', 10));
            $patientId = $request->patient_id;
            $relationship = $request->relationship;

            $summary = $this->getBookingHistorySummary($user, $patientId, $relationship);

            $query = $this->bookingHistoryBaseQuery($user, $patientId, $relationship)
                ->with([
                    'doctor:id,name,doctor_image',
                    'branch:id,name,area,address',
                    'hospital:id,name,area,address',
                    'department:id,name',
                ]);

            if ($request->filled('search')) {
                $search = '%' . trim($request->search) . '%';
                $query->where(function (Builder $q) use ($search) {
                    $q->whereHas('doctor', fn (Builder $doctor) => $doctor->where('name', 'like', $search))
                        ->orWhereHas('department', fn (Builder $dept) => $dept->where('name', 'like', $search))
                        ->orWhere('name', 'like', $search);
                });
            }

            if ($request->type === 'upcoming') {
                $query->whereIn('status', ['confirmed'])
                    ->whereDate('booking_date', '>=', now()->toDateString())
                    ->orderBy('booking_date')
                    ->orderBy('id');
            } elseif ($request->type === 'completed') {
                $query->where('status', 'completed')
                    ->orderByDesc('booking_date')
                    ->orderByDesc('id');
            } else {
                $query->where('status', 'cancelled')
                    ->orderByDesc('booking_date')
                    ->orderByDesc('id');
            }

            $bookings = $query->paginate($pageLimit, ['*'], 'page', $page);

            $data = collect($bookings->items())
                ->map(fn (DoctorBooking $booking) => $this->formatBookingHistoryItem($booking))
                ->values();

            $messages = [
                'upcoming'  => 'Upcoming bookings fetched successfully',
                'completed' => 'Completed bookings fetched successfully',
                'cancelled' => 'Cancelled bookings fetched successfully',
            ];

            return response()->json([
                'status'         => 200,
                'message'        => $messages[$request->type] ?? 'Bookings fetched successfully',
                'summary'        => $summary,
                'family_members' => $this->buildBookingHistoryFamilyMembers($user),
                'data'           => $data,
                'total'          => $bookings->total(),
                'page'           => $bookings->currentPage(),
                'page_limit'     => $bookings->perPage(),
                'count'          => $data->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching booking history', ['error' => $e->getMessage()]);

            return response()->json([
                'status'     => 500,
                'message'    => 'Error fetching booking history',
                'data'       => [],
                'total'      => 0,
                'page'       => 1,
                'page_limit' => (int) env('PAGELIMIT', 10),
                'count'      => 0,
            ], 500);
        }
    }

    public function familyMembers(Request $request){

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthenticated',
                'data'    => [],
            ], 401);
        }

        $primaryPerson = Persons::query()
        ->where('hip_user_id', $user->id)
        ->where('is_primary', true)
        ->first();

        $selfName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $selfImage = $user->profile_image
            ? $this->personProfileImageUrl($user->profile_image)
            : ($primaryPerson?->image ? $this->personProfileImageUrl($primaryPerson->image) : null);

        $members[] = [
            // 'id'           => $user->id,
            'patient_id'   => $primaryPerson?->id ?? $user->id,
            'name'         => $selfName !== '' ? $selfName : 'Self',
            // 'relationship' => 'Self',
            'label'        => 'Self',
            'image'        => $selfImage,
            'age'          => $this->ageFromDob($primaryPerson?->dob ?? $user->dob ?? null),
        ];

        if (! $primaryPerson) {
            return $members;
        }

        $dependents = Persons::query()
            ->where('parent_id', $primaryPerson->id)
            ->where('id', '!=', $primaryPerson->id)
            ->orderBy('first_name')
            ->get();

        foreach ($dependents as $dependent) {
            $relationship = $dependent->relationship
                ?: ($dependent->gender === 'Female' ? 'Mother' : 'Father');

            $members[] = [
                // 'id'           => $dependent->id,
                'patient_id'   => $dependent->id,
                // 'name'         => trim(($dependent->first_name ?? '') . ' ' . ($dependent->last_name ?? '')),
                // 'relationship' => $relationship,
                'label'        => $relationship,
                'image'        => $this->personProfileImageUrl($dependent->image),
                'age'          => $this->ageFromDob($dependent->dob),
            ];
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Family members fetched successfully',
            'data'    => $members,
        ], 200);
    }

    public function homepageData(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'per_page'    => 'nullable|integer|min:1|max:50',
            'page'        => 'nullable|integer|min:1',
        ]);

        try {
            $user = $request->user();

            $hospitalId = $this->resolveHomepageHospitalId($request, $user);
            if ($hospitalId <= 0) {
                return $this->branchRequiredResponse();
            }

            $branchesResult = $this->fetchHospitalBranchesData();
            $branches       = $branchesResult['data'];

            $perPage = (int) ($request->per_page ?? 10);
            $page    = (int) ($request->page ?? 1);

            $userCoins    = $this->fetchUserCoinsData($user);
            $specialities = $this->fetchDoctorSpecialitiesData($hospitalId);
            $diseases     = $this->fetchDiseasesData($hospitalId);
            $doctorList   = $this->fetchDoctorListData($hospitalId, $perPage, $page);

            return response()->json([
                'status'  => 200,
                'message' => 'Homepage data fetched successfully',
                'data'    => [
                    'hospital_id'         => $hospitalId,
                    'preferred_branch_id' => $user->preferred_branch_id,
                    'user_coins'          => $userCoins ?? 0,
                    'hospital_branches'   => $branches,
                    'doctor_specialities' => $specialities['data'],
                    'diseases'            => $diseases['data'],
                    'doctor_list'         => $doctorList['data'],
                ],
                'hospital_branches_count'   => $branchesResult['count'],
                'doctor_specialities_count' => $specialities['count'],
                'diseases_count'            => $diseases['count'],
                'doctor_list_count'         => $doctorList['count'],
                'current_page'              => $doctorList['current_page'],
                'last_page'                 => $doctorList['last_page'],
                'per_page'                  => $doctorList['per_page'],
                'total'                     => $doctorList['total'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching homepage data', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => app()->environment('local')
                    ? ('Error fetching homepage data: ' . $e->getMessage())
                    : 'Error fetching homepage data',
                'data'    => [],
            ], 500);
        }
    }

    private function fetchUserCoinsData($user): ?array
    {
        $person = Persons::where('hip_user_id', $user->id)->first();

        if (!$person) {
            return null;
        }

        $coins = Coins::where('person_id', $person->parent_id ?? $person->id)->first();

        return [
            'coins' => $coins->coins ?? 0,
        ];
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, count: int}
     */
    private function fetchHospitalBranchesData(): array
    {
        $areas = LocationMaster::query()
            ->select('id', 'area', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $hospitals = Hospital::query()
            ->where('status', 'active')
            ->select('id', 'name', 'logo', 'location_id', 'admin_latitude', 'admin_longitude', 'address', 'admin_contact', 'is_24_hours_available')
            ->with('location:id,area,latitude,longitude')
            ->orderBy('name')
            ->get();

        $data = $hospitals->map(function (Hospital $hospital) use ($areas) {
            $coordinates = $this->resolveHospitalCoordinates($hospital);

            if (!$coordinates) {
                return null;
            }

            [$latitude, $longitude] = $coordinates;

            $areaName = $hospital->location?->area
                ?? $this->findNearestArea($areas, $latitude, $longitude)?->area;

            return [
                'hospital_id' => $hospital->id,
                'branch_name' => $areaName ? "{$areaName} Branch" : null,
                'address'     => $hospital->address ?? $areaName,
                'contact'     => $hospital->admin_contact,
                'logo'        => $hospital->logo
                    ? url('storage/hospital/' . $hospital->logo)
                    : null,
                'is_24_hours_available' => $hospital->is_24_hours_available,
            ];
        })
            ->filter()
            ->sortBy('branch_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return [
            'data'  => $data,
            'count' => $data->count(),
        ];
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, count: int}
     */
    private function fetchDoctorSpecialitiesData(?int $hospitalId = null, ?string $search = null): array
    {
        $countsByMasterId = [];

        $doctorQuery = Doctor::query()
            ->select(['id', 'speciality', 'assigned_speciality'])
            ->where('status', 'active');

        if ($hospitalId) {
            $this->scopeDoctorsForHospital($doctorQuery, $hospitalId);
        }

        $doctorQuery->chunk(200, function ($doctors) use (&$countsByMasterId) {
            foreach ($doctors as $doctor) {
                $masterIds = array_unique(array_merge(
                    array_map('intval', (array) ($doctor->speciality ?? [])),
                    array_map('intval', (array) ($doctor->assigned_speciality ?? []))
                ));

                foreach ($masterIds as $masterId) {
                    if ($masterId > 0) {
                        $countsByMasterId[$masterId] = ($countsByMasterId[$masterId] ?? 0) + 1;
                    }
                }
            }
        });

        $mastersQuery = SpecialitiesMaster::query()
            ->where('status', 'active')
            ->when($search !== null && $search !== '', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name');

        if ($hospitalId) {
            if ($countsByMasterId === []) {
                return ['data' => collect(), 'count' => 0];
            }

            $mastersQuery->whereIn('id', array_keys($countsByMasterId));
        }

        $masters = $mastersQuery->get();

        $data = $masters->map(function (SpecialitiesMaster $master) use ($countsByMasterId, $hospitalId) {
            $count = $countsByMasterId[$master->id] ?? 0;

            return [
                'id'                => $master->id,
                'speciality_name'   => $master->name,
                'icon'              => $master->display_image
                    ? url('storage/speciality/' . basename($master->display_image))
                    : null,
                'specialists_count' => $count,
                'speciality_description' => $master->description,
            ];
        });

        if ($hospitalId) {
            $data = $data->filter(fn (array $row) => $row['specialists_count'] > 0)->values();
        } else {
            $data = $data->values();
        }

        return [
            'data'  => $data,
            'count' => $data->count(),
        ];
    }

    /**
     * Diseases assigned on doctors (optionally scoped to a branch). Search matches name and symptoms.
     *
     * @return array{data: \Illuminate\Support\Collection, count: int}
     */
    private function fetchDiseasesData(?int $hospitalId = null, ?string $search = null): array
    {
        // Step 1 — collect all department IDs assigned to active doctors
        $departmentIdCounts = [];

        $doctorQuery = Doctor::query()
            ->select(['id', 'assigned_diseases'])
            ->where('status', 'active');

        if ($hospitalId) {
            $this->scopeDoctorsForHospital($doctorQuery, $hospitalId);
        }

        $doctorQuery->chunk(200, function ($doctors) use (&$departmentIdCounts) {
            foreach ($doctors as $doctor) {
                $deptIds = array_unique(
                    array_map('intval', (array) ($doctor->assigned_diseases ?? []))
                );

                foreach ($deptIds as $deptId) {
                    if ($deptId > 0) {
                        $departmentIdCounts[$deptId] = ($departmentIdCounts[$deptId] ?? 0) + 1;
                    }
                }
            }
        });

        if (empty($departmentIdCounts)) {
            return ['data' => collect(), 'count' => 0];
        }

        // Step 2 — fetch active departments that were found (no search filter here anymore)
        $departments = DiseaseDepartment::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys($departmentIdCounts))
            ->select(['id', 'department_name', 'diseases', 'department_image'])
            ->orderBy('department_name')
            ->get();

        if ($departments->isEmpty()) {
            return ['data' => collect(), 'count' => 0];
        }

        // Step 3 — collect all disease IDs from those departments
        $allDiseaseIds = $departments
            ->flatMap(function (DiseaseDepartment $dept) {
                $raw = is_array($dept->diseases)
                    ? $dept->diseases
                    : (json_decode($dept->diseases ?? '[]', true) ?? []);

                return array_map('intval', $raw);
            })
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        // Step 4 — fetch diseases, applying search filter on disease name and symptoms
        $diseaseQuery = Disease::query()
            ->where('is_active', true)
            ->whereIn('id', $allDiseaseIds);

        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $diseaseQuery->where('name', 'like', $term);
        }

        $diseaseMap = $diseaseQuery
            ->orderBy('name')
            ->get(['id', 'name', 'symptoms'])
            ->keyBy('id');

        // If search given and no diseases matched at all, return empty
        if ($search !== null && $search !== '' && $diseaseMap->isEmpty()) {
            return ['data' => collect(), 'count' => 0];
        }

        // Step 5 — build response: one row per department, with only matching diseases
        $data = $departments->map(function (DiseaseDepartment $dept) use ($diseaseMap, $departmentIdCounts) {
            $raw = is_array($dept->diseases)
                ? $dept->diseases
                : (json_decode($dept->diseases ?? '[]', true) ?? []);

            $departmentImage = $dept->department_image
                ? url('storage/disease-departments/' . basename($dept->department_image))
                : null;

            $diseases = collect($raw)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->map(fn ($id) => $diseaseMap->get($id))
                ->filter() // only diseases that passed the search filter (or all if no search)
                ->map(fn (Disease $disease) => [
                    'id'               => $disease->id,
                    'name'             => $disease->name,
                    'symptoms'         => $disease->symptoms ?? [],
                    'department_image' => $departmentImage,
                ])
                ->values()
                ->all();

            $doctorCount = $departmentIdCounts[$dept->id] ?? 0;

            return [
                'department_id'   => $dept->id,
                'department_name' => $dept->department_name,
                'department_image'=> $departmentImage,
                'diseases'        => $diseases,
                'doctors_count'   => $doctorCount,
                'doctors_label'   => $doctorCount === 1 ? '1 Doctor' : "{$doctorCount} Doctors",
            ];
        })
        ->filter(fn ($row) => !empty($row['diseases'])) // drop departments with no matching diseases
        ->values();

        return [
            'data'  => $data,
            'count' => $data->count(),
        ];
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, count: int, current_page: int, last_page: int, per_page: int, total: int}
     */
    private function fetchDoctorListData(int $hospitalId, int $perPage = 10, int $page = 1): array
    {
        $doctors = $this->scopeDoctorsForHospital(
            Doctor::query()->select('id', 'name', 'doctor_image', 'qualifications', 'speciality', 'working_since'),
            $hospitalId
        )
            ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                $q->where('status', 'active');
            }], 'rating')
            ->withCount(['doctorReviews as reviews_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->with(['assignments' => function ($q) use ($hospitalId) {
                $q->where('status', 'active')
                    ->where('hospital_id', $hospitalId)
                    ->whereNotNull('time_slots')
                    ->select('id', 'doctor_id', 'hospital_id', 'time_slots', 'procedure_ids', 'day', 'date', 'status');
            }])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        if ($doctors->isEmpty()) {
            return [
                'data'         => collect(),
                'count'        => 0,
                'current_page' => $doctors->currentPage(),
                'last_page'    => $doctors->lastPage(),
                'per_page'     => $doctors->perPage(),
                'total'        => $doctors->total(),
            ];
        }

        $procedureIds = $doctors->getCollection()
            ->flatMap(fn ($doctor) => $doctor->assignments->flatMap(
                fn ($assignment) => (array) ($assignment->procedure_ids ?? [])
            ))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $procedureMap = Procedure::query()
            ->where('status', 'active')
            ->whereIn('id', $procedureIds)
            ->pluck('procedure_name', 'id');

        return [
            'data'         => $doctors->getCollection()
                ->map(fn ($doctor) => $this->formatDoctorCard($doctor, $hospitalId, $procedureMap))
                ->values(),
            'count'        => $doctors->count(),
            'current_page' => $doctors->currentPage(),
            'last_page'    => $doctors->lastPage(),
            'per_page'     => $doctors->perPage(),
            'total'        => $doctors->total(),
        ];
    }

    public function doctorTimeSlots(Request $request){

        $request->validate([
            'doctor_id' => 'required|uuid|exists:doctors,id',
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
        ]);

        try {
            $doctor = Doctor::query()->find($request->doctor_id);

            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;

            if (!$doctor) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Doctor not found',
                    'data'    => [],
                ], 404);
            }

            $assignments = $doctor->assignments()
                ->where('status', 'active')
                ->whereNotNull('time_slots')
                ->when($hospitalId, fn ($q) => $q->where('hospital_id', $hospitalId))
                ->select('id', 'doctor_id', 'hospital_id', 'time_slots', 'day', 'date', 'status')
                ->get();

            $timeSlots = $this->buildNextAvailableSlotsByDay($assignments, $hospitalId, 14, 14);

            $serviceCharges = (float) config('services.service_charges_percent');
            $totalAmount = $serviceCharges + $doctor->consultation_fee;

            return response()->json([
                'status'  => 200,
                'message' => 'Doctor time slots fetched successfully',
                'consultation_fee' => $doctor->consultation_fee,
                'service_charges' => $serviceCharges,
                'total_amount' => $totalAmount,
                'data'    => $timeSlots,
                'count'   => count($timeSlots),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error fetching doctor time slots', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong',
                'data'    => [],
            ], 500);
        }
    }


    /**
     * @return array<string, mixed>
     */
    private function buildCoinsPageSummary($user, Persons $person): array
    {
        $walletPersonId = (string) ($person->parent_id ?? $person->id);
        $coinsWallet = Coins::query()->where('person_id', $walletPersonId)->latest('id')->first();
        $remainingCoins = (int) ($coinsWallet?->coins ?? 0);

        $amountForOneCoin = (float) app_setting(
            'amount_for_one_coin',
            config('settings.payment.amount_for_one_coin', 1)
        );

        $personIds = Persons::query()
            ->where('hip_user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $invoiceQuery = Invoice::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($personIds) {
                $q->whereIn('primary_person_id', $personIds)
                    ->orWhereIn('person_id', $personIds);
            });

        $totalEarned = (int) (clone $invoiceQuery)->sum('coins_earned');
        $totalRedeemed = (int) (clone $invoiceQuery)->sum('coins_applied');

        $expiresAt = $coinsWallet?->expires_at ? Carbon::parse($coinsWallet->expires_at) : null;
        $expiringCoins = ($remainingCoins > 0 && $expiresAt && $expiresAt->isFuture())
            ? $remainingCoins
            : 0;

        return [
            'total_remaining_coins' => $remainingCoins,
            'amount_for_one_coin'     => $amountForOneCoin,
            'coins_value'             => round($remainingCoins * $amountForOneCoin, 2),
            'total_earned'            => $totalEarned,
            'total_redeemed'          => $totalRedeemed,
            'expiring_coins'          => $expiringCoins,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCoinsPageOverview(Persons $person): array
    {
        $walletPersonId = (string) ($person->parent_id ?? $person->id);
        $coinsWallet = Coins::query()->where('person_id', $walletPersonId)->latest('id')->first();

        $remainingCoins = (int) ($coinsWallet?->coins ?? 0);
        $expiresAt = $coinsWallet?->expires_at ? Carbon::parse($coinsWallet->expires_at) : null;

        $expiringCoins = 0;
        if ($remainingCoins > 0 && $expiresAt && $expiresAt->isFuture()) {
            $expiringCoins = $remainingCoins;
        }

        return [
            // 'expiring_coins' => $expiringCoins,
            // 'expires_at'     => $expiresAt?->format('Y-m-d'),
            // 'date'           => $expiresAt?->format('d M Y'),
            // 'day'            => $expiresAt?->format('l'),
            'label'          => ($expiringCoins > 0 && $expiresAt)
                ? sprintf(
                    '%d coins expiring on %s',
                    $expiringCoins,
                    $expiresAt->format('d F Y')
                )
                : null,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, pagination: array<string, int>}
     */
    private function buildCoinsPageHistory($user, int $perPage, int $page): array
    {
        $personIds = Persons::query()
            ->where('hip_user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $invoices = Invoice::query()
            ->where('status', 'completed')
            ->where(function ($q) use ($personIds) {
                $q->whereIn('primary_person_id', $personIds)
                    ->orWhereIn('person_id', $personIds);
            })
            ->where(function ($q) {
                $q->where('coins_earned', '>', 0)
                    ->orWhere('coins_applied', '>', 0);
            })
            ->orderByDesc('updated_at')
            ->get();

        $entries = [];

        foreach ($invoices as $invoice) {
            $occurredAt = $invoice->updated_at ?? $invoice->created_at;
            $titleBase = $this->resolveInvoiceCoinsTitle($invoice);

            $coinsEarned = (int) ($invoice->coins_earned ?? 0);
            if ($coinsEarned > 0) {
                $entries[] = [
                    'invoice_id' => (int) $invoice->id,
                    'title'      => $titleBase,
                    'coins'      => $coinsEarned,
                    'type'       => 'credited',
                    'status'     => 'CREDITED',
                    'occurred_at'=> $occurredAt,
                ];
            }

            $coinsApplied = (int) ($invoice->coins_applied ?? 0);
            if ($coinsApplied > 0) {
                $entries[] = [
                    'invoice_id' => (int) $invoice->id,
                    'title'      => str_ends_with(strtolower($titleBase), 'discount')
                        ? $titleBase
                        : $titleBase . ' Discount',
                    'coins'      => $coinsApplied,
                    'type'       => 'redeemed',
                    'status'     => 'REDEEMED',
                    'occurred_at'=> $occurredAt,
                ];
            }
        }

        usort($entries, function (array $a, array $b) {
            return ($b['occurred_at']?->timestamp ?? 0) <=> ($a['occurred_at']?->timestamp ?? 0);
        });

        $total = count($entries);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;

        $items = array_slice($entries, $offset, $perPage);
        $items = array_map(function (array $entry) {
            $date = $entry['occurred_at'] instanceof Carbon
                ? $entry['occurred_at']
                : Carbon::parse($entry['occurred_at']);

            return [
                // 'invoice_id' => $entry['invoice_id'],
                'title'      => $entry['title'],
                // 'coins'      => (int) $entry['coins'],
                'coins_label'=> ($entry['type'] === 'redeemed' ? '-' : '+') . $entry['coins'],
                // 'type'       => $entry['type'],
                'status'     => $entry['status'],
                'date'       => $date->format('d M Y'),
                // 'day'        => $date->format('l'),
            ];
        }, $items);

        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'count'        => count($items),
                'total'        => $total,
                'last_page'    => $lastPage,
            ],
        ];
    }

    private function resolveInvoiceCoinsTitle(Invoice $invoice): string
    {
        $types = is_array($invoice->service_types) ? $invoice->service_types : [];
        $firstType = strtolower((string) ($types[0] ?? ''));

        $details = is_array($invoice->invoice_details) ? $invoice->invoice_details : [];
        $firstDetail = is_array($details[0] ?? null) ? $details[0] : [];
        $detailService = strtolower((string) ($firstDetail['service'] ?? ''));

        $key = $firstType ?: str_replace(' ', '_', $detailService);

        return match (true) {
            str_contains($key, 'doctor') || str_contains($key, 'consultation') || str_contains($key, 'appointment') => 'Appointment Booking',
            str_contains($key, 'lab') || str_contains($key, 'diagnostic') => 'Lab Test',
            str_contains($key, 'pharmacy') => 'Pharmacy',
            str_contains($key, 'package') || str_contains($key, 'health') => 'Health Package',
            str_contains($key, 'procedure') => 'Procedure',
            default => $detailService !== ''
                ? ucwords($detailService)
                : ($firstType !== '' ? ucwords(str_replace('_', ' ', $firstType)) : 'Hospital Service'),
        };
    }

    public function coinsPage(Request $request)
    {
        $request->validate([
            'type'     => 'nullable|string|in:overview,history',
            'per_page' => 'nullable|integer|min:1|max:50',
            'page'     => 'nullable|integer|min:1',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthenticated',
                'data'    => [],
            ], 401);
        }

        $person = Persons::query()->where('hip_user_id', $user->id)->first();

        if (! $person) {
            return response()->json([
                'status'  => 404,
                'message' => 'Person not found',
                'data'    => [],
            ], 404);
        }

        try {
            $type = strtolower((string) $request->input('type', 'overview'));
            $summary = $this->buildCoinsPageSummary($user, $person);

            $data = array_merge($summary, [
                'type' => $type,
            ]);

            if ($type === 'history') {
                $history = $this->buildCoinsPageHistory(
                    $user,
                    (int) ($request->per_page ?? 10),
                    (int) ($request->page ?? 1)
                );
                $data['history'] = $history['items'];
                $data['pagination'] = $history['pagination'];
            } else {
                $data['overview'] = $this->buildCoinsPageOverview($person);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Coins page data fetched successfully',
                'data'    => $data,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching coins page data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching coins page data',
                'data'    => [],
            ], 500);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildProfileFamilyMembers(HIPUser $user, ?Persons $primaryPerson): array
    {
        $selfName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $selfImage = $user->profile_image
            ? url('storage/users/' . $user->profile_image)
            : ($primaryPerson?->image ? $this->personProfileImageUrl($primaryPerson->image) : null);

        // $members = [[
        //     'patient_id' => $primaryPerson?->id ?? $user->id,
        //     'name'       => $selfName !== '' ? $selfName : 'Self',
        //     'label'      => 'Self',
        //     'image'      => $selfImage,
        //     // 'age'        => $this->ageFromDob($primaryPerson?->dob ?? $user->dob ?? null),
        // ]];

        // if (! $primaryPerson) {
        //     return $members;
        // }

        $dependentData = [];

        if (! $primaryPerson) {
            return $dependentData;
        }

        $dependents = Persons::query()
            ->where('parent_id', $primaryPerson->id)
            ->where('id', '!=', $primaryPerson->id)
            ->orderBy('first_name')
            ->get();

        foreach ($dependents as $dependent) {
            $relationship = $dependent->relationship
                ?: ($dependent->gender === 'Female' ? 'Mother' : 'Father');

            $dependentData[] = [
                'patient_id' => $dependent->id,
                'label'      => $relationship,
                'image'      => $this->personProfileImageUrl($dependent->image),
            ];
        }

        return $dependentData;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildUserProfileBranchContact(HIPUser $user): ?array
    {
        $branchId = (int) ($user->preferred_branch_id ?? 0);

        if ($branchId <= 0) {
            return null;
        }

        $hospital = Hospital::query()
            ->where('status', 'active')
            ->whereKey($branchId)
            ->select('id', 'name', 'admin_contact', 'admin_latitude', 'admin_longitude', 'location_id','address')
            ->with('location:id,area,latitude,longitude')
            ->first();

        if (! $hospital) {
            return null;
        }

        $coordinates = $this->resolveHospitalCoordinates($hospital);

        if (! $coordinates) {
            return null;
        }

        [$latitude, $longitude] = $coordinates;

        $areas = LocationMaster::query()
            ->select('id', 'area', 'latitude', 'longitude')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $areaName = $hospital->location?->area
            ?? $this->findNearestArea($areas, $latitude, $longitude)?->area;

        $branchName = $areaName ? "{$areaName} Branch" : $hospital->name;

        return [
            'branch_id'       => (int) $hospital->id,
            'branch_name'     => $branchName,
            'hospital_name'   => $hospital->name,
            'address'         => $hospital->address ?? null,
            'contact_number'  => $hospital->admin_contact,
            'google_map_link' => sprintf(
                'https://www.google.com/maps/search/?api=1&query=%s,%s',
                $latitude,
                $longitude
            ),
        ];
    }

    public function userProfile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status'  => 401,
                'message' => 'Unauthenticated',
                'data'    => [],
            ], 401);
        }

        try {
            $primaryPerson = Persons::query()
                ->where('hip_user_id', $user->id)
                ->where('is_primary', true)
                ->first();

            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            if ($fullName === '' && $primaryPerson) {
                $fullName = trim(($primaryPerson->first_name ?? '') . ' ' . ($primaryPerson->last_name ?? ''));
            }

            $dob = $user->dob ?? $primaryPerson?->dob;
            $bookingBase = DoctorBooking::query()->where('member_id', $user->id);

            $upcomingCount = (clone $bookingBase)
                ->where('status', 'confirmed')
                ->where('relationship', 'Self')
                ->whereDate('booking_date', '>=', now()->toDateString())
                ->count();

            $pastVisitsCount = (clone $bookingBase)
                ->where('status', 'completed')
                ->where('relationship', 'Self')
                ->count();

            $followUpCount = (clone $bookingBase)
                ->where(function ($q) {
                    $q->whereRaw('LOWER(appointment_type) LIKE ?', ['%follow%'])
                        ->orWhereRaw('LOWER(consultation_type) LIKE ?', ['%follow%']);
                })
                ->where('relationship', 'Self')
                ->count();

            return response()->json([
                'status'  => 200,
                'message' => 'User profile fetched successfully',
                'data'    => [
                    'profile' => [
                        'name'          => $fullName,
                        'mobile_number' => $user->mobile_num,
                        'email'         => $user->email ?? $primaryPerson?->email,
                        'dob'           => $dob ? Carbon::parse($dob)->format('d/m/Y') : null,
                        'hip_id'        => $user->hip_id,
                        'profile_image' => $user->profile_image
                            ? url('storage/users/' . $user->profile_image)
                            : ($primaryPerson?->image ? $this->personProfileImageUrl($primaryPerson->image) : null),
                        'emergency_contact' => [
                            // 'name'         => $user->emergency_contact_person_name,
                            'phone'        => $user->emergency_contact_person_phone,
                            // 'relationship' => $user->emergency_contact_person_relationship,
                        ],
                    ],
                    'hospital_activity' => [
                        'upcoming'    => $upcomingCount,
                        'past_visits' => $pastVisitsCount,
                        'follow_ups'  => $followUpCount,
                    ],
                    'health_info' => [
                        'blood_group' => $user->blood_group,
                    ],
                    'family_members' => $this->buildProfileFamilyMembers($user, $primaryPerson),
                    'branch_contact' => $this->buildUserProfileBranchContact($user),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching user profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching user profile',
                'data'    => [],
            ], 500);
        }
    }

    public function diseases(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'search'      => 'nullable|string|max:255',
        ]);

        try {
            $search = $request->filled('search') ? trim((string) $request->search) : null;
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;

            $result = $this->fetchDiseasesData($hospitalId, $search);

            return response()->json([
                'status'  => 200,
                'message' => $result['count'] > 0
                    ? 'Diseases fetched successfully'
                    : 'No diseases found',
                'data'    => $result['data'],
                'count'   => $result['count'],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching diseases', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching diseases',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

    private function formatDoctorCards($doctor, ?int $hospitalId = null, $procedureMap = null): array
    {
        $assignments = $doctor->relationLoaded('assignments')
            ? $doctor->assignments
            : collect();

        if ($hospitalId) {
            $assignments = $assignments->where('hospital_id', $hospitalId);
        }

        $assignments = $assignments->where('status', 'active')->values();
        $nextSlot = $this->resolveNextSlotFromAssignments($assignments);
        $procedureNames = $procedureMap
            ? $this->resolveProcedureNames($assignments, $procedureMap)
            : [];

        return [
            'id'                  => $doctor->id,
            'name'                => $doctor->name,
            'doctor_image'        => $doctor->doctor_image
                ? url('storage/doctor/' . $doctor->doctor_image)
                : null,
            'qualification_names' => $doctor->qualification_names,
            'speciality_names'    => $doctor->speciality_names,
            'experience'          => $this->formatDoctorExperience($doctor->working_since),
            'rating'              => (string) round((float) $doctor->rating_avg, 1),
            'review_count'        => (int) ($doctor->reviews_count ?? 0),
            'consultation_fee'    => (float) ($doctor->consultation_fee ?? 0),
            'available_today'     => $nextSlot !== null && ($nextSlot['date'] ?? null) === today()->toDateString(),
            // 'procedure_names'     => $procedureNames,
            // 'next_slot'           => $nextSlot,
            'next_slot_label'     => $nextSlot['label'] ?? null,
        ];
    }

    public function diseaseDetails(Request $request)
    {
        $request->validate([
            'disease_id'  => 'required|integer|exists:diseases,id',
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'sort'        => 'nullable|string|in:name_asc,name_desc,rating_high,rating_low,fee_low,fee_high,experience_high,experience_low',
        ]);

        try {
            $diseaseId  = (int) $request->disease_id;
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;
            $sort       = $request->filled('sort') ? (string) $request->sort : 'name_asc';

            // Fetch the disease
            $disease = Disease::query()
                ->where('is_active', true)
                ->find($diseaseId);

            if (! $disease) {
                return response()->json([
                    'status'  => 404,
                    'message' => 'Disease not found',
                    'data'    => [],
                ], 404);
            }

            // Find the department that contains this disease_id in its JSON diseases column
            $department = DiseaseDepartment::query()
                ->where('is_active', true)
                ->whereRaw('JSON_VALID(diseases) = 1')
                ->whereRaw(
                    '(JSON_CONTAINS(diseases, ?) OR JSON_CONTAINS(diseases, ?))',
                    [json_encode($diseaseId), json_encode((string) $diseaseId)]
                )
                ->select('id', 'department_name', 'diseases')
                ->first();

            // Build doctor query scoped to the department
            $doctorQuery = Doctor::query()
                ->select(
                    'id',
                    'name',
                    'doctor_image',
                    'qualifications',
                    'speciality',
                    'consultation_fee',
                    'working_since'
                )
                ->where('status', 'active');

            if ($department) {
                $this->scopeDoctorsForDepartment($doctorQuery, $department->id);
            } else {
                $doctorQuery->whereRaw('1 = 0');
            }

            if ($hospitalId) {
                $this->scopeDoctorsForHospital($doctorQuery, $hospitalId);
            }

            $doctorQuery
                ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                    $q->where('status', 'active');
                }], 'rating')
                ->withCount(['doctorReviews as reviews_count' => function ($q) {
                    $q->where('status', 'active');
                }])
                ->with(['assignments' => function ($q) use ($hospitalId) {
                    $q->where('status', 'active')
                        ->whereNotNull('time_slots')
                        ->when($hospitalId, fn ($inner) => $inner->where('hospital_id', $hospitalId))
                        ->select('id', 'doctor_id', 'hospital_id', 'time_slots', 'procedure_ids', 'day', 'date', 'status');
                }]);

            // Apply sort
            match ($sort) {
                'name_desc'        => $doctorQuery->orderBy('name', 'desc'),
                'rating_high'      => $doctorQuery->orderByRaw('COALESCE(rating_avg, 0) DESC'),
                'rating_low'       => $doctorQuery->orderByRaw('COALESCE(rating_avg, 0) ASC'),
                'fee_low'          => $doctorQuery->orderByRaw('COALESCE(consultation_fee, 0) ASC'),
                'fee_high'         => $doctorQuery->orderByRaw('COALESCE(consultation_fee, 0) DESC'),
                'experience_high'  => $doctorQuery->orderByRaw('COALESCE(working_since, YEAR(NOW())) ASC'),  // earlier year = more experience
                'experience_low'   => $doctorQuery->orderByRaw('COALESCE(working_since, YEAR(NOW())) DESC'),
                default            => $doctorQuery->orderBy('name', 'asc'),  // name_asc
            };

            $doctors = $doctorQuery->get();

            $doctorCards = $doctors
                ->map(fn ($doctor) => $this->formatDoctorCards($doctor, $hospitalId))
                ->values();

            // Expand all disease names inside the department
            $departmentDiseases = [];
            if ($department) {
                $rawIds = is_array($department->diseases)
                    ? $department->diseases
                    : (json_decode($department->diseases ?? '[]', true) ?? []);

                $departmentDiseases = Disease::query()
                    ->where('is_active', true)
                    ->whereIn('id', array_map('intval', $rawIds))
                    ->orderBy('name')
                    ->get(['id', 'name', 'symptoms'])
                    ->map(fn (Disease $d) => [
                        'id'       => $d->id,
                        'name'     => $d->name,
                        'symptoms' => $d->symptoms ?? [],
                    ])
                    ->values()
                    ->all();
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Disease details fetched successfully',
                'data'    => [
                    // 'id'                  => $disease->id,
                    // 'disease_name'        => $disease->name,
                    // 'about'               => $disease->about,
                    // 'symptoms'            => $disease->symptoms ?? [],
                    // 'recommended_tests'   => $disease->recommended_tests ?? [],
                    // 'department_id'       => $department?->id,
                    // 'department_name'     => $department?->department_name,
                    // 'department_diseases' => $departmentDiseases,
                    'doctors'             => $doctorCards,
                    // 'doctors_count'       => $doctorCards->count(),
                ],
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error fetching disease details', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching disease details',
                'data'    => [],
            ], 500);
        }
    }

/**
 * Scope doctors whose assigned_diseases JSON column contains the given department ID.
 * Replaces scopeDoctorsForDisease — assigned_diseases stores department IDs, not disease IDs.
 */
    private function scopeDoctorsForDepartment($query, int $departmentId)
    {
        $deptAsNumber = json_encode($departmentId);
        $deptAsString = json_encode((string) $departmentId);

        return $query->where(function ($q) use ($deptAsNumber, $deptAsString) {
            $q->whereNotNull('assigned_diseases')
                ->whereRaw('JSON_VALID(assigned_diseases) = 1')
                ->whereRaw(
                    '(JSON_CONTAINS(assigned_diseases, ?) OR JSON_CONTAINS(assigned_diseases, ?))',
                    [$deptAsNumber, $deptAsString]
                );
        });
    }

    public function secondOpinion(Request $request)
    {
        // Log::info('secondOpinion API hit', [
        //     'path'              => $request->path(),
        //     'method'            => $request->method(),
        //     'user_id'           => $request->user()?->id,
        //     'has_report_1'      => $request->hasFile('report_1'),
        //     'has_report_2'      => $request->hasFile('report_2'),
        //     'preferred_time_slots_raw' => $request->input('preferred_time_slots'),
        //     'ip'                => $request->ip(),
        // ]);

        try {
            $request->merge([
                'patient_id'           => $request->input('patient_id', $request->input('member_id')),
                'branch_id'            => $request->input('branch_id', $request->input('hospital_id')),
                'preferred_time_slots' => $this->normalizeTimeSlotsInput($request->input('preferred_time_slots')),
            ]);

            $request->validate([
                'patient_id'             => 'required|uuid',
                'doctor_id'              => 'required|uuid|exists:doctors,id',
                'branch_id'              => 'required|integer|exists:hospitals,id',
                'speciality_id'          => 'nullable|integer|exists:specialities_masters,id',
                'department_id'          => 'nullable|integer|exists:specialities_masters,id',
                'diagnosis'              => 'nullable|string|max:500',
                'treatment'              => 'nullable|string|max:500',
                'question_for_doctor'    => 'nullable|string|max:2000',
                'mode_of_consultation'   => 'nullable|string|max:50',
                'preferred_date'         => 'required|date|after_or_equal:today',
                'preferred_time_slots'   => 'required|array|min:1',
                'preferred_time_slots.*' => 'string|max:50',
                'is_coins_applied'       => 'nullable|boolean',
                'is_online_payment'      => 'nullable|boolean',
                'coins_used'             => 'nullable|integer|min:0',
                'consultation_fee'       => 'nullable|numeric|min:0',
                'report_1'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'report_2'               => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'report_1_name'          => 'nullable|string|max:255',
                'report_2_name'          => 'nullable|string|max:255',
            ]);

            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Unauthenticated. Please login and send Authorization: Bearer {token}.',
                    'data'    => [],
                ], 401);
            }

            $result = $this->bookingApiService->secondOpinion($request, $authUser->id);

            $booking = $result['booking'] ?? null;

            if (! $booking instanceof SecondOpinion) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Second opinion request not created',
                    'data'    => [],
                ], 400);
            }

            $documents = $booking->documentRecords()->map(fn ($doc) => [
                'id'              => $doc->id,
                'document_name'   => $doc->document_name,
                'document_path'   => $doc->document_path,
                'document_url'    => $doc->document_url,
                'document_type'   => $doc->document_type,
                'document_size'   => $doc->document_size,
            ])->values();

            $data = [
                'second_opinion_id'     => $booking->id,
                'member_id'             => $booking->member_id,
                'patient_id'            => $booking->patient_id,
                'patient_name'          => $booking->patient_name,
                'branch_id'             => $booking->branch_id,
                'speciality_id'         => $booking->speciality_id,
                'doctor_id'             => $booking->doctor_id,
                'diagnosis'             => $booking->diagnosis,
                'treatment'             => $booking->treatment,
                'question_for_doctor'   => $booking->question_for_doctor,
                'document_ids'          => $booking->document_ids,
                'documents'             => $documents,
                'mode_of_consultation'  => $booking->mode_of_consultation,
                'preferred_date'        => $booking->preferred_date?->format('Y-m-d'),
                'preferred_time_slots'  => $booking->preferred_time_slots,
                'relationship'          => $booking->relationship,
                'status'                => $booking->status,
                'is_coins_applied'      => (bool) $booking->is_coins_applied,
                'is_online_payment'     => (bool) $booking->is_online_payment,
                'payment_status'        => $booking->payment_status,
                'invoice_id'            => $booking->invoice_id ? (int) $booking->invoice_id : null,
                'coins_used'            => (int) ($booking->coins_used ?? 0),
                'consultation_fee'      => (float) ($booking->consultation_fee ?? 0),
                'service_charges'       => (float) ($booking->service_charges ?? 0),
                'total_discount'        => (float) ($booking->total_discount ?? 0),
                'amount_after_discount' => (float) ($booking->amount_after_discount ?? 0),
                'total_amount'          => (float) ($booking->total_amount ?? 0),
            ];

            if (! empty($result['payment'])) {
                $data['payment'] = $result['payment'];
            }

            // Log::info('secondOpinion created', [
            //     'second_opinion_id' => $booking->id,
            //     'payment_status'    => $booking->payment_status,
            // ]);

            return response()->json([
                'status'  => 200,
                'message' => 'Second opinion request created successfully',
                'data'    => $data,
            ], 200);
        } catch (ValidationException $e) {
            // Log::warning('secondOpinion validation failed', [
            //     'errors' => $e->errors(),
            // ]);

            return response()->json([
                'status'  => 422,
                'message' => $e->validator->errors()->first() ?: 'Validation failed',
                'errors'  => $e->errors(),
                'data'    => [],
            ], 422);
        } catch (\InvalidArgumentException $e) {
            // Log::warning('secondOpinion rejected', ['message' => $e->getMessage()]);

            return response()->json([
                'status'  => 422,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        } catch (\Throwable $e) {
            // Log::error('Second opinion creation failed', [
            //     'error' => $e->getMessage(),
            //     'file'  => $e->getFile(),
            //     'line'  => $e->getLine(),
            //     'trace' => $e->getTraceAsString(),
            // ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong',
                'data'    => [],
            ], 500);
        }
    }

    /**
     * Accept preferred_time_slots as array, JSON string, comma-separated, or single value (e.g. "02:00").
     *
     * @return list<string>
     */
    private function normalizeTimeSlotsInput(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($slot) => trim((string) $slot),
                $value
            ), fn ($slot) => $slot !== ''));
        }

        if (! is_string($value)) {
            return [];
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return [];
        }

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $this->normalizeTimeSlotsInput($decoded);
        }

        if (str_contains($trimmed, ',')) {
            return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
        }

        return [$trimmed];
    }

    public function userDetails(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $hipUser = HIPUser::where('id', $user->id)->first();

        if (! $hipUser) {
            return response()->json([
                'status' => 404,
                'message' => 'HIP User not found',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'User details fetched successfully',
            'data' => [
                'full_name' => $hipUser->first_name . ' ' . $hipUser->last_name,
                'email' => $hipUser->email,
                'gender' => $hipUser->gender,
                'dob' => $hipUser->dob,
                'profile_image' => $hipUser->profile_image ? url('storage/users/' . $hipUser->profile_image) : null,
                'marital_status' => $hipUser->marital_status,
                'blood_group' => $hipUser->blood_group,
                'preferred_branch_id' => $hipUser->preferred_branch_id,
                'emergency_contact_person_name' => $hipUser->emergency_contact_person_name,
                'emergency_contact_person_phone' => $hipUser->emergency_contact_person_phone,
                'emergency_contact_person_relationship' => $hipUser->emergency_contact_person_relationship,
                'house_number' => $hipUser->house_number,
                'street' => $hipUser->street,
                'city' => $hipUser->city,
                'state' => $hipUser->state,
                'zip_code' => $hipUser->zip_code,
            ],
        ], 200);
    }

}
