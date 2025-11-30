<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchedulePeriod;
use App\Rules\NoScheduleOverlap;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class SchedulePeriodController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = SchedulePeriod::query()->with(['schedule']);
        
        // Filter by schedule_id if provided
        if ($request->has('schedule_id')) {
            $query->where('schedule_id', $request->input('schedule_id'));
        }
        
        $records = $query->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        $count = SchedulePeriod::count();
        return $this->successResponse(['count' => $count]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'start_time' => ['required', 'date_format:H:i:s', new NoScheduleOverlap],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time', new NoScheduleOverlap],
            'active' => 'boolean',
        ]);

        $record = SchedulePeriod::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = SchedulePeriod::query()->with(['schedule'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = SchedulePeriod::findOrFail($id);

        $updateRules = [
            'schedule_id' => 'sometimes|exists:schedules,id',
            'start_time' => ['sometimes', 'date_format:H:i:s', new NoScheduleOverlap($id)],
            'end_time' => ['sometimes', 'date_format:H:i:s', 'after:start_time', new NoScheduleOverlap($id)],
            'active' => 'sometimes|boolean',
        ];

        // We don't need to replace {id} in rules that contain objects, only in string rules
        // So we'll reconstruct the rules without str_replace since we're using objects now

        $validated = $request->validate($updateRules);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = SchedulePeriod::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
