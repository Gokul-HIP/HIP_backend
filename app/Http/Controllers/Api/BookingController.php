<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CareGiver;
use App\Models\CaregiverBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Api\BookingApiService;
use App\Services\NotificationService;

class BookingController extends Controller
{
    protected $bookingApiService;

    public function __construct(BookingApiService $bookingApiService){
        $this->bookingApiService = $bookingApiService;
    }

    public function wellnessList(){

        $wellnessCenters = $this->bookingApiService->wellnessList();
        
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

        $wellnessCenter = $this->bookingApiService->wellnessDetails($id);

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
            'message' => 'nullable|string|max:255',
            'procedure_id' => 'required|numeric|exists:procedures,id',
            'hospital_id' => 'required|numeric|exists:hospitals,id',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array|min:1',
            // 'device_id' => 'nullable|string',
        ]);
    
        try{

            $procedureBooking = $this->bookingApiService->procedureBooking($request, $request->user()->id ?? null);

            if(!$procedureBooking){
                return response()->json([
                    'status' => 400,
                    'message' => 'Procedure booking not created',
                ], 400);
            }

            // if ($request->user() && $request->user()->id && $request->device_id) {
            //     try {
            //         $service->sendToDevice(
            //             $request->user()->id,
            //             $request->device_id,
            //             'New Procedure Booking',
            //             'You have a new procedure booking request',
            //             [
            //                 'type' => 'procedure_booking',
            //                 'booking_id' => (string) $procedureBooking->id,
            //                 'route' => '/procedure-detail/' . $procedureBooking->id,
            //             ]
            //         );
            //     } catch (\Throwable $e) {
            //         Log::warning('Failed to send procedure booking notification', [
            //             'error' => $e->getMessage(),
            //             'booking_id' => $procedureBooking->id,
            //         ]);
            //     }
            // }
    
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
            // 'hospital_id' => 'required|numeric|exists:hospitals,id',    
            'doctor_id' => 'required|uuid|exists:doctors,id',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array',
            'purpose' => 'nullable|string|max:255|text',
        ]);

        try{
            $doctorBooking = $this->bookingApiService->doctorBooking($request, $request->user()->id ?? null);

            if(!$doctorBooking){
                return response()->json([
                    'status' => 400,
                    'message' => 'Doctor booking not created',
                ], 400);
            }

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
            'message' => 'nullable|string|max:255',
        ]);

        try{

            $wellnessBooking = $this->bookingApiService->wellnessBooking($request, $request->user()->id ?? null);

            if(!$wellnessBooking){
                return response()->json([
                    'status' => 400,
                    'message' => 'Wellness booking not created',
                ], 400);
            }

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
        $request->validate([
            'type' => 'required|string|in:service,package',
        ]);

        if($request->type == 'service'){

            $request->validate([
                'name' => 'required|string|max:255|min:3',
                'mobile_number' => 'required|digits:10',
                'diagnostic_center_id' => 'required|integer|exists:diagnostics,id',
                'test_items' => 'required|array|min:1',
                'sample_collection' => 'required|string|in:home,lab',
                'booking_date' => 'required|date',
                'required_time_slots' => 'required|array|min:1',
                'message' => 'nullable|string|max:255',
                'device_id' => 'nullable|string',
            ]);

        } elseif($request->type == 'package'){
            
            $request->validate([
                'name' => 'required|string|max:255|min:3',
                'mobile_number' => 'required|digits:10',
                'diagnostic_center_id' => 'required|integer|exists:diagnostics,id',
                'package_id' => 'required|integer|exists:diagnostic_packages,id',
                'booking_date' => 'required|date',
                'sample_collection' => 'required|string|in:home,lab',
                'required_time_slots' => 'required|array|min:1',
                'message' => 'nullable|string|max:255',
                'device_id' => 'nullable|string',
            ]);

        }

        try {

            $diagnosticTestBooking = $this->bookingApiService->diagnosticTestBooking($request, $request->user()->id ?? null, $request->device_id);

            if(!$diagnosticTestBooking){
                return response()->json([
                    'status' => 400,
                    'message' => 'Diagnostic test booking not created',
                ], 400);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Diagnostic test booking created successfully',
                'data' => [
                    'booking_id' => $diagnosticTestBooking->id,
                    'test_type' => $diagnosticTestBooking->test_type
                ],
            ], 200);

        } catch (\Throwable $e) {

            Log::error('Diagnostic test booking creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

    public function stemCellBooking(Request $request){

        if(is_string($request->required_time_slots)){
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
            
            $stemCellBooking = $this->bookingApiService->stemCellBooking($request, $request->user()->id ?? null);

            if(!$stemCellBooking){
                return response()->json([
                    'status' => 400,
                    'message' => 'Stem cell booking not created',
                ], 400);
            }

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

    public function caregiverBooking(Request $request, NotificationService $service){

        $request->validate([
            'name' => 'required|string|max:255|min:3',
            'mobile_number' => 'required|digits:10',
            'caregiver_id' => 'required|integer|exists:care_givers,id',
            // 'wellness_center_id' => 'required|integer|exists:wellness_centres,id',
            'booking_date' => 'required|date',
            'required_time_slots' => 'required|array|min:1',
            'purpose' => 'nullable|string|max:255',
            'device_id' => 'required|string',
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
            
            if ($request->user()->id) {
                $service->sendToDevice(
                    $request->user()->id,
                    $request->device_id,
                    'New Caregiver Booking',
                    'You have a new booking request',
                    [
                        'type' => 'caregiver_booking',
                        'booking_id' => (string) $caregiverBooking->id,
                    ]
                );
            }

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