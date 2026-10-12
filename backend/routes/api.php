<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);

    // Authenticated routes (Sanctum + active user check)
    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Employee attendance endpoints
        Route::prefix('attendance')->group(function () {
            Route::get('/today', [AttendanceController::class, 'today']);
            Route::post('/check-in', [AttendanceController::class, 'checkIn']);
            Route::post('/check-out', [AttendanceController::class, 'checkOut']);
            Route::get('/history', [AttendanceController::class, 'history']);
        });

        // Admin-only route foundation
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::get('/ping', function () {
                return response()->json([
                    'data' => [
                        'status' => 'admin-access-granted',
                    ],
                    'message' => 'Success',
                ]);
            });
        });
    });
});
