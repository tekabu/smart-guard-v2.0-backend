<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleAttendanceController extends Controller
{
    use ApiResponse;

    private const STATUSES = ['PRESENT', 'LATE', 'ABSENT'];

    public function index()
    {
        $records = ScheduleAttendance::with(['scheduleSession', 'student'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        return $this->successResponse(['count' => ScheduleAttendance::count()]);
    }

    public function overview(Request $request)
    {
        $validated = $request->validate([
            'section_id' => ['sometimes', 'integer', 'exists:sections,id'],
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
            'faculty_id' => ['sometimes', 'string', 'exists:users,faculty_id'],
            'date_in' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        $query = ScheduleAttendance::query()->with([
            'student:id,name,student_id',
            'scheduleSession.faculty:id,name,faculty_id',
            'scheduleSession.sectionSubjectSchedule.sectionSubject.section',
            'scheduleSession.sectionSubjectSchedule.sectionSubject.subject',
            'scheduleSession.sectionSubjectSchedule.sectionSubject.faculty:id,name,faculty_id',
        ]);

        if (isset($validated['section_id'])) {
            $query->whereHas('scheduleSession.sectionSubjectSchedule.sectionSubject', function ($q) use ($validated) {
                $q->where('section_id', $validated['section_id']);
            });
        }

        if (isset($validated['subject_id'])) {
            $query->whereHas('scheduleSession.sectionSubjectSchedule.sectionSubject', function ($q) use ($validated) {
                $q->where('subject_id', $validated['subject_id']);
            });
        }

        if (isset($validated['faculty_id'])) {
            $facultyIds = User::query()
                ->where('faculty_id', $validated['faculty_id'])
                ->pluck('id')
                ->all();

            $query->where(function ($builder) use ($facultyIds) {
                $builder->whereHas('scheduleSession', function ($sessionQuery) use ($facultyIds) {
                    $sessionQuery->whereIn('faculty_id', $facultyIds);
                })->orWhereHas('scheduleSession.sectionSubjectSchedule.sectionSubject', function ($sectionSubjectQuery) use ($facultyIds) {
                    $sectionSubjectQuery->whereIn('faculty_id', $facultyIds);
                });
            });
        }

        if (isset($validated['date_in'])) {
            $query->whereDate('date_in', $validated['date_in']);
        }

        $records = $query->get()->map(function (ScheduleAttendance $attendance) {
            $student = $attendance->student;
            $session = $attendance->scheduleSession;
            $sectionSubject = $session?->sectionSubjectSchedule?->sectionSubject;
            $sessionFaculty = $session?->faculty;
            $sectionFaculty = $sectionSubject?->faculty;

            return [
                'section' => $sectionSubject?->section?->section,
                'subject' => $sectionSubject?->subject?->subject,
                'faculty' => $sessionFaculty?->name ?? $sectionFaculty?->name,
                'student' => $student?->name,
                'student_id' => $student?->student_id,
                'faculty_id' => $sessionFaculty?->faculty_id ?? $sectionFaculty?->faculty_id,
                'date_in' => $attendance->date_in,
                'time_in' => $attendance->time_in,
            ];
        })->values();

        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $record = ScheduleAttendance::create($validated);

        return $this->successResponse($record->load(['scheduleSession', 'student']), 201);
    }

    public function show(string $id)
    {
        $record = ScheduleAttendance::with(['scheduleSession', 'student'])->findOrFail($id);
        return $this->successResponse($record);
    }

    public function update(Request $request, string $id)
    {
        $record = ScheduleAttendance::findOrFail($id);

        $validated = $this->validatePayload($request, true, $record);

        $record->update($validated);

        return $this->successResponse($record->load(['scheduleSession', 'student']));
    }

    public function destroy(string $id)
    {
        $record = ScheduleAttendance::findOrFail($id);
        $record->delete();

        return response()->json(null, 204);
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?ScheduleAttendance $record = null): array
    {
        $studentRule = Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'STUDENT'));
        $rules = [
            'schedule_session_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:schedule_sessions,id'],
            'student_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', $studentRule],
            'date_in' => ['nullable', 'date'],
            'time_in' => ['nullable', 'date_format:H:i:s'],
            'date_out' => ['nullable', 'date'],
            'time_out' => ['nullable', 'date_format:H:i:s'],
            'attendance_status' => [$isUpdate ? 'sometimes' : 'required', Rule::in(self::STATUSES)],
        ];

        $validated = $request->validate($rules);

        $resolved = array_merge(
            $record?->only([
                'schedule_session_id',
                'student_id',
                'date_in',
                'time_in',
                'date_out',
                'time_out',
                'attendance_status',
            ]) ?? [],
            $validated
        );

        $this->ensureChronology(
            $resolved['date_in'] ?? null,
            $resolved['date_out'] ?? null,
            $resolved['time_in'] ?? null,
            $resolved['time_out'] ?? null
        );

        $this->ensureUniqueAttendance($resolved, $record?->id);
        $this->ensureSessionIsActive($resolved, $isUpdate);

        return $validated;
    }

    private function ensureChronology(?string $dateIn, ?string $dateOut, ?string $timeIn, ?string $timeOut): void
    {
        if ($dateIn && $dateOut) {
            $start = Carbon::parse($dateIn)->startOfDay();
            $end = Carbon::parse($dateOut)->startOfDay();

            if ($end->lt($start)) {
                throw ValidationException::withMessages([
                    'date_out' => ['The exit date must be on or after the entry date.'],
                ]);
            }
        }

        if ($timeIn && $timeOut) {
            $start = Carbon::createFromFormat('H:i:s', $timeIn);
            $end = Carbon::createFromFormat('H:i:s', $timeOut);

            if ($end->lessThan($start)) {
                throw ValidationException::withMessages([
                    'time_out' => ['The exit time must be after the entry time.'],
                ]);
            }
        }
    }

    private function ensureUniqueAttendance(array $data, ?int $ignoreId = null): void
    {
        if (!isset($data['schedule_session_id'], $data['student_id']) || !array_key_exists('date_in', $data)) {
            return;
        }

        $query = ScheduleAttendance::query()
            ->where('schedule_session_id', $data['schedule_session_id'])
            ->where('student_id', $data['student_id']);

        if (is_null($data['date_in'])) {
            $query->whereNull('date_in');
        } else {
            $query->whereDate('date_in', $data['date_in']);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'date_in' => ['Attendance for this student and session on the specified date already exists.'],
            ]);
        }
    }

    private function ensureSessionIsActive(array $data, bool $isUpdate): void
    {
        if ($isUpdate || !isset($data['schedule_session_id'])) {
            return;
        }

        $isActive = ScheduleSession::query()
            ->whereKey($data['schedule_session_id'])
            ->isActive()
            ->exists();

        if (!$isActive) {
            throw ValidationException::withMessages([
                'schedule_session_id' => ['Attendance can only be recorded for active schedule sessions.'],
            ]);
        }
    }
}
