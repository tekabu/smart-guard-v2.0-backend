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
}
