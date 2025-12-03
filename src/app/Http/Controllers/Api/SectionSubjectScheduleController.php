<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SectionSubject;
use App\Models\SectionSubjectSchedule;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SectionSubjectScheduleController extends Controller
{
    use ApiResponse;

    private const DAYS = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
    private const RELATIONS = [
        'sectionSubject.section',
        'sectionSubject.subject',
        'sectionSubject.faculty',
        'room',
    ];

    public function index(Request $request)
    {
        $records = SectionSubjectSchedule::with(self::RELATIONS)
            ->when($request->filled('section_id'), fn ($query) => $query->whereHas(
                'sectionSubject',
                fn ($sectionSubjectQuery) => $sectionSubjectQuery->where('section_id', $request->input('section_id'))
            ))
            ->when($request->filled('subject_id'), fn ($query) => $query->whereHas(
                'sectionSubject',
                fn ($sectionSubjectQuery) => $sectionSubjectQuery->where('subject_id', $request->input('subject_id'))
            ))
            ->when($request->filled('day_of_week'), fn ($query) => $query->where('day_of_week', $request->input('day_of_week')))
            ->when($request->filled('room_id'), fn ($query) => $query->where('room_id', $request->input('room_id')))
            ->get();

        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_subject_id' => ['required', 'exists:section_subjects,id'],
            'day_of_week' => ['required', Rule::in(self::DAYS)],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s'],
        ]);

        $this->ensureValidTimeRange($validated['start_time'], $validated['end_time']);
        $this->ensureUniqueCombination($validated);
        $this->ensureNoRoomScheduleConflict($validated);

        $record = SectionSubjectSchedule::create($validated);

        return $this->successResponse($record->load(self::RELATIONS), 201);
    }

    public function show(string $id)
    {
        $record = SectionSubjectSchedule::with(self::RELATIONS)->findOrFail($id);

        return $this->successResponse($record);
    }

    public function currentScheduleForFaculty(string $facultyId)
    {
        $facultyAssigned = SectionSubject::where('faculty_id', $facultyId)->exists();

        if (! $facultyAssigned) {
            return $this->errorResponse('Faculty is not assigned to any section subject.', 404);
        }

        $now = Carbon::now();
        $currentDay = strtoupper($now->format('l'));
        $currentTime = $now->format('H:i:s');

        $schedules = SectionSubjectSchedule::with(self::RELATIONS)
            ->whereHas('sectionSubject', fn ($query) => $query->where('faculty_id', $facultyId))
            ->where('day_of_week', $currentDay)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->get();

        if ($schedules->isEmpty()) {
            return $this->errorResponse('Faculty has no schedule for the current time window.', 404);
        }

        return $this->successResponse($schedules);
    }

    public function update(Request $request, string $id)
    {
        $record = SectionSubjectSchedule::findOrFail($id);

        $validated = $request->validate([
            'section_subject_id' => ['sometimes', 'exists:section_subjects,id'],
            'day_of_week' => ['sometimes', Rule::in(self::DAYS)],
            'room_id' => ['sometimes', 'exists:rooms,id'],
            'start_time' => ['sometimes', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'date_format:H:i:s'],
        ]);

        $data = array_merge($record->only([
            'section_subject_id',
            'day_of_week',
            'room_id',
            'start_time',
            'end_time',
        ]), $validated);

        $this->ensureValidTimeRange($data['start_time'], $data['end_time']);
        $this->ensureUniqueCombination($data, (int) $id);
        $this->ensureNoRoomScheduleConflict($data, (int) $id);

        $record->update($validated);

        return $this->successResponse($record->load(self::RELATIONS));
    }

    public function destroy(string $id)
    {
        $record = SectionSubjectSchedule::findOrFail($id);
        $record->delete();

        return response()->json(null, 204);
    }

    private function ensureValidTimeRange(string $start, string $end): void
    {
        $startTime = Carbon::createFromFormat('H:i:s', $start);
        $endTime = Carbon::createFromFormat('H:i:s', $end);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            throw ValidationException::withMessages([
                'end_time' => ['The end time must be after the start time.'],
            ]);
        }
    }

    private function ensureUniqueCombination(array $data, ?int $ignoreId = null): void
    {
        $query = SectionSubjectSchedule::query()
            ->where('section_subject_id', $data['section_subject_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('room_id', $data['room_id'])
            ->where('start_time', $data['start_time'])
            ->where('end_time', $data['end_time']);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'section_subject_id' => ['The combination of section subject, day, room, and times has already been taken.'],
            ]);
        }
    }

    private function ensureNoRoomScheduleConflict(array $data, ?int $ignoreId = null): void
    {
        $conflictQuery = SectionSubjectSchedule::query()
            ->where('room_id', $data['room_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time']);

        if ($ignoreId) {
            $conflictQuery->where('id', '!=', $ignoreId);
        }

        if ($conflictQuery->exists()) {
            throw ValidationException::withMessages([
                'start_time' => ['The specified time range conflicts with an existing schedule for this room and day.'],
            ]);
        }
    }
}
