<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\DeviceBoard;
use App\Models\UserAuditLog;
use Illuminate\Http\Request;

class DeviceBoardController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = DeviceBoard::query()->with('device');

        // Filter by device_id if provided
        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Filter by board_type if provided
        if ($request->has('board_type')) {
            $query->where('board_type', $request->board_type);
        }

        // Filter by active status if provided
        if ($request->has('active')) {
            $query->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN));
        }

        $records = $query->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'board_type' => 'required|in:FINGERPRINT,RFID,LOCK,CAMERA,DISPLAY',
            'mac_address' => 'required|string|unique:device_boards,mac_address',
            'firmware_version' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $record = DeviceBoard::create($validated);
        return $this->successResponse($record->load('device'), 201);
    }

    public function show(string $id)
    {
        $record = DeviceBoard::query()->with('device')->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = DeviceBoard::findOrFail($id);

        $updateRules = [
            'device_id' => 'sometimes|exists:devices,id',
            'board_type' => 'sometimes|in:FINGERPRINT,RFID,LOCK,CAMERA,DISPLAY',
            'mac_address' => 'sometimes|string|unique:device_boards,mac_address,' . $id,
            'firmware_version' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ];

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return $this->successResponse($record->load('device'));
    }

    public function destroy(string $id)
    {
        $record = DeviceBoard::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }

    /**
     * Generate API token for a device board
     */
    public function generateToken(Request $request, string $id)
    {
        $validated = $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $board = DeviceBoard::findOrFail($id);
        
        // Revoke previous tokens for this board (security measure)
        $board->tokens()->delete();

        // Generate longer-lived token for ESP32 devices (30 days)
        $token = $board->createToken($validated['token_name'], ['*'], now()->addDays(30))->plainTextToken;

        // Log token generation for audit
        $user = $request->user();
        UserAuditLog::create([
            'user_id' => $user ? $user->id : null,
            'description' => "Generated ESP32 token for device board: {$board->id}",
        ]);

        return $this->successResponse([
            'board' => $board,
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => now()->addDays(30),
            'capabilities' => [
                'heartbeat' => 'POST /api/device-boards/heartbeat',
                'access_control' => 'Door access operations'
            ],
            'message' => 'Token generated successfully. Store this token securely as it will not be shown again.',
        ]);
    }

    /**
     * Update last_seen_at timestamp (called by ESP32 heartbeat)
     */
    public function heartbeat(Request $request)
    {
        // The authenticated board (via Sanctum token)
        $board = $request->user();

        if (!$board instanceof DeviceBoard) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid authentication. Device board token required.',
            ], 401);
        }

        // Update last seen and log heartbeat
        $board->update([
            'last_seen_at' => now(),
            'last_ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'message' => 'Heartbeat received',
            'id' => $board->id,
            'last_seen_at' => $board->last_seen_at->toDateTimeString(),
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}