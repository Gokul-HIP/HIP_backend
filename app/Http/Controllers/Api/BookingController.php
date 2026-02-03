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
            'speciality_master_id' => 'required|integer', // Changed from exists validation
            'procedure_id' => 'required|exists:procedures,id',
            'hospital_id' => 'required|exists:hospitals,id',
        ]);
    
        try{
    
            $procedureBooking = ProcedureBooking::create([
                'name' => $request->name,
                'mobile_number' => $request->mobile_number,
                'speciality_master_id' => $request->speciality_master_id,
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
