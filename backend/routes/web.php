<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkLocationController;
use App\Http\Controllers\Admin\WorkScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Admin Guest Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
});

// Protected Admin Web Dashboard Routes
Route::middleware(['auth:web', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // User (Employee) Management
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Work Location & Geofence Management
        Route::resource('locations', WorkLocationController::class)->except(['show', 'destroy']);
        Route::post('locations/{location}/toggle-status', [WorkLocationController::class, 'toggleStatus'])->name('locations.toggle-status');

        // Work Schedule Management
        Route::get('schedules', [WorkScheduleController::class, 'index'])->name('schedules.index');
        Route::get('schedules/{schedule}/edit', [WorkScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('schedules/{schedule}', [WorkScheduleController::class, 'update'])->name('schedules.update');

        // Reports
        Route::get('reports/attendance', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/attendance/export', [ReportController::class, 'export'])->name('reports.export');

        // Audit Logs (Read-only)
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
