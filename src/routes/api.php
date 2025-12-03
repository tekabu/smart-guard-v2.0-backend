<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassSessionController;
use App\Http\Controllers\Api\DeviceBoardController;
use App\Http\Controllers\Api\DeviceCommunicationController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ScheduleAttendanceController;
use App\Http\Controllers\Api\SchedulePeriodController;
use App\Http\Controllers\Api\ScheduleSessionController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\SectionSubjectController;
use App\Http\Controllers\Api\SectionSubjectScheduleController;
use App\Http\Controllers\Api\SectionSubjectStudentController;
use App\Http\Controllers\Api\StudentScheduleController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\UserAccessLogController;
use App\Http\Controllers\Api\UserAuditLogController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserFingerprintController;
use App\Http\Controllers\Api\UserRfidController;
use App\Http\Middleware\EnsureDeviceBoard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health Check API
Route::get('health', [HealthController::class, 'check']);

// Authentication API
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// Token Management API (admin only)
Route::prefix('tokens')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [AuthController::class, 'createToken']);
    Route::get('/', [AuthController::class, 'listTokens']);
    Route::delete('{tokenId}', [AuthController::class, 'revokeToken']);
});

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
    Route::get('schedule-sessions/count', [ScheduleSessionController::class, 'count']);
    Route::get('schedule-sessions/overview', [ScheduleSessionController::class, 'overview']);
    Route::get('schedule-attendance/count', [ScheduleAttendanceController::class, 'count']);
    Route::get('schedule-attendance/overview', [ScheduleAttendanceController::class, 'overview']);
    Route::get('user-access-logs/count', [UserAccessLogController::class, 'count']);
    Route::get('user-audit-logs/count', [UserAuditLogController::class, 'count']);

    // Users API
    Route::apiResource("users", UserController::class);

    // User Fingerprints API
    Route::get('user-fingerprints/fingerprint/{fingerprintId}', [UserFingerprintController::class, 'showByFingerprintId']);
    Route::apiResource("user-fingerprints", UserFingerprintController::class);

    // User RFIDs API
    Route::get('user-rfids/card/{cardId}', [UserRfidController::class, 'showByCardId']);
    Route::apiResource("user-rfids", UserRfidController::class);

    // Devices API
    Route::apiResource("devices", DeviceController::class);

    // Device Boards API
    Route::apiResource("device-boards", DeviceBoardController::class);

    // Rooms API
    Route::apiResource("rooms", RoomController::class);

    // Subjects API
    Route::apiResource("subjects", SubjectController::class);
    // Sections API
    Route::apiResource("sections", SectionController::class);
    Route::get('section-subjects/options', [SectionSubjectController::class, 'options']);
    Route::apiResource("section-subjects", SectionSubjectController::class);
    Route::get('section-subject-schedules/faculty/{facultyId}/current', [SectionSubjectScheduleController::class, 'currentScheduleForFaculty']);
    Route::post('section-subject-schedules/student/{studentId}/attendance', [SectionSubjectScheduleController::class, 'recordStudentAttendance']);
    Route::apiResource("section-subject-schedules", SectionSubjectScheduleController::class);
    Route::apiResource("section-subject-students", SectionSubjectStudentController::class);

    // Schedules API
    Route::apiResource("schedules", ScheduleController::class);

    // Schedule Periods API
    Route::apiResource("schedule-periods", SchedulePeriodController::class);

    // Schedule Sessions API
    Route::post('schedule-sessions/create', [ScheduleSessionController::class, 'createFromSchedule']);
    Route::post('schedule-sessions/{schedule_session}/start', [ScheduleSessionController::class, 'start']);
    Route::post('schedule-sessions/{schedule_session}/close', [ScheduleSessionController::class, 'close']);
    Route::apiResource("schedule-sessions", ScheduleSessionController::class);

    // Schedule Attendance API
    Route::apiResource("schedule-attendance", ScheduleAttendanceController::class);

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
