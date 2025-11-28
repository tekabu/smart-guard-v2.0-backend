<?php

namespace Tests\Feature\Api;

use App\Models\SchedulePeriod;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePeriodControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_schedule_periods()
    {
        SchedulePeriod::factory()->count(3)->create();
        $response = $this->getJson("/api/schedule-periods");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_schedule_period()
    {
        $schedule = Schedule::factory()->create();
        $periodData = [
            "schedule_id" => $schedule->id,
            "start_time" => "08:00:00",
            "end_time" => "09:30:00",
            "active" => true
        ];
        
        $response = $this->postJson("/api/schedule-periods", $periodData);
        $response->assertStatus(201)->assertJsonFragment(["start_time" => "08:00:00", "end_time" => "09:30:00"]);
        $this->assertDatabaseHas("schedule_periods", ["start_time" => "08:00:00"]);
    }

    public function test_can_show_schedule_period()
    {
        $period = SchedulePeriod::factory()->create();
        $response = $this->getJson("/api/schedule-periods/{$period->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $period->id]);
    }

    public function test_can_update_schedule_period()
    {
        $period = SchedulePeriod::factory()->create();
        $updateData = ["start_time" => "10:00:00", "end_time" => "11:30:00"];
        $response = $this->putJson("/api/schedule-periods/{$period->id}", $updateData);
        $response->assertStatus(200)->assertJsonFragment(["start_time" => "10:00:00"]);
        $this->assertDatabaseHas("schedule_periods", ["id" => $period->id, "start_time" => "10:00:00"]);
    }

    public function test_can_delete_schedule_period()
    {
        $period = SchedulePeriod::factory()->create();
        $response = $this->deleteJson("/api/schedule-periods/{$period->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("schedule_periods", ["id" => $period->id]);
    }

    public function test_requires_all_fields()
    {
        $response = $this->postJson("/api/schedule-periods", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["schedule_id", "start_time", "end_time"]);
    }
}
