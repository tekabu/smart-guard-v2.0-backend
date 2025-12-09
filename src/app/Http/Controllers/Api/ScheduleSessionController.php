<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SectionSubjectSchedule;
use App\Services\Mqtt\SmartGuardMqttPublisher;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleSessionController extends Controller
{
    use ApiResponse;

    private const DAYS = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

    public function __construct(private readonly SmartGuardMqttPublisher $mqttPublisher)
    {
    }

    public function index()
    {
        $records = ScheduleSession::with(['sectionSubjectSchedule', 'faculty', 'room'])->get();
        return $this->successResponse($records);
    }

    public function count()
    {
        return $this->successResponse(['count' => ScheduleSession::count()]);
    }

    public function overview(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'sometimes|integer|exists:sections,id',
            'subject_id' => 'sometimes|integer|exists:subjects,id',
            'faculty_id' => 'sometimes|integer|exists:users,id',
            'day_of_week' => ['sometimes', Rule::in(self::DAYS)],
            'start_date' => 'sometimes|date',
            'has_class' => 'sometimes|boolean',
        ]);

        $query = ScheduleSession::query()
            ->with([
                'sectionSubjectSchedule.sectionSubject.section',
                'sectionSubjectSchedule.sectionSubject.subject',
                'faculty',
            ]);

        if (isset($validated['section_id'])) {
            $query->whereHas('sectionSubjectSchedule.sectionSubject', function ($q) use ($validated) {
                $q->where('section_id', $validated['section_id']);
            });
        }

        if (isset($validated['subject_id'])) {
            $query->whereHas('sectionSubjectSchedule.sectionSubject', function ($q) use ($validated) {
                $q->where('subject_id', $validated['subject_id']);
            });
        }

        if (isset($validated['faculty_id'])) {
            $query->where('faculty_id', $validated['faculty_id']);
        }

        if (isset($validated['day_of_week'])) {
            $query->where('day_of_week', $validated['day_of_week']);
        }

        if (isset($validated['start_date'])) {
            $query->whereDate('start_date', $validated['start_date']);
        }

        if (!empty($validated['has_class'])) {
            $query->whereNotNull('start_time');
        }

        $records = $query->get()->map(function (ScheduleSession $session) {
            $sectionSubject = $session->sectionSubjectSchedule?->sectionSubject;

            return [
                'id' => $session->id,
                'section' => $sectionSubject?->section?->section,
                'subject' => $sectionSubject?->subject?->subject,
                'faculty' => $session->faculty?->name ?? $sectionSubject?->faculty?->name,
                'day_of_week' => $session->day_of_week,
                'start_date' => $session->start_date,
                'start_time' => $session->start_time,
                'end_time' => $session->end_time,
            ];
        })->values();

        return $this->successResponse($records);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $record = ScheduleSession::create($validated);

        $record->load(['sectionSubjectSchedule', 'faculty', 'room']);

        if ($this->shouldBroadcastSessionCreation($request)) {
            $record->loadMissing([
                'sectionSubjectSchedule.sectionSubject.section',
                'sectionSubjectSchedule.sectionSubject.subject',
                'sectionSubjectSchedule.sectionSubject.faculty',
            ]);

            $this->broadcastSessionCreated($record);
        }

        return $this->successResponse($record, 201);
    }

    public function createFromSchedule(Request $request)
    {
        $payload = $request->validate([
            'section_subject_schedule_id' => ['required', 'integer', 'exists:section_subject_schedules,id'],
        ]);

        $sectionSchedule = SectionSubjectSchedule::with('sectionSubject')
            ->findOrFail($payload['section_subject_schedule_id']);

        $sectionSubject = $sectionSchedule->sectionSubject;

        if (!$sectionSubject || !$sectionSubject->faculty_id) {
            throw ValidationException::withMessages([
                'section_subject_schedule_id' => ['Section subject schedules require an assigned faculty before creating sessions.'],
            ]);
        }

        $now = $this->ensureScheduleIsActiveNow($sectionSchedule);
        $currentDay = strtoupper($now->format('l'));

        $shouldStart = $request->boolean('start');

        $request->replace([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSubject->faculty_id,
            'day_of_week' => $currentDay,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => $shouldStart ? Carbon::today()->toDateString() : null,
            'start_time' => $shouldStart ? $now->format('H:i:s') : null,
        ]);

        return $this->store($request);
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

    public function start(string $id)
    {
        $record = ScheduleSession::with('sectionSubjectSchedule')->findOrFail($id);

        $schedule = $record->sectionSubjectSchedule;
        if (!$schedule) {
            throw ValidationException::withMessages([
                'section_subject_schedule_id' => ['Schedule session must have an associated schedule before starting.'],
            ]);
        }

        $now = $this->ensureScheduleIsActiveNow($schedule);

        $record->update([
            'start_date' => $now->toDateString(),
            'start_time' => $now->format('H:i:s'),
        ]);

        return $this->successResponse($record->fresh(['sectionSubjectSchedule', 'faculty', 'room']));
    }

    public function close(string $id)
    {
        $record = ScheduleSession::findOrFail($id);

        $today = Carbon::today();
        $startDate = $record->start_date ? Carbon::parse($record->start_date) : null;
        $endDate = $today;

        if ($startDate && $today->lessThanOrEqualTo($startDate)) {
            $endDate = $startDate;
        }

        $now = Carbon::now();
        $startTime = $record->start_time ? Carbon::createFromFormat('H:i:s', $record->start_time) : null;
        $endTime = $now->format('H:i:s');

        if ($startTime && $now->lessThanOrEqualTo($startTime)) {
            $endTime = $startTime->format('H:i:s');
        }

        $record->update([
            'end_date' => $endDate->toDateString(),
            'end_time' => $endTime,
        ]);

        return $this->successResponse($record->fresh(['sectionSubjectSchedule', 'faculty', 'room']));
    }

    public function destroy(string $id)
    {
        $record = ScheduleSession::findOrFail($id);
        $record->delete();

        return response()->json(null, 204);
    }

    private function broadcastSessionCreated(ScheduleSession $session): void
    {
        $sectionSubject = $session->sectionSubjectSchedule?->sectionSubject;

        $this->mqttPublisher->publish([
            'mode' => 'CLASS_SESSION',
            'section' => $sectionSubject?->section?->section ?? '',
            'subject' => $sectionSubject?->subject?->subject ?? '',
            'faculty' => $session->faculty?->name ?? $sectionSubject?->faculty?->name ?? '',
            'session_id' => $session->id,
        ]);
    }

    private function shouldBroadcastSessionCreation(Request $request): bool
    {
        return $request->is('api/schedule-sessions/create');
    }

    private function validatePayload(Request $request, bool $isUpdate = false, ?ScheduleSession $record = null): array
    {
        $facultyRule = Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'FACULTY'));
        $rules = [
            'section_subject_schedule_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:section_subject_schedules,id'],
            'faculty_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', $facultyRule],
            'day_of_week' => [$isUpdate ? 'sometimes' : 'required', Rule::in(self::DAYS)],
            'room_id' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'exists:rooms,id'],
            'start_date' => [$isUpdate ? 'sometimes' : 'present', 'nullable', 'date'],
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
        $this->ensureCreationRules($resolved, $isUpdate);

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

    private function ensureCreationRules(array $data, bool $isUpdate): void
    {
        if ($isUpdate) {
            return;
        }

        if (!isset($data['start_date'], $data['section_subject_schedule_id'])) {
            return;
        }

        $startDate = Carbon::parse($data['start_date']);
        if (!$startDate->isToday()) {
            throw ValidationException::withMessages([
                'start_date' => ['Schedule sessions can only be created for the current date.'],
            ]);
        }

        $schedule = SectionSubjectSchedule::find($data['section_subject_schedule_id']);
        if (!$schedule) {
            return;
        }

        $now = Carbon::now();
        $scheduleStart = Carbon::today()->setTimeFromTimeString($schedule->start_time);
        $scheduleEnd = Carbon::today()->setTimeFromTimeString($schedule->end_time);

        if ($now->lt($scheduleStart) || $now->gt($scheduleEnd)) {
            throw ValidationException::withMessages([
                'section_subject_schedule_id' => ['Schedule sessions can only be created while the schedule time window is active.'],
            ]);
        }
    }

    private function ensureScheduleIsActiveNow(SectionSubjectSchedule $schedule): Carbon
    {
        $now = Carbon::now();
        $currentDay = strtoupper($now->format('l'));

        if ($schedule->day_of_week !== $currentDay) {
            throw ValidationException::withMessages([
                'section_subject_schedule_id' => ['Schedule sessions can only be created on the assigned day of week.'],
            ]);
        }

        $scheduleStart = Carbon::today()->setTimeFromTimeString($schedule->start_time);
        $scheduleEnd = Carbon::today()->setTimeFromTimeString($schedule->end_time);

        if ($now->lt($scheduleStart) || $now->gt($scheduleEnd)) {
            throw ValidationException::withMessages([
                'section_subject_schedule_id' => ['Schedule sessions can only be created during the schedule time window.'],
            ]);
        }

        return $now;
    }
}
