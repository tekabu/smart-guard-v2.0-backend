<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Rules\UniqueClassSessionPerDay;
use App\Rules\ValidClassSessionDay;
use App\Rules\ValidClassSessionTime;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ClassSessionController extends Controller
{
    use ApiResponse;

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
        $validated = $request->validate([
            'schedule_period_id' => [
                'required',
                'exists:schedule_periods,id',
                new ValidClassSessionDay,
                new ValidClassSessionTime,
                new UniqueClassSessionPerDay,
            ],
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
        ]);

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

        $validated = $request->validate([
            'schedule_period_id' => [
                'sometimes',
                'exists:schedule_periods,id',
                new ValidClassSessionDay,
                new ValidClassSessionTime,
                new UniqueClassSessionPerDay($id),
            ],
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
        ]);

        $record->update($validated);
        return $this->successResponse($record);
    }

    public function destroy(string $id)
    {
        $record = ClassSession::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }
}
