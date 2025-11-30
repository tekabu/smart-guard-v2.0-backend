<?php

namespace Tests\Feature\Api;

use App\Models\SchedulePeriod;
use App\Models\Schedule;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulePeriodControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_schedule_periods()
    {
        $user = User::factory()->create(); // Acting user
        SchedulePeriod::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/schedule-periods');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_schedule_period()
    {
        $user = User::factory()->create(); // Acting user
        $schedule = Schedule::factory()->create();
        $periodData = [
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'active' => true
        ];

        $response = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.start_time', '08:00:00')
            ->assertJsonPath('data.end_time', '09:30:00');
        $this->assertDatabaseHas('schedule_periods', ['start_time' => '08:00:00']);
    }

    public function test_can_show_schedule_period()
    {
        $user = User::factory()->create(); // Acting user
        $period = SchedulePeriod::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/schedule-periods/' . $period->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $period->id);
    }

    public function test_can_update_schedule_period()
    {
        $user = User::factory()->create(); // Acting user
        $period = SchedulePeriod::factory()->create();
        $updateData = ['start_time' => '10:00:00', 'end_time' => '11:30:00'];
        $response = $this->actingAs($user)->putJson('/api/schedule-periods/' . $period->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.start_time', '10:00:00');
        $this->assertDatabaseHas('schedule_periods', ['id' => $period->id, 'start_time' => '10:00:00']);
    }

    public function test_can_delete_schedule_period()
    {
        $user = User::factory()->create(); // Acting user
        $period = SchedulePeriod::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/schedule-periods/' . $period->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('schedule_periods', ['id' => $period->id]);
    }

    public function test_requires_all_fields()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/schedule-periods', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_id', 'start_time', 'end_time']);
    }

    public function test_show_schedule_period_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/schedule-periods/99999');
        $response->assertStatus(404);
    }

    public function test_update_schedule_period_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['start_time' => '10:00:00', 'end_time' => '11:30:00'];
        $response = $this->actingAs($user)->putJson('/api/schedule-periods/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_schedule_period_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/schedule-periods/99999');
        $response->assertStatus(404);
    }

    public function test_create_schedule_period_with_overlapping_time_same_room_same_day()
    {
        $user = User::factory()->create(); // Acting user
        // Create a room
        $room = Room::factory()->create();
        
        // Create a schedule for room1 on MONDAY
        $schedule1 = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        // Create a schedule for room1 on TUESDAY (different day)
        $schedule2 = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'TUESDAY'
        ]);
        
        // Create a schedule period: MONDAY 13:00:00 - 16:00:00
        $existingPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule1->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00'
        ]);
        
        // Try to create a conflicting schedule period: MONDAY 10:00:00 - 14:00:00 (should fail)
        $conflictingPeriodData = [
            'schedule_id' => $schedule1->id,
            'start_time' => '10:00:00',
            'end_time' => '14:00:00'
        ];
        
        $response = $this->actingAs($user)->postJson('/api/schedule-periods', $conflictingPeriodData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }

    public function test_create_schedule_period_with_non_overlapping_time_same_room_same_day()
    {
        $user = User::factory()->create(); // Acting user
        // Create a room
        $room = Room::factory()->create();
        
        // Create a schedule for room1 on MONDAY
        $schedule = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        // Create a schedule period: MONDAY 13:00:00 - 16:00:00
        $existingPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00'
        ]);
        
        // Try to create a non-conflicting schedule period: MONDAY 16:00:00 - 17:00:00 (should pass)
        $nonConflictingPeriodData = [
            'schedule_id' => $schedule->id,
            'start_time' => '16:00:00',
            'end_time' => '17:00:00'
        ];
        
        $response = $this->actingAs($user)->postJson('/api/schedule-periods', $nonConflictingPeriodData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('schedule_periods', ['start_time' => '16:00:00']);
    }
    
    public function test_create_schedule_period_with_overlapping_time_different_room_same_day()
    {
        $user = User::factory()->create(); // Acting user
        // Create two different rooms
        $room1 = Room::factory()->create();
        $room2 = Room::factory()->create();
        
        // Create schedules for different rooms on same day
        $schedule1 = Schedule::factory()->create([
            'room_id' => $room1->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        $schedule2 = Schedule::factory()->create([
            'room_id' => $room2->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        // Create a schedule period: MONDAY Room1 13:00:00 - 16:00:00
        $existingPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule1->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00'
        ]);
        
        // Try to create a period on same day but different room: MONDAY Room2 10:00:00 - 14:00:00 (should pass)
        $newPeriodData = [
            'schedule_id' => $schedule2->id,
            'start_time' => '10:00:00',
            'end_time' => '14:00:00'
        ];
        
        $response = $this->actingAs($user)->postJson('/api/schedule-periods', $newPeriodData);
        $response->assertStatus(201);
        $this->assertDatabaseHas('schedule_periods', ['start_time' => '10:00:00']);
    }

    public function test_update_schedule_period_with_overlapping_time()
    {
        $user = User::factory()->create(); // Acting user
        // Create a room
        $room = Room::factory()->create();
        
        // Create schedules for the same room and day
        $schedule = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        // Create existing schedule period: MONDAY 13:00:00 - 16:00:00
        $existingPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00'
        ]);
        
        // Create another schedule period: MONDAY 10:00:00 - 12:00:00
        $toBeUpdatedPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00'
        ]);
        
        // Try to update the second period to conflict with the first: 14:00:00 - 17:00:00 (should fail)
        $updateData = [
            'start_time' => '14:00:00',
            'end_time' => '17:00:00'
        ];
        
        $response = $this->actingAs($user)->putJson('/api/schedule-periods/' . $toBeUpdatedPeriod->id, $updateData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);
    }
    
    public function test_schedule_period_overlap_scenarios()
    {
        $user = User::factory()->create(); // Acting user
        // Create a room
        $room = Room::factory()->create();
        
        // Create a schedule for room1 on MONDAY
        $schedule = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'MONDAY'
        ]);
        
        // Existing Record: Room1, Monday, 10:00:00 to 14:00:00
        $existingPeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '10:00:00',
            'end_time' => '14:00:00'
        ]);
        
        // New Record, Room1, Monday, assert 08:00:00 to 10:00:00 true (should pass - abuts start time)
        $periodData1 = [
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00'
        ];
        $response1 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData1);
        $response1->assertStatus(201);
        
        // Cleanup: Remove the just-created period so it doesn't interfere with next test
        $this->actingAs($user)->deleteJson('/api/schedule-periods/' . $response1->decodeResponseJson()['data']['id']);
        
        // New Record, Room1, Monday, assert 14:00:00 to 16:00:00 true (should pass - abuts end time)
        $periodData2 = [
            'schedule_id' => $schedule->id,
            'start_time' => '14:00:00',
            'end_time' => '16:00:00'
        ];
        $response2 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData2);
        $response2->assertStatus(201);
        
        // Cleanup: Remove the just-created period so it doesn't interfere with next test
        $this->actingAs($user)->deleteJson('/api/schedule-periods/' . $response2->decodeResponseJson()['data']['id']);
        
        // New Record, Room1, Monday, assert 08:00:00 to 11:00:00 false (should fail - overlaps)
        $periodData3 = [
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00'
        ];
        $response3 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData3);
        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['start_time']);
        
        // New Record, Room1, Monday, assert 13:00:00 to 16:00:00 false (should fail - overlaps)
        $periodData4 = [
            'schedule_id' => $schedule->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00'
        ];
        $response4 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData4);
        $response4->assertStatus(422);
        $response4->assertJsonValidationErrors(['start_time']);
        
        // New Record, Room1, Monday, assert 08:00:00 to 16:00:00 false (should fail - overlaps)
        $periodData5 = [
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '16:00:00'
        ];
        $response5 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData5);
        $response5->assertStatus(422);
        $response5->assertJsonValidationErrors(['start_time']);
        
        // New Record, Room1, Tuesday, assert 08:00:00 to 11:00:00 true (should pass - different day)
        $scheduleDifferentDay = Schedule::factory()->create([
            'room_id' => $room->id,
            'day_of_week' => 'TUESDAY'
        ]);
        
        $periodData6 = [
            'schedule_id' => $scheduleDifferentDay->id,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00'
        ];
        $response6 = $this->actingAs($user)->postJson('/api/schedule-periods', $periodData6);
        $response6->assertStatus(201);
    }

    public function test_can_get_schedule_periods_count()
    {
        $user = User::factory()->create();
        SchedulePeriod::factory()->count(10)->create();

        $response = $this->actingAs($user)->getJson('/api/schedule-periods/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 10);
    }
}
