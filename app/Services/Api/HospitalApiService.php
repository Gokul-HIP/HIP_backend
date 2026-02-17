<?php

namespace App\Services\Api;

use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Procedure;
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

    public function getAllDoctorsList(int $hospitalId, int $specialityId): array
    {
        return [
            'doctors' => Doctor::query()
                ->whereJsonContains('hospital_ids', $hospitalId)
                ->whereJsonContains('speciality', (string) $specialityId)
                ->select('id', 'name', 'doctor_image', 'qualifications', 'speciality')
                ->orderBy('name')
                ->get(),
        ];
    }

}