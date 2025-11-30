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
use App\Http\Controllers\Api\ClassSessionController;
use App\Http\Controllers\Api\StudentScheduleController;
use App\Http\Controllers\Api\UserAccessLogController;
use App\Http\Controllers\Api\UserAuditLogController;
use App\Http\Controllers\Api\DeviceBoardController;
use App\Http\Controllers\Api\DeviceCommunicationController;
use App\Http\Middleware\EnsureDeviceBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health Check API
Route::get('health', [HealthController::class, 'check']);

// Authentication API
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');

Route::prefix('device-communications')
    ->middleware(['auth:sanctum', EnsureDeviceBoard::class])
    ->group(function () {
        Route::post('heartbeat', [DeviceCommunicationController::class, 'heartbeat']);
        Route::get('me', [DeviceCommunicationController::class, 'me']);
        Route::post('validate-card', [DeviceCommunicationController::class, 'validateCard']);
        Route::post('validate-fingerprint', [DeviceCommunicationController::class, 'validateFingerprint']);
        Route::post('scan-card', [DeviceCommunicationController::class, 'scanCard']);
        Route::post('scan-fingerprint', [DeviceCommunicationController::class, 'scanFingerprint']);
        Route::post('class-sessions/from-card', [DeviceCommunicationController::class, 'createClassSessionFromCard']);
        Route::post('class-sessions/from-fingerprint', [DeviceCommunicationController::class, 'createClassSessionFromFingerprint']);
    });

Route::middleware('auth:sanctum')->group(function () {
    // Count endpoints
    Route::get('users/count', [UserController::class, 'count']);
    Route::get('user-fingerprints/count', [UserFingerprintController::class, 'count']);
    Route::get('user-rfids/count', [UserRfidController::class, 'count']);
    Route::get('devices/count', [DeviceController::class, 'count']);
    Route::get('device-boards/count', [DeviceBoardController::class, 'count']);
    Route::get('rooms/count', [RoomController::class, 'count']);
    Route::get('subjects/count', [SubjectController::class, 'count']);
    Route::get('schedules/count', [ScheduleController::class, 'count']);
    Route::get('schedules/by-subject', [ScheduleController::class, 'bySubject']);
    Route::get('schedule-periods/count', [SchedulePeriodController::class, 'count']);
    Route::get('class-sessions/count', [ClassSessionController::class, 'count']);
    Route::get('student-schedules/count', [StudentScheduleController::class, 'count']);
    Route::get('user-access-logs/count', [UserAccessLogController::class, 'count']);
    Route::get('user-audit-logs/count', [UserAuditLogController::class, 'count']);

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

    // Class Sessions API
    Route::post('class-sessions/{class_session}/close', [ClassSessionController::class, 'close']);
    Route::apiResource("class-sessions", ClassSessionController::class);

    // Student Schedules API
    Route::apiResource("student-schedules", StudentScheduleController::class);

    // User Access Logs API
    Route::apiResource("user-access-logs", UserAccessLogController::class)->except(["update"]);

    // User Audit Logs API
    Route::apiResource("user-audit-logs", UserAuditLogController::class)->except(["update"]);
});
