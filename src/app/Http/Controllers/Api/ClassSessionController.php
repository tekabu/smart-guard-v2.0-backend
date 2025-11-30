<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Rules\UniqueClassSessionPerDay;
use App\Rules\ValidClassSessionDay;
use App\Rules\ValidClassSessionTime;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
        if (!$request->filled('start_time')) {
            $request->merge(['start_time' => Carbon::now()->format('H:i:s')]);
        }

        $validated = $request->validate($this->validationRules());

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

        $validated = $request->validate($this->validationRules(true, $id));

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

    /**
     * Build validation rules for create/update operations.
     */
    private function validationRules(bool $isUpdate = false, ?int $ignoreId = null): array
    {
        $scheduleRules = [
            $isUpdate ? 'sometimes' : 'required',
            'exists:schedule_periods,id',
            new ValidClassSessionDay,
            new ValidClassSessionTime,
            new UniqueClassSessionPerDay($ignoreId),
        ];

        return [
            'schedule_period_id' => $scheduleRules,
            'start_time' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'date_format:H:i:s',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i:s',
            ],
        ];
    }

    /**
     * Ensure end time is never before start time.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function ensureEndTimeNotBeforeStart(?string $startTime, ?string $endTime): void
    {
        if ($startTime && $endTime && $endTime < $startTime) {
            throw ValidationException::withMessages([
                'end_time' => ['End time must not be earlier than start time.'],
            ]);
        }
    }
}
