<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\ComplaintController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FacilityController;
use App\Http\Controllers\Web\FeeController;
use App\Http\Controllers\Web\HostelController;
use App\Http\Controllers\Web\InventoryController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\RequestController;
use App\Http\Controllers\Web\RoomController;
use App\Http\Controllers\Web\RoomSwapController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.store')->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}/slip', [BookingController::class, 'slip'])->name('bookings.slip');
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::get('complaints/{complaint}/ticket', [ComplaintController::class, 'ticket'])->name('complaints.ticket');
    Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
    Route::get('fees/{fee}/voucher', [FeeController::class, 'voucher'])->name('fees.voucher');
    Route::get('room-swaps', [RoomSwapController::class, 'index'])->name('swaps.index');
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('facilities', [FacilityController::class, 'index'])->name('facilities.index');

    /*
    |--------------------------------------------------------------------------
    | Student actions
    |--------------------------------------------------------------------------
    */
    Route::middleware('webrole:student')->group(function () {
        Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

        Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');

        Route::post('fees/{fee}/pay', [FeeController::class, 'pay'])->name('fees.pay');

        Route::post('room-swaps', [RoomSwapController::class, 'store'])->name('swaps.store');
        Route::post('room-swaps/{swapRequest}/cancel', [RoomSwapController::class, 'cancel'])->name('swaps.cancel');

        Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
        Route::post('requests/{studentRequest}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');
    });

    /*
    |--------------------------------------------------------------------------
    | Staff actions
    |--------------------------------------------------------------------------
    */
    Route::middleware('webrole:employee,admin')->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::patch('students/{student}', [StudentController::class, 'update'])->name('students.update');

        Route::get('hostels', [HostelController::class, 'index'])->name('hostels.index');
        Route::post('hostels', [HostelController::class, 'store'])->name('hostels.store');
        Route::patch('hostels/{hostel}', [HostelController::class, 'update'])->name('hostels.update');
        Route::delete('hostels/{hostel}', [HostelController::class, 'destroy'])->name('hostels.destroy');

        Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::patch('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::patch('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::post('bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
        Route::post('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
        Route::post('bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('bookings.check-in');
        Route::post('bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('bookings.check-out');

        Route::patch('complaints/{complaint}', [ComplaintController::class, 'update'])->name('complaints.update');
        Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');
        Route::patch('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.status');

        Route::post('fees', [FeeController::class, 'store'])->name('fees.store');
        Route::get('fees/vouchers/batch', [FeeController::class, 'vouchers'])->name('fees.vouchers');
        Route::patch('fees/{fee}', [FeeController::class, 'update'])->name('fees.update');
        Route::post('fees/{fee}/verify', [FeeController::class, 'verify'])->name('fees.verify');
        Route::post('fees/{fee}/waive', [FeeController::class, 'waive'])->name('fees.waive');

        Route::post('facilities', [FacilityController::class, 'store'])->name('facilities.store');
        Route::patch('facilities/{facility}', [FacilityController::class, 'update'])->name('facilities.update');
        Route::patch('facilities/{facility}/status', [FacilityController::class, 'setStatus'])->name('facilities.status');

        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::patch('inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::post('inventory/{item}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');

        Route::post('requests/{studentRequest}/review', [RequestController::class, 'review'])->name('requests.review');

        Route::post('room-swaps/{swapRequest}/approve', [RoomSwapController::class, 'approve'])->name('swaps.approve');
        Route::post('room-swaps/{swapRequest}/reject', [RoomSwapController::class, 'reject'])->name('swaps.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin only
    |--------------------------------------------------------------------------
    */
    Route::middleware('webrole:admin')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::delete('facilities/{facility}', [FacilityController::class, 'destroy'])->name('facilities.destroy');
        Route::delete('inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });
});
