<?php

use App\Modules\Workflow\Controllers\WorkflowBuilderController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('workflows', [WorkflowBuilderController::class, 'index']);
    Route::post('workflows', [WorkflowBuilderController::class, 'store']);
    Route::get('workflows/{id}', [WorkflowBuilderController::class, 'show']);
    Route::put('workflows/{id}', [WorkflowBuilderController::class, 'update']);
    Route::delete('workflows/{id}', [WorkflowBuilderController::class, 'destroy']);
    Route::post('workflows/{id}/publish', [WorkflowBuilderController::class, 'publish']);

    Route::prefix('workflow')->group(function () {
        Route::get('triggers', [WorkflowBuilderController::class, 'triggers']);
        Route::get('variables', [WorkflowBuilderController::class, 'variables']);
        Route::get('templates', [WorkflowBuilderController::class, 'templates']);
        Route::post('templates/{id}/preview', [WorkflowBuilderController::class, 'previewTemplate']);
        Route::get('executions', [WorkflowBuilderController::class, 'executions']);
    });
});
