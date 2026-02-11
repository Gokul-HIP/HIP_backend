<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareGiver;
use App\Models\CaregiverBooking;
use Illuminate\Http\Request;
use App\Models\ProcedureBooking;
use App\Models\WellnessCenters;
use App\Models\DoctorBooking;
use App\Models\WellnessBooking;
use Illuminate\Support\Facades\Log;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticPackage;
use App\Models\StemCellBooking;

class BookingController extends Controller
{

    public function wellnessList(){

        $wellnessCenters = WellnessCenters::with('wellnessCategory')->select('id', 'centre_name', 'centre_type')->paginate(10);
        
        return response()->json([
            'status' => 200,
            'message' => 'Wellness centers fetched successfully',
            'data' => $wellnessCenters->map(function ($center) {
                return [
                    'id' => $center->id,
                    'centre_name' => $center->centre_name,
                    'centre_type' => $center->wellnessCategory->parent_category ?? null,
                ];
            }),
            'count' => $wellnessCenters->count(),
            'page' => $wellnessCenters->currentPage(),
            'limit' => $wellnessCenters->perPage(),
            'total' => $wellnessCenters->total(),
            'total_pages' => ceil($wellnessCenters->total() / $wellnessCenters->perPage()),
        ], 200);

    }

    public function wellnessDetails($id){

        $wellnessCenter = WellnessCenters::with('wellnessCategory')->select('id', 'centre_name', 'centre_type', 'address_line_1', 'address_line_2', 'city', 'state', 'pincode', 'latitude', 'longitude', 'contact_person_name', 'contact_person_mobile', 'contact_person_email', 'centre_website', 'centre_instagram_links', 'centre_facebook_links', 'centre_linkedin_links', 'centre_twitter_links', 'centre_youtube_links')->find($id);

        if(!$wellnessCenter){
            return response()->json([
                'status' => 404,
                'message' => 'Wellness center not found',
            ], 404);
        }
        return response()->json([
            'status' => 200,
            'message' => 'Wellness center details fetched successfully',
            'data' => [
                'wellness_center' => [
                    'id' => $wellnessCenter->id,
                    'centre_name' => $wellnessCenter->centre_name,
                    'centre_type' => $wellnessCenter->wellnessCategory->parent_category ?? null,
                    'address_line_1' => $wellnessCenter->address_line_1,
                    'address_line_2' => $wellnessCenter->address_line_2,
                    'city' => $wellnessCenter->city,
                    'state' => $wellnessCenter->state,
                ],
            ],
        ], 200);
    }
   
