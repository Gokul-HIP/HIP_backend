<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\Api\ReviewApiService;
use App\Services\NotificationService;

class ReviewController extends Controller
{
    public function doctorReview(Request $request, ReviewApiService $reviewService, NotificationService $service)
    {
        $request->validate([
            'doctor_id'  => 'required|exists:doctors,id|uuid',
            'review'     => 'nullable|string',
            'rating'     => 'required|integer|min:1|max:5',
            'device_id'  => 'nullable|string',
            'quick_tags' => 'nullable|string',
            'quick_tag'  => 'nullable|string',
        ]);

        try {

            $quickTags = $request->input('quick_tags', $request->input('quick_tag'));

            $reviewService->createDoctorReview(
                (string) $request->user()->id,
                (string) $request->doctor_id,
                $request->review,
                (int) $request->rating,
                is_string($quickTags) ? $quickTags : null
            );

            // if ($request->user()->id) {
            //     $service->sendToDevice(
            //         $request->user()->id,
            //         $request->device_id,
            //         'New Doctor Review',
            //         'You have a new doctor review',
            //         [
            //             'type' => 'review_popup',
            //             'entity_type' => 'doctor', 
            //             'entity_id' => (string) $request->doctor_id,
            //         ]
            //     );
            // }
        
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

    public function hospitalReview(Request $request, ReviewApiService $reviewService, NotificationService $service)
    {
        $request->validate([
            'hospital_id' => 'required|exists:hospitals,id|integer',
            'review' => 'nullable|string',
            'rating' => 'required|integer|min:1|max:5',
            'device_id' => 'nullable|string',
        ]);

        try{

            $reviewService->createHospitalReview(
                $request->user()->id,
                (int) $request->hospital_id,
                $request->review,
                (int) $request->rating
            );

            if ($request->user()->id) {
                if ($request->filled('device_id')) {
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

    public function getReviews($type, $id, ReviewApiService $reviewService)
    {
        try{
            $result = $reviewService->getReviews((string) $type, (int) $id);

            $reviews = $result['reviews'];

            $items = collect($reviews->items())->map(function ($review) {
                return [
                    'id' => $review->id,
                    'reviewer_name' => $review->member->name,
                    'reviewer_image' => $review->member->profile_image ? url('storage/profile/' . $review->member->profile_image) : null,
                    'comment'    => $review->displayComment(),
                    'quick_tags' => $review->quick_tags,
                    'rating'     => $review->rating,
                    'created_at' => $review->created_at->format('d M Y'),
                ];
            });

            return response()->json([
                'status' => 200,
                'message' => $result['message'],
                'data' => $items,
                'average_rating' => $result['average_rating'],
                'total_reviews' => $reviews->total(),
                'count' => $items->count(),
                'per_page' => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ], 200);
        }catch(\InvalidArgumentException $e){
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }catch(\Throwable $e){
            Log::error('Error fetching reviews', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching reviews',
            ], 500);
        }

    }

}