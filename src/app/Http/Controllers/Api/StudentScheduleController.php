<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentSchedule;
use App\Traits\ApiResponse;
use App\Rules\UniqueStudentScheduleCombination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentScheduleController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $records = StudentSchedule::with(['student', 'subject', 'schedule', 'schedulePeriod'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        return $this->successResponse(['count' => StudentSchedule::count()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $record = StudentSchedule::create($validated);
        return $this->successResponse($record->load(['student', 'subject', 'schedule', 'schedulePeriod']), 201);
    }

    public function show(string $id)
    {
        $record = StudentSchedule::with(['student', 'subject', 'schedule', 'schedulePeriod'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = StudentSchedule::findOrFail($id);

        $this->hydrateMissingFields($request, $record);
        $validated = $request->validate($this->rules(true, $id));

        $record->update($validated);
        return $this->successResponse($record->load(['student', 'subject', 'schedule', 'schedulePeriod']));
    }

    public function destroy(string $id)
    {
        $record = StudentSchedule::findOrFail($id);
        $record->delete();
        return response()->json(null, 204);
    }

    private function rules(bool $isUpdate = false, ?int $id = null): array
    {
        $studentRule = Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'STUDENT'));
        $base = [
            'student_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', $studentRule],
            'subject_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:subjects,id'],
            'schedule_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:schedules,id'],
            'schedule_period_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:schedule_periods,id',
                new UniqueStudentScheduleCombination($id),
            ],
        ];

        return $base;
    }

    private function hydrateMissingFields(Request $request, StudentSchedule $record): void
    {
        foreach (['student_id', 'subject_id', 'schedule_id', 'schedule_period_id'] as $field) {
            if (!$request->has($field)) {
                $request->merge([$field => $record->{$field}]);
            }
        }
    }
}
