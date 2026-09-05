<?php

use App\Http\Controllers\Api\Admin\DocumentRequestAdminController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentRequestController;
use App\Http\Controllers\Api\DocumentTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public auth routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated routes (any role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Reference data
    Route::get('/document-types', [DocumentTypeController::class, 'index']);

    // Resident-facing document requests
    Route::prefix('document-requests')->group(function () {
        Route::get('/', [DocumentRequestController::class, 'index']);
        Route::post('/', [DocumentRequestController::class, 'store']);
        Route::get('/track/{trackingNumber}', [DocumentRequestController::class, 'trackByNumber']);
        Route::get('/{documentRequest}', [DocumentRequestController::class, 'show']);
        Route::post('/{documentRequest}/cancel', [DocumentRequestController::class, 'cancel']);
    });

    /*
    |----------------------------------------------------------------------
    | Admin-only routes
    |----------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

        // Admin-facing document requests → resolves to /api/admin/document-requests/...
        Route::prefix('document-requests')->group(function () {
            Route::get('/', [DocumentRequestAdminController::class, 'index']);
            Route::get('/{documentRequest}', [DocumentRequestAdminController::class, 'show']);
            Route::patch('/{documentRequest}/status', [DocumentRequestAdminController::class, 'updateStatus']);
        });
    });
});