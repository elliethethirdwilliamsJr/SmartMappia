<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('send-verification-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('verify-and-register', [AuthController::class, 'verifyAndRegister']);
    Route::post('register', [AuthController::class, 'register']); // Backward compatibility
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-phone', [AuthController::class, 'verifyPhone']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    // Debug/Admin endpoint to view all users
    Route::get('users', [AuthController::class, 'allUsers']);
});

// Public location data
Route::prefix('locations')->group(function () {
    Route::get('terminals', [LocationController::class, 'terminals']);
    Route::get('districts', [LocationController::class, 'districts']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user/profile', [AuthController::class, 'updateProfile']);
    
    // Rides
    Route::prefix('rides')->group(function () {
        Route::get('/', [RideController::class, 'index']);
        Route::post('/', [RideController::class, 'store']);
        Route::get('{ride}', [RideController::class, 'show']);
        Route::post('{ride}/cancel', [RideController::class, 'cancel']);
        Route::post('{ride}/rate', [RideController::class, 'rate']);
        Route::get('{ride}/track', [RideController::class, 'track']);
    });
    
    // Driver routes
    Route::prefix('drivers')->group(function () {
        Route::get('nearby', [DriverController::class, 'nearby']);
        Route::post('register', [DriverController::class, 'register']);
        Route::put('status', [DriverController::class, 'updateStatus']);
        Route::put('location', [DriverController::class, 'updateLocation']);
        Route::get('earnings', [DriverController::class, 'earnings']);
        Route::get('trips', [DriverController::class, 'trips']);
        
        // Driver accepting/rejecting rides
        Route::post('rides/{ride}/accept', [DriverController::class, 'acceptRide']);
        Route::post('rides/{ride}/reject', [DriverController::class, 'rejectRide']);
        Route::post('rides/{ride}/arrive', [DriverController::class, 'arrive']);
        Route::post('rides/{ride}/start', [DriverController::class, 'startRide']);
        Route::post('rides/{ride}/complete', [DriverController::class, 'completeRide']);
    });
    
    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('calculate-fare', [PaymentController::class, 'calculateFare']);
        Route::post('process', [PaymentController::class, 'process']);
        Route::get('history', [PaymentController::class, 'history']);
        Route::get('{payment}', [PaymentController::class, 'show']);
    });
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'Route not found'
    ], 404);
});
