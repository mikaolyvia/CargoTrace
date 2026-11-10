<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExceptionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
use App\Http\Controllers\Api\VesselController;
use App\Http\Controllers\Api\VesselIntegrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CargoTrace Platform
|--------------------------------------------------------------------------
|
| All endpoints here are automatically prefixed with /api.
| Authentication is managed through Laravel Sanctum bearer tokens.
|
*/

// Public Authentication Endpoints (Strictly no public registration)
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected API Endpoints
Route::middleware('auth:sanctum')->group(function () {
    // Auth Session
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');

    // Master Data (Read-only for authenticated roles)
    Route::get('/carriers', [CarrierController::class, 'index'])->name('api.carriers.index');
    Route::get('/locations', [LocationController::class, 'index'])->name('api.locations.index');
    Route::get('/ports', [LocationController::class, 'index'])->name('api.ports.index');
    Route::get('/vessels', [VesselController::class, 'index'])->name('api.vessels.index');
    Route::get('/vessels/{vessel}', [VesselController::class, 'show'])->name('api.vessels.show');
    Route::get('/vessels/{vessel}/positions', [VesselController::class, 'positions'])->name('api.vessels.positions');

    // External Integration (Python AIS Data Collector & IoT telemetry)
    Route::post('/integrations/vessel-positions', [VesselIntegrationController::class, 'updatePosition'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.integrations.vessel_positions');

    // Shipments
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('api.shipments.index');
    Route::post('/shipments', [ShipmentController::class, 'store'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.shipments.store');
    Route::get('/shipments/{shipment}', [ShipmentController::class, 'show'])->name('api.shipments.show');
    Route::match(['put', 'patch'], '/shipments/{shipment}', [ShipmentController::class, 'update'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.shipments.update');
    Route::patch('/shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.shipments.status');
    Route::delete('/shipments/{shipment}', [ShipmentController::class, 'destroy'])
        ->middleware('role:ADMIN')
        ->name('api.shipments.destroy');

    // Tracking Management
    Route::get('/shipments/{shipment}/tracking', [TrackingController::class, 'index'])->name('api.tracking.index');
    Route::post('/shipments/{shipment}/tracking', [TrackingController::class, 'store'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.tracking.store');

    // Exception Management
    Route::get('/shipments/{shipment}/exceptions', [ExceptionController::class, 'index'])->name('api.exceptions.index');
    Route::post('/shipments/{shipment}/exceptions', [ExceptionController::class, 'store'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.exceptions.store');
    Route::patch('/exceptions/{exception}/investigate', [ExceptionController::class, 'investigate'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.exceptions.investigate');
    Route::patch('/exceptions/{exception}/resolve', [ExceptionController::class, 'resolve'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.exceptions.resolve');

    // Dashboard Metrics
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read_all');

    // Audit Logs (Admin & Operator only)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware('role:ADMIN,OPERATOR')
        ->name('api.audit_logs.index');
});
