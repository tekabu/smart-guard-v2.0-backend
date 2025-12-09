<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\DeviceBoard;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Models\UserFingerprint;
use App\Models\UserRfid;
use App\Traits\ApiResponse;
use App\Traits\HandlesClassSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DeviceCommunicationController extends Controller
{
    use ApiResponse, HandlesClassSessions;

    /**
     * Allow an authenticated device board to report its status and update metadata.
     */
    public function heartbeat(Request $request)
    {
        /** @var DeviceBoard $deviceBoard */
        $deviceBoard = $request->user();

        $validated = $request->validate([
            'firmware_version' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('firmware_version', $validated)) {
            $deviceBoard->firmware_version = $validated['firmware_version'];
        }

        $deviceBoard->last_seen_at = now();
        $deviceBoard->last_ip = $request->ip();
        $deviceBoard->save();

        return $this->successResponse([
            'board' => $deviceBoard->fresh()->load('device'),
        ]);
    }

    /**
     * Fetch the authenticated device board profile.
     */
    public function me(Request $request)
    {
        /** @var DeviceBoard $deviceBoard */
        $deviceBoard = $request->user()->load('device');

        return $this->successResponse($deviceBoard);
    }

    public function validateCard(Request $request)
    {
        /** @var DeviceBoard $board */
        $board = $request->user();

        $validated = $request->validate([
            'card_id' => ['required', 'string'],
        ]);

        $rfid = UserRfid::where('card_id', $validated['card_id'])->with('user')->first();

        if (!$rfid) {
            return $this->errorResponse('Card not found.', 404);
        }

        $attendanceRecorded = false;

        if ($rfid->user && $rfid->user->role === 'STUDENT') {
            $result = $this->recordStudentAttendance($rfid->user, $board);
            if ($result instanceof JsonResponse) {
                return $result;
            }
            $attendanceRecorded = $result;
        }

        return $this->successResponse([
            'valid' => true,
            'user_id' => $rfid->user_id,
            'attendance_recorded' => $attendanceRecorded,
        ]);
    }

    public function validateFingerprint(Request $request)
    {
        /** @var DeviceBoard $board */
        $board = $request->user();

        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100'],
        ]);

        $fingerprint = UserFingerprint::where('fingerprint_id', $validated['fingerprint_id'])->with('user')->first();

        if (!$fingerprint) {
            return $this->errorResponse('Fingerprint not found.', 404);
        }

        $attendanceRecorded = false;

        if ($fingerprint->user && $fingerprint->user->role === 'STUDENT') {
            $result = $this->recordStudentAttendance($fingerprint->user, $board);
            if ($result instanceof JsonResponse) {
                return $result;
            }
            $attendanceRecorded = $result;
        }

        return $this->successResponse([
            'valid' => true,
            'user_id' => $fingerprint->user_id,
            'attendance_recorded' => $attendanceRecorded,
        ]);
    }

    public function scanCard(Request $request)
    {
        $request->validate([
            'card_id' => ['required', 'string'],
        ]);

        return $this->successResponse([
            'scanned' => true,
            'card_id' => $request->input('card_id'),
        ]);
    }

    public function scanFingerprint(Request $request)
    {
        $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100'],
        ]);

        return $this->successResponse([
            'scanned' => true,
            'fingerprint_id' => $request->input('fingerprint_id'),
        ]);
    }

    public function createClassSessionFromCard(Request $request)
    {
        $validated = $request->validate([
            'card_id' => ['required', 'string'],
        ]);

        $rfid = UserRfid::where('card_id', $validated['card_id'])->with('user')->first();

        if (!$rfid) {
            return $this->errorResponse('Card not found.', 404);
        }

        return $this->createSessionForUser($rfid->user);
    }

    public function createClassSessionFromFingerprint(Request $request)
    {
        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string', 'max:100'],
        ]);

        $fingerprint = UserFingerprint::where('fingerprint_id', $validated['fingerprint_id'])->with('user')->first();

        if (!$fingerprint) {
            return $this->errorResponse('Fingerprint not found.', 404);
        }

        return $this->createSessionForUser($fingerprint->user);
    }

    private function createSessionForUser(?User $user)
    {
        if (!$user) {
            return $this->errorResponse('User not found for credential.', 404);
        }

        if ($user->role !== 'FACULTY') {
            return $this->errorResponse('Only faculty members can start class sessions.', 422);
        }

        $schedulePeriod = $this->findActiveSchedulePeriod($user);

        if (!$schedulePeriod) {
            return $this->errorResponse('No active schedule period found for this faculty member.', 422);
        }

        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => Carbon::now()->format('H:i:s'),
            'end_time' => null,
        ];

        Validator::make($sessionData, $this->classSessionValidationRules())->validate();

        $this->ensureEndTimeNotBeforeStart($sessionData['start_time'], $sessionData['end_time']);

        $record = ClassSession::create($sessionData);

        return $this->successResponse($record, 201);
    }

    private function findActiveSchedulePeriod(User $user): ?SchedulePeriod
    {
        $currentDay = strtoupper(Carbon::now()->format('l'));
        $currentTime = Carbon::now()->format('H:i:s');

        return SchedulePeriod::whereHas('schedule', function ($query) use ($user, $currentDay) {
                $query->where('user_id', $user->id)
                    ->where('day_of_week', $currentDay)
                    ->where('active', true);
            })
            ->where('active', true)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();
    }

    private function recordStudentAttendance(User $student, DeviceBoard $board): JsonResponse|bool
    {
        $classSession = $this->findApplicableClassSessionForStudent($student, $board);

        if (!$classSession) {
            return $this->errorResponse('No active class session found for this student.', 422);
        }

        $attendance = StudentAttendance::firstOrCreate([
            'user_id' => $student->id,
            'class_session_id' => $classSession->id,
        ]);

        return $attendance->wasRecentlyCreated || $attendance->exists();
    }

    private function findApplicableClassSessionForStudent(User $student, DeviceBoard $board): ?ClassSession
    {
        $roomId = $this->resolveBoardRoomId($board);

        if (!$roomId) {
            return null;
        }

        $currentDay = strtoupper(Carbon::now()->format('l'));
        $currentDate = Carbon::now()->toDateString();
        $currentTime = Carbon::now()->format('H:i:s');

        $classSession = ClassSession::whereHas('schedulePeriod.schedule', function ($query) use ($roomId, $currentDay) {
                $query->where('room_id', $roomId)
                    ->where('day_of_week', $currentDay)
                    ->where('active', true);
            })
            ->whereDate('created_at', $currentDate)
            ->where('start_time', '<=', $currentTime)
            ->where(function ($query) use ($currentTime) {
                $query->whereNull('end_time')
                    ->orWhere('end_time', '>=', $currentTime);
            })
            ->with(['schedulePeriod.schedule'])
            ->latest('start_time')
            ->first();

        if (!$classSession) {
            return null;
        }

        $schedule = $classSession->schedulePeriod->schedule;

        $studentHasSchedule = Schedule::where('user_id', $student->id)
            ->where('room_id', $schedule->room_id)
            ->where('subject_id', $schedule->subject_id)
            ->where('day_of_week', $schedule->day_of_week)
            ->where('active', true)
            ->exists();

        return $studentHasSchedule ? $classSession : null;
    }

    private function resolveBoardRoomId(DeviceBoard $board): ?int
    {
        $device = $board->device()->with('rooms')->first();

        if (!$device) {
            return null;
        }

        $room = $device->rooms->first();

        return $room?->id;
    }
}
