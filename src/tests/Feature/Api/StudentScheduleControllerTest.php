<?php

namespace Tests\Feature\Api;

use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\StudentSchedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_student_schedules()
    {
        $admin = $this->createAdminUser();
        StudentSchedule::factory()->count(3)->create();

        $response = $this->actingAs($admin)->getJson('/api/student-schedules');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_student_schedule()
    {
        $admin = $this->createAdminUser();
        $components = $this->buildScheduleComponents();

        $response = $this->actingAs($admin)->postJson('/api/student-schedules', [
            'student_id' => $components['student']->id,
            'subject_id' => $components['subject']->id,
            'schedule_id' => $components['schedule']->id,
            'schedule_period_id' => $components['schedulePeriod']->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.student_id', $components['student']->id)
            ->assertJsonPath('data.subject_id', $components['subject']->id);

        $this->assertDatabaseHas('student_schedules', [
            'student_id' => $components['student']->id,
            'subject_id' => $components['subject']->id,
        ]);
    }

    public function test_can_show_student_schedule()
    {
        $admin = $this->createAdminUser();
        $record = $this->createStudentScheduleRecord();

        $response = $this->actingAs($admin)->getJson('/api/student-schedules/' . $record->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $record->id);
    }

    public function test_can_update_student_schedule()
    {
        $admin = $this->createAdminUser();
        $record = $this->createStudentScheduleRecord();
        $newComponents = $this->buildScheduleComponents();

        $response = $this->actingAs($admin)->putJson('/api/student-schedules/' . $record->id, [
            'student_id' => $record->student_id,
            'subject_id' => $newComponents['subject']->id,
            'schedule_id' => $newComponents['schedule']->id,
            'schedule_period_id' => $newComponents['schedulePeriod']->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.subject_id', $newComponents['subject']->id);

        $this->assertDatabaseHas('student_schedules', [
            'id' => $record->id,
            'subject_id' => $newComponents['subject']->id,
        ]);
    }

    public function test_can_delete_student_schedule()
    {
        $admin = $this->createAdminUser();
        $record = $this->createStudentScheduleRecord();

        $response = $this->actingAs($admin)->deleteJson('/api/student-schedules/' . $record->id);
        $response->assertStatus(204);

        $this->assertDatabaseMissing('student_schedules', ['id' => $record->id]);
    }

    public function test_requires_unique_combination()
    {
        $admin = $this->createAdminUser();
        $record = $this->createStudentScheduleRecord();

        $payload = [
            'student_id' => $record->student_id,
            'subject_id' => $record->subject_id,
            'schedule_id' => $record->schedule_id,
            'schedule_period_id' => $record->schedule_period_id,
        ];

        $response = $this->actingAs($admin)->postJson('/api/student-schedules', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_student_id_must_be_student_role()
    {
        $admin = $this->createAdminUser();
        $components = $this->buildScheduleComponents();
        $faculty = User::factory()->create(['role' => 'FACULTY']);

        $payload = [
            'student_id' => $faculty->id,
            'subject_id' => $components['subject']->id,
            'schedule_id' => $components['schedule']->id,
            'schedule_period_id' => $components['schedulePeriod']->id,
        ];

        $response = $this->actingAs($admin)->postJson('/api/student-schedules', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['student_id']);
    }

    public function test_can_get_student_schedules_count()
    {
        $admin = $this->createAdminUser();
        StudentSchedule::factory()->count(4)->create();

        $response = $this->actingAs($admin)->getJson('/api/student-schedules/count');
        $response->assertStatus(200)
            ->assertJsonPath('data.count', 4);
    }

    private function createAdminUser(): User
    {
        return User::factory()->create(['role' => 'ADMIN']);
    }

    private function buildScheduleComponents(): array
    {
        $student = User::factory()->create(['role' => 'STUDENT']);
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create(['subject_id' => $subject->id]);
        $schedulePeriod = SchedulePeriod::factory()->create(['schedule_id' => $schedule->id]);

        return compact('student', 'subject', 'schedule', 'schedulePeriod');
    }

    private function createStudentScheduleRecord(): StudentSchedule
    {
        $components = $this->buildScheduleComponents();

        return StudentSchedule::create([
            'student_id' => $components['student']->id,
            'subject_id' => $components['subject']->id,
            'schedule_id' => $components['schedule']->id,
            'schedule_period_id' => $components['schedulePeriod']->id,
        ]);
    }
}
