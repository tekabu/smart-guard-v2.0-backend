<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index()
    {
        $records = Device::query()->with(['lastAccessedByUser', 'rooms'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|unique:devices,device_id',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ]);

        $record = Device::create($validated);
        return response()->json($record, 201);
    }

    public function show(string $id)
    {
        $record = Device::query()->with(['lastAccessedByUser', 'rooms'])->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Device::findOrFail($id);

        $updateRules = [
            'device_id' => 'sometimes|string|unique:devices,device_id,{id}',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'sometimes|boolean',
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
        $record = Device::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
