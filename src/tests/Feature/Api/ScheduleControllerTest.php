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
        $response = $this->getJson("/api/schedules");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_schedule()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        
        $scheduleData = [
            "user_id" => $user->id,
            "day_of_week" => "MONDAY",
            "room_id" => $room->id,
            "subject_id" => $subject->id,
            "active" => true
        ];
        
        $response = $this->postJson("/api/schedules", $scheduleData);
        $response->assertStatus(201)->assertJsonFragment(["day_of_week" => "MONDAY"]);
        $this->assertDatabaseHas("schedules", ["day_of_week" => "MONDAY"]);
    }

    public function test_can_show_schedule()
    {
        $schedule = Schedule::factory()->create();
        $response = $this->getJson("/api/schedules/{$schedule->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $schedule->id]);
    }

    public function test_can_update_schedule()
    {
        $schedule = Schedule::factory()->create();
        $updateData = ["day_of_week" => "FRIDAY", "active" => false];
        $response = $this->putJson("/api/schedules/{$schedule->id}", $updateData);
        $response->assertStatus(200)->assertJsonFragment(["day_of_week" => "FRIDAY"]);
        $this->assertDatabaseHas("schedules", ["id" => $schedule->id, "day_of_week" => "FRIDAY"]);
    }

    public function test_can_delete_schedule()
    {
        $schedule = Schedule::factory()->create();
        $response = $this->deleteJson("/api/schedules/{$schedule->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("schedules", ["id" => $schedule->id]);
    }

    public function test_cannot_create_schedule_with_invalid_day()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        
        $scheduleData = [
            "user_id" => $user->id,
            "day_of_week" => "INVALID_DAY",
            "room_id" => $room->id,
            "subject_id" => $subject->id,
        ];
        
        $response = $this->postJson("/api/schedules", $scheduleData);
        $response->assertStatus(422)->assertJsonValidationErrors(["day_of_week"]);
    }

    public function test_requires_all_fields()
    {
        $response = $this->postJson("/api/schedules", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "day_of_week", "room_id", "subject_id"]);
    }
}
