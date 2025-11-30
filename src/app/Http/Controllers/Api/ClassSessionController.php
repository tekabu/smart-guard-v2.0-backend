<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Traits\ApiResponse;
use App\Traits\HandlesClassSessions;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    use ApiResponse, HandlesClassSessions;

    public function index()
    {
        $records = ClassSession::query()->with(['schedulePeriod'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        $count = ClassSession::count();
        return $this->successResponse(['count' => $count]);
    }

    public function store(Request $request)
    {
        if (!$request->filled('start_time')) {
            $request->merge(['start_time' => Carbon::now()->format('H:i:s')]);
        }

        $validated = $request->validate($this->classSessionValidationRules());

        $this->ensureEndTimeNotBeforeStart($validated['start_time'] ?? null, $validated['end_time'] ?? null);

        $record = ClassSession::create($validated);
        return $this->successResponse($record, 201);
    }

    public function show(string $id)
    {
        $record = ClassSession::query()->with(['schedulePeriod'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = ClassSession::findOrFail($id);

        $validated = $request->validate($this->classSessionValidationRules(true, $id));

        $nextStartTime = $validated['start_time'] ?? $record->start_time;
        $nextEndTime = array_key_exists('end_time', $validated) ? $validated['end_time'] : $record->end_time;

        $this->ensureEndTimeNotBeforeStart($nextStartTime, $nextEndTime);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function close(Request $request, string $id)
    {
        $record = ClassSession::findOrFail($id);

        $validated = $request->validate([
            'end_time' => ['nullable', 'date_format:H:i:s'],
        ]);

        $endTime = $validated['end_time'] ?? Carbon::now()->format('H:i:s');

        if ($record->start_time && $endTime < $record->start_time) {
            $endTime = $record->start_time;
        }

        $record->update(['end_time' => $endTime]);

        return $this->successResponse($record->fresh());
    }

    public function destroy(string $id)
    {
        $record = ClassSession::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
