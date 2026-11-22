<?php

use App\Http\Controllers\Web\AuditLogWebController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardWebController;
use App\Http\Controllers\Web\NotificationWebController;
use App\Http\Controllers\Web\TrackingWebController;
use Illuminate\Support\Facades\Route;

// Authentication & Multi-Role Switching
Route::get('/login', [AuthWebController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->name('login.post');
Route::match(['get', 'post'], '/logout', [AuthWebController::class, 'logout'])->name('logout');
Route::post('/switch-role/{role}', [AuthWebController::class, 'switchRole'])->name('auth.switch_role');

// In-App Notifications
Route::get('/notifications', [NotificationWebController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{notification}/read', [NotificationWebController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllAsRead'])->name('notifications.read_all');

// Regulatory Audit Trail & Activity Logs (Admin & Operator)
Route::get('/audit-logs', [AuditLogWebController::class, 'index'])->name('audit.index');

// Core Web Views
Route::get('/', [DashboardWebController::class, 'index'])->name('home');
Route::get('/dashboard', [DashboardWebController::class, 'index'])->name('dashboard');

Route::get('/tracking-map', [TrackingWebController::class, 'index'])->name('tracking.map');
Route::get('/tracking-map/export', [TrackingWebController::class, 'export'])->name('tracking.export');
Route::post('/tracking-map/simulate-ais', [TrackingWebController::class, 'simulateAis'])->name('tracking.simulate_ais');

Route::get('/tracking', [TrackingWebController::class, 'index'])->name('tracking.index');
Route::get('/track', [TrackingWebController::class, 'index'])->name('tracking.track');
