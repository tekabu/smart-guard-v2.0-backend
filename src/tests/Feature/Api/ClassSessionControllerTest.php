<?php

namespace Tests\Feature\Api;

use App\Models\ClassSession;
use App\Models\SchedulePeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSessionControllerTest extends TestCase
{
    use RefreshDatabase;

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
        $schedulePeriod = SchedulePeriod::factory()->create();
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

    public function test_requires_all_fields()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/class-sessions', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['schedule_period_id', 'start_time', 'end_time']);
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
        $schedulePeriod = SchedulePeriod::factory()->create();
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
        $updateData = ['start_time' => '10:00:00'];
        $response = $this->actingAs($user)->putJson('/api/class-sessions/' . $session->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonPath('data.start_time', '10:00:00');
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
}
