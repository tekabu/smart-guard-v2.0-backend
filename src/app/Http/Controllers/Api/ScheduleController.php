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
        // Validate basic fields
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:SUNDAY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY',
            'room_id' => 'required|exists:rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'active' => 'boolean',
        ]);

        // Check for unique combination: user_id, day_of_week, room_id, subject_id
        $existing = Schedule::where('user_id', $validated['user_id'])
                    ->where('day_of_week', $validated['day_of_week'])
                    ->where('room_id', $validated['room_id'])
                    ->where('subject_id', $validated['subject_id'])
                    ->first();

        if ($existing) {
            return response()->json(['errors' => ['combination' => ['A schedule with the same user, day of week, room, and subject already exists.']]], 422);
        }

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

        $validated = $request->validate($updateRules);

        // Check for unique combination only if any of the key fields are being updated
        $userId = $validated['user_id'] ?? $record->user_id;
        $dayOfWeek = $validated['day_of_week'] ?? $record->day_of_week;
        $roomId = $validated['room_id'] ?? $record->room_id;
        $subjectId = $validated['subject_id'] ?? $record->subject_id;

        // Check if another schedule already exists with the same combination
        $existing = Schedule::where('user_id', $userId)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('room_id', $roomId)
                    ->where('subject_id', $subjectId)
                    ->where('id', '!=', $id)  // Exclude the current record
                    ->first();

        if ($existing) {
            return response()->json(['errors' => ['combination' => ['A schedule with the same user, day of week, room, and subject already exists.']]], 422);
        }

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
