<?php

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

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

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
