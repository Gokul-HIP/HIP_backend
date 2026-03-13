<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationFilter;
use App\Http\Controllers\Api\HospitalController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\NotificationController;
use Kreait\Firebase\Contract\Messaging;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\WellnessController;
use App\Http\Controllers\Api\TransactionsController;
use App\Http\Controllers\InvoicePaymentController;

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
    Route::get('dependent-members', 'getDependentMembers')->middleware('auth:sanctum');
    Route::post('update-dependent-member', 'updateDependentMember')->middleware('auth:sanctum');

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
    Route::post('all-specialities-lists', 'allSpecialitiesLists');
    Route::post('all-doctors-list', 'allDoctorsList');
    Route::post('all-doctors-lists', 'allDoctorsLists');
    Route::post('get-procedures-list', 'getProceduresList');
    Route::post('doctors-by-location', 'doctorsByLocation')->middleware('auth:sanctum');
    Route::get('get-hospital/{id}', 'getHospital');
    Route::get('get-doctor-details', 'doctorDetails');
    // Route::get('appointment-calendar', 'appointmentCalendar');
    // Route::get('doctor/appointment-calendar', 'doctorAppointmentCalendar');
    Route::get('doctor/schedule-for-date', 'doctorScheduleForDate');
    Route::post('hospital-search', 'hospitalSearch');
    Route::post('doctor-search', 'doctorSearch');
    Route::post('diagnostic-center-list', 'diagnosticCenterList');

});

Route::prefix('booking')->controller(BookingController::class)->group(function(){

    Route::post('procedure-booking', 'procedureBooking')->middleware('auth:sanctum');
    Route::get('wellness-list', 'wellnessList');
    Route::get('wellness-details/{id}', 'wellnessDetails');
    Route::post('doctor-booking', 'doctorBooking')->middleware('auth:sanctum');
    Route::post('wellness-booking', 'wellnessBooking')->middleware('auth:sanctum');
    Route::post('diagnostic-test-booking', 'diagnosticTestBooking')->middleware('auth:sanctum');
    Route::post('stem-cell-booking', 'stemCellBooking')->middleware('auth:sanctum');
    Route::post('caregiver-booking', 'caregiverBooking')->middleware('auth:sanctum');

});

Route::prefix('review')->controller(ReviewController::class)->group(function(){

    Route::post('doctor-review', 'doctorReview')->middleware('auth:sanctum');
    Route::post('hospital-review', 'hospitalReview')->middleware('auth:sanctum');
    Route::get('get-reviews/{type}/{id}', 'getReviews')->middleware('auth:sanctum');
    
});

Route::prefix('content')->controller(ContentController::class)->group(function(){

    Route::post('content-list', 'contentList')->middleware('auth:sanctum');
    Route::get('toggle-like/{content}', 'toggleLike')->middleware('auth:sanctum');
    Route::post('add-comment/{content}', 'addComment')->middleware('auth:sanctum');
    Route::get('get-comments/{content}', 'getComments')->middleware('auth:sanctum');
    Route::delete('content/{content}/comment/{comment}','deleteComment')->middleware('auth:sanctum');
    Route::get('view/{content}', 'addView')->middleware('auth:sanctum');

});

Route::prefix('wellness')->controller(WellnessController::class)->group(function(){

    Route::get('wellness-types', 'wellnessTypes');
    Route::post('wellness-list', 'wellnessList');
    Route::get('wellness-details/{id}', 'wellnessDetails')->middleware('auth:sanctum');

});

Route::get('/firebase-test', function (Messaging $messaging) {
    return response()->json([
        'status' => 'Firebase connected successfully'
    ]);
});


Route::prefix('notification')->middleware('auth:sanctum')->controller(NotificationController::class)->group(function(){

    Route::post('save-fcm-token', 'saveFcmToken');
    Route::post('send-notification', 'sendNotification');

});

// Notifications API
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead']);
    Route::delete('/notifications/{id}', [App\Http\Controllers\Api\NotificationController::class, 'delete']);
    Route::delete('/notifications-clear', [App\Http\Controllers\Api\NotificationController::class, 'clearAll']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    
});

Route::get('transactions/history', [TransactionsController::class, 'getTransactions'])->middleware('auth:sanctum');
Route::get('coins/history', [TransactionsController::class, 'getCoinsHistory'])->middleware('auth:sanctum');

Route::post('/invoices/{invoice_id}/pay', [InvoicePaymentController::class, 'pay']);
Route::post('/invoices/{invoice_id}/verify-payment', [InvoicePaymentController::class, 'verifyPayment']);
Route::get('/payment-requests/{invoice_id}', [InvoicePaymentController::class, 'paymentRequest']);
Route::post('/payment-requests/{invoice_id}/apply-coins', [InvoicePaymentController::class, 'applyCoins']);
Route::post('/payment-requests/{invoice_id}/pay', [InvoicePaymentController::class, 'pay']);


// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/register
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/otp-verification
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/otp-resend
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/login
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/logout
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/profile
// https://subbasal-elijah-vainly.ngrok-free.dev/api/auth/form-update
// https://subbasal-elijah-vainly.ngrok-free.dev/api/location/search-area
