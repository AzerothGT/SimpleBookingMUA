<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BookingTaskController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceImageController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Services (public view)
Route::apiResource('services', ServiceController::class)->only(['index', 'show']);

// Bookings (client-facing)
Route::post('/bookings', [BookingController::class, 'store']);
Route::post('/schedule/check', [BookingController::class, 'checkAvailability']);
Route::get('/schedule/calendar', [BookingController::class, 'calendar']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth.session')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Users
    Route::apiResource('users', UserController::class);

    // Services (admin only)
    Route::apiResource('services', ServiceController::class)->except(['index', 'show']);
    Route::apiResource('services.serviceImages', ServiceImageController::class)
        ->scoped()
        ->only(['store', 'update', 'destroy']);

    // Bookings
    Route::apiResource('bookings', BookingController::class)->except(['store']);
    Route::post('/bookings/{booking}/assign-staff', [BookingController::class, 'assignStaff']);
    Route::patch('/bookings/{booking}/status', [BookingController::class, 'changeStatus']);

    // Booking Tasks
    Route::apiResource('bookings.bookingTasks', BookingTaskController::class)
        ->scoped()
        ->only(['store', 'update', 'destroy']);

    // Transactions
    Route::get('/bookings/{booking}/transactions', [TransactionController::class, 'index'])->scopeBindings();
    Route::post('/bookings/{booking}/transactions/snap', [TransactionController::class, 'createSnap'])->scopeBindings();
    Route::get('/bookings/{booking}/transactions/{transaction}', [TransactionController::class, 'show'])->scopeBindings();

    // Activity Logs
    Route::apiResource('activity-logs', ActivityLogController::class)->only(['index', 'show']);
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (no auth - Midtrans sends webhook)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/midtrans', [TransactionController::class, 'webhook']);
