<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorReview;
use App\Models\HospitalReview;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;

class ReviewController extends Controller
{
    
    public function doctorReview(Request $request, NotificationService $service)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id|integer',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'device_id' => 'required|string',
        ]);

        try{

           DoctorReview::create([
                'member_id' => $request->user()->id,
                'doctor_id' => $request->doctor_id,
                'review' => $request->review,
                'rating' => $request->rating,
            ]);

            if ($request->user()->id) {
                $service->sendToDevice(
                    $request->user()->id,
                    $request->device_id,
                    'New Doctor Review',
                    'You have a new doctor review',
                    [
                        'type' => 'review_popup',
                        'entity_type' => 'doctor', 
                        'entity_id' => (string) $request->doctor_id,
                    ]
                );
            }
        
            return response()->json([
                'status' => 200,
                'message' => 'Review created successfully'
                ], 200);

        }catch(\Throwable $e){
            Log::error('Review creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Review creation failed',
            ], 500);
        }

    }

    public function hospitalReview(Request $request, NotificationService $service)
    {
        $request->validate([
            'hospital_id' => 'required|exists:hospitals,id|integer',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'device_id' => 'nullable|string',
        ]);

        try{

            HospitalReview::create([
                'member_id' => $request->user()->id,
                'hospital_id' => $request->hospital_id,
                'review' => $request->review,
                'rating' => $request->rating,
            ]);

            if ($request->user()->id && $request->filled('device_id')) {
                $service->sendToDevice(
                    $request->user()->id,
                    $request->device_id,
                    'New Hospital Review',
                    'You have a new hospital review',
                    [
                        'type' => 'review_popup',
                        'entity_type' => 'hospital',
                        'entity_id' => (string) $request->hospital_id,
                    ]
                );
            }

            return response()->json([
                'status' => 200,
                'message' => 'Review created successfully'
                ], 200);

        }catch(\Throwable $e){
            Log::error('Review creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Review creation failed',
            ], 500);
        }
    }

    public function getReviews($type,$id){

        try{
            
            if($type == 'hospital'){
                $reviews = HospitalReview::where('hospital_id', $id)->where('status', 'active')->with('member')->paginate(10);
            }else if($type == 'doctor'){
                $reviews = DoctorReview::where('doctor_id', $id)->where('status', 'active')->with('member')->paginate(10);
            }else{
                return response()->json([
                    'status' => 400,
                    'message' => 'Invalid type',
                ], 400);
            }

            $hospitalRating = $reviews->avg('rating');
            $hospitalRating = round($hospitalRating, 1);

            if($type == 'hospital'){
                $message = 'Hospital reviews fetched successfully';
            }else if($type == 'doctor'){
                $message = 'Doctor reviews fetched successfully';
            }

            return response()->json([
                'status' => 200,
                'message' => $message,
                'data' => $reviews->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'reviewer_name' => $review->member->name,
                        'reviewer_image' => $review->member->profile_image ? url('storage/profile/' . $review->member->profile_image) : null,
                        'comment' => $review->review,
                        'rating' => $review->rating,
                        'created_at' => $review->created_at->format('d M Y'),
                    ];
                }),
                'average_rating' => $hospitalRating,
                'total_reviews' => $reviews->total(),
                'count' => $reviews->count(),
                'per_page' => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ], 200);
        }catch(\Throwable $e){
            Log::error('Error fetching reviews', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching reviews',
            ], 500);
        }

    }

}