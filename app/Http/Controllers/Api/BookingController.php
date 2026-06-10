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

    public function doctorBooking(Request $request)
    {
        $request->merge([
            'patient_id' => $request->input('patient_id', $request->input('member_id')),
            'branch_id'  => $request->input('branch_id', $request->input('hospital_id')),
        ]);

        $request->validate([
            'patient_id'          => 'required|uuid',
            'doctor_id'           => 'required|uuid|exists:doctors,id',
            'branch_id'           => 'required|integer|exists:hospitals,id',
            'appointment_type'    => 'required|string|max:50',
            'is_coins_applied'    => 'nullable|boolean',
            'is_online_payment'   => 'nullable|boolean',
            'coins_used'          => 'nullable|integer|min:0',
            'department_id'       => 'nullable|integer|exists:specialities_masters,id',
            'booking_date'        => 'required|date|after_or_equal:today',
            'required_time_slots' => 'required|array|min:1',
            'reason_of_visit'     => 'nullable|string|max:255',
            'message'             => 'nullable|string|max:2000',
            'purpose'             => 'nullable|string|max:255',
            'device_id'           => 'nullable|string',
            'is_follow_up'        => 'nullable|boolean'
        ]);

        try {
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Unauthenticated. Please login and send Authorization: Bearer {token}.',
                    'data'    => [],
                ], 401);
            }

            $result = $this->bookingApiService->doctorBooking(
                $request,
                $authUser->id
            );

            $doctorBooking = $result['booking'] ?? null;

            if (! $doctorBooking) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Doctor booking not created',
                    'data'    => [],
                ], 400);
            }

            $data = [
                'booking_id'        => $doctorBooking->id,
                'member_id'         => $doctorBooking->member_id,
                'patient_id'        => $doctorBooking->patient_id,
                'branch_id'         => $doctorBooking->branch_id,
                'doctor_id'         => $doctorBooking->doctor_id,
                'department_id'     => $doctorBooking->department_id,
                'appointment_type'  => $doctorBooking->appointment_type,
                'name'              => $doctorBooking->name,
                'mobile_number'     => $doctorBooking->mobile_number,
                'relationship'      => $doctorBooking->relationship,
                'reason_of_visit'   => $doctorBooking->reason_of_visit,
                'message'           => $doctorBooking->message,
                'is_follow_up'      => $doctorBooking->is_follow_up,
                'booking_date'      => $doctorBooking->booking_date?->format('Y-m-d'),
                'required_time_slots' => $doctorBooking->required_time_slots,
                'is_coins_applied' => (bool) $doctorBooking->is_coins_applied,
                'is_online_payment' => (bool) $doctorBooking->is_online_payment,
                'payment_status' => $doctorBooking->payment_status,
                'invoice_id' => $doctorBooking->invoice_id ? (int) $doctorBooking->invoice_id : null,
                'coins_used' => (int) ($doctorBooking->coins_used ?? 0),
                'consultation_fee' => (float) ($doctorBooking->consultation_fee ?? 0),
                'service_charges' => (float) ($doctorBooking->service_charges ?? 0),
                'total_discount' => (float) ($doctorBooking->total_discount ?? 0),
                'amount_after_discount' => (float) ($doctorBooking->amount_after_discount ?? 0),
                'total_amount' => (float) ($doctorBooking->total_amount ?? 0),
            ];

            if (! empty($result['payment'])) {
                $data['payment'] = $result['payment'];
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Doctor booking created successfully',
                'data'    => $data,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 422,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Doctor booking creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong',
                'data'    => [],
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
        $request->merge([
            'patient_id'        => $request->input('patient_id', $request->input('member_id')),
            'branch_id'         => $request->input('branch_id', $request->input('hospital_id')),
            'sample_collection' => $this->bookingApiService->normalizeSampleCollection(
                (string) $request->input('sample_collection', '')
            ),
        ]);

        $request->validate([
            'patient_id'          => 'required|uuid',
            'branch_id'           => 'required|integer|exists:hospitals,id',
            'package_type'        => 'required|string|in:diagnostic,disease',
            'package_id'          => 'required|integer|min:1',
            'sample_collection'   => 'required|string|in:home,lab',
            'booking_date'        => 'nullable|date|after_or_equal:today',
            'required_time_slots' => 'nullable|array|min:1',
            'is_coins_applied'    => 'nullable|boolean',
            'is_online_payment'   => 'nullable|boolean',
            'coins_used'          => 'nullable|integer|min:0',
            'message'             => 'nullable|string|max:255',
            'purpose'             => 'nullable|string|max:255',
            'device_id'           => 'nullable|string',
        ]);

        try {
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status'  => 401,
                    'message' => 'Unauthenticated. Please login and send Authorization: Bearer {token}.',
                    'data'    => [],
                ], 401);
            }

            $result = $this->bookingApiService->diagnosticTestBooking(
                $request,
                $authUser->id,
                $request->device_id
            );

            $booking = $result['booking'] ?? null;

            if (! $booking) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Package booking not created',
                    'data'    => [],
                ], 400);
            }

            $data = [
                'booking_id'            => $booking->id,
                'test_type'             => $booking->test_type,
                'package_type'          => $booking->package_type,
                'package_id'            => $booking->package_id,
                'branch_id'             => $booking->branch_id,
                'diagnostic_center_id'  => $booking->diagnostic_center_id,
                'patient_id'            => $booking->patient_id,
                'member_id'             => $booking->member_id,
                'name'                  => $booking->name,
                'mobile_number'         => $booking->mobile_number,
                'relationship'          => $booking->relationship,
                'sample_collection'     => $booking->sample_collection,
                // 'booking_date'          => $booking->booking_date?->format('Y-m-d'),
                // 'required_time_slots'   => $booking->required_time_slots,
                'is_coins_applied'      => (bool) $booking->is_coins_applied,
                'is_online_payment'     => (bool) $booking->is_online_payment,
                'payment_status'        => $booking->payment_status,
                'invoice_id'            => $booking->invoice_id ? (int) $booking->invoice_id : null,
                'coins_used'            => (int) ($booking->coins_used ?? 0),
                'package_fee'           => (float) ($booking->package_fee ?? 0),
                'service_charges'       => (float) ($booking->service_charges ?? 0),
                'total_discount'        => (float) ($booking->total_discount ?? 0),
                'amount_after_discount' => (float) ($booking->amount_after_discount ?? 0),
                'total_amount'          => (float) ($booking->total_amount ?? 0),
            ];

            if (! empty($result['payment'])) {
                $data['payment'] = $result['payment'];
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Package booking created successfully',
                'data'    => $data,
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 422,
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Diagnostic package booking creation failed', [
                'error' => $e->getMessage(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong',
                'data'    => [],
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