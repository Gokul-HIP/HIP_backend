<?php

use App\Modules\Workflow\Controllers\WorkflowBuilderController;
use App\Modules\Workflow\Controllers\WorkflowTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('workflows', [WorkflowBuilderController::class, 'index']);
    Route::post('workflows', [WorkflowBuilderController::class, 'store']);
    Route::get('workflows/{id}', [WorkflowBuilderController::class, 'show']);
    Route::put('workflows/{id}', [WorkflowBuilderController::class, 'update']);
    Route::delete('workflows/{id}', [WorkflowBuilderController::class, 'destroy']);
    Route::post('workflows/{id}/publish', [WorkflowBuilderController::class, 'publish']);
    Route::post('workflows/{id}/duplicate', [WorkflowBuilderController::class, 'duplicate']);
    Route::get('hospitals', [WorkflowBuilderController::class, 'hospitals']);

    /*
    |--------------------------------------------------------------------------
    | Workflow Templates — frontend read (active blueprints only)
    |--------------------------------------------------------------------------
    */
    Route::get('workflow-templates', [WorkflowTemplateController::class, 'catalog'])
        ->name('workflow-templates.catalog');
    Route::get('workflow-templates/{id}', [WorkflowTemplateController::class, 'showActive'])
        ->name('workflow-templates.show-active');
    Route::get('workflow-templates/{id}/preview', [WorkflowTemplateController::class, 'preview'])
        ->name('workflow-templates.preview');

    /*
    |--------------------------------------------------------------------------
    | Workflow Templates — admin CRUD (Backend Admin / Template Mode)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        Route::get('workflow-templates', [WorkflowTemplateController::class, 'index'])
            ->name('admin.workflow-templates.index');
        Route::post('workflow-templates', [WorkflowTemplateController::class, 'store'])
            ->name('admin.workflow-templates.store');
        Route::get('workflow-templates/{id}', [WorkflowTemplateController::class, 'show'])
            ->name('admin.workflow-templates.show');
        Route::put('workflow-templates/{id}', [WorkflowTemplateController::class, 'update'])
            ->name('admin.workflow-templates.update');
        Route::delete('workflow-templates/{id}', [WorkflowTemplateController::class, 'destroy'])
            ->name('admin.workflow-templates.destroy');
        Route::post('workflow-templates/{id}/duplicate', [WorkflowTemplateController::class, 'duplicate'])
            ->name('admin.workflow-templates.duplicate');
        Route::get('workflow-templates/{id}/preview', [WorkflowTemplateController::class, 'preview'])
            ->name('admin.workflow-templates.preview');
    });

    Route::prefix('workflow')->group(function () {
        Route::get('triggers', [WorkflowBuilderController::class, 'triggers']);
        Route::get('variables', [WorkflowBuilderController::class, 'variables']);
        Route::get('templates', [WorkflowBuilderController::class, 'templates']);
        Route::post('templates/{id}/preview', [WorkflowBuilderController::class, 'previewTemplate']);
        Route::get('executions', [WorkflowBuilderController::class, 'executions']);
    });
});
