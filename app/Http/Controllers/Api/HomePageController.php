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
use App\Services\AssignDoctorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\LocationMaster;

class HomePageController extends Controller
{

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
            'experience'          => $this->formatDoctorExperience($doctor->working_since),
            'rating'              => $rating,
            'review_count'        => (int) ($doctor->reviews_count ?? 0),
            'available_today'     => $nextSlot !== null && ($nextSlot['date'] ?? null) === today()->toDateString(),
            // 'procedure_names'     => $procedureNames,
            'next_slot'           => $nextSlot,
        ];
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
                    'latitude'      => $latitude,
                    'longitude'     => $longitude,
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

        $person = Persons::where('hip_user_id', $user->id)->first();

        if(!$person){
            return response()->json([
                'status' => 404,
                'message' => 'Person not found',
            ], 404);
        }

        $coins = Coins::where('person_id', $person->parent_id ?? $person->id)->first();

        return response()->json([
            'status' => 200,
            'message' => 'Coins fetched successfully',
            'data' => [
                'coins' => $coins->coins,
            ],
        ], 200);

    }

    public function doctorSpecialities(Request $request)
    {
        if (!$request->filled('hospital_id')) {
            return $this->branchRequiredResponse();
        }

        $request->validate([
            'hospital_id' => 'required|integer|exists:hospitals,id',
        ]);

        $hospitalId = (int) $request->hospital_id;

        try {
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
                return response()->json([
                    'status'  => 200,
                    'message' => 'No doctor specialities found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $masters = SpecialitiesMaster::query()
                ->where('status', 'active')
                ->whereIn('id', array_keys($countsByMasterId))
                ->orderBy('name')
                ->get();

            $data = $masters->map(function (SpecialitiesMaster $master) use ($countsByMasterId) {
                $count = $countsByMasterId[$master->id] ?? 0;

                return [
                    'id'                 => $master->id,
                    'speciality_name'    => $master->name,
                    'description'        => $master->description,
                    'icon'               => $master->display_image
                        ? url('storage/speciality/' . basename($master->display_image))
                        : null,
                    'specialists_count'  => $count,
                ];
            })->filter(fn (array $row) => $row['specialists_count'] > 0)->values();

            return response()->json([
                'status'  => 200,
                'message' => 'Doctor specialities fetched successfully',
                'data'    => $data,
                'count'   => $data->count(),
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
        ]);

        try {
            $hospitalId = (int) $request->hospital_id;
            $perPage    = (int) ($request->per_page ?? 10);

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
                ->paginate($perPage);

            if ($doctors->isEmpty()) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No doctors found',
                    'data'    => [],
                    'count'   => 0,
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
                'current_page' => $doctors->currentPage(),
                'last_page'    => $doctors->lastPage(),
                'per_page'     => $doctors->perPage(),
                'total'        => $doctors->total(),
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
            $areas = LocationMaster::query()
                ->select('id', 'area', 'latitude', 'longitude')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->get();

            $hospitals = Hospital::query()
                ->where('status', 'active')
                ->select('id', 'name', 'logo', 'location_id', 'admin_latitude', 'admin_longitude', 'address','admin_contact')
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
                    // 'area'          => $areaName,
                    // 'latitude'      => $latitude,
                    // 'longitude'     => $longitude,
                    'address'       => $hospital->address ?? $areaName,
                    'contact'       => $hospital->admin_contact,
                    'logo'          => $hospital->logo
                        ? url('storage/hospital/' . $hospital->logo)
                        : null,
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

    public function doctors(Request $request)
    {
        $request->validate([
            'hospital_id' => 'nullable|integer|exists:hospitals,id',
            'search'      => 'nullable|string|max:255',
            'sort'        => 'nullable|string|in:name_asc,name_desc,rating_desc,rating_asc,experience_desc,experience_asc,availability,newest,oldest',
            'per_page'    => 'nullable|integer|min:1|max:50',
            'page'        => 'nullable|integer|min:1',
        ]);

        try {
            $perPage = (int) ($request->per_page ?? 10);
            $sort    = $request->input('sort', 'name_asc');
            $hospitalId = $request->filled('hospital_id') ? (int) $request->hospital_id : null;

            $query = Doctor::query()
                ->select(
                    'id',
                    'name',
                    'doctor_image',
                    'qualifications',
                    'speciality',
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

}
