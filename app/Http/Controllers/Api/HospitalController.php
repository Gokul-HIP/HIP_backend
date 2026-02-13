<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Procedure;
use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use App\Models\Speciality;
use App\Models\DiagnosticPackage;
use App\Models\Pharmacy;
use App\Models\PharmacyProducts;
use Illuminate\Support\Facades\Log;
use App\Services\Api\HospitalApiService;
use App\Models\SpecialitiesMaster;
use App\Models\ProcedureMaster;

class HospitalController extends Controller
{
    protected $hospitalApiService;

    public function __construct(HospitalApiService $hospitalApiService)
    {
        $this->hospitalApiService = $hospitalApiService;
    }

    public function hospitalDetails(Request $request)
    {
        $request->validate([
            'id'        => 'required|exists:hospitals,id',
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
    
         try{

            $userLat = $request->latitude;
            $userLng = $request->longitude;
        
            $hospital = $this->hospitalApiService->getHospitalDetails($request->id, $userLat, $userLng);

                if (!$hospital) {
                    Log::info('Hospital not found', ['hospital_id' => $request->id]);   
                    return response()->json([
                        'status' => 404,
                        'message' => 'Hospital not found',
                        'data' => [],
                        'count' => 0
                    ], 404);
                }
        
            $googleMapUrl = "https://www.google.com/maps/dir/"
                . "{$userLat},{$userLng}/"
                . "{$hospital->admin_latitude},{$hospital->admin_longitude}";
        
            return response()->json([
                'status' => 200,
                'message' => 'Hospital fetched successfully',
                'data' => [
                    'id'            => $hospital->id,
                    'hospital_name' => $hospital->name,
                    'subtitle'      => $hospital->subtitle,
                    'about'         => $hospital->about,
                    'address'       => $hospital->address,
                    'distance_km'   => round($hospital->distance, 2),
                    'google_map_url'=> $googleMapUrl,
                    'rating'        => 4.5,
                    'logo'          => $hospital->logo ? url('storage/hospital/' . $hospital->logo) : null,
                ]
            ], 200);

         }catch(\Throwable $e){
            Log::error('Error fetching hospital details', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching hospital details',
                'data' => [],
                'count' => 0
            ], 500);
        }
        
    }

    public function doctorsByLocation(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'per_page'  => 'nullable|integer'
        ]);

