<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\DeviceBoard;
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
}