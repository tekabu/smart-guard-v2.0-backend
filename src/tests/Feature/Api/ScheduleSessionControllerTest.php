<?php

namespace Tests\Feature\Api;

use App\Models\ScheduleSession;
use App\Models\SectionSubjectSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_create_schedule_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => 'WEDNESDAY',
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ]);
        $faculty = $sectionSchedule->sectionSubject->faculty;

        $payload = [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $faculty->id,
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => Carbon::today()->toDateString(),
            'start_time' => '08:00:00',
            'end_date' => Carbon::today()->toDateString(),
            'end_time' => '09:00:00',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions', $payload);

        $response->assertStatus(201)->assertJsonPath('data.day_of_week', $sectionSchedule->day_of_week);
        $this->assertDatabaseHas('schedule_sessions', $payload);
    }

    public function test_can_auto_create_schedule_session_with_start_flag()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:45:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => strtoupper(Carbon::now()->format('l')),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions/create?start=1', [
            'section_subject_schedule_id' => $sectionSchedule->id,
        ]);

        $dayOfWeek = strtoupper(Carbon::now()->format('l'));
        $response->assertStatus(201)
            ->assertJsonPath('data.section_subject_schedule_id', $sectionSchedule->id)
            ->assertJsonPath('data.faculty_id', $sectionSchedule->sectionSubject->faculty_id)
            ->assertJsonPath('data.day_of_week', $dayOfWeek)
            ->assertJsonPath('data.room_id', $sectionSchedule->room_id)
            ->assertJsonPath('data.start_date', Carbon::today()->toDateString())
            ->assertJsonPath('data.start_time', Carbon::now()->format('H:i:s'));

        $this->assertDatabaseHas('schedule_sessions', [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSchedule->sectionSubject->faculty_id,
            'day_of_week' => $dayOfWeek,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => Carbon::today()->toDateString(),
            'start_time' => Carbon::now()->format('H:i:s'),
        ]);
    }

    public function test_auto_create_schedule_session_without_start_flag_sets_null_start_fields()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:45:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => strtoupper(Carbon::now()->format('l')),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions/create', [
            'section_subject_schedule_id' => $sectionSchedule->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.start_date', null)
            ->assertJsonPath('data.start_time', null);

        $this->assertDatabaseHas('schedule_sessions', [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'start_date' => null,
            'start_time' => null,
        ]);
    }

    public function test_auto_create_schedule_session_fails_if_day_does_not_match()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:45:00')); // Wednesday
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => 'THURSDAY',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions/create?start=1', [
            'section_subject_schedule_id' => $sectionSchedule->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_schedule_id']);
    }

    public function test_auto_create_schedule_session_fails_if_current_time_not_in_window()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 07:00:00')); // before start time
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'day_of_week' => strtoupper(Carbon::now()->format('l')),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions/create?start=1', [
            'section_subject_schedule_id' => $sectionSchedule->id,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_schedule_id']);
    }

    public function test_prevents_duplicate_start_date_per_schedule()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $sectionSchedule = SectionSubjectSchedule::factory()->create();
        $existing = ScheduleSession::factory()->create([
            'section_subject_schedule_id' => $sectionSchedule->id,
            'start_date' => '2025-01-01',
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
        ]);

        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00'));
        $payload = [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSchedule->sectionSubject->faculty->id,
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => '2025-01-01',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['start_date']);
    }

    public function test_end_time_must_be_after_start_time()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create();
        $payload = [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSchedule->sectionSubject->faculty->id,
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => Carbon::today()->toDateString(),
            'start_time' => '10:00:00',
            'end_date' => Carbon::today()->toDateString(),
            'end_time' => '09:00:00',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_can_update_schedule_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $record = ScheduleSession::factory()->create([
            'day_of_week' => 'MONDAY',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/schedule-sessions/{$record->id}", [
            'day_of_week' => 'FRIDAY',
            'end_time' => '10:00:00',
        ]);

        $response->assertOk()->assertJsonPath('data.day_of_week', 'FRIDAY');
        $this->assertDatabaseHas('schedule_sessions', [
            'id' => $record->id,
            'day_of_week' => 'FRIDAY',
            'end_time' => '10:00:00',
        ]);
    }

    public function test_can_delete_schedule_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $record = ScheduleSession::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/schedule-sessions/{$record->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('schedule_sessions', ['id' => $record->id]);
    }

    public function test_can_start_schedule_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00')); // Wednesday
        $session = ScheduleSession::factory()
            ->for(SectionSubjectSchedule::factory()->state([
                'day_of_week' => strtoupper(Carbon::now()->format('l')),
                'start_time' => '08:00:00',
                'end_time' => '09:30:00',
            ]), 'sectionSubjectSchedule')
            ->create([
                'start_date' => null,
                'start_time' => null,
            ]);

        $response = $this->actingAs($admin)->postJson("/api/schedule-sessions/{$session->id}/start");

        $response->assertOk()
            ->assertJsonPath('data.start_date', '2025-01-01')
            ->assertJsonPath('data.start_time', '08:30:00');

        $this->assertDatabaseHas('schedule_sessions', [
            'id' => $session->id,
            'start_date' => '2025-01-01',
            'start_time' => '08:30:00',
        ]);
    }

    public function test_start_schedule_session_fails_if_day_mismatch()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 08:30:00')); // Wednesday
        $session = ScheduleSession::factory()
            ->for(SectionSubjectSchedule::factory()->state([
                'day_of_week' => 'THURSDAY',
                'start_time' => '08:00:00',
                'end_time' => '09:30:00',
            ]), 'sectionSubjectSchedule')
            ->create([
                'start_date' => null,
                'start_time' => null,
            ]);

        $response = $this->actingAs($admin)->postJson("/api/schedule-sessions/{$session->id}/start");

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_schedule_id']);
    }

    public function test_start_schedule_session_fails_if_outside_time_window()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 07:00:00')); // before start
        $session = ScheduleSession::factory()
            ->for(SectionSubjectSchedule::factory()->state([
                'day_of_week' => strtoupper(Carbon::now()->format('l')),
                'start_time' => '08:00:00',
                'end_time' => '09:30:00',
            ]), 'sectionSubjectSchedule')
            ->create([
                'start_date' => null,
                'start_time' => null,
            ]);

        $response = $this->actingAs($admin)->postJson("/api/schedule-sessions/{$session->id}/start");

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_schedule_id']);
    }

    public function test_can_close_schedule_session()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 09:30:00'));
        $record = ScheduleSession::factory()->create([
            'start_date' => '2025-01-01',
            'start_time' => '08:00:00',
            'end_date' => null,
            'end_time' => null,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/schedule-sessions/{$record->id}/close");

        $response->assertOk()
            ->assertJsonPath('data.end_date', '2025-01-01')
            ->assertJsonPath('data.end_time', '09:30:00');

        $this->assertDatabaseHas('schedule_sessions', [
            'id' => $record->id,
            'end_date' => '2025-01-01',
            'end_time' => '09:30:00',
        ]);
    }

    public function test_close_schedule_session_before_start_aligns_with_start_values()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 07:00:00'));
        $record = ScheduleSession::factory()->create([
            'start_date' => '2025-01-02',
            'start_time' => '08:00:00',
            'end_date' => null,
            'end_time' => null,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/schedule-sessions/{$record->id}/close");

        $response->assertOk()
            ->assertJsonPath('data.end_date', '2025-01-02')
            ->assertJsonPath('data.end_time', '08:00:00');

        $this->assertDatabaseHas('schedule_sessions', [
            'id' => $record->id,
            'end_date' => '2025-01-02',
            'end_time' => '08:00:00',
        ]);
    }

    public function test_can_get_schedule_sessions_count()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        ScheduleSession::factory()->count(2)->create();

        $response = $this->actingAs($admin)->getJson('/api/schedule-sessions/count');

        $response->assertOk()->assertJsonPath('data.count', 2);
    }

    public function test_cannot_create_schedule_session_for_past_or_future_date()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-02 08:30:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create();
        $payload = [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSchedule->sectionSubject->faculty->id,
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => '2025-01-01',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['start_date']);
    }

    public function test_cannot_create_schedule_session_outside_schedule_window()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        Carbon::setTestNow(Carbon::parse('2025-01-01 07:00:00'));
        $sectionSchedule = SectionSubjectSchedule::factory()->create([
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $payload = [
            'section_subject_schedule_id' => $sectionSchedule->id,
            'faculty_id' => $sectionSchedule->sectionSubject->faculty->id,
            'day_of_week' => $sectionSchedule->day_of_week,
            'room_id' => $sectionSchedule->room_id,
            'start_date' => Carbon::today()->toDateString(),
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-sessions', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_schedule_id']);
    }

    public function test_can_get_schedule_session_overview_with_filters()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $faculty = User::factory()->create(['role' => 'FACULTY']);

        $matchingSession = ScheduleSession::factory()->create([
            'day_of_week' => 'MONDAY',
            'start_date' => '2025-01-01',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'faculty_id' => $faculty->id,
        ]);

        ScheduleSession::factory()->create([
            'day_of_week' => 'TUESDAY',
            'start_date' => '2025-02-01',
        ]);

        $sectionSubject = $matchingSession->sectionSubjectSchedule->sectionSubject;
        $section = $sectionSubject->section;
        $subject = $sectionSubject->subject;

        $response = $this->actingAs($admin)->getJson('/api/schedule-sessions/overview?' . http_build_query([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
            'day_of_week' => 'MONDAY',
            'start_date' => '2025-01-01',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.section', $section->section)
            ->assertJsonPath('data.0.subject', $subject->subject)
            ->assertJsonPath('data.0.faculty', $faculty->name)
            ->assertJsonPath('data.0.day_of_week', 'MONDAY')
            ->assertJsonPath('data.0.start_date', '2025-01-01')
            ->assertJsonPath('data.0.start_time', '08:00:00')
            ->assertJsonPath('data.0.end_time', '09:00:00');
    }

    public function test_can_filter_schedule_sessions_that_have_class()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $sessionWithClass = ScheduleSession::factory()->create([
            'start_time' => '08:00:00',
        ]);

        ScheduleSession::factory()->create([
            'start_time' => null,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/schedule-sessions/overview?has_class=1');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sessionWithClass->id)
            ->assertJsonPath('data.0.start_time', '08:00:00');
    }
}
