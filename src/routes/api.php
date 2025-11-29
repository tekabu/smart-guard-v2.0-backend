<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserFingerprintController;
use App\Http\Controllers\Api\UserRfidController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchedulePeriodController;
use App\Http\Controllers\Api\UserAccessLogController;
use App\Http\Controllers\Api\UserAuditLogController;
use App\Http\Controllers\Api\DeviceBoardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health Check API
Route::get('health', [HealthController::class, 'check']);

// Authentication API
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Users API
    Route::apiResource("users", UserController::class);

    // User Fingerprints API
    Route::apiResource("user-fingerprints", UserFingerprintController::class);

    // User RFIDs API
    Route::apiResource("user-rfids", UserRfidController::class);

    // Devices API
    Route::apiResource("devices", DeviceController::class);

    // Device Boards API
    Route::apiResource("device-boards", DeviceBoardController::class);

    // Rooms API
    Route::apiResource("rooms", RoomController::class);

    // Subjects API
    Route::apiResource("subjects", SubjectController::class);

    // Schedules API
    Route::apiResource("schedules", ScheduleController::class);

    // Schedule Periods API
    Route::apiResource("schedule-periods", SchedulePeriodController::class);

    // User Access Logs API
    Route::apiResource("user-access-logs", UserAccessLogController::class)->except(["update"]);

    // User Audit Logs API
    Route::apiResource("user-audit-logs", UserAuditLogController::class)->except(["update"]);
});
