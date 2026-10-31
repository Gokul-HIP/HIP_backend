<?php

use App\Http\Controllers\Hms\MembershipPlansController;
use App\Http\Controllers\Hms\PatientMembershipController;
use App\Http\Controllers\Hms\PaymentReceivedController;
use App\Http\Middleware\LogHmsRequest;
use App\Http\Middleware\RejectDuplicateHmsEvent;
use Illuminate\Support\Facades\Route;

/*
| HMS client API. The "apiv1/client" prefix is applied in bootstrap/app.php.
*/

Route::middleware([
    LogHmsRequest::class,
    'hms.client',
    RejectDuplicateHmsEvent::class,
])->group(function () {
    Route::get('get/patient/membership', PatientMembershipController::class);
    Route::get('get/membership/plans', MembershipPlansController::class);
    Route::post('payment/received', PaymentReceivedController::class);
});
