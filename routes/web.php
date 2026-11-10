<?php

use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\TrackingWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardWebController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardWebController::class, 'index'])->name('dashboard');

Route::get('/tracking-map', [TrackingWebController::class, 'index'])->name('tracking.map');
Route::get('/tracking-map/export', [TrackingWebController::class, 'export'])->name('tracking.export');
Route::post('/tracking-map/simulate-ais', [TrackingWebController::class, 'simulateAis'])->name('tracking.simulate_ais');

Route::get('/tracking', [TrackingWebController::class, 'index'])->name('tracking.index');
Route::get('/track', [TrackingWebController::class, 'index'])->name('tracking.track');
