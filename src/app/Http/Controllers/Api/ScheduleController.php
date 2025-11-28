<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $records = Schedule::query()->with(['user', 'room', 'subject', 'periods'])->get();
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:SUNDAY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY',
            'room_id' => 'required|exists:rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'active' => 'boolean',
        ]);

        $record = Schedule::create($validated);
        return response()->json($record, 201);
    }

    public function show(string $id)
    {
        $record = Schedule::query()->with(['user', 'room', 'subject', 'periods'])->findOrFail($id);
        return response()->json($record);
    }

    public function update(Request $request, string $id)
    {
        $record = Schedule::findOrFail($id);

        $updateRules = [
            'user_id' => 'sometimes|exists:users,id',
            'day_of_week' => 'sometimes|in:SUNDAY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY',
            'room_id' => 'sometimes|exists:rooms,id',
            'subject_id' => 'sometimes|exists:subjects,id',
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
        $record = Schedule::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
