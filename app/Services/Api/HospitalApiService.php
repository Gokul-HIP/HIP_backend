<?php

namespace App\Services\Api;

use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\LocationMaster;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use App\Models\DiagnosticPackage;
use App\Models\Pharmacy;
use App\Models\Speciality;


class HospitalApiService
{
   
    public function getHospitalDetails($id, $latitude, $longitude) : ?Hospital {

        return Hospital::query()
        ->selectRaw("
            hospitals.*,

            (
                6371 * acos(
                    cos(radians(?))
                    * cos(radians(hospitals.admin_latitude))
                    * cos(radians(hospitals.admin_longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(hospitals.admin_latitude))
                )
            ) AS distance
        ", [$latitude, $longitude, $latitude])
        ->where('hospitals.id', $id)
        ->whereNotNull('hospitals.admin_latitude')
        ->whereNotNull('hospitals.admin_longitude')
        ->first();

    }

    public function getAssignedDoctors(int $hospitalId, int $perPage) : LengthAwarePaginator{

        return Doctor::whereJsonContains('hospital_ids', $hospitalId)
        ->select('id', 'name', 'doctor_image', 'qualifications', 'speciality', 'about_doctor')
        ->withAvg(['doctorReviews as rating_avg' => function ($q) {
            $q->where('status', 'active');
        }], 'rating')
        ->orderBy('name')
        ->paginate($perPage);        

    }

    public function getHospitalProcedures(int $hospitalId) :Collection{

        return Procedure::where('hospital_id', $hospitalId)->with('speciality:id,speciality_name')->select('id', 'procedure_name','speciality_id', 
        'description', 'cost', 'estimated_time','image','recovery_time','success_rate','hospitalization_days')->get();

    }

    public function getDiagnosticLabTests(int $hospitalId, int $perPage = 12) : ?array{

        $hospital = Hospital::select('id', 'diagnostic_center_id')->find($hospitalId);

        if(!$hospital){
            return null;
        }

        $diagnostic = Diagnostic::select('id', 'name', 'logo')->find($hospital->diagnostic_center_id);

        if(!$diagnostic){
            return null;
        }

        $labTests = DiagnosticLabTest::where('diagnostic_id', $diagnostic->id)->select('id', 'test_name', 'test_price', 'test_image')->paginate($perPage);

        return [
            'diagnostic' => $diagnostic,
            'labTests' => $labTests,
        ];

    }

    public function getDiagnosticPackages(int $diagnosticId) : ?array{

        $diagnostic = Diagnostic::select('id', 'name', 'logo')->find($diagnosticId);

        if(!$diagnostic){
            return null;
        }

        $packages = DiagnosticPackage::where('diagnostic_id', $diagnostic->id)
            ->select('id','name','description','price','discount','weight','lab_tests')
            ->get();

        // if(!$packages){
        //     return null;
        // }

        return [
            'diagnostic' => $diagnostic,
            'packages' => $packages,
        ];
    }

    public function getHospitalPharmaciesList(int $hospitalId) : ?array{

        $hospital = Hospital::select('id', 'pharmacy_ids')->find($hospitalId);

        if(!$hospital){
            return null;
        }

        $pharmacyIds = $hospital->pharmacy_ids;

        if(!is_array($pharmacyIds) || empty($pharmacyIds)){
            return [
                'hospital' => $hospital,
                'pharmacyProducts' => collect(),
                'pharmacy' => null, 
            ];
        }

        $pharmacy = Pharmacy::whereIn('id', $pharmacyIds)->select('id', 'name', 'logo')->first();

        $pharmacyProducts = $hospital->pharmacyProducts()
        ->select('id', 'product_name', 'product_image', 'selling_price', 'pharmacy_id','product_description','pack_size')->paginate(12);

        return [
            'hospital' => $hospital,
            'pharmacyProducts' => $pharmacyProducts,
            'pharmacy' => $pharmacy,
        ];

    }

    public function getAllSpecialitiesList(int $hospitalId) : array{

       return[
        'specialities' => Speciality::where('hospital_id', $hospitalId)->select('id', 'department_category', 'speciality_logo','speciality_master_id')
        ->join('specialities_masters', 'specialities.speciality_master_id', '=', 'specialities_masters.id')
        ->select('specialities.id', 'specialities.department_category', 'specialities.speciality_logo', 'specialities.speciality_master_id', 'specialities_masters.name as speciality_master_name', 'specialities_masters.display_image as speciality_master_image')
        ->orderBy('specialities.department_category')->get(),

        // 'specialities' => Speciality::where('hospital_id', $hospitalId)
        //     ->select('id', 'department_category', 'speciality_logo', 'speciality_master_id')
        //     ->orderBy('department_category')
        //     ->get()
        //     ->unique('department_category')
        //     ->values(),

        // 'specialities' => Speciality::where('hospital_id', $hospitalId)
        //     ->selectRaw('
        //         MIN(id) as id,
        //         department_category,
        //         MIN(speciality_logo) as speciality_logo,
        //         MIN(speciality_master_id) as speciality_master_id
        //     ')
        //     ->groupBy('department_category')
        //     ->orderBy('department_category')
        //     ->get(),


       ];

    }

    /**
     * Get doctors for one hospital and speciality. Skips invalid JSON rows; matches speciality/hospital_ids as number or string.
     */
    public function getAllDoctorsList(int $hospitalId, int $specialityId): array
    {
        $hospitalAsNumber = json_encode($hospitalId);
        $hospitalAsString = json_encode((string) $hospitalId);
        $specialityAsNumber = json_encode($specialityId);
        $specialityAsString = json_encode((string) $specialityId);

        $doctors = Doctor::query()
            ->select('id', 'name', 'doctor_image', 'qualifications', 'speciality')
            ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                $q->where('status', 'active');
            }], 'rating')
            ->orderBy('name')
            ->whereRaw('JSON_VALID(speciality) = 1 AND JSON_VALID(hospital_ids) = 1')
            ->whereRaw('(JSON_CONTAINS(hospital_ids, ?) OR JSON_CONTAINS(hospital_ids, ?))', [$hospitalAsNumber, $hospitalAsString])
            ->whereRaw('(JSON_CONTAINS(speciality, ?) OR JSON_CONTAINS(speciality, ?))', [$specialityAsNumber, $specialityAsString])
            ->get();