        try {
            $lat = $request->latitude;
            $lng = $request->longitude;
            $radius = 15; // km
            $perPage = $request->get('per_page', 10);

            $hospitalIds = Hospital::query()
                ->leftJoin('location_masters as lm', 'lm.id', '=', 'hospitals.location_id')
                ->selectRaw("
                    hospitals.id,
                    (6371 * acos(
                        cos(radians(?))
                        * cos(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                    ))
                    * cos(radians(
                        CASE
                            WHEN hospitals.admin_longitude BETWEEN -180 AND 180
                            THEN hospitals.admin_longitude
                            ELSE lm.longitude
                        END
                    ) - radians(?))
                    + sin(radians(?))
                    * sin(radians(
                        CASE
                            WHEN hospitals.admin_latitude BETWEEN -90 AND 90
                            THEN hospitals.admin_latitude
                            ELSE lm.latitude
                        END
                        ))
                    )) AS distance
                ", [$lat, $lng, $lat])
                ->where('hospitals.status', 'active')
                ->having('distance', '<=', $radius)
                ->pluck('hospitals.id');

            if ($hospitalIds->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No doctors found',
                    'data' => [],
                    'count' => 0
                ]);
            }

            $doctors = Doctor::where(function ($query) use ($hospitalIds) {
                    foreach ($hospitalIds as $hospitalId) {
                        $query->orWhereJsonContains('hospital_ids', $hospitalId);
                    }
                })
                ->where('status', 'active')
                ->paginate($perPage);

            $doctors->getCollection()->transform(function ($doctor) {
                return [
                    'id'               => $doctor->id,
                    'name'             => $doctor->name,
                    'doctor_image'     => $doctor->doctor_image
                        ? url('storage/doctor/' . $doctor->doctor_image)
                        : null,
                    'qualification_names' => $doctor->qualification_names,
                    'speciality_names' => $doctor->speciality_names,
                    'rating'           => '4.5',
                ];
            });

            return response()->json([
                'status'  => 200,
                'message' => 'Doctors fetched successfully',
                'doctors'    => $doctors->items(),
                'page'    => $doctors->currentPage(),
                'total'   => $doctors->total(),
                'count'   => count($doctors->items()),
            ]);

        } catch (\Throwable $e) {
            Log::error('Error fetching doctors by location', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Error fetching doctors',
                'data' => [],
                'count' => 0
            ], 500);
        }
    }
    
    public function assignedDoctors(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:hospitals,id',
        ]);
    
        try{

            $perPage = $request->get('per_page', 10);

            $hospitalId = $request->id;

            $doctors = $this->hospitalApiService->getAssignedDoctors($hospitalId ,$perPage);
        
            if ($doctors->total() === 0) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No doctors found',
                    'hospital_id' => $hospitalId,
                    'data' => [],
                    'count' => 0
                ]);
            }
        
                $doctors = $doctors->through(function ($doctor) {
                return [
                    'id'                  => $doctor->id,
                    'name'                => $doctor->name,
                    'doctor_image'        => $doctor->doctor_image
                                            ? url('storage/doctor/' . $doctor->doctor_image)
                                            : null,
                    'qualification_names' => $doctor->qualification_names,
                    'speciality_names'    => $doctor->speciality_names,
                    'rating'              => '4.5',
                ];
            });
        
            return response()->json([
                'status'  => 200,
                'message' => 'Doctors fetched successfully',
                'data'    => $doctors->items(),
                'page'    => $doctors->currentPage(),
                'total'   => $doctors->total(),
                'count'   => count($doctors->items()),
            ], 200);

        }catch(\Throwable $e){
            Log::error('Error fetching assigned doctors', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching assigned doctors',
                'data' => [],
                'count' => 0
            ], 500);
        }
       
    }

    public function getHospital($id){

        $hospital = Hospital::find($id)->select('id', 'name', 'about', 'subtitle', 'logo', 'is_promoted')->first();

        if(!$hospital){
            return response()->json([
                'status' => 404,
                'message' => 'Hospital not found',
                'data' => [],
                'count' => 0
            ], 404);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Hospital fetched successfully',
                    'data' => [
               'id' => $hospital->id,
               'hospital_name' => $hospital->name,
               'hospital_about' => $hospital->about,
               'subtitle'      => $hospital->subtitle,
               'hospital_rating' => '4.5',
               'logo'          => $hospital->logo ? url('storage/hospital/' . $hospital->logo): null,
               'is_promoted'   => $hospital->is_promoted,
            ],
            'count' => 1
        ], 200);

    }
    
    public function hospitalProcedures(Request $request){

        $request->validate([
            'id' => 'required|exists:hospitals,id',
        ]);

        try{
         
            $hospitalId = $request->id;

            $procedures = $this->hospitalApiService->getHospitalProcedures($hospitalId);
            
               if ($procedures->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No procedures found',
                    'data' => [],
                    'count' => 0
                ], 200);
            }
    
            return response()->json([
                'status' => 200,
                'message' => 'Procedures fetched successfully',
                'data' => $procedures->map(function($procedure){
                    return [
                        'id' => $procedure->id,
                        'procedure_name' => $procedure->procedure_name,
                        'type' => $procedure->speciality->speciality_name ?? null,
                        'description' => $procedure->description,
                        'cost' => $procedure->cost,
                        'duration' => $procedure->estimated_time,
                        'image' => $procedure->image ? url('storage/procedures/' . $procedure->image) : null,
                        'recovery_time' => $procedure->recovery_time,
                        'success_rate' => $procedure->success_rate.' %',
                        'hospitalization_days' => $procedure->hospitalization_days.' days',
                    ];
                }),
                'count' => $procedures->count(),
            ], 200);
            
        }catch(\Throwable $e){
            Log::error('Error fetching hospital procedures', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching hospital procedures',
                'data' => [],
                'count' => 0
            ], 500);
        }

    }

    // public function organizationDiagnostics(Request $request, $hospitalId)
    // {
    //     $request->validate([
    //         'latitude'  => 'required|numeric',
    //         'longitude' => 'required|numeric',
    //     ]);

    //     $lat = $request->latitude;
    //     $lng = $request->longitude;
    //     $radius = 15;

    //     $hospital = Hospital::findOrFail($hospitalId);

    //     $diagnostics = Diagnostic::query()
    //         ->leftJoin('location_masters as lm', 'lm.id', '=', 'diagnostics.location_id')
    //         ->where('diagnostics.organization_id', $hospital->organization_id)
    //         ->selectRaw("
    //             diagnostics.id,
    //             diagnostics.diagnostic_center_name,
    //             diagnostics.diagnostic_center_address,
    //             diagnostics.diagnostic_logo,
    //             diagnostics.organization_id,

    //             COALESCE(lm.area, 'Unknown Area') as area,
    //             lm.zipcode,

    //             (
    //                 6371 * acos(
    //                     cos(radians(?))
    //                     * cos(radians(
    //                         IFNULL(diagnostics.diagnostic_contact_person_latitude, lm.latitude)
    //                     ))
    //                     * cos(radians(
    //                         IFNULL(diagnostics.diagnostic_contact_person_longitude, lm.longitude)
    //                     ) - radians(?))
    //                     + sin(radians(?))
    //                     * sin(radians(
    //                         IFNULL(diagnostics.diagnostic_contact_person_latitude, lm.latitude)
    //                     ))
    //                 )
    //             ) AS distance
    //         ", [$lat, $lng, $lat])
    //         ->havingNotNull('distance')
    //         ->having('distance', '<=', $radius)
    //         ->orderBy('distance')
    //         ->get();

    //     return response()->json([
    //         'data' => $diagnostics->map(function ($diagnostic) {
    //             return [
    //                 'id'              => $diagnostic->id,
    //                 'name'            => $diagnostic->name ?? null,
    //                 'address'         => $diagnostic->diagnostic_center_address ?? null,
    //                 'area'            => $diagnostic->area ?? null,
    //                 'diagnostic_image' => $diagnostic->logo ? url('storage/diagnostic/' . $diagnostic->logo): null,
    //                 'organization_id' => $diagnostic->organization_id ?? null,
    //                 'zipcode'         => $diagnostic->zipcode ?? null,
    //                 'distance_km'     => round($diagnostic->distance, 2),
    //             ];
    //         }),
    //     ]);
    // }


    public function organizationDiagnosticsCenterLabTests(Request $request){

        $request->validate([
            'id' => 'required|exists:hospitals,id',
        ]);

        try{

            $hospitalId = $request->id;
            $perPage = $request->get('per_page', 12);

            $result = $this->hospitalApiService->getDiagnosticLabTests($hospitalId, $perPage);

            if(!$result){
                return response()->json([
                    'status' => 404,
                    'message' => 'Hospital or Diagnostic center not found',
                    'data' => [],
                    'count' => 0
                ], 404);
            }

            $diagnostic = $result['diagnostic'];
            $labTests = $result['labTests'];

            if ($labTests->total() === 0) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No lab tests found',
                    'diagnostic_center_id' => $diagnostic->id,
                    'diagnostic_image' => $diagnostic->logo ? url('storage/diagnostic/' . $diagnostic->logo) : null,
                    'diagnostic_name' => $diagnostic->name,
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            $labTestsData = $labTests->through(function ($labTest) {
                return [
                    'id'         => $labTest->id,
                    'name'       => $labTest->test_name ?? null,
                    'test_image' => $labTest->test_image ? url('storage/diagnostic-lab-test/' . $labTest->test_image): null,
                    'test_price' => $labTest->test_price,
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => 'Services fetched successfully',
                'diagnostic_center_id' => $diagnostic->id,
                'data' => $labTestsData->items(),
                'current_page' => $labTests->currentPage(),
                'last_page' => $labTests->lastPage(),
                'total' => $labTests->total(),
                'count' => $labTests->count(),
            ], 200);

        }catch(\Throwable $e){
            Log::error('Error fetching diagnostic lab tests', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching diagnostic lab tests',
                'data' => [],
                'count' => 0
            ], 500);
        }

    }

    // public function organizationDiagnosticsLabTestsDetails(Request $request){

    //     $request->validate([
    //         'id' => 'required|exists:diagnostics,id',
    //     ]);

    //     $diagnostic = Diagnostic::findOrFail($request->id);

    //     $labTests = DiagnosticLabTest::where('diagnostic_id', $diagnostic->id)->get();

    //     return response()->json([
    //         'data' => $labTests->map(function($labTest){
    //             return [
    //                 'id' => $labTest->id,
    //                 'test_name' => $labTest->test_name,
    //                 'test_price' => $labTest->test_price,
    //                 'test_image' => $labTest->test_image ? url('storage/diagnostic-lab-test/' . $labTest->test_image): null,
    //                 'diagnostic_id' => $labTest->diagnostic_id,
    //             ];
    //         }),
    //     ]);

    // }


    public function organizationDiagnosticsPackages(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:diagnostics,id',
        ]);

        try{

            $diagnosticId = $request->id;

            $result = $this->hospitalApiService->getDiagnosticPackages($diagnosticId);

            if(!$result){
                return response()->json([
                    'status' => 404,
                    'message' => 'Diagnostic center not found',
                    'data' => [],
                    'count' => 0
                ], 404);
            }

            $diagnostic = $result['diagnostic'];
            $packages = $result['packages'];

            if ($packages->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No packages found',
                    'diagnostic_id' => $diagnostic->id,
                    'diagnostic_image' => $diagnostic->logo ? url('storage/diagnostic/' . $diagnostic->logo) : null,
                    'diagnostic_name' => $diagnostic->name,
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Diagnostic packages fetched successfully',
                'diagnostic_id' => $diagnostic->id,
                'diagnostic_image' => $diagnostic->logo ? url('storage/diagnostic/' . $diagnostic->logo) : null,
                'diagnostic_name' => $diagnostic->name,
                'data' => $packages->map(function ($package) {
                    return [
                        'id'        => $package->id,
                        'name'      => $package->name,
                        'price'     => $package->price,
                        'weight'    => $package->weight,
                        'lab_tests' => $package->lab_tests_list,
                    ];
                }),
                'count' => $packages->count(),
            ], 200);

        }catch(\Throwable $e){
            Log::error('Error fetching diagnostic packages', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching diagnostic packages',
                'data' => [],
                'count' => 0
            ], 500);
        }

    }

    public function hospitalPharmaciesList(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:hospitals,id',
        ]);

        try {
            $hospitalId = $request->id;

            $result = $this->hospitalApiService->getHospitalPharmaciesList($hospitalId);

            if (!$result) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Hospital not found',
                    'data' => [],
                    'count' => 0
                ], 404);
            }

            $pharmacyProducts = $result['pharmacyProducts'];
            $pharmacy = $result['pharmacy'];

            if ($pharmacyProducts->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No pharmacy products found',
                    'pharmacy' => $pharmacy ? [
                        'id' => $pharmacy->id,
                            'name' => $pharmacy->pharmacy_name ?? null,
                            'image' => $pharmacy->pharmacy_logo ? url('storage/pharmacy/' . $pharmacy->pharmacy_logo) : null,
                        ] : null,
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Pharmacy products fetched successfully',
                'pharmacy' => $pharmacy ? [
                    'id' => $pharmacy->id,
                    'name' => $pharmacy->pharmacy_name,
                    'image' => $pharmacy->pharmacy_logo ? url('storage/pharmacy/' . $pharmacy->pharmacy_logo) : null,
                ] : null,
                'data' => $pharmacyProducts->map(function ($pharmacyProduct) {
                    return [
                        'id' => $pharmacyProduct->id,
                        'name' => $pharmacyProduct->product_name,
                        'image' => $pharmacyProduct->product_image
                            ? url('storage/pharmacy/products/' . $pharmacyProduct->product_image)
                            : null,
                        'price' => $pharmacyProduct->selling_price,
                        'description' => $pharmacyProduct->product_description,
                        'pack_size' => $pharmacyProduct->pack_size,
                    ];
                }),
                'current_page' => $pharmacyProducts->currentPage(),
                'last_page' => $pharmacyProducts->lastPage(),
                'total' => $pharmacyProducts->total(),
                'count' => $pharmacyProducts->count(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error fetching pharmacy products', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching pharmacy products',
                'data' => [],
                'count' => 0
            ], 500);
        }
    }

    public function allSpecialitiesList(Request $request){

        $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
        ]);
        
       try{

            $result = $this->hospitalApiService->getAllSpecialitiesList($request->hospital_id);

            $specialities = $result['specialities'];

            if ($specialities->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No specialities found',
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Specialities fetched successfully',
                'data' => $specialities->map(function ($speciality) {
                    return [
                        'id' => $speciality->id,
                        'name' => $speciality->department_category,
                        // 'image' => $speciality->speciality_logo ? url('storage/speciality/' . $speciality->speciality_logo) : null,
                        'speciality_id' => $speciality->speciality_master_id,
                        // 'speciality_master_name' => $speciality->speciality_master_name,
                        'image' => $speciality->speciality_master_image ? url('storage/speciality/' . basename($speciality->speciality_master_image)) : null,
                    ];
                }),
                'count' => $specialities->count(),
        ], 200);

       }catch(\Throwable $e){
        Log::error('Error fetching specialities', ['error' => $e->getMessage()]);
        return response()->json([
            'status' => 500,
            'message' => 'Error fetching specialities',
            'data' => [],
            'count' => 0
        ], 500);
       }

    }


    public function allDoctorsList(Request $request){

        $request->validate([
            'hospital_id' => 'required',
            'speciality_id' => 'required',
        ]);

        try{
            $result = $this->hospitalApiService->getAllDoctorsList($request->hospital_id, $request->speciality_id);

            $doctors = $result['doctors'];

            if ($doctors->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No doctors found',
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Doctors fetched successfully',
                'data' => $doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'doctor_image' => $doctor->doctor_image
                            ? url('storage/doctor/' . $doctor->doctor_image)
                            : null,
                        'qualification_names' => $doctor->qualification_names,
                        'speciality_names' => $doctor->speciality_names,
                        'rating' => '4.5',
                    ];
                }),
                'count' => $doctors->count(),
            ], 200);

        }catch(\Throwable $e){
            Log::error('Error fetching all doctors list', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching all doctors list',
                'data' => [],
                'count' => 0
            ], 500);
        }
    }   

    public function getProceduresList(Request $request)
    {
        $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'speciality_id' => 'required|exists:specialities_masters,id',
        ]);

        try {
            $hospitalId = $request->hospital_id;
            $specialityId = $request->speciality_id;

            // Get all procedure_master_ids for this speciality
            $procedureMasterIds = ProcedureMaster::where('speciality_master_id', $specialityId)
                ->pluck('id')
                ->toArray();

            // Get procedures that belong to this hospital and match the procedure_master_ids
            $procedures = Procedure::where('hospital_id', $hospitalId)
                ->whereIn('procedure_master_id', $procedureMasterIds)
                ->get();

            if ($procedures->isEmpty()) {
                return response()->json([
                    'status' => 200,
                    'message' => 'No procedures found',
                    'data' => [],
                    'count' => 0
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Procedures fetched successfully',
                'data' => $procedures->map(function ($procedure) {
                    return [
                        'id' => $procedure->id,
                        'procedure_name' => $procedure->procedure_name ?? null,
                        'procedure_code' => $procedure->procedure_code ?? null,
                        'description' => $procedure->description ?? null,
                        'cost' => $procedure->cost ?? null,
                        'estimated_time' => $procedure->estimated_time ?? null,
                        'image' => $procedure->image ? url('storage/procedures/' . $procedure->image) : null,
                        'recovery_time' => $procedure->recovery_time ?? null,
                        'success_rate' => $procedure->success_rate ? $procedure->success_rate . ' %' : null,
                        'hospitalization_days' => $procedure->hospitalization_days ? $procedure->hospitalization_days . ' days' : null,
                        'count' => $procedure->count(),
                    ];
                }),
                'count' => $procedures->count(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Error fetching procedures list', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching procedures list',
                'data' => [],
                'count' => 0
            ], 500);
        }
    }

}