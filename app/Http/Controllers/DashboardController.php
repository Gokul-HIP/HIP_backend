<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Hospital;
use App\Models\Diagnostic;
use App\Models\Pharmacy;
use App\Models\Doctor;

class DashboardController extends Controller
{
    
    public function index(){

        $organizations = Organization::where('status', 'active')->count();
        $hospitals = Hospital::where('status', 'active')->count();
        $diagnosticCenters = Diagnostic::where('status', 'active')->count();
        $pharmacies = Pharmacy::where('status', 'active')->count();
        $doctors = Doctor::where('status', 'active')->count();

        return view('admin.dashboard', compact('organizations', 'hospitals', 'diagnosticCenters', 'pharmacies', 'doctors'));
        
    }

}
