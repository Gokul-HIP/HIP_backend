<?php

use App\Modules\Automation\Http\Controllers\HospitalAutomationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('hospital-automation')->group(function () {
    Route::get('/catalog', [HospitalAutomationController::class, 'catalog']);
    Route::get('/workflows', [HospitalAutomationController::class, 'workflows']);
    Route::get('/executions', [HospitalAutomationController::class, 'executions']);
    Route::get('/templates', [HospitalAutomationController::class, 'templates']);
    Route::post('/trigger', [HospitalAutomationController::class, 'trigger']);
});
