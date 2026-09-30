<?php

use App\Http\Controllers\Api\Admin\AppointmentAdminController;
use App\Http\Controllers\Api\Admin\DocumentRequestAdminController;
use App\Http\Controllers\Api\Admin\ReportsAdminController;
use App\Http\Controllers\Api\Admin\ResidentAdminController;
use App\Http\Controllers\Api\Admin\ResidentLookupController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentRequestController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\NotificationController;
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

    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::post('/', [AppointmentController::class, 'store']);
        Route::post('/{appointment}/cancel', [AppointmentController::class, 'cancel']);
    });

    // Notifications — belongs here, not under /admin, since residents,
    // staff, and admin all need to read their own notifications.
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
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
            Route::post('/', [DocumentRequestAdminController::class, 'store']);
            Route::get('/{documentRequest}', [DocumentRequestAdminController::class, 'show']);
            Route::patch('/{documentRequest}/status', [DocumentRequestAdminController::class, 'updateStatus']);
        });

        Route::get('residents', [ResidentAdminController::class, 'index']);
        Route::get('residents/lookup', [ResidentLookupController::class, 'index']);
        Route::post('residents', [ResidentAdminController::class, 'store']);

        Route::prefix('appointments')->group(function () {
            Route::get('/', [AppointmentAdminController::class, 'index']);
            Route::patch('/{appointment}/status', [AppointmentAdminController::class, 'updateStatus']);
        });

        Route::get('reports', [ReportsAdminController::class, 'index']);
    });
});