        return ['doctors' => $doctors];
    }

    /**
     * Get hospital IDs within radius (km) of lat/lng. Same distance logic as LocationFilter::byLocation.
     */
    public function getNearbyHospitalIds(float $lat, float $lng, int $radiusKm = 15, int $limit = 50): array
    {
        $nearestArea = LocationMaster::selectRaw("
            id,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))
                + sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
            ->orderBy('distance')
            ->first();

        // Use 0 when no location_masters row (avoids binding NULL in strict MySQL)
        $nearestAreaId = $nearestArea?->id ?? 0;

        $query = Hospital::query()
            ->leftJoin('location_masters as lm', 'lm.id', '=', 'hospitals.location_id')
            ->selectRaw("
                hospitals.id,
                (6371 * acos(
                    cos(radians(?))
                    * cos(radians(
                        CASE WHEN hospitals.admin_latitude BETWEEN -90 AND 90 THEN hospitals.admin_latitude ELSE lm.latitude END
                    ))
                    * cos(radians(
                        CASE WHEN hospitals.admin_longitude BETWEEN -180 AND 180 THEN hospitals.admin_longitude ELSE lm.longitude END
                    ) - radians(?))
                    + sin(radians(?))
                    * sin(radians(
                        CASE WHEN hospitals.admin_latitude BETWEEN -90 AND 90 THEN hospitals.admin_latitude ELSE lm.latitude END
                    ))
                )) AS distance,
                CASE WHEN lm.id = ? THEN 0 ELSE 1 END AS area_priority
            ", [$lat, $lng, $lat, $nearestAreaId])
            ->where('hospitals.status', 'active')
            ->whereRaw("
                (6371 * acos(
                    cos(radians(?))
                    * cos(radians(
                        CASE WHEN hospitals.admin_latitude BETWEEN -90 AND 90 THEN hospitals.admin_latitude ELSE lm.latitude END
                    ))
                    * cos(radians(
                        CASE WHEN hospitals.admin_longitude BETWEEN -180 AND 180 THEN hospitals.admin_longitude ELSE lm.longitude END
                    ) - radians(?))
                    + sin(radians(?))
                    * sin(radians(
                        CASE WHEN hospitals.admin_latitude BETWEEN -90 AND 90 THEN hospitals.admin_latitude ELSE lm.latitude END
                    ))
                )) <= ?
            ", [$lat, $lng, $lat, $radiusKm])
            ->orderBy('area_priority')
            ->orderBy('distance')
            ->limit($limit);

        return $query->pluck('id')->all();
    }

    /**
     * Get all specialities for given hospital IDs (unique by speciality_master_id). Same shape as getAllSpecialitiesList.
     */
    public function getSpecialitiesByHospitalIds(array $hospitalIds): Collection
    {
        if (empty($hospitalIds)) {
            return collect([]);
        }

        return Speciality::whereIn('hospital_id', $hospitalIds)
            ->join('specialities_masters', 'specialities.speciality_master_id', '=', 'specialities_masters.id')
            ->select(
                'specialities.id',
                'specialities.department_category',
                'specialities.speciality_logo',
                'specialities.speciality_master_id',
                'specialities_masters.name as speciality_master_name',
                'specialities_masters.display_image as speciality_master_image',
            )
            ->orderBy('specialities.department_category')
            ->get()
            ->unique('speciality_master_id')
            ->values();
    }

    /**
     * Get doctors assigned to any of the given hospitals and having the given speciality.
     * Skips rows where speciality or hospital_ids contain invalid JSON (avoids 500 on bad data).
     */
    public function getDoctorsByHospitalIdsAndSpeciality(array $hospitalIds, int $specialityId): Collection
    {
        if (empty($hospitalIds)) {
            return collect([]);
        }

        $placeholders = implode(' OR ', array_fill(0, count($hospitalIds), 'JSON_CONTAINS(hospital_ids, ?)'));
        $hospitalBindings = array_map(fn ($id) => json_encode((int) $id), $hospitalIds);

        // Match speciality as both number and string (DB may store [6,8] or ["16","6","17"])
        $specialityAsNumber = json_encode($specialityId);
        $specialityAsString = json_encode((string) $specialityId);

        return Doctor::query()
            ->select('id', 'name', 'doctor_image', 'qualifications', 'speciality')
            ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                $q->where('status', 'active');
            }], 'rating')
            ->orderBy('name')
            ->whereRaw('JSON_VALID(speciality) = 1 AND JSON_VALID(hospital_ids) = 1')
            ->whereRaw('(JSON_CONTAINS(speciality, ?) OR JSON_CONTAINS(speciality, ?))', [$specialityAsNumber, $specialityAsString])
            ->whereRaw("({$placeholders})", $hospitalBindings)
            ->get();
    }
}