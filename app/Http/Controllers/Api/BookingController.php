<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procedure;
use App\Models\ProcedureBooking;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
   
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
                'status' => 201,
                'message' => 'Procedure booking created successfully',
                'data' => [
                    'booking_id' => $procedureBooking->id,
                ],
            ], 201);
    
        }catch(\Throwable $e){
            Log::error('Procedure booking creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
            ], 500);
        }
    }

}
