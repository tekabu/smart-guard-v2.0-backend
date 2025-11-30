<?php

namespace Tests\Feature\Api;

use App\Models\UserAccessLog;
use App\Models\User;
use App\Models\Room;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_access_logs()
    {
        $user = User::factory()->create(); // Acting user
        UserAccessLog::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson("/api/user-access-logs");
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_access_log()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $device = Device::factory()->create();

        $logData = [
            "user_id" => $user->id,
            "room_id" => $room->id,
            "device_id" => $device->id,
            "access_used" => "FINGERPRINT"
        ];

        $response = $this->actingAs($authUser)->postJson("/api/user-access-logs", $logData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.access_used', 'FINGERPRINT');
        $this->assertDatabaseHas("user_access_logs", ["access_used" => "FINGERPRINT"]);
    }

    public function test_can_show_access_log()
    {
        $user = User::factory()->create(); // Acting user
        $log = UserAccessLog::factory()->create();
        $response = $this->actingAs($user)->getJson("/api/user-access-logs/{$log->id}");
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $log->id);
    }

    public function test_can_delete_access_log()
    {
        $user = User::factory()->create(); // Acting user
        $log = UserAccessLog::factory()->create();
        $response = $this->actingAs($user)->deleteJson("/api/user-access-logs/{$log->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_access_logs", ["id" => $log->id]);
    }

    public function test_cannot_create_log_with_invalid_access_method()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $device = Device::factory()->create();
        
        $logData = [
            "user_id" => $user->id,
            "room_id" => $room->id,
            "device_id" => $device->id,
            "access_used" => "INVALID_METHOD"
        ];
        
        $response = $this->actingAs($authUser)->postJson("/api/user-access-logs", $logData);
        $response->assertStatus(422)->assertJsonValidationErrors(["access_used"]);
    }

    public function test_requires_all_fields()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson("/api/user-access-logs", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "room_id", "device_id", "access_used"]);
    }

    public function test_can_get_user_access_logs_count()
    {
        $user = User::factory()->create();
        UserAccessLog::factory()->count(15)->create();

        $response = $this->actingAs($user)->getJson('/api/user-access-logs/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 15);
    }
}
