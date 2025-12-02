<?php

namespace Tests\Feature\Api;

use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\SectionSubjectSchedule;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_create_schedule_attendance()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $session = $this->createActiveSession();
        $student = User::factory()->create(['role' => 'STUDENT']);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => Carbon::today()->toDateString(),
            'time_in' => '08:00:00',
            'date_out' => Carbon::today()->toDateString(),
            'time_out' => '09:00:00',
            'attendance_status' => 'PRESENT',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-attendance', $payload);

        $response->assertStatus(201)->assertJsonPath('data.attendance_status', 'PRESENT');
        $this->assertDatabaseHas('schedule_attendance', $payload);
    }

    public function test_requires_unique_attendance_per_student_date()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $session = $this->createActiveSession();
        $student = User::factory()->create(['role' => 'STUDENT']);

        ScheduleAttendance::factory()->create([
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => '2025-01-01',
        ]);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => Carbon::today()->toDateString(),
            'attendance_status' => 'ABSENT',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-attendance', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['date_in']);
    }

    public function test_time_out_must_be_after_time_in()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $session = $this->createActiveSession();
        $student = User::factory()->create(['role' => 'STUDENT']);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'time_in' => '10:00:00',
            'time_out' => '09:00:00',
            'attendance_status' => 'LATE',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-attendance', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['time_out']);
    }

    public function test_can_update_schedule_attendance()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $record = ScheduleAttendance::factory()->create([
            'attendance_status' => 'ABSENT',
        ]);
        $baseDate = $record->date_in
            ? Carbon::parse($record->date_in)
            : Carbon::parse('2025-01-01');
        $nextDate = $baseDate->copy()->addDay()->format('Y-m-d');

        $response = $this->actingAs($admin)->putJson("/api/schedule-attendance/{$record->id}", [
            'attendance_status' => 'PRESENT',
            'date_out' => $nextDate,
        ]);

        $response->assertOk()->assertJsonPath('data.attendance_status', 'PRESENT');
        $this->assertDatabaseHas('schedule_attendance', [
            'id' => $record->id,
            'attendance_status' => 'PRESENT',
            'date_out' => $nextDate,
        ]);
    }

    public function test_can_delete_schedule_attendance()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $record = ScheduleAttendance::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/schedule-attendance/{$record->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('schedule_attendance', ['id' => $record->id]);
    }

    public function test_can_get_schedule_attendance_count()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        ScheduleAttendance::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/schedule-attendance/count');

        $response->assertOk()->assertJsonPath('data.count', 3);
    }

    public function test_cannot_create_attendance_for_inactive_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $student = User::factory()->create(['role' => 'STUDENT']);
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'start_time' => '06:00:00',
            'end_time' => '07:00:00',
        ]);
        $session = ScheduleSession::factory()
            ->for($sectionSchedule, 'sectionSubjectSchedule')
            ->create([
                'start_date' => Carbon::yesterday()->toDateString(),
                'day_of_week' => $sectionSchedule->day_of_week,
                'room_id' => $sectionSchedule->room_id,
            ]);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'attendance_status' => 'PRESENT',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-attendance', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_session_id']);
    }

    public function test_can_get_schedule_attendance_overview_with_filters()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();
        $subject = Subject::factory()->create();
        $faculty = User::factory()->create([
            'role' => 'FACULTY',
            'faculty_id' => 'FAC-001',
        ]);

        $sectionSubject = SectionSubject::factory()
            ->for($section)
            ->for($subject)
            ->for($faculty, 'faculty')
            ->create();

        $sectionSchedule = SectionSubjectSchedule::factory()
            ->for($sectionSubject)
            ->create();

        $session = ScheduleSession::factory()
            ->for($sectionSchedule, 'sectionSubjectSchedule')
            ->for($faculty, 'faculty')
            ->create();

        $student = User::factory()->create([
            'role' => 'STUDENT',
            'student_id' => 'STU-001',
        ]);

        ScheduleAttendance::factory()
            ->for($session, 'scheduleSession')
            ->for($student, 'student')
            ->create([
                'date_in' => '2025-01-02',
                'time_in' => '08:05:00',
            ]);

        ScheduleAttendance::factory()->create([
            'date_in' => '2025-02-01',
        ]);

        $query = http_build_query([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => 'FAC-001',
            'date_in' => '2025-01-02',
        ]);

        $response = $this->actingAs($admin)->getJson("/api/schedule-attendance/overview?{$query}");

        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.section', $section->section);
        $response->assertJsonPath('data.0.subject', $subject->subject);
        $response->assertJsonPath('data.0.faculty', $faculty->name);
        $response->assertJsonPath('data.0.student', $student->name);
        $response->assertJsonPath('data.0.student_id', 'STU-001');
        $response->assertJsonPath('data.0.faculty_id', 'FAC-001');
        $response->assertJsonPath('data.0.date_in', '2025-01-02');
        $response->assertJsonPath('data.0.time_in', '08:05:00');
    }

    private function createActiveSession(): ScheduleSession
    {
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ]);

        return ScheduleSession::factory()
            ->for($sectionSchedule, 'sectionSubjectSchedule')
            ->create([
                'day_of_week' => $sectionSchedule->day_of_week,
                'room_id' => $sectionSchedule->room_id,
                'start_date' => Carbon::today()->toDateString(),
            ]);
    }
}
