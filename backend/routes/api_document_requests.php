<?php

use App\Http\Controllers\Api\Admin\DocumentRequestAdminController;
use App\Http\Controllers\Api\DocumentRequestController;
use App\Http\Controllers\Api\DocumentTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Request routes
|--------------------------------------------------------------------------
| Copy this block into routes/api.php, inside your existing
| Route::middleware('auth:sanctum')->group(...) as appropriate.
| Adjust the 'role:admin,staff' middleware to match your project's
| existing RBAC middleware name.
*/

Route::middleware('auth:sanctum')->group(function () {

    // Reference data
    Route::get('document-types', [DocumentTypeController::class, 'index']);

    // Resident-facing
    Route::prefix('document-requests')->group(function () {
        Route::get('/', [DocumentRequestController::class, 'index']);
        Route::post('/', [DocumentRequestController::class, 'store']);
        Route::get('/track/{trackingNumber}', [DocumentRequestController::class, 'trackByNumber']);
        Route::get('/{documentRequest}', [DocumentRequestController::class, 'show']);
        Route::post('/{documentRequest}/cancel', [DocumentRequestController::class, 'cancel']);
    });

    // Admin/staff-facing
    Route::prefix('admin/document-requests')
        ->middleware('role:admin,staff')
        ->group(function () {
            Route::get('/', [DocumentRequestAdminController::class, 'index']);
            Route::get('/{documentRequest}', [DocumentRequestAdminController::class, 'show']);
            Route::patch('/{documentRequest}/status', [DocumentRequestAdminController::class, 'updateStatus']);
        });
});
