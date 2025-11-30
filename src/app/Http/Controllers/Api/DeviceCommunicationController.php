<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\DeviceBoard;
use App\Models\SchedulePeriod;
use App\Models\User;
use App\Models\UserFingerprint;
use App\Models\UserRfid;
use App\Traits\ApiResponse;
use App\Traits\HandlesClassSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'card_id' => ['required', 'string'],
        ]);

        $rfid = UserRfid::where('card_id', $validated['card_id'])->first();

        if (!$rfid) {
            return $this->errorResponse('Card not found.', 404);
        }

        return $this->successResponse([
            'valid' => true,
            'user_id' => $rfid->user_id,
        ]);
    }

    public function validateFingerprint(Request $request)
    {
        $validated = $request->validate([
            'fingerprint_id' => ['required', 'string'],
        ]);

        $fingerprint = UserFingerprint::where('fingerprint_id', $validated['fingerprint_id'])->first();

        if (!$fingerprint) {
            return $this->errorResponse('Fingerprint not found.', 404);
        }

        return $this->successResponse([
            'valid' => true,
            'user_id' => $fingerprint->user_id,
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
            'fingerprint_id' => ['required', 'string'],
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
            'fingerprint_id' => ['required', 'string'],
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
}
