<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ComplaintController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FacilityController;
use App\Http\Controllers\Api\V1\FeeController;
use App\Http\Controllers\Api\V1\HostelController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\RoomSwapController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\StudentRequestController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', fn () => response()->json([
        'name' => 'Hostel Management System API',
        'version' => 'v1',
        'status' => 'ok',
    ]));

    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Shared (any authenticated user)
        |--------------------------------------------------------------------------
        */
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/password', [AuthController::class, 'changePassword']);
        Route::patch('auth/profile', [AuthController::class, 'updateProfile']);

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::get('hostels', [HostelController::class, 'index']);
        Route::get('hostels/{hostel}', [HostelController::class, 'show']);

        Route::get('rooms', [RoomController::class, 'index']);
        Route::get('rooms/{room}', [RoomController::class, 'show']);

        Route::get('facilities', [FacilityController::class, 'index']);

        Route::get('bookings/available-rooms', [BookingController::class, 'availableRooms']);

        Route::get('complaints', [ComplaintController::class, 'index']);
        Route::get('complaints/{complaint}', [ComplaintController::class, 'show']);

        Route::get('fees', [FeeController::class, 'index']);
        Route::get('fees/{fee}', [FeeController::class, 'show']);

        Route::get('room-swap-requests', [RoomSwapController::class, 'index']);
        Route::get('room-swap-requests/{swapRequest}', [RoomSwapController::class, 'show']);

        Route::get('student-requests', [StudentRequestController::class, 'index']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Student only
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:student')->group(function () {
            Route::get('bookings/mine', [BookingController::class, 'mine']);
            Route::post('bookings', [BookingController::class, 'store']);
            Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

            Route::post('complaints', [ComplaintController::class, 'store']);

            Route::post('fees/{fee}/pay', [FeeController::class, 'pay']);

            Route::post('room-swap-requests', [RoomSwapController::class, 'store']);
            Route::post('room-swap-requests/{swapRequest}/cancel', [RoomSwapController::class, 'cancel']);

            Route::post('student-requests', [StudentRequestController::class, 'store']);
            Route::post('student-requests/{studentRequest}/cancel', [StudentRequestController::class, 'cancel']);

            Route::patch('student/profile', [StudentController::class, 'updateOwnProfile']);
        });

        /*
        |--------------------------------------------------------------------------
        | Staff (employee + admin)
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:employee,admin')->group(function () {
            Route::get('students', [StudentController::class, 'index']);
            Route::get('students/{student}', [StudentController::class, 'show']);
            Route::patch('students/{student}', [StudentController::class, 'update']);

            Route::post('hostels', [HostelController::class, 'store']);
            Route::patch('hostels/{hostel}', [HostelController::class, 'update']);
            Route::delete('hostels/{hostel}', [HostelController::class, 'destroy']);

            Route::post('rooms', [RoomController::class, 'store']);
            Route::patch('rooms/{room}', [RoomController::class, 'update']);
            Route::delete('rooms/{room}', [RoomController::class, 'destroy']);

            Route::get('bookings', [BookingController::class, 'index']);
            Route::post('bookings/{booking}/approve', [BookingController::class, 'approve']);
            Route::post('bookings/{booking}/reject', [BookingController::class, 'reject']);
            Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn']);
            Route::post('bookings/{booking}/check-out', [BookingController::class, 'checkOut']);

            Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign']);
            Route::patch('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus']);

            Route::post('fees', [FeeController::class, 'store']);
            Route::post('fees/{fee}/verify', [FeeController::class, 'verify']);
            Route::post('fees/{fee}/waive', [FeeController::class, 'waive']);

            Route::post('facilities', [FacilityController::class, 'store']);
            Route::patch('facilities/{facility}', [FacilityController::class, 'update']);
            Route::patch('facilities/{facility}/status', [FacilityController::class, 'setStatus']);

            Route::get('inventory', [InventoryController::class, 'index']);
            Route::post('inventory', [InventoryController::class, 'store']);
            Route::patch('inventory/{item}', [InventoryController::class, 'update']);
            Route::post('inventory/{item}/adjust', [InventoryController::class, 'adjustStock']);

            Route::post('student-requests/{studentRequest}/review', [StudentRequestController::class, 'review']);

            Route::post('room-swap-requests/{swapRequest}/approve', [RoomSwapController::class, 'approve']);
            Route::post('room-swap-requests/{swapRequest}/reject', [RoomSwapController::class, 'reject']);
        });

        /*
        |--------------------------------------------------------------------------
        | Admin only
        |--------------------------------------------------------------------------
        */
        Route::middleware('role:admin')->group(function () {
            Route::get('users', [UserController::class, 'index']);
            Route::post('users', [UserController::class, 'store']);
            Route::get('users/{user}', [UserController::class, 'show']);
            Route::patch('users/{user}', [UserController::class, 'update']);
            Route::delete('users/{user}', [UserController::class, 'destroy']);

            Route::delete('complaints/{complaint}', [ComplaintController::class, 'destroy']);
            Route::delete('fees/{fee}', [FeeController::class, 'destroy']);
            Route::delete('facilities/{facility}', [FacilityController::class, 'destroy']);
            Route::delete('inventory/{item}', [InventoryController::class, 'destroy']);
        });
    });
});
