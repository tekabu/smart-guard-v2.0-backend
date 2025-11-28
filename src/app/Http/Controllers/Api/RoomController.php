<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class RoomController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = Room::query()->with(['device', 'lastOpenedByUser', 'lastClosedByUser'])->get();
        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'device_id' => 'nullable|exists:devices,id',
            'active' => 'boolean',
        ]);

        $record = Room::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = Room::query()->with(['device', 'lastOpenedByUser', 'lastClosedByUser'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Room::findOrFail($id);

        $updateRules = [
            'room_number' => 'sometimes|string|unique:rooms,room_number,{id}',
            'device_id' => 'nullable|exists:devices,id',
            'active' => 'sometimes|boolean',
        ];

        // Replace {id} placeholder in unique rules
        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = Room::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
