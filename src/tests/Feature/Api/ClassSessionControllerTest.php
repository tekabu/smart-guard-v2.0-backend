<?php

namespace Tests\Feature\Api;

use App\Models\ClassSession;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Set a fixed test time: Friday, 09:00:00
        Carbon::setTestNow(Carbon::parse('2024-01-05 09:00:00')); // Friday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    public function test_can_list_class_sessions()
    {
        $user = User::factory()->create();
        ClassSession::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/class-sessions');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_class_session()
    {
        $user = User::factory()->create();
        // Create a schedule for FRIDAY (current test day)
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        // Create schedule period with time range that includes 09:00:00
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.start_time', '08:00:00')
            ->assertJsonPath('data.end_time', '09:30:00');
        $this->assertDatabaseHas('class_sessions', ['start_time' => '08:00:00']);
    }

    public function test_start_time_defaults_to_current_time_when_missing()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(201)
            ->assertJsonPath('data.start_time', '09:00:00');
    }

    public function test_end_time_is_optional()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(201)
            ->assertJsonPath('data.end_time', null);
    }

    public function test_can_show_class_session()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/class-sessions/' . $session->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $session->id);
    }

    public function test_can_update_class_session()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create();
        $updateData = ['start_time' => '10:00:00', 'end_time' => '11:30:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.start_time', '10:00:00');
        $this->assertDatabaseHas('class_sessions', ['id' => $session->id, 'start_time' => '10:00:00']);
    }

    public function test_can_delete_class_session()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/class-sessions/' . $session->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('class_sessions', ['id' => $session->id]);
    }

    public function test_requires_schedule_period_id()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/class-sessions', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_show_class_session_that_does_not_exist()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/class-sessions/99999');
        $response->assertStatus(404);
    }

    public function test_update_class_session_that_does_not_exist()
    {
        $user = User::factory()->create();
        $updateData = ['start_time' => '10:00:00', 'end_time' => '11:30:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_class_session_that_does_not_exist()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/class-sessions/99999');
        $response->assertStatus(404);
    }

    public function test_end_time_must_be_after_start_time()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '11:00:00',
        ]);
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '09:30:00',
            'end_time' => '08:00:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_requires_valid_schedule_period_id()
    {
        $user = User::factory()->create();
        $sessionData = [
            'schedule_period_id' => 99999,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_requires_valid_time_format()
    {
        $user = User::factory()->create();
        $schedulePeriod = SchedulePeriod::factory()->create();
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => 'invalid-time',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['start_time']);
    }

    public function test_can_partially_update_class_session()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create(['start_time' => '08:00:00', 'end_time' => '09:30:00']);
        $updateData = ['start_time' => '07:00:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonPath('data.start_time', '07:00:00');
    }

    public function test_cannot_update_end_time_before_start_time()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create(['start_time' => '09:00:00', 'end_time' => '10:00:00']);

        $updateData = ['end_time' => '08:00:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_cannot_update_start_time_after_existing_end_time()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create(['start_time' => '08:00:00', 'end_time' => '09:30:00']);

        $updateData = ['start_time' => '10:30:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_can_close_class_session()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create(['start_time' => '08:00:00', 'end_time' => null]);

        $response = $this->actingAs($user)->postJson('/api/class-sessions/' . $session->id . '/close');
        $response->assertStatus(200)
            ->assertJsonPath('data.end_time', '09:00:00');

        $this->assertDatabaseHas('class_sessions', [
            'id' => $session->id,
            'end_time' => '09:00:00',
        ]);
    }

    public function test_close_session_uses_start_time_when_past_end_time()
    {
        $user = User::factory()->create();
        $session = ClassSession::factory()->create(['start_time' => '10:00:00', 'end_time' => null]);

        $payload = ['end_time' => '09:00:00'];
        $response = $this->actingAs($user)->postJson('/api/class-sessions/' . $session->id . '/close', $payload);
        $response->assertStatus(200)
            ->assertJsonPath('data.end_time', '10:00:00');

        $this->assertDatabaseHas('class_sessions', [
            'id' => $session->id,
            'end_time' => '10:00:00',
        ]);
    }

    public function test_unauthenticated_users_cannot_access_class_sessions()
    {
        $response = $this->getJson('/api/class-sessions');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_users_cannot_create_class_sessions()
    {
        $schedulePeriod = SchedulePeriod::factory()->create();
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ];
        $response = $this->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_users_cannot_update_class_sessions()
    {
        $session = ClassSession::factory()->create();
        $updateData = ['start_time' => '10:00:00'];
        $response = $this->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_users_cannot_delete_class_sessions()
    {
        $session = ClassSession::factory()->create();
        $response = $this->deleteJson('/api/class-sessions/' . $session->id);
        $response->assertStatus(401);
    }

    public function test_cannot_create_duplicate_class_session_same_day()
    {
        $user = User::factory()->create();
        // Create schedule for Friday
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Create first class session today
        ClassSession::factory()->create(['schedule_period_id' => $schedulePeriod->id]);

        // Try to create second class session with same schedule_period_id on the same day
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_can_create_class_session_different_day()
    {
        $user = User::factory()->create();
        // Create schedule for Friday
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Create first class session on Friday (current test date)
        ClassSession::factory()->create(['schedule_period_id' => $schedulePeriod->id]);

        // Move time to next Friday (different day)
        Carbon::setTestNow(Carbon::parse('2024-01-12 09:00:00')); // Next Friday

        // Should be able to create another class session for the same period on a different day
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(201);
    }

    public function test_cannot_create_class_session_wrong_day()
    {
        $user = User::factory()->create();
        // Create schedule for MONDAY
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'MONDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Try to create on Friday (current test day) when schedule is for Monday
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_cannot_create_class_session_outside_time_range()
    {
        $user = User::factory()->create();
        // Create schedule for Friday with time range 10:00-12:00 (current time is 09:00)
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);
        $schedulePeriod = SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // Try to create at 09:00 when period starts at 10:00
        $sessionData = [
            'schedule_period_id' => $schedulePeriod->id,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ];

        $response = $this->actingAs($user)->postJson('/api/class-sessions', $sessionData);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id']);
    }

    public function test_can_get_class_sessions_count()
    {
        $user = User::factory()->create();
        ClassSession::factory()->count(7)->create();

        $response = $this->actingAs($user)->getJson('/api/class-sessions/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 7);
    }
}
