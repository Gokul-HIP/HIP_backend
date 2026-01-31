<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Procedure;

class BookingController extends Controller
{
    public function sendProceduresList(Request $request){
        $request->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'speciality_id' => 'required|exists:specialities,id',
        ]);

        $hospitalId = $request->hospital_id;
        $specialityId = $request->speciality_id;

        

    }
}