    public function procedureBooking(Request $request){

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|numeric|digits:10',
            'message' => 'nullable|string|max:255|',
            'procedure_id' => 'numeric|exists:procedures,id',
            'hospital_id' => 'numeric|exists:hospitals,id',
        ]);
    
        try{
    
            $procedureBooking = ProcedureBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'message' => $request->message,
                'procedure_id' => $request->procedure_id,
                'hospital_id' => $request->hospital_id,
            ]);
    
            return response()->json([
                'status' => 200,
                'message' => 'Procedure booking created successfully',
                'data' => [
                    'booking_id' => $procedureBooking->id,
                ],
            ], 200);
    
        }catch(\Throwable $e){
            Log::error('Procedure booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function doctorBooking(Request $request){

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|numeric|digits:10',
            // 'member_id' => 'required|numeric|exists:healthinpocket_users,id',
            'hospital_id' => 'required|numeric|exists:hospitals,id',    
            'doctor_id' => 'required|numeric|exists:doctors,id',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array',
            'purpose' => 'nullable|string|max:255|text',
        ]);

        try{
            $doctorBooking = DoctorBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $request->user()->id,
                'hospital_id' => $request->hospital_id,
                'doctor_id' => $request->doctor_id,
                'booking_date' => $request->booking_date,
                'required_time_slots' => $request->required_time_slots,    
                'purpose' => $request->purpose,
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Doctor booking created successfully',
                'data' => [
                    'booking_id' => $doctorBooking->id,
                ],
            ], 200);
            
        }catch(\Throwable $e){
            Log::error('Doctor booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function wellnessBooking(Request $request){
        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|numeric|digits:10',
            // 'member_id' => 'required|numeric|exists:healthinpocket_users,id',
            'center_id' => 'required|numeric|exists:wellness_centres,id',
            // 'consultation_type' => 'required|string|in:In-Person,Online',
            'purpose' => 'nullable|string|max:255',
        ]);

        try{
            $wellnessBooking = WellnessBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $request->user()->id ?? null,
                'center_id' => $request->center_id,
                'consultation_type' => "In-Person",
                'purpose' => $request->purpose ?? null,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Wellness booking created successfully',
                'data' => [
                    'booking_id' => $wellnessBooking->id,
                ],
            ], 200);

        }catch(\Throwable $e){
            Log::error('Wellness booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function diagnosticTestBooking(Request $request)
    {
        if($request->test_type == 'single' || $request->test_type == 'multi'){

            $request->validate([
                'name' => 'required|string|max:255|min:3',
                'mobile_number' => 'required|digits:10',
                'diagnostic_center_id' => 'required|integer|exists:diagnostics,id',
                'test_items' => 'required|array|min:1',
                'test_items.*' => 'required|integer|exists:diagnostic_lab_tests,id',
                'test_type' => 'required|string|in:single,multi',
                'sample_collection' => 'required|string|in:home,lab',
                'booking_date' => 'required|date',
                'required_time_slots' => 'required|array|min:1',
                'purpose' => 'nullable|string|max:255',
            ]);

        }

        if($request->test_type == 'package'){
            
            $request->validate([
                'name' => 'required|string|max:255|min:3',
                'mobile_number' => 'required|digits:10',
                'diagnostic_center_id' => 'required|integer|exists:diagnostics,id',
                'package_id' => 'required|integer|exists:diagnostic_packages,id',
                'test_type' => 'required|string|in:package',
                'booking_date' => 'required|date',
                'sample_collection' => 'required|string|in:home,lab',
                'required_time_slots' => 'required|array|min:1',
                'purpose' => 'nullable|string|max:255',
            ]);

        }

        try {

            if($request->test_type == 'single' || $request->test_type == 'multi'){

                // $testItems = $request->test_items;

                // $testType = count($testItems) > 1 ? 'multi' : 'single';

                $diagnosticTestBooking = DiagnosticTestBooking::create([
                    'name' => $request->name,
                    'mobile_number' => $request->mobile_number,
                    'member_id' => $request->user()->id ?? null,
                    'diagnostic_center_id' => $request->diagnostic_center_id,
                    'test_type' => $request->test_type,
                    'test_items' => $request->test_items,
                    'sample_collection' => $request->sample_collection,
                    'booking_date' => $request->booking_date,
                    'required_time_slots' => $request->required_time_slots,
                    'purpose' => $request->purpose,
                    'status' => 'pending',
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Diagnostic test booking created successfully',
                    'data' => [
                        'booking_id' => $diagnosticTestBooking->id,
                        'test_type' => $request->test_type
                    ],
                ], 200);

            }

            if($request->test_type == 'package'){

                $package = DiagnosticPackage::find($request->package_id);

                $testItesms = $package?->lab_tests ?? [];

                if(is_string($testItesms)) {
                    $testItesms = json_decode($testItesms, true);
                }

                if(empty($testItesms)){
                    return response()->json([
                        'status' => 400,
                        'message' => 'Test items not found',
                    ], 400);
                }

                $diagnosticTestBooking = DiagnosticTestBooking::create([
                    'name' => $request->name,
                    'mobile_number' => $request->mobile_number,
                    'member_id' => $request->user()->id ?? null,
                    'diagnostic_center_id' => $request->diagnostic_center_id,
                    'test_type' => $request->test_type,
                    'test_items' => $testItesms,
                    'sample_collection' => $request->sample_collection,
                    'booking_date' => $request->booking_date,
                    'required_time_slots' => $request->required_time_slots,
                    'purpose' => $request->purpose,
                    'status' => 'pending',
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'Diagnostic test booking created successfully',
                    'data' => [
                        'booking_id' => $diagnosticTestBooking->id,
                        'test_type' => $request->test_type
                    ],
                ], 200);
            }

        } catch (\Throwable $e) {

            Log::error('Diagnostic test booking creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function stemCellBooking(Request $request){

        if (is_string($request->required_time_slots)) {
            $request->merge([
                'required_time_slots' => [$request->required_time_slots]
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|digits:10',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array|min:1',
            'purpose' => 'nullable|string|max:255',
        ]);

        try{
            
            $stemCellBooking = StemCellBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $request->user()->id ?? null,
                'booking_date' => $request->booking_date,
                'required_time_slots' => $request->required_time_slots,
                'purpose' => $request->purpose,
                'status' => 'enquiry',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Stem cell booking created successfully',
                'data' => [
                    'booking_id' => $stemCellBooking->id,
                ],
            ], 200);

        }catch(\Throwable $e){
            Log::error('Stem cell booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
        
    }

    public function caregiverBooking(Request $request){

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|digits:10',
            'caregiver_id' => 'required|integer|exists:care_givers,id',
            // 'wellness_center_id' => 'required|integer|exists:wellness_centres,id',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array|min:1',
            'purpose' => 'nullable|string|max:255',
        ]);

        try{
            
            $caregiver = CareGiver::find($request->caregiver_id);

            if(!$caregiver){
                return response()->json([
                    'status' => 400,
                    'message' => 'Caregiver not found',
                ], 400);
            }

            $caregiverBooking = CaregiverBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'member_id' => $request->user()->id ?? null,
                'caregiver_id' => $request->caregiver_id,
                'wellness_center_id' => $caregiver?->wellness_center_id,
                'booking_date' => $request->booking_date,
                'required_time_slots' => $request->required_time_slots,
                'purpose' => $request->purpose,
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Caregiver booking created successfully',
                'data' => [
                    'booking_id' => $caregiverBooking->id,
                ],
            ], 200);

        }catch(\Throwable $e){
            Log::error('Caregiver booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }

    }

}