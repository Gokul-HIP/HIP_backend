<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\DoctorAssignment;
use App\Models\Procedure;
use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use App\Models\Speciality;
use App\Models\DiagnosticPackage;
use App\Models\DoctorReview;
use App\Models\MasterQualification;
use App\Models\Pharmacy;
use App\Models\PharmacyProducts;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Api\HospitalApiService;
use App\Services\AssignDoctorService;
use App\Models\SpecialitiesMaster;
use App\Models\ProcedureMaster;
use App\Models\HospitalReview;
use Carbon\Carbon;

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
            $hospitalRating = (string) round(
                (float) HospitalReview::where('hospital_id', $request->id)
                    ->where('status', 'active')
                    ->avg('rating'),
                1
            ) ?: '0';
        
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
                    'hospital_rating'        => $hospitalRating,
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
                ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                    $q->where('status', 'active');
                }], 'rating')
                ->paginate($perPage);

            $doctors->getCollection()->transform(function ($doctor) {
                $rating = $doctor->rating_avg !== null
                    ? (string) round((float) $doctor->rating_avg, 1)
                    : '0';
                return [
                    'id'               => $doctor->id,
                    'name'             => $doctor->name,
                    'doctor_image'     => $doctor->doctor_image
                        ? url('storage/doctor/' . $doctor->doctor_image)
                        : null,
                    'qualification_names' => $doctor->qualification_names,
                    'speciality_names' => $doctor->speciality_names,
                    'rating'           => $rating,
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
                $rating = $doctor->rating_avg !== null
                    ? (string) round((float) $doctor->rating_avg, 1)
                    : '0';
                return [
                    'id'                  => $doctor->id,
                    'name'                => $doctor->name,
                    'doctor_image'        => $doctor->doctor_image
                                            ? url('storage/doctor/' . $doctor->doctor_image)
                                            : null,
                    'qualification_names' => $doctor->qualification_names,
                    'speciality_names'    => $doctor->speciality_names,
                    'rating'              => $rating,
                    'about'               => $doctor->about_doctor ?? null
                ];
            });
        
            return response()->json([
                'status'  => 200,
                'message' => 'Doctors fetched successfully',
                'data'    => $doctors->items(),
                'per_page' => $doctors->perPage(),
                'current_page'    => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
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

        $hospital = Hospital::find($id);

        if(!$hospital){
            return response()->json([
                'status' => 404,
                'message' => 'Hospital not found',
                'data' => [],
                'count' => 0
            ], 404);
        }

        $avgRating = (string) round(
            (float) HospitalReview::where('hospital_id', $hospital->id)
                ->where('status', 'active')
                ->avg('rating'),
            1
        );

        return response()->json([
            'status' => 200,
            'message' => 'Hospital fetched successfully',
                    'data' => [
               'id' => $hospital->id,
               'hospital_name' => $hospital->name,
               'hospital_about' => $hospital->about,
               'subtitle'      => $hospital->subtitle,
               'hospital_rating' => $avgRating ?: '0',
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
                'per_page' => $labTests->perPage(),
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
                            'name' => $pharmacy->name ?? null,
                            'image' => $pharmacy->logo ? url('storage/pharmacy/' . $pharmacy->logo) : null,
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
                    'name' => $pharmacy->name,
                    'image' => $pharmacy->logo ? url('storage/pharmacy/' . $pharmacy->logo) : null,
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
                'per_page' => $pharmacyProducts->perPage(),
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
            'speciality_id' => 'nullable',
        ]);

        try{
            $result = $this->hospitalApiService->getAllDoctorsList($request->hospital_id, $request->speciality_id ?? 0);

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
                    $rating = $doctor->rating_avg !== null
                        ? (string) round((float) $doctor->rating_avg, 1)
                        : '0';
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->name,
                        'doctor_image' => $doctor->doctor_image
                            ? url('storage/doctor/' . $doctor->doctor_image)
                            : null,
                        'qualification_names' => $doctor->qualification_names,
                        'speciality_names' => $doctor->speciality_names,
                        'rating' => $rating,
                    ];
                }),
                'count' => $doctors->count(),
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
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

    public function doctorDetails(Request $request){

        $request->validate([
            'id' => 'required|integer|exists:doctors,id',
            'month'     => 'nullable|integer|min:1|max:12',
            'year'      => 'nullable|integer|min:2000|max:2100',
        ]);


        $doctor = Doctor::where('id', $request->id)->select('id', 'name', 'doctor_image', 'qualifications', 'speciality', 'about_doctor')->first();

        if (! $doctor) {
            return response()->json([
                'status' => 404,
                'message' => 'Doctor not found',
                'data' => [],
                'count' => 0
            ], 404);
        }

        $reviews = DoctorReview::where('doctor_id', $request->id)->where('status', 'active')->with('member')->paginate(10);
        $reviewsData = $reviews->getCollection()->map(function ($review) {
            $member = $review->member;
            return [
                'id' => $review->id,
                'reviewer_name' => $member ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) : 'Anonymous',
                'reviewer_image' => $member && $member->profile_image ? url('storage/users/' . $member->profile_image) : null,
                'comment' => $review->review,
                'rating' => $review->rating,
                'created_at' => $review->created_at ? $review->created_at->format('d M Y') : '',
            ];
        })->values()->all();

        return response()->json([
            'status' => 200,
            'message' => 'Doctor details fetched successfully',
            'data' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'doctor_image' => $doctor->doctor_image ? url('storage/doctor/' . $doctor->doctor_image) : null,
                'qualifications' => MasterQualification::whereIn('id', (array) ($doctor->qualifications ?? []))->pluck('name')->join(', '),
                'speciality' => SpecialitiesMaster::whereIn('id', (array) ($doctor->speciality ?? []))->pluck('name')->join(', '),
                'about' => $doctor->about_doctor,
                'rating' => (string) (round(
                    (float) DoctorReview::where('doctor_id', $doctor->id)->where('status', 'active')->avg('rating'),
                    1
                ) ?: '0'),
                ],
            'testimonials' => $reviewsData,
            'count' => 1
        ], 200);

    }

    public function allSpecialitiesLists(Request $request)
    {
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $lat    = (float) $request->latitude;
            $lng    = (float) $request->longitude;
            $radius = (int) ($request->radius_km ?? 15);

            $hospitalIds = $this->hospitalApiService->getNearbyHospitalIds($lat, $lng, $radius);

            if (empty($hospitalIds)) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No nearby hospitals found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $specialities = $this->hospitalApiService->getSpecialitiesByHospitalIds($hospitalIds);

            if ($specialities->isEmpty()) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No specialities found for nearby hospitals',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Specialities fetched successfully',
                'hospital_id' => $hospitalIds,
                'data'    => $specialities->map(function ($speciality) {
                    return [
                        'id'            => $speciality->id,
                        'name'          => $speciality->department_category,
                        'speciality_id' => $speciality->speciality_master_id,
                        'image'         => $speciality->speciality_master_image
                            ? url('storage/speciality/' . basename($speciality->speciality_master_image))
                            : null,
                    ];
                }),
                'count' => $specialities->count(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching specialities by location', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching specialities',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

    public function allDoctorsLists(Request $request)
    {
        $request->validate([
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'speciality_id' => 'required|integer',
            'radius_km'     => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $lat           = (float) $request->latitude;
            $lng           = (float) $request->longitude;
            $specialityId  = (int) $request->speciality_id;
            $radius        = (int) ($request->radius_km ?? 15);

            $hospitalIds = $this->hospitalApiService->getNearbyHospitalIds($lat, $lng, $radius);

            if (empty($hospitalIds)) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No nearby hospitals found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $doctorsPaginator = $this->hospitalApiService->getDoctorsByHospitalIdsAndSpeciality($hospitalIds, $specialityId);

            if ($doctorsPaginator->isEmpty()) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No doctors found for this speciality in nearby hospitals',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $data = collect($doctorsPaginator->items())->map(function ($doctor) {
                $rating = $doctor->rating_avg !== null
                    ? (string) round((float) $doctor->rating_avg, 1)
                    : '0';
                return [
                    'id'                  => $doctor->id,
                    'name'                => $doctor->name,
                    'doctor_image'        => $doctor->doctor_image
                        ? url('storage/doctor/' . $doctor->doctor_image)
                        : null,
                    'qualification_names' => $doctor->qualification_names,
                    'speciality_names'    => $doctor->speciality_names,
                    'rating'              => $rating,
                ];
            })->values()->all();

            return response()->json([
                'status'        => 200,
                'message'       => 'Doctors fetched successfully',
                'data'          => $data,
                'count'         => count($data),
                'current_page'  => $doctorsPaginator->currentPage(),
                'last_page'     => $doctorsPaginator->lastPage(),
                'per_page'      => $doctorsPaginator->perPage(),
                'total'         => $doctorsPaginator->total(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching doctors list by location', [
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            $response = [
                'status'  => 500,
                'message' => 'Error fetching doctors list',
                'data'    => [],
                'count'   => 0,
            ];
            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => $e->getMessage(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                ];
            }
            return response()->json($response, 500);
        }
    }

    // public function appointmentCalendar()
    // {
    //     $today = Carbon::now();
    //     $daysInMonth = $today->daysInMonth;

    //     $days = [];

    //     for ($i = 1; $i <= $daysInMonth; $i++) {
    //         $date = Carbon::create($today->year, $today->month, $i);

    //         $days[] = [
    //             'date'        => $date->format('Y-m-d'),
    //             'day'         => $date->format('D'),
    //             'day_number'  => $date->day,
    //         ];
    //     }

    //     return response()->json([
    //         'status' => 200,
    //         'month'  => $today->format('F'),
    //         'year'   => $today->year,
    //         'days'   => $days,
    //     ]);
    // }

    /**
     * Return a month view of a doctor's schedule.
     *
     * Request params:
     * - doctor_id (required)
     * - month (optional: 1–12, defaults to current month)
     * - year  (optional: 4‑digit, defaults to current year)
     *
     * Response:
     * {
     *   status: 200,
     *   doctor_id: 1,
     *   month: "March",
     *   year: 2026,
     *   days: [
     *     {
     *       date: "2026-03-01",
     *       day: "Sun",
     *       day_number: 1,
     *       has_schedule: true,
     *       time_slots: [
     *         { from: "10:00", to: "12:00" },
     *         ...
     *       ]
     *     },
     *     ...
     *   ]
     * }
     */
    public function doctorAppointmentCalendar(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'   => ['required', 'integer', 'exists:doctors,id'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'month'       => ['nullable', 'integer', 'min:1', 'max:12'],
            'year'        => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $month = (int) ($validated['month'] ?? now()->month);
        $year  = (int) ($validated['year'] ?? now()->year);

        $firstOfMonth = Carbon::create($year, $month, 1);
        $daysInMonth  = $firstOfMonth->daysInMonth;

        $doctorId   = (int) $validated['doctor_id'];
        $hospitalId = isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null;

        // Build weekday -> slots from doctor_assignments (one row per doctor or doctor+hospital)
        $slotsByWeekday = $this->buildSlotsByWeekdayFromDoctorAssignmentsRaw($doctorId, $hospitalId);
        $hasSlotsFromAssignments = collect($slotsByWeekday)->contains(fn ($slots) => ! empty($slots));

        // Fallback: if no slots from doctor_assignments, use doctor_schedules (date-specific) for this month
        $schedulesByDate = collect();
        if (! $hasSlotsFromAssignments) {
            $schedulesByDate = DoctorSchedule::query()
                ->where('doctor_id', $doctorId)
                ->whereYear('schedule_date', $year)
                ->whereMonth('schedule_date', $month)
                ->get()
                ->groupBy(fn (DoctorSchedule $s) => $s->schedule_date?->format('Y-m-d'));
        }

        $days = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::create($year, $month, $i);
            $dateKey = $date->format('Y-m-d');

            if ($hasSlotsFromAssignments) {
                // Get weekday from calendar date (e.g. "Monday") and show slots stored for that day
                $weekdayName = $date->format('l');
                $slots = $slotsByWeekday[$weekdayName] ?? [];
            } else {
                $schedule = $schedulesByDate->get($dateKey)?->first();
                $slots = $this->normalizeScheduleSlots($schedule?->time_slots ?? []);
            }

            $days[] = [
                'date'         => $dateKey,
                'day'          => $date->format('D'),
                'day_number'   => $date->day,
                'has_schedule' => ! empty($slots),
                'time_slots'   => $slots,
            ];
        }

        return response()->json([
            'status'    => 200,
            'doctor_id' => (int) $validated['doctor_id'],
            'month'     => $firstOfMonth->format('F'),
            'year'      => $year,
            'days'      => $days,
        ]);
    }

    /**
     * Return all time slots for a doctor on a specific date.
     * Uses doctor_assignments.time_slots (recurring by weekday).
     *
     * Request params:
     * - doctor_id (required)
     * - date (required, Y-m-d)
     */
    public function doctorScheduleForDate(Request $request)
    {
        $validated = $request->validate([
            'doctor_id'   => ['required', 'integer', 'exists:doctors,id'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
            'date'        => ['required', 'date_format:Y-m-d'],
        ]);

        $doctorId   = (int) $validated['doctor_id'];
        $hospitalId = isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null;
        $date       = $validated['date'];

        $slotsByWeekday = $this->buildSlotsByWeekdayFromDoctorAssignmentsRaw($doctorId, $hospitalId);
        $weekdayName = Carbon::parse($date)->format('l');
        $slots = $slotsByWeekday[$weekdayName] ?? [];

        // Fallback: if no slots from assignments, use doctor_schedules for this date
        if (empty($slots)) {
            $schedule = DoctorSchedule::query()
                ->where('doctor_id', $doctorId)
                ->whereDate('schedule_date', $date)
                ->first();
            $slots = $this->normalizeScheduleSlots($schedule?->time_slots ?? []);
        }

        return response()->json([
            'status'      => 200,
            'doctor_id'   => $doctorId,
            'date'        => $date,
            'hasSchedule' => ! empty($slots),
            'time_slots'  => $slots,
        ]);
    }

    /**
     * Normalize time_slots from DoctorSchedule (from/to) or mixed format to ['from' => x, 'to' => y].
     */
    private function normalizeScheduleSlots(array $rawSlots): array
    {
        $out = [];
        foreach ($rawSlots as $slot) {
            $slot = is_array($slot) ? $slot : (array) $slot;
            $from = (string) ($slot['from'] ?? $slot['start'] ?? '');
            $to   = (string) ($slot['to'] ?? $slot['end'] ?? '');
            if ($from !== '' && $to !== '') {
                $out[] = ['from' => $from, 'to' => $to];
            }
        }
        return $out;
    }

    /** Map short or variant day names to canonical full weekday name. */
    private function normalizeWeekdayName(string $day): string
    {
        $day = ucfirst(strtolower(trim($day)));
        $map = [
            'Mon' => 'Monday', 'Monday' => 'Monday',
            'Tue' => 'Tuesday', 'Tues' => 'Tuesday', 'Tuesday' => 'Tuesday',
            'Wed' => 'Wednesday', 'Wednesday' => 'Wednesday',
            'Thu' => 'Thursday', 'Thur' => 'Thursday', 'Thurs' => 'Thursday', 'Thursday' => 'Thursday',
            'Fri' => 'Friday', 'Friday' => 'Friday',
            'Sat' => 'Saturday', 'Saturday' => 'Saturday',
            'Sun' => 'Sunday', 'Sunday' => 'Sunday',
        ];
        return $map[$day] ?? $day;
    }

    /**
     * Build weekday name => slots from doctor_assignments using raw DB query.
     * When multiple rows exist for the same doctor (and hospital), merge time_slots from all rows:
     * for each day, include every unique time slot from any row so the response has a single combined schedule.
     *
     * @param  int  $doctorId
     * @param  int|null  $hospitalId  When provided, only use assignments for this doctor at this hospital.
     */
    private function buildSlotsByWeekdayFromDoctorAssignmentsRaw(int $doctorId, ?int $hospitalId = null): array
    {
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $slotsByWeekday = array_fill_keys($weekdays, []);

        $query = DB::table('doctor_assignments')->where('doctor_id', $doctorId);
        if ($hospitalId !== null) {
            $query->where('hospital_id', $hospitalId);
        }
        $rows = $query->orderByDesc('updated_at')->orderByDesc('id')->get(['id', 'time_slots']);

        // Merge time_slots from all rows: for each day, add every slot from every row (missing in one row is added from another)
        foreach ($rows as $r) {
            $rawSlots = $r->time_slots;
            if (is_string($rawSlots)) {
                $rawSlots = json_decode($rawSlots, true);
            }
            if (! is_array($rawSlots)) {
                continue;
            }
            foreach ($rawSlots as $slot) {
                $slot = is_array($slot) ? $slot : (array) $slot;
                $dayRaw = $slot['day'] ?? null;
                if ($dayRaw === null || $dayRaw === '') {
                    continue;
                }
                $day = $this->normalizeWeekdayName((string) $dayRaw);
                if (! isset($slotsByWeekday[$day])) {
                    continue;
                }
                $from = (string) ($slot['start'] ?? $slot['from'] ?? '');
                $to   = (string) ($slot['end'] ?? $slot['to'] ?? '');
                $from = $this->ensureAmPmFormat($from);
                $to   = $this->ensureAmPmFormat($to);
                if ($from !== '') {
                    $slotsByWeekday[$day][] = ['from' => $from, 'to' => $to];
                }
            }
        }

        // Deduplicate per day by from|to so the same slot from multiple rows appears once
        foreach ($slotsByWeekday as $day => $slots) {
            $seen = [];
            $slotsByWeekday[$day] = array_values(array_filter($slots, function (array $s) use (&$seen) {
                $key = $s['from'] . '|' . $s['to'];
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            }));
        }

        return $slotsByWeekday;
    }

    /** Ensure time string is in AM/PM format for API response; convert from 24h if needed. */
    private function ensureAmPmFormat(string $time): string
    {
        $time = trim($time);
        if ($time === '') {
            return '';
        }
        if (preg_match('/\s*(AM|PM)\s*$/i', $time)) {
            return $time;
        }
        return AssignDoctorService::timeToAmPm($time);
    }

    /**
     * Build weekday name => slots array from Eloquent DoctorAssignment collection (used when you already have models).
     */
    private function buildSlotsByWeekdayFromAssignments($assignments): array
    {
        $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $slotsByWeekday = array_fill_keys($weekdays, []);

        foreach ($assignments as $assignment) {
            $rawSlots = $assignment->getRawOriginal('time_slots');
            if (is_string($rawSlots)) {
                $rawSlots = json_decode($rawSlots, true);
            }
            if (! is_array($rawSlots)) {
                $rawSlots = $assignment->time_slots;
            }
            if (! is_array($rawSlots)) {
                continue;
            }

            foreach ($rawSlots as $slot) {
                $slot = is_array($slot) ? $slot : (array) $slot;
                $day = $slot['day'] ?? null;
                if ($day === null || $day === '') {
                    continue;
                }
                $day = ucfirst(strtolower(trim((string) $day)));
                if (! isset($slotsByWeekday[$day])) {
                    continue;
                }
                $from = (string) ($slot['start'] ?? $slot['from'] ?? '');
                $to   = (string) ($slot['end'] ?? $slot['to'] ?? '');
                $from = $this->ensureAmPmFormat($from);
                $to   = $this->ensureAmPmFormat($to);
                if ($from !== '' && $to !== '') {
                    $slotsByWeekday[$day][] = ['from' => $from, 'to' => $to];
                }
            }
        }

        foreach ($slotsByWeekday as $day => $slots) {
            $seen = [];
            $slotsByWeekday[$day] = array_values(array_filter($slots, function (array $s) use (&$seen) {
                $key = $s['from'] . '|' . $s['to'];
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            }));
        }

        return $slotsByWeekday;
    }

    /**
     * Search hospitals by name/address/city/area etc. within a radius of the given lat/lng.
     * Uses the same nearby-hospital logic as LocationFilter::byLocation (Haversine distance).
     */
    public function hospitalSearch(Request $request)
    {
        $request->validate([
            'search'     => 'required|string',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'radius_km'  => 'nullable|integer|min:1|max:100',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $search  = trim($request->search);
        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        if (strlen($search) < 2) {
            return response()->json([
                'status'   => 200,
                'message'  => 'Search term must be at least 2 characters.',
                'hospitals' => [],
                'current_page' => 1,
                'per_page' => $perPage,
                'total'     => 0,
                'last_page' => 1,
            ]);
        }

        $searchLike = '%' . $search . '%';

        $hospitals = Hospital::query()
            ->leftJoin('location_masters as lm', 'lm.id', '=', 'hospitals.location_id')
            ->leftJoinSub(
                HospitalReview::query()
                    ->selectRaw('hospital_id, ROUND(AVG(rating), 1) as avg_rating')
                    ->where('status', 'active')
                    ->groupBy('hospital_id'),
                'hr',
                'hr.hospital_id',
                '=',
                'hospitals.id'
            )
            ->selectRaw("
                hospitals.*,
                COALESCE(lm.area, 'Unknown Area') as area,
                lm.zipcode,
                COALESCE(hr.avg_rating, 0) as hospital_rating,

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
            ->where(function ($query) use ($searchLike) {
                $query->where('hospitals.name', 'like', $searchLike);
            })
            ->whereRaw("
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
                )) <= ?
            ", [$lat, $lng, $lat, $radius])
            ->orderBy('distance')
            ->paginate($perPage);

        $data = $hospitals->getCollection()->map(function ($hospital) {
            return [
                'id'              => $hospital->id,
                'hospital_name'   => $hospital->name,
                // 'hospital_about'  => $hospital->about,
                'subtitle'        => $hospital->subtitle,
                // 'address'         => $hospital->address,
                // 'city'            => $hospital->city,
                // 'area'            => $hospital->area ?? 'Unknown Area',
                // 'distance_km'     => round((float) ($hospital->distance ?? 0), 2),
                'hospital_rating' => (string) ($hospital->hospital_rating ?? '0'),
                'logo'            => $hospital->logo ? url('storage/hospital/' . $hospital->logo) : null,
                'is_promoted'     => $hospital->is_promoted,
            ];
        });

        return response()->json([
            'status'       => 200,
            'message'      => 'Nearby hospitals matching search.',
            'hospitals'    => $data,
            'current_page' => $hospitals->currentPage(),
            'per_page'     => $hospitals->perPage(),
            'total'        => $hospitals->total(),
            'last_page'    => $hospitals->lastPage(),
        ]);
    }

    public function doctorSearch(Request $request)
    {
        $request->validate([
            'search'        => 'required|string',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'speciality_id' => 'nullable|integer|exists:specialities_masters,id',
            'radius_km'     => 'nullable|integer|min:1|max:100',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $search = trim($request->search);

        if (strlen($search) < 2) {
            return response()->json([
                'status'  => 200,
                'message' => 'Search term must be at least 2 characters.',
                'data'    => [],
                'count'   => 0,
            ], 200);
        }

        try {
            $lat          = (float) $request->latitude;
            $lng          = (float) $request->longitude;
            $radius       = (int) ($request->radius_km ?? 15);
            $perPage      = (int) ($request->per_page ?? 10);
            $specialityId = $request->speciality_id ? (int) $request->speciality_id : null;
            $searchLike   = '%' . $search . '%';

            // Get nearby hospitals (same logic as allDoctorsLists)
            $hospitalIds = $this->hospitalApiService->getNearbyHospitalIds($lat, $lng, $radius);

            if (empty($hospitalIds)) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No nearby hospitals found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            // Build doctor query: by nearby hospital, optional speciality, and search
            $placeholders     = implode(' OR ', array_fill(0, count($hospitalIds), 'JSON_CONTAINS(hospital_ids, ?)'));
            $hospitalBindings = array_map(fn ($id) => json_encode((int) $id), $hospitalIds);

            $query = Doctor::query()
                ->select('id', 'name', 'doctor_image', 'qualifications', 'speciality')
                ->withAvg(['doctorReviews as rating_avg' => function ($q) {
                    $q->where('status', 'active');
                }], 'rating')
                ->where('status', 'active')
                ->whereRaw("({$placeholders})", $hospitalBindings)
                ->where(function ($q) use ($searchLike) {
                    $q->where('name', 'like', $searchLike);
                });

            if ($specialityId) {
                $specialityAsNumber = json_encode($specialityId);
                $specialityAsString = json_encode((string) $specialityId);

                $query->whereRaw(
                    '(JSON_CONTAINS(speciality, ?) OR JSON_CONTAINS(speciality, ?))',
                    [$specialityAsNumber, $specialityAsString]
                );
            }

            $doctors = $query
                ->orderBy('name')
                ->paginate($perPage);

            if ($doctors->total() === 0) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'No doctors found',
                    'data'    => [],
                    'count'   => 0,
                ], 200);
            }

            $doctors->getCollection()->transform(function ($doctor) {
                $rating = $doctor->rating_avg !== null
                    ? (string) round((float) $doctor->rating_avg, 1)
                    : '0';

                return [
                    'id'                  => $doctor->id,
                    'name'                => $doctor->name,
                    'doctor_image'        => $doctor->doctor_image
                        ? url('storage/doctor/' . $doctor->doctor_image)
                        : null,
                    'qualification_names' => $doctor->qualification_names ?? null,
                    'speciality_names'    => $doctor->speciality_names ?? null,
                    'rating'              => $rating,
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
        } catch (\Throwable $e) {
            Log::error('Error searching doctors', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error searching doctors',
                'data'    => [],
                'count'   => 0,
            ], 500);
        }
    }

    public function diagnosticCenterList(Request $request){

        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        try {
            $centres = Diagnostic::query()
                ->where('status', 'active')
                ->whereNotNull('contact_person_latitude')
                ->whereNotNull('contact_person_longitude')
                ->selectRaw("
                    diagnostics.*,
                    (6371 * acos(
                        cos(radians(?))
                        * cos(radians(contact_person_latitude))
                        * cos(radians(contact_person_longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(contact_person_latitude))
                    )) AS distance
                ", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->paginate($perPage);

            $data = $centres->getCollection()->map(function ($centre) {
                return [
                    'id'           => (int) $centre->id,
                    'name'         => $centre->name,
                    // 'address'      => $centre->address,
                    // 'distance_km'  => round((float) ($centre->distance ?? 0), 2),
                    'logo'         => $centre->logo ? url('storage/diagnostics/' . $centre->logo) : null,
                ];
            });

            return response()->json([
                'status'     => 200,
                'message'    => 'Nearby diagnostic centers fetched successfully',
                'data'       => $data,
                'pagination' => [
                    'current_page' => $centres->currentPage(),
                    'per_page'     => $centres->perPage(),
                    'total'        => $centres->total(),
                    'last_page'    => $centres->lastPage(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching diagnostic centers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching diagnostic centers',
                'data'    => [],
            ], 500);
        }
    }

    public function diagnosticCenterDetails(Request $request, $id)
    {
        $diagnosticCenter = Diagnostic::find($id);

        if (! $diagnosticCenter) {
            return response()->json([
                'status' => 404,
                'message' => 'Diagnostic center not found',
                'data' => [],
            ], 404);
        }

        $labTestsPage = max(1, (int) $request->input('lab_tests_page', 1));
        $labTestsPerPage = max(1, min(100, (int) $request->input('lab_tests_per_page', 10)));
        $packagesPage = max(1, (int) $request->input('packages_page', 1));
        $packagesPerPage = max(1, min(100, (int) $request->input('packages_per_page', 10)));

        $labTestsTransform = function ($test) {
            return [
                'id' => (int) $test->id,
                'test_name' => $test->test_name,
                'test_code' => $test->test_code ?? null,
                'test_description' => $test->test_description ?? null,
                'test_price' => (float) ($test->test_price ?? 0),
                'test_discount' => (float) ($test->test_discount ?? 0),
                'test_image' => $test->test_image ? url('storage/diagnostics/' . $test->test_image) : null,
                'test_status' => $test->test_status ?? null,
                'test_category' => $test->test_category ?? null,
            ];
        };

        $labTestsPaginator = DiagnosticLabTest::where('diagnostic_id', $diagnosticCenter->id)
            ->paginate($labTestsPerPage, ['*'], 'page', $labTestsPage);
        $labTestsPaginator->getCollection()->transform($labTestsTransform);
        $labTests = $labTestsPaginator->getCollection()->values()->all();

        $allLabTests = DiagnosticLabTest::where('diagnostic_id', $diagnosticCenter->id)->get()->map($labTestsTransform)->keyBy('id');

        $packagesPaginator = DiagnosticPackage::where('diagnostic_id', $diagnosticCenter->id)
            ->paginate($packagesPerPage, ['*'], 'page', $packagesPage);
        $packagesPaginator->getCollection()->transform(function ($pkg) use ($allLabTests) {
            $labTestIds = $this->normalizePackageLabTests($pkg->lab_tests);
            $labTestsDetails = collect($labTestIds)
                ->map(fn ($testId) => $allLabTests->get($testId))
                ->filter()
                ->values()
                ->select('id', 'test_name');

            return [
                'id' => (int) $pkg->id,
                'name' => $pkg->name,
                'code' => $pkg->code ?? null,
                'description' => $pkg->description ?? null,
                'price' => (float) ($pkg->price ?? 0),
                'discount' => (float) ($pkg->discount ?? 0),
                'image' => $pkg->image ? url('storage/diagnostics/' . $pkg->image) : null,
                'status' => $pkg->status ?? null,
                // 'lab_tests' => $labTestIds,
                'lab_tests' => $labTestsDetails,
                // 'lab_tests_details' => $labTestsDetails,
            ];
        });
        $packages = $packagesPaginator->getCollection()->values()->all();

        return response()->json([
            'status' => 200,
            'message' => 'Diagnostic center details fetched successfully',
            'data' => [
                'id' => $diagnosticCenter->id,
                'name' => $diagnosticCenter->name,
                'address' => $diagnosticCenter->address ?? null,
                'logo' => $diagnosticCenter->logo ? url('storage/diagnostics/' . $diagnosticCenter->logo) : null,
                'lab_tests' => $labTests,
                'packages' => $packages,
            ],
            'count' => 1,
            'lab_tests_pagination' => [
                'current_page' => $labTestsPaginator->currentPage(),
                'per_page' => $labTestsPaginator->perPage(),
                'total' => $labTestsPaginator->total(),
                'last_page' => $labTestsPaginator->lastPage(),
            ],
            'packages_pagination' => [
                'current_page' => $packagesPaginator->currentPage(),
                'per_page' => $packagesPaginator->perPage(),
                'total' => $packagesPaginator->total(),
                'last_page' => $packagesPaginator->lastPage(),
            ],
        ], 200);
    }

    /**
     * Normalize package lab_tests to a flat array of integer ids.
     * Handles values as stored in DB: "[56,57,58,59]" (JSON string), or array [56,57,58,59],
     * or mixed ["[109]"], ["[112,113,114,115]"] from double-encoded storage.
     */
    private function normalizePackageLabTests(mixed $labTests): array
    {
        if (is_string($labTests)) {
            $labTests = json_decode(trim($labTests), true);
        }
        if (! is_array($labTests)) {
            return [];
        }
        $ids = [];
        foreach ($labTests as $item) {
            if (is_int($item) || (is_string($item) && is_numeric(trim((string) $item)))) {
                $ids[] = (int) $item;
                continue;
            }
            if (is_string($item)) {
                $decoded = json_decode(trim($item), true);
                if (is_array($decoded)) {
                    foreach ($decoded as $id) {
                        $ids[] = (int) $id;
                    }
                }
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Search diagnostic centers by name/address within a radius of the given lat/lng.
     */
    public function diagnosticCenterSearch(Request $request)
    {
        $request->validate([
            'search'    => 'required|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $search  = trim($request->search);
        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        if (strlen($search) < 2) {
            return response()->json([
                'status'  => 200,
                'message' => 'Search term must be at least 2 characters.',
                'data'    => [],
                'pagination' => ['current_page' => 1, 'per_page' => $perPage, 'total' => 0, 'last_page' => 1],
            ], 200);
        }

        $searchLike = '%' . $search . '%';

        try {
            $centres = Diagnostic::query()
                ->where('diagnostics.status', 'active')
                ->whereNotNull('diagnostics.contact_person_latitude')
                ->whereNotNull('diagnostics.contact_person_longitude')
                ->where(function ($query) use ($searchLike) {
                    $query->where('diagnostics.name', 'like', $searchLike)
                        ->orWhere('diagnostics.address', 'like', $searchLike);
                })
                ->selectRaw("
                    diagnostics.*,
                    (6371 * acos(
                        cos(radians(?))
                        * cos(radians(diagnostics.contact_person_latitude))
                        * cos(radians(diagnostics.contact_person_longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(diagnostics.contact_person_latitude))
                    )) AS distance
                ", [$lat, $lng, $lat])
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->paginate($perPage);

            $data = $centres->getCollection()->map(function ($centre) {
                return [
                    'id'          => (int) $centre->id,
                    'name'        => $centre->name,
                    // 'address'     => $centre->address ?? null,
                    // 'distance_km' => round((float) ($centre->distance ?? 0), 2),
                    'logo'        => $centre->logo ? url('storage/diagnostics/' . $centre->logo) : null,
                ];
            });

            return response()->json([
                'status'     => 200,
                'message'    => 'Diagnostic centers matching search.',
                'data'       => $data,
                'pagination' => [
                    'current_page' => $centres->currentPage(),
                    'per_page'     => $centres->perPage(),
                    'total'        => $centres->total(),
                    'last_page'    => $centres->lastPage(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error in diagnostic center search', ['error' => $e->getMessage(), 'search' => $search]);
            return response()->json([
                'status'     => 500,
                'message'    => 'Error searching diagnostic centers',
                'data'       => [],
                'pagination' => ['current_page' => 1, 'per_page' => $perPage, 'total' => 0, 'last_page' => 1],
            ], 500);
        }
    }

    public function pharmacyList(Request $request){
        $request->validate([
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        try {
            // 1) Get nearby hospital IDs based on location and radius
            $hospitalIds = $this->hospitalApiService->getNearbyHospitalIds($lat, $lng, $radius);

            if (empty($hospitalIds)) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No nearby hospitals found',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            // 2) Collect all linked pharmacy IDs from those hospitals
            $pharmacyIdCollection = Hospital::whereIn('id', $hospitalIds)
                ->whereNotNull('pharmacy_ids')
                ->pluck('pharmacy_ids')
                ->filter()
                ->flatMap(function ($ids) {
                    return is_array($ids) ? $ids : [];
                })
                ->unique()
                ->values();

            if ($pharmacyIdCollection->isEmpty()) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No pharmacies linked to nearby hospitals',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            // 3) Fetch pharmacies and paginate
            $pharmacies = Pharmacy::whereIn('id', $pharmacyIdCollection)
                ->where('status', 'active')
                ->paginate($perPage);

            if ($pharmacies->isEmpty()) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No pharmacies found',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            $data = $pharmacies->getCollection()->map(function ($pharmacy) {
                return [
                    'id'      => (int) $pharmacy->id,
                    'name'    => $pharmacy->name,
                    'address' => $pharmacy->address ?? null,
                    'logo'    => $pharmacy->logo ? url('storage/pharmacy/' . $pharmacy->logo) : null,
                ];
            });

            return response()->json([
                'status'     => 200,
                'message'    => 'Nearby pharmacies fetched successfully',
                'data'       => $data,
                'pagination' => [
                    'current_page' => $pharmacies->currentPage(),
                    'per_page'     => $pharmacies->perPage(),
                    'total'        => $pharmacies->total(),
                    'last_page'    => $pharmacies->lastPage(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching nearby pharmacies', [
                'error' => $e->getMessage(),
                'lat'   => $lat,
                'lng'   => $lng,
            ]);

            return response()->json([
                'status'     => 500,
                'message'    => 'Error fetching nearby pharmacies',
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ], 500);
        }
    }

    public function pharmacySearch(Request $request)
    {
        $request->validate([
            'search'    => 'required|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius_km' => 'nullable|integer|min:1|max:100',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $search  = trim($request->search);
        $lat     = (float) $request->latitude;
        $lng     = (float) $request->longitude;
        $radius  = (int) ($request->radius_km ?? 15);
        $perPage = (int) ($request->per_page ?? 10);

        if (strlen($search) < 2) {
            return response()->json([
                'status'     => 200,
                'message'    => 'Search term must be at least 2 characters.',
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ], 200);
        }

        $searchLike = '%' . $search . '%';

        try {
            // 1) Nearby hospitals by lat/lng
            $hospitalIds = $this->hospitalApiService->getNearbyHospitalIds($lat, $lng, $radius);

            if (empty($hospitalIds)) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No nearby hospitals found',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            // 2) Collect linked pharmacy IDs from those hospitals
            $pharmacyIdCollection = Hospital::whereIn('id', $hospitalIds)
                ->whereNotNull('pharmacy_ids')
                ->pluck('pharmacy_ids')
                ->filter()
                ->flatMap(function ($ids) {
                    return is_array($ids) ? $ids : [];
                })
                ->unique()
                ->values();

            if ($pharmacyIdCollection->isEmpty()) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No pharmacies linked to nearby hospitals',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            // 3) Search pharmacies by name/address within those IDs
            $pharmacies = Pharmacy::query()
                ->whereIn('id', $pharmacyIdCollection)
                ->where('status', 'active')
                ->where(function ($query) use ($searchLike) {
                    $query->where('name', 'like', $searchLike)
                        ->orWhere('address', 'like', $searchLike);
                })
                ->paginate($perPage);

            if ($pharmacies->isEmpty()) {
                return response()->json([
                    'status'     => 200,
                    'message'    => 'No pharmacies found for this search in nearby hospitals',
                    'data'       => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page'     => $perPage,
                        'total'        => 0,
                        'last_page'    => 1,
                    ],
                ], 200);
            }

            $data = $pharmacies->getCollection()->map(function ($pharmacy) {
                return [
                    'id'      => (int) $pharmacy->id,
                    'name'    => $pharmacy->name,
                    'address' => $pharmacy->address ?? null,
                    'logo'    => $pharmacy->logo ? url('storage/pharmacy/' . $pharmacy->logo) : null,
                ];
            });

            return response()->json([
                'status'     => 200,
                'message'    => 'Pharmacies matching search in nearby hospitals.',
                'data'       => $data,
                'pagination' => [
                    'current_page' => $pharmacies->currentPage(),
                    'per_page'     => $pharmacies->perPage(),
                    'total'        => $pharmacies->total(),
                    'last_page'    => $pharmacies->lastPage(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error in pharmacy search', [
                'error' => $e->getMessage(),
                'search' => $search,
                'lat'    => $lat,
                'lng'    => $lng,
            ]);

            return response()->json([
                'status'     => 500,
                'message'    => 'Error searching pharmacies',
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page'     => $perPage,
                    'total'        => 0,
                    'last_page'    => 1,
                ],
            ], 500);
        }
    }

    public function pharmacyDetails(Request $request, $id){

        $pharmacy = Pharmacy::find($id);

        if(!$pharmacy){
            return response()->json([
                'status' => 404,
                'message' => 'Pharmacy not found',
            ], 404);
        }
        
        $pharmacyProducts = PharmacyProducts::where('pharmacy_id', $id)->paginate(10);

        return response()->json([
            'status' => 200,
            'message' => 'Pharmacy details fetched successfully',
            'data' => [
                'id' => $pharmacy->id,
                'name' => $pharmacy->name,
                'address' => $pharmacy->address ?? null,
            ],
            'pharmacy_products' =>  $pharmacyProducts->getCollection()->map(function ($pharmacyProduct) {
                    return [
                        'id' => $pharmacyProduct->id,
                        'name' => $pharmacyProduct->product_name,
                        'image' => $pharmacyProduct->product_image ? url('storage/pharmacy/products/' . $pharmacyProduct->product_image) : null,
                        'price' => $pharmacyProduct->selling_price,
                        'description' => $pharmacyProduct->product_description,
                        'pack_size' => $pharmacyProduct->pack_size,
                    ];
                }),
                'current_page' => $pharmacyProducts->currentPage(),
                'per_page' => $pharmacyProducts->perPage(),
                'total' => $pharmacyProducts->total(),
                'last_page' => $pharmacyProducts->lastPage(),
                'count' => $pharmacyProducts->count(),
            ], 200);

    }

}