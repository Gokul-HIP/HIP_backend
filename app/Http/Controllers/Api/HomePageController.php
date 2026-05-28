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
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\LocationMaster;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Disease;
use Illuminate\Database\Eloquent\Builder;

class HomePageController extends Controller
{
    public function __construct(
        protected HospitalApiService $hospitalApiService
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
            })->orWhere(function ($inner) use ($specialityAsNumber, $specialityAsString) {
                $inner->whereRaw('JSON_VALID(assigned_speciality) = 1')
                    ->whereRaw(
                        '(JSON_CONTAINS(assigned_speciality, ?) OR JSON_CONTAINS(assigned_speciality, ?))',
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

        return $date->format('l');
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
                    'latitude'      => $coordinates[0],
                    'longitude'     => $coordinates[1],
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

                return [
                    'day' => strtoupper(substr($weekday, 0, 3)),
                    'branches' => $branchesForDay,
                ];
            })
            ->all();
    }

    private function buildReviewsSummary(string $doctorId): array
    {
        $reviews = DoctorReview::query()
            ->where('doctor_id', $doctorId)
            ->where('status', 'active');

        $total = (clone $reviews)->count();
        $average = round((float) ((clone $reviews)->avg('rating') ?? 0), 1);

        $breakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $breakdown[(string) $star] = (clone $reviews)->where('rating', $star)->count();
        }

        return [
            'average_rating'   => $total > 0 ? (string) $average : '0',
            'total_reviews'    => $total,
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
            'next_slot'           => $nextSlot,
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
                ->select('id', 'name', 'logo', 'location_id', 'admin_latitude', 'admin_longitude')
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
                    'branch_name'   => $areaName ? "{$areaName} Branch" : null,
                    'area'          => $areaName,
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

        return response()->json([
            'status' => 200,
            'message' => 'Coins fetched successfully',
            'data' => $coinsData,
        ], 200);

    }

    public function doctorSpecialities(Request $request)
    {
        if (!$request->filled('hospital_id')) {
            return $this->branchRequiredResponse();
        }

        $request->validate([
            'hospital_id' => 'required|integer|exists:hospitals,id',
            'search'      => 'nullable|string|max:255',
        ]);

        try {
            $search = $request->filled('search') ? trim((string) $request->search) : null;
            $result = $this->fetchDoctorSpecialitiesData((int) $request->hospital_id, $search);

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

            $procedureMap = Procedure::query()
                ->where('status', 'active')
                ->whereIn('id', $procedureIds)
                ->pluck('procedure_name', 'id');

            return response()->json([
                'status'       => 200,
                'message'      => 'Doctors fetched successfully',
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

            $rating = $doctor->rating_avg !== null
                ? (string) round((float) $doctor->rating_avg, 1)
                : '0';

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
                    'speciality_names'    => $doctor->speciality_names,
                    'specialities'        => $specialities,
                    'specializations'     => $specializations,
                    'experience'          => $this->formatDoctorExperience($doctor->working_since),
                    'about'               => $doctor->about_doctor,
                    'rating'              => $rating,
                    'review_count'        => (int) ($doctor->reviews_count ?? 0),
                    'available_today'     => (bool) (collect($calendar)->firstWhere('date', today()->toDateString())['is_available'] ?? false),
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

    private function formatDiagnosticPackageRow($package): array
    {
        return [
            'id'        => $package->id,
            'name'      => $package->name,
            'price'     => (float) ($package->price ?? 0),
            'package_discount' => (float) ($package->discount ?? 0)."%",
            'package_discount_price' => (float) ($package->price - ($package->price * ($package->discount / 100))),
            // 'package_image' => $package->image ? url('storage/diagnostic-packages/' . $package->image) : null,
            // 'package_status' => $package->status,
            'weight'    => (float) ($package->weight ?? 0),
            'lab_tests' => $package->lab_tests_list,
        ];
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
        $request->merge([
            'hospital_id' => $request->input('hospital_id', $request->input('id')),
        ]);

        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'page'        => 'nullable|integer|min:1',
            'page_limit'  => 'nullable|integer|min:1|max:50',
            'per_page'    => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;
            $page       = (int) ($request->page ?? 1);
            $pageLimit  = (int) ($request->page_limit ?? $request->per_page ?? env('PAGELIMIT', 10));

            $result = $this->hospitalApiService->getHospitalDiagnosticPackages($hospitalId, $page, $pageLimit);

            if ($hospitalId !== null && $result === null) {
                return response()->json([
                    'status'      => 404,
                    'message'     => 'Hospital or diagnostic center not found',
                    'data'        => [],
                    'total'       => 0,
                    'page'        => $page,
                    'page_limit'  => $pageLimit,
                    'count'       => 0,
                ], 404);
            }

            $packages = $result['packages'];

            if ($result['scoped']) {
                $hospital   = $result['hospital'];
                $diagnostic = $result['diagnostic'];

                $data = collect($packages->items())->map(fn ($package) => $this->formatDiagnosticPackageRow($package))->values();

                return response()->json([
                    'status'           => 200,
                    'message'          => $data->isEmpty() ? 'No packages found' : 'Diagnostic packages fetched successfully',
                    'hospital_id'      => $hospital->id,
                    'hospital_name'    => $hospital->name,
                    'diagnostic_id'    => $diagnostic->id,
                    'diagnostic_image' => $this->diagnosticLogoUrl($diagnostic->logo),
                    'diagnostic_name'  => $diagnostic->name,
                    'data'             => $data,
                    'total'            => $packages->total(),
                    'page'             => $packages->currentPage(),
                    'page_limit'       => $packages->perPage(),
                    'count'            => $data->count(),
                ], 200);
            }

            $diagnostics           = $result['diagnostics'];
            $hospitalsByDiagnostic = $result['hospitalsByDiagnostic'];

            $data = collect($packages->items())->map(function ($package) use ($diagnostics, $hospitalsByDiagnostic) {
                $diagnostic = $diagnostics->get($package->diagnostic_id);
                $hospital   = $hospitalsByDiagnostic->get($package->diagnostic_id)?->first();

                return array_merge($this->formatDiagnosticPackageRow($package), [
                    'diagnostic_id'    => $package->diagnostic_id,
                    'diagnostic_name'  => $diagnostic?->name,
                    'diagnostic_image' => $this->diagnosticLogoUrl($diagnostic?->logo),
                    'hospital_id'      => $hospital?->id,
                    'hospital_name'    => $hospital?->name,
                ]);
            })->values();

            return response()->json([
                'status'     => 200,
                'message'    => $data->isEmpty() ? 'No packages found' : 'Diagnostic packages fetched successfully',
                'data'       => $data,
                'total'      => $packages->total(),
                'page'       => $packages->currentPage(),
                'page_limit' => $packages->perPage(),
                'count'      => $data->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching diagnostic packages', ['error' => $e->getMessage()]);

            return response()->json([
                'status'     => 500,
                'message'    => 'Error fetching diagnostic packages',
                'data'       => [],
                'total'      => 0,
                'page'       => 1,
                'page_limit' => (int) env('PAGELIMIT', 10),
                'count'      => 0,
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
            ->whereIn('status', ['pending', 'confirmed'])
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
            'status'            => strtoupper((string) $booking->status),
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
            'time_slots'        => $timeSlots,
            'branch_id'         => $booking->branch_id ?? $booking->hospital_id,
            'branch_name'       => $branch?->name,
            'location'          => implode(', ', $locationParts) ?: $branch?->address,
            'appointment_type'  => $booking->appointment_type ?? $booking->consultation_type,
            'reason_of_visit'   => $booking->reason_of_visit,
            'message'           => $booking->message,
            'booking_date'      => $booking->booking_date?->format('Y-m-d'),
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
                $query->whereIn('status', ['pending', 'confirmed'])
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
                'name'         => trim(($dependent->first_name ?? '') . ' ' . ($dependent->last_name ?? '')),
                // 'relationship' => $relationship,
                'label'        => $relationship,
                'image'        => $this->personProfileImageUrl($dependent->image)
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
                    'user_coins'          => $userCoins,
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
                // 'contact'     => $hospital->admin_contact,
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
    private function fetchDoctorSpecialitiesData(int $hospitalId, ?string $search = null): array
    {
        $countsByMasterId = [];

        $this->scopeDoctorsForHospital(
            Doctor::query()->select(['id', 'speciality', 'assigned_speciality']),
            $hospitalId
        )->chunk(200, function ($doctors) use (&$countsByMasterId) {
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

        if ($countsByMasterId === []) {
            return ['data' => collect(), 'count' => 0];
        }

        $masters = SpecialitiesMaster::query()
            ->where('status', 'active')
            ->whereIn('id', array_keys($countsByMasterId))
            ->when($search !== null && $search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->get();

        $data = $masters->map(function (SpecialitiesMaster $master) use ($countsByMasterId) {
            $count = $countsByMasterId[$master->id] ?? 0;

            return [
                'id'                => $master->id,
                'speciality_name'   => $master->name,
                'description'       => $master->description,
                'icon'              => $master->display_image
                    ? url('storage/speciality/' . basename($master->display_image))
                    : null,
                'specialists_count' => $count,
            ];
        })->filter(fn (array $row) => $row['specialists_count'] > 0)->values();

        return [
            'data'  => $data,
            'count' => $data->count(),
        ];
    }

    /**
     * Diseases assigned on doctors at this branch (only diseases with at least one doctor).
     *
     * @return array{data: \Illuminate\Support\Collection, count: int}
     */
    private function fetchDiseasesData(int $hospitalId): array
    {
        $countsByDiseaseId = [];

        $this->scopeDoctorsForHospital(
            Doctor::query()->select(['id', 'assigned_diseases']),
            $hospitalId
        )->chunk(200, function ($doctors) use (&$countsByDiseaseId) {
            foreach ($doctors as $doctor) {
                $diseaseIds = array_unique(array_map('intval', (array) ($doctor->assigned_diseases ?? [])));

                foreach ($diseaseIds as $diseaseId) {
                    if ($diseaseId > 0) {
                        $countsByDiseaseId[$diseaseId] = ($countsByDiseaseId[$diseaseId] ?? 0) + 1;
                    }
                }
            }
        });

        if ($countsByDiseaseId === []) {
            return ['data' => collect(), 'count' => 0];
        }

        $diseases = Disease::query()
            ->where('is_active', true)
            ->whereIn('id', array_keys($countsByDiseaseId))
            ->orderBy('name')
            ->get();

        $data = $diseases->map(function (Disease $disease) use ($countsByDiseaseId) {
            $count = $countsByDiseaseId[$disease->id] ?? 0;

            return [
                'id'            => $disease->id,
                'disease_name'  => $disease->name,
                'doctors_count' => $count,
                'doctors_label' => $count === 1 ? '1 Doctor' : "{$count} Doctors",
            ];
        })->filter(fn (array $row) => $row['doctors_count'] > 0)->values();

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

}
