<?php

use App\Http\Controllers\Hms\PatientMembershipController;
use App\Http\Controllers\Hms\PatientRewardPlanController;
use App\Http\Controllers\Hms\PriceQuoteController;
use App\Http\Middleware\LogHmsRequest;
use App\Http\Middleware\RejectDuplicateHmsEvent;
use Illuminate\Support\Facades\Route;

/*
| HMS client API. The "apiv1/client" prefix is applied in bootstrap/app.php
| and this file is not given the framework "api" prefix.
|
| Later steps register booking and payment webhooks in this same group so they
| share signature auth, the inbox log, and hospital scoping. Those handlers
| should extend App\Http\Controllers\Hms\HmsController and claim the inbox row
| for X-HMS-Event-Id before changing any booking or payment state.
*/

Route::middleware([
    LogHmsRequest::class,
    'hms.client',
    RejectDuplicateHmsEvent::class,
])->group(function () {
    Route::get('get/patient/membership', PatientMembershipController::class);
    Route::get('get/patient/reward/plan', PatientRewardPlanController::class);
    Route::post('price/quote', PriceQuoteController::class);
});
