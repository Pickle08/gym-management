<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CoachingRequestController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\ProgressLogController;

// Public
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Semua yang di bawah ini wajib login
Route::middleware('auth:sanctum')->group(function () {

    // Semua role
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1');
    });

    // admin, staff, trainer (read-only)
    Route::middleware('role:admin,staff,trainer')->group(function () {
        Route::get('members', [MemberController::class, 'index']);
        Route::get('members/{member}', [MemberController::class, 'show']);
        Route::get('attendances', [AttendanceController::class, 'index']);
        Route::get('coaching-requests', [CoachingRequestController::class, 'index']);
        Route::get('coaching-requests/{id}', [CoachingRequestController::class, 'show'])->whereNumber('id');
        Route::get('programs', [ProgramController::class, 'index']);
        Route::get('programs/{id}', [ProgramController::class, 'show'])->whereNumber('id');
    });

    // admin, staff
    Route::middleware('role:admin,staff')->group(function () {
        Route::post('members', [MemberController::class, 'store']);
        Route::put('members/{member}', [MemberController::class, 'update']);
        Route::get('members/{member}/qr', [MemberController::class, 'qr']);

        Route::get('plans', [MembershipPlanController::class, 'index']);

        Route::get('members/{member}/memberships', [MembershipController::class, 'indexByMember']);
        Route::post('members/{member}/memberships', [MembershipController::class, 'store']);
        Route::get('memberships/{membership}', [MembershipController::class, 'show']);
        Route::post('memberships/{membership}/cancel', [MembershipController::class, 'cancel']);
        Route::post('memberships/{membership}/payments', [PaymentController::class, 'store'])
            ->middleware('throttle:30,1');

        Route::post('attendances/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('throttle:60,1');

        Route::get('dashboard/summary', [DashboardController::class, 'summary']);
        Route::get('dashboard/expiring', [DashboardController::class, 'expiring']);
        Route::get('reports/attendance', [ReportController::class, 'attendance']);

        Route::post('coaching-requests', [CoachingRequestController::class, 'store']);
        Route::post('coaching-requests/{id}/cancel', [CoachingRequestController::class, 'cancel'])->whereNumber('id');

        Route::get('trainers', [CoachingRequestController::class, 'trainers']);
    });

    // admin only
    Route::middleware('role:admin')->group(function () {
        Route::delete('members/{member}', [MemberController::class, 'destroy']);

        Route::post('plans', [MembershipPlanController::class, 'store']);
        Route::put('plans/{plan}', [MembershipPlanController::class, 'update']);
        Route::delete('plans/{plan}', [MembershipPlanController::class, 'destroy']);

        Route::get('reports/revenue', [ReportController::class, 'revenue']);

        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::put('users/{user}', [UserController::class, 'update']);
    });

    // admin atau trainer yang menangani (kepemilikan dicek lewat scope visibleTo)
    Route::middleware('role:admin,trainer')->group(function () {
        Route::patch('coaching-requests/{id}/respond', [CoachingRequestController::class, 'respond'])->whereNumber('id');
        Route::put('programs/{id}/nutrition', [ProgramController::class, 'updateNutrition'])->whereNumber('id');
        Route::patch('programs/{id}/status', [ProgramController::class, 'updateStatus'])->whereNumber('id');

        Route::get('programs/{id}/logs', [ProgressLogController::class, 'index'])->whereNumber('id');
        Route::post('programs/{id}/logs', [ProgressLogController::class, 'store'])->whereNumber('id');
        Route::put('progress-logs/{id}', [ProgressLogController::class, 'update'])->whereNumber('id');
        Route::delete('progress-logs/{id}', [ProgressLogController::class, 'destroy'])->whereNumber('id');
    });
});
