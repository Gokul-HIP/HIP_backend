<?php

namespace App\Http\Controllers\Api\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AutomationController extends Controller
{

    public function UILogo(){

        $logo = app_logo_url();

        if(!$logo){
            return response()->json([
                'status' => false,
                'message' => 'Logo not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Logo fetched successfully',
            'logo' => $logo
        ],200);
    }

}
