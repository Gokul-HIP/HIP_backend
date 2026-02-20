<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DoctorReview;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    
    public function doctorReview(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id|integer',
            'review' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        try{

            DoctorReview::create([
                'member_id' => $request->user()->id,
                'doctor_id' => $request->doctor_id,
                'review' => $request->review,
                'rating' => $request->rating,
            ]);
        
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
}