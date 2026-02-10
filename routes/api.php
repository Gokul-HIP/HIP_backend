<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationFilter;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\BookingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('auth')->controller(AuthController::class)->group(function(){

    Route::post('register' , 'register');
    Route::post('otp-verification' , 'otpVerification');
    Route::post('otp-resend' , 'resendOTP');
    Route::post('login' , 'login');
    Route::post('logout' , 'logout')->middleware('auth:sanctum');
    Route::get( 'profile' , 'userProfile')->middleware('auth:sanctum');
    Route::post('form-update' , 'formUpdate')->middleware('auth:sanctum');
    Route::delete('delete-user' , 'deleteUser')->middleware('auth:sanctum');

});

Route::prefix('location')->controller(LocationFilter::class)->group(function(){

    Route::post('by-hospital', 'byLocation');
    Route::post('search-area', 'searchArea');
    
});

Route::prefix('hospital')->controller(HospitalController::class)->group(function(){

    Route::post('details', 'hospitalDetails');
    Route::post('assigned-doctors', 'assignedDoctors');
    Route::post('procedures', 'hospitalProcedures');
    Route::post('diagnostics-services-list', 'organizationDiagnosticsCenterLabTests');
    // Route::post('diagnostics-tests-list', 'organizationDiagnosticsLabTestsDetails');
    Route::post('pharmacies-list', 'hospitalPharmaciesList');
    Route::post('diagnostics-packages-list', 'organizationDiagnosticsPackages');
    Route::post('all-specialities-list', 'allSpecialitiesList');
    Route::post('all-doctors-list', 'allDoctorsList');
    Route::post('get-procedures-list', 'getProceduresList');
    Route::post('doctors-by-location', 'doctorsByLocation');
    Route::get('get-hospital/{id}', 'getHospital');
    
});

Route::prefix('booking')->controller(BookingController::class)->group(function(){

    Route::post('procedure-booking', 'procedureBooking');
    Route::get('wellness-list', 'wellnessList');
    Route::get('wellness-details/{id}', 'wellnessDetails');
    Route::post('doctor-booking', 'doctorBooking')->middleware('auth:sanctum');
    Route::post('wellness-booking', 'wellnessBooking')->middleware('auth:sanctum');

});

// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/register
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/otp-verification
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/otp-resend
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/login
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/logout
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/profile
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/form-update
// https://subbasal-elijah-vainly.ngrok-free.dev/api/location/search-area