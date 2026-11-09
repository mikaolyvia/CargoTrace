<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarrierController;
use App\Http\Controllers\Api\ExceptionController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\TrackingController;
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
});
