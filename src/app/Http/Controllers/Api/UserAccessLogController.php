<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAccessLog;
use Illuminate\Http\Request;

class UserAccessLogController extends Controller
{
    public function index()
    {
        $records = UserAccessLog::query()->with(['user', 'room', 'device'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'device_id' => 'required|exists:devices,id',
            'access_used' => 'required|in:FINGERPRINT,RFID,ADMIN,MANUAL',
        ]);

        $record = UserAccessLog::create($validated);
        return response()->json($record, 201);
    }

    public function show(string $id)
    {
        $record = UserAccessLog::query()->with(['user', 'room', 'device'])->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = UserAccessLog::findOrFail($id);

        $updateRules = [
            'user_id' => 'sometimes|exists:users,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'device_id' => 'sometimes|exists:devices,id',
            'access_used' => 'sometimes|in:FINGERPRINT,RFID,ADMIN,MANUAL',
        ];

        // Replace {id} placeholder in unique rules
        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return response()->json($record);
    }

    public function destroy(string $id)
    {
        $record = UserAccessLog::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
