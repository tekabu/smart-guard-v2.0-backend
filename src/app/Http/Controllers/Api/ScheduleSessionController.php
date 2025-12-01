<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleSessionController extends Controller
{
    use ApiResponse;

    private const DAYS = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

    public function index()
    {
        $records = ScheduleSession::with(['sectionSubjectSchedule', 'faculty', 'room'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        return $this->successResponse(['count' => ScheduleSession::count()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $record = ScheduleSession::create($validated);

        return $this->successResponse($record->load(['sectionSubjectSchedule', 'faculty', 'room']), 201);
    }

    public function show(string $id)
    {
        $record = ScheduleSession::with(['sectionSubjectSchedule', 'faculty', 'room'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = ScheduleSession::findOrFail($id);

        $validated = $this->validatePayload($request, true, $record);

        $record->update($validated);

        return $this->successResponse($record->load(['sectionSubjectSchedule', 'faculty', 'room']));
    }

    public function destroy(string $id)
    {
        $record = ScheduleSession::findOrFail($id);
        $record->delete();

        return response()->json(null, 204);
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?ScheduleSession $record = null): array
    {
        $facultyRule = Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'FACULTY'));
        $rules = [
            'section_subject_schedule_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:section_subject_schedules,id'],
            'faculty_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', $facultyRule],
            'day_of_week' => [$isUpdate ? 'sometimes' : 'required', Rule::in(self::DAYS)],
            'room_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:rooms,id'],
            'start_date' => ['nullable', 'date'],
            'start_time' => ['nullable', 'date_format:H:i:s'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i:s'],
        ];

        $validated = $request->validate($rules);

        $resolved = array_merge(
            $record?->only([
                'section_subject_schedule_id',
                'faculty_id',
                'day_of_week',
                'room_id',
                'start_date',
                'start_time',
                'end_date',
                'end_time',
            ]) ?? [],
            $validated
        );

        $this->ensureChronology(
            $resolved['start_date'] ?? null,
            $resolved['end_date'] ?? null,
            $resolved['start_time'] ?? null,
            $resolved['end_time'] ?? null
        );

        $this->ensureUniqueSession($resolved, $record?->id);

        return $validated;
    }

    private function ensureChronology(?string $startDate, ?string $endDate, ?string $startTime, ?string $endTime): void
    {
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'end_date' => ['The end date must be on or after the start date.'],
                ]);
            }
        }

        if ($startTime && $endTime) {
            $start = Carbon::createFromFormat('H:i:s', $startTime);
            $end = Carbon::createFromFormat('H:i:s', $endTime);

            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages([
                    'end_time' => ['The end time must be after the start time.'],
                ]);
            }
        }
    }

    private function ensureUniqueSession(array $data, ?int $ignoreId = null): void
    {
        if (!isset($data['section_subject_schedule_id'])) {
            return;
        }

        $query = ScheduleSession::query()
            ->where('section_subject_schedule_id', $data['section_subject_schedule_id']);

        if (array_key_exists('start_date', $data)) {
            if (is_null($data['start_date'])) {
                $query->whereNull('start_date');
            } else {
                $query->whereDate('start_date', $data['start_date']);
            }
        } else {
            return;
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_date' => ['A session for this schedule on the specified start date already exists.'],
            ]);
        }
    }
}
