<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\SectionSubjectSchedule;
use App\Models\SectionSubjectStudent;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionSubjectScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_section_subject_schedule()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $room = Room::factory()->create();

        $payload = [
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $payload);

        $response->assertStatus(201)->assertJsonPath('data.day_of_week', 'MONDAY');
        $this->assertDatabaseHas('section_subject_schedules', $payload);
    }

    public function test_requires_valid_day_of_week()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $room = Room::factory()->create();

        $payload = [
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'NOT_A_DAY',
            'room_id' => $room->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_cannot_duplicate_section_subject_schedule_combination()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $schedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $payload = [
            'section_subject_id' => $schedule->section_subject_id,
            'day_of_week' => 'MONDAY',
            'room_id' => $schedule->room_id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_id']);
    }

    public function test_cannot_overlap_schedule_with_same_room_and_day()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $room = Room::factory()->create();
        $existingSectionSubject = SectionSubject::factory()->create();
        $existing = SectionSubjectSchedule::factory()
            ->for($existingSectionSubject)
            ->for($room)
            ->create([
                'day_of_week' => 'MONDAY',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
            ]);

        $payload = [
            'section_subject_id' => SectionSubject::factory()->create()->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $existing->room_id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['start_time']);
    }

    public function test_end_time_must_be_after_start_time()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $room = Room::factory()->create();

        $payload = [
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'TUESDAY',
            'room_id' => $room->id,
            'start_time' => '09:00:00',
            'end_time' => '08:00:00',
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_can_update_section_subject_schedule()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $schedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $updateData = [
            'day_of_week' => 'FRIDAY',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ];

        $response = $this->actingAs($actingUser)->putJson("/api/section-subject-schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200)->assertJsonPath('data.day_of_week', 'FRIDAY');
        $this->assertDatabaseHas('section_subject_schedules', [
            'id' => $schedule->id,
            'day_of_week' => 'FRIDAY',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);
    }

    public function test_cannot_update_schedule_to_overlap_existing_room_day()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $room = Room::factory()->create();
        $sectionSubject = SectionSubject::factory()->create();

        $existing = SectionSubjectSchedule::factory()
            ->for($sectionSubject)
            ->for($room)
            ->create([
                'day_of_week' => 'MONDAY',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
            ]);

        $target = SectionSubjectSchedule::factory()
            ->for($sectionSubject)
            ->for($room)
            ->create([
                'day_of_week' => 'MONDAY',
                'start_time' => '11:00:00',
                'end_time' => '12:00:00',
            ]);

        $response = $this->actingAs($actingUser)->putJson(
            "/api/section-subject-schedules/{$target->id}",
            ['start_time' => '09:00:00', 'end_time' => '10:00:00']
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['start_time']);
    }

    public function test_can_create_schedule_when_room_or_day_differs()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $roomMonday = Room::factory()->create();
        $roomOther = Room::factory()->create();
        $sectionSubject = SectionSubject::factory()->create();
        $baseSchedule = SectionSubjectSchedule::factory()
            ->for($sectionSubject)
            ->for($roomMonday)
            ->create([
                'day_of_week' => 'MONDAY',
                'start_time' => '08:00:00',
                'end_time' => '11:00:00',
            ]);

        $differentDayPayload = [
            'section_subject_id' => $baseSchedule->section_subject_id,
            'day_of_week' => 'TUESDAY',
            'room_id' => $baseSchedule->room_id,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
        ];

        $differentRoomPayload = [
            'section_subject_id' => $baseSchedule->section_subject_id,
            'day_of_week' => 'MONDAY',
            'room_id' => $roomOther->id,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
        ];

        $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $differentDayPayload)
            ->assertStatus(201);

        $this->actingAs($actingUser)->postJson('/api/section-subject-schedules', $differentRoomPayload)
            ->assertStatus(201);
    }

    public function test_can_delete_section_subject_schedule()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $schedule = SectionSubjectSchedule::factory()->create();

        $response = $this->actingAs($actingUser)->deleteJson("/api/section-subject-schedules/{$schedule->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('section_subject_schedules', ['id' => $schedule->id]);
    }

    public function test_section_subject_schedule_responses_include_section_subject_and_faculty()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $schedule = SectionSubjectSchedule::factory()->create();

        $response = $this->actingAs($actingUser)->getJson("/api/section-subject-schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.section_subject.section.id', $schedule->sectionSubject->section->id)
            ->assertJsonPath('data.section_subject.subject.id', $schedule->sectionSubject->subject->id)
            ->assertJsonPath('data.section_subject.faculty.id', $schedule->sectionSubject->faculty->id);
    }

    public function test_can_filter_section_subject_schedules_by_section_subject_day_and_room()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();
        $otherSection = Section::factory()->create();
        $subject = Subject::factory()->create();
        $otherSubject = Subject::factory()->create();
        $room = Room::factory()->create();
        $otherRoom = Room::factory()->create();

        $matchingSectionSubject = SectionSubject::factory()->create([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);

        $matchingSchedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $matchingSectionSubject->id,
            'day_of_week' => 'FRIDAY',
            'room_id' => $room->id,
        ]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => SectionSubject::factory()->create([
                'section_id' => $section->id,
                'subject_id' => $otherSubject->id,
            ])->id,
            'day_of_week' => 'FRIDAY',
            'room_id' => $room->id,
        ]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => SectionSubject::factory()->create([
                'section_id' => $otherSection->id,
                'subject_id' => $subject->id,
            ])->id,
            'day_of_week' => 'FRIDAY',
            'room_id' => $room->id,
        ]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $matchingSectionSubject->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
        ]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $matchingSectionSubject->id,
            'day_of_week' => 'FRIDAY',
            'room_id' => $otherRoom->id,
        ]);

        $query = http_build_query([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
            'room_id' => $room->id,
        ]);

        $response = $this->actingAs($actingUser)->getJson("/api/section-subject-schedules?{$query}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingSchedule->id);
    }

    public function test_can_fetch_current_schedule_for_faculty()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 10:00:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $faculty = User::factory()->create();
        $sectionSubject = SectionSubject::factory()->create(['faculty_id' => $faculty->id]);
        $schedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        $response = $this->actingAs($actingUser)
            ->getJson("/api/section-subject-schedules/faculty/{$faculty->id}/current");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $schedule->id);

        Carbon::setTestNow();
    }

    public function test_current_schedule_route_returns_404_when_faculty_unassigned()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 10:00:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $faculty = User::factory()->create();

        $response = $this->actingAs($actingUser)
            ->getJson("/api/section-subject-schedules/faculty/{$faculty->id}/current");

        $response->assertStatus(404)->assertJson(['status' => false]);

        Carbon::setTestNow();
    }

    public function test_current_schedule_route_returns_404_when_no_schedule_in_window()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 10:00:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $faculty = User::factory()->create();
        $sectionSubject = SectionSubject::factory()->create(['faculty_id' => $faculty->id]);

        SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        $response = $this->actingAs($actingUser)
            ->getJson("/api/section-subject-schedules/faculty/{$faculty->id}/current");

        $response->assertStatus(404)->assertJson(['status' => false]);

        Carbon::setTestNow();
    }

    public function test_student_attendance_route_creates_record()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 09:30:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $student = User::factory()->create(['role' => 'STUDENT']);

        $sectionSubject = SectionSubject::factory()->create();
        SectionSubjectStudent::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'student_id' => $student->id,
        ]);

        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $session = ScheduleSession::factory()->create([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $sectionSchedule->room_id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $response = $this->actingAs($actingUser)
            ->postJson("/api/section-subject-schedules/student/{$student->id}/attendance");

        $response->assertStatus(201)
            ->assertJsonPath('data.schedule_session_id', $session->id)
            ->assertJsonPath('data.student_id', $student->id);

        $this->assertDatabaseHas('schedule_attendance', [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'attendance_status' => 'PRESENT',
        ]);

        Carbon::setTestNow();
    }

    public function test_student_attendance_route_handles_existing_record()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 09:30:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $student = User::factory()->create(['role' => 'STUDENT']);
        $sectionSubject = SectionSubject::factory()->create();
        SectionSubjectStudent::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'student_id' => $student->id,
        ]);

        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $session = ScheduleSession::factory()->create([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $sectionSchedule->room_id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        ScheduleAttendance::create([
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => Carbon::now()->toDateString(),
            'time_in' => '09:05:00',
            'attendance_status' => 'PRESENT',
        ]);

        $response = $this->actingAs($actingUser)
            ->postJson("/api/section-subject-schedules/student/{$student->id}/attendance");

        $response->assertStatus(200)
            ->assertJsonPath('data.schedule_session_id', $session->id)
            ->assertJsonPath('data.student_id', $student->id);

        $this->assertDatabaseCount('schedule_attendance', 1);

        Carbon::setTestNow();
    }

    public function test_student_attendance_route_requires_membership()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 09:30:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $student = User::factory()->create(['role' => 'STUDENT']);
        $sectionSubject = SectionSubject::factory()->create();

        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        ScheduleSession::factory()->create([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $sectionSchedule->room_id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $response = $this->actingAs($actingUser)
            ->postJson("/api/section-subject-schedules/student/{$student->id}/attendance");

        $response->assertStatus(422)->assertJson(['status' => false]);

        $this->assertDatabaseCount('schedule_attendance', 0);

        Carbon::setTestNow();
    }

    public function test_student_attendance_route_requires_active_session()
    {
        Carbon::setTestNow(Carbon::parse('2023-09-04 09:30:00'));

        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $student = User::factory()->create(['role' => 'STUDENT']);
        $sectionSubject = SectionSubject::factory()->create();
        SectionSubjectStudent::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'student_id' => $student->id,
        ]);

        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'section_subject_id' => $sectionSubject->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        ScheduleSession::factory()->create([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $sectionSchedule->room_id,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
        ]);

        $response = $this->actingAs($actingUser)
            ->postJson("/api/section-subject-schedules/student/{$student->id}/attendance");

        $response->assertStatus(404)->assertJson(['status' => false]);

        $this->assertDatabaseCount('schedule_attendance', 0);

        Carbon::setTestNow();
    }
}
