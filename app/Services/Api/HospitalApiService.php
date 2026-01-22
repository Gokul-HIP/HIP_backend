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


class HospitalApiService
{
   
    public function getHospitalDetails($id, $latitude, $longitude) : ?Hospital {

        return Hospital::query()
        ->selectRaw("
            hospitals.*,

            (
                6371 * acos(
                    cos(radians(?))
                    * cos(radians(hospitals.hospital_admin_latitude))
                    * cos(radians(hospitals.hospital_admin_longitude) - radians(?))
                    + sin(radians(?))
                    * sin(radians(hospitals.hospital_admin_latitude))
                )
            ) AS distance
        ", [$latitude, $longitude, $latitude])
        ->where('hospitals.id', $id)
        ->whereNotNull('hospitals.hospital_admin_latitude')
        ->whereNotNull('hospitals.hospital_admin_longitude')
        ->first();

    }

    public function getAssignedDoctors(int $hospitalId, int $perPage) : LengthAwarePaginator{

        return Doctor::whereJsonContains('hospital_ids', $hospitalId)
        ->select('id', 'doctor_name', 'doctor_image', 'qualifications', 'speciality')
        ->orderBy('doctor_name')
        ->paginate($perPage);        

    }

    public function getHospitalProcedures(int $hospitalId) :Collection{

        return Procedure::where('hospital_id', $hospitalId)->with('speciality:id,speciality_name')->select('id', 'procedure_name','speciality_id', 
        'description', 'cost', 'estimated_time')->get();

    }

    public function getDiagnosticLabTests(int $hospitalId, int $perPage = 12) : ?array{

        $hospital = Hospital::select('id', 'diagnostic_center_id')->find($hospitalId);

        if(!$hospital){
            return null;
        }

        $diagnostic = Diagnostic::select('id', 'diagnostic_center_name', 'diagnostic_logo')->find($hospital->diagnostic_center_id);

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

        $diagnostic = Diagnostic::select('id', 'diagnostic_center_name', 'diagnostic_logo')->find($diagnosticId);

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

        $pharmacy = Pharmacy::whereIn('id', $pharmacyIds)->select('id', 'pharmacy_name', 'pharmacy_logo')->first();

        $pharmacyProducts = $hospital->pharmacyProducts()
        ->select('id', 'product_name', 'product_image', 'selling_price', 'pharmacy_id','product_description','pack_size')
        ->get();

        return [
            'hospital' => $hospital,
            'pharmacyProducts' => $pharmacyProducts,
            'pharmacy' => $pharmacy,
        ];

    }

}