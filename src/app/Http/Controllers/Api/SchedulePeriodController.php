<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchedulePeriod;
use Illuminate\Http\Request;

class SchedulePeriodController extends Controller
{
    public function index()
    {
        $records = SchedulePeriod::query()->with(['schedule'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'active' => 'boolean',
        ]);

        $record = SchedulePeriod::create($validated);
        return response()->json($record, 201);
    }

    public function show(string $id)
    {
        $record = SchedulePeriod::query()->with(['schedule'])->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = SchedulePeriod::findOrFail($id);

        $updateRules = [
            'schedule_id' => 'sometimes|exists:schedules,id',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
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
        $record = SchedulePeriod::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
