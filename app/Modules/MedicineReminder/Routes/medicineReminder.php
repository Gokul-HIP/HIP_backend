<?php

use App\Modules\MedicineReminder\Controllers\MedicineReminderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('medicine-workflows', [MedicineReminderController::class, 'indexWorkflows']);
    Route::get('medicine-workflows/{id}', [MedicineReminderController::class, 'showWorkflow']);
    Route::post('medicine-workflows', [MedicineReminderController::class, 'storeWorkflow']);
    Route::put('medicine-workflows/{id}', [MedicineReminderController::class, 'updateWorkflow']);
    Route::delete('medicine-workflows/{id}', [MedicineReminderController::class, 'destroyWorkflow']);

    Route::get('medicine-reminders', [MedicineReminderController::class, 'indexReminders']);
    Route::post('medicine-reminders/test', [MedicineReminderController::class, 'testReminder']);
});
