<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = Device::query()->with(['lastAccessedByUser', 'rooms'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        $count = Device::count();
        return $this->successResponse(['count' => $count]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'device_id' => 'required|string|unique:devices,device_id',
            'api_token' => 'nullable|string|max:80|unique:devices,api_token',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'boolean',
            'last_seen_at' => 'nullable|date',
        ]);

        $record = Device::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = Device::query()->with(['lastAccessedByUser', 'rooms'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Device::findOrFail($id);

        $updateRules = [
            'device_id' => 'sometimes|string|unique:devices,device_id,{id}',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'sometimes|boolean',
            'last_seen_at' => 'nullable|date',
        ];

        foreach ($updateRules as $field => $rule) {
            $updateRules[$field] = str_replace('{id}', $id, $rule);
        }

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = Device::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
