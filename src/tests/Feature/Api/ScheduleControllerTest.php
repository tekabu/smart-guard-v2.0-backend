<?php

namespace Tests\Feature\Api;

use App\Models\Schedule;
use App\Models\User;
use App\Models\Room;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_schedules()
    {
        Schedule::factory()->count(3)->create();
        $response = $this->getJson('/api/schedules');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_schedule()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        $scheduleData = [
            'user_id' => $user->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'active' => true
        ];

        $response = $this->postJson('/api/schedules', $scheduleData);
        $response->assertStatus(201)->assertJsonFragment(['day_of_week' => 'MONDAY']);
        $this->assertDatabaseHas('schedules', ['day_of_week' => 'MONDAY']);
    }

    public function test_can_show_schedule()
    {
        $schedule = Schedule::factory()->create();
        $response = $this->getJson('/api/schedules/' . $schedule->id);
        $response->assertStatus(200)->assertJsonFragment(['id' => $schedule->id]);
    }

    public function test_can_update_schedule()
    {
        $schedule = Schedule::factory()->create();
        $updateData = ['day_of_week' => 'FRIDAY', 'active' => false];
        $response = $this->putJson('/api/schedules/' . $schedule->id, $updateData);
        $response->assertStatus(200)->assertJsonFragment(['day_of_week' => 'FRIDAY']);
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'day_of_week' => 'FRIDAY']);
    }

    public function test_can_delete_schedule()
    {
        $schedule = Schedule::factory()->create();
        $response = $this->deleteJson('/api/schedules/' . $schedule->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_cannot_create_schedule_with_invalid_day()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        $scheduleData = [
            'user_id' => $user->id,
            'day_of_week' => 'INVALID_DAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id,
        ];

        $response = $this->postJson('/api/schedules', $scheduleData);
        $response->assertStatus(422)->assertJsonValidationErrors(['day_of_week']);
    }

    public function test_requires_all_fields()
    {
        $response = $this->postJson('/api/schedules', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'day_of_week', 'room_id', 'subject_id']);
    }

    public function test_show_schedule_that_does_not_exist()
    {
        $response = $this->getJson('/api/schedules/99999');
        $response->assertStatus(404);
    }

    public function test_update_schedule_that_does_not_exist()
    {
        $updateData = ['day_of_week' => 'FRIDAY', 'active' => false];
        $response = $this->putJson('/api/schedules/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_schedule_that_does_not_exist()
    {
        $response = $this->deleteJson('/api/schedules/99999');
        $response->assertStatus(404);
    }

    public function test_cannot_create_schedule_with_duplicate_combination()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        // Create the first schedule with a specific combination
        $firstScheduleData = [
            'user_id' => $user->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id
        ];

        $response = $this->postJson('/api/schedules', $firstScheduleData);
        $response->assertStatus(201);

        // Try to create a second schedule with the same combination (should fail)
        $duplicateScheduleData = [
            'user_id' => $user->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id
        ];

        $response = $this->postJson('/api/schedules', $duplicateScheduleData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['combination']);
    }

    public function test_update_schedule_with_duplicate_combination()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();  // Different user initially
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();

        // Create two different schedules
        $schedule1 = Schedule::factory()->create([
            'user_id' => $user->id,
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id
        ]);

        $schedule2 = Schedule::factory()->create([
            'user_id' => $user2->id,  // Different user initially
            'day_of_week' => 'MONDAY',
            'room_id' => $room->id,
            'subject_id' => $subject->id
        ]);

        // Try to update schedule2 to use the same combination as schedule1 (should fail)
        $updateData = [
            'user_id' => $user->id,  // Same user as schedule1
            'day_of_week' => 'MONDAY',  // Same day as schedule1
            'room_id' => $room->id,  // Same room as schedule1
            'subject_id' => $subject->id  // Same subject as schedule1
        ];

        $response = $this->putJson('/api/schedules/' . $schedule2->id, $updateData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['combination']);
    }
}
