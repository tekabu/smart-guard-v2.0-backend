<?php

namespace Tests\Feature\Api;

use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleAttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_schedule_attendance()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $session = ScheduleSession::factory()->create();
        $student = User::factory()->create(['role' => 'STUDENT']);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => '2025-01-01',
            'time_in' => '08:00:00',
            'date_out' => '2025-01-01',
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
        $session = ScheduleSession::factory()->create();
        $student = User::factory()->create(['role' => 'STUDENT']);

        ScheduleAttendance::factory()->create([
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => '2025-01-01',
        ]);

        $payload = [
            'schedule_session_id' => $session->id,
            'student_id' => $student->id,
            'date_in' => '2025-01-01',
            'attendance_status' => 'ABSENT',
        ];

        $response = $this->actingAs($admin)->postJson('/api/schedule-attendance', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors(['date_in']);
    }

    public function test_time_out_must_be_after_time_in()
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $session = ScheduleSession::factory()->create();
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
}
