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
        UserAccessLog::factory()->count(3)->create();
        $response = $this->getJson("/api/user-access-logs");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_access_log()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $device = Device::factory()->create();
        
        $logData = [
            "user_id" => $user->id,
            "room_id" => $room->id,
            "device_id" => $device->id,
            "access_used" => "FINGERPRINT"
        ];
        
        $response = $this->postJson("/api/user-access-logs", $logData);
        $response->assertStatus(201)->assertJsonFragment(["access_used" => "FINGERPRINT"]);
        $this->assertDatabaseHas("user_access_logs", ["access_used" => "FINGERPRINT"]);
    }

    public function test_can_show_access_log()
    {
        $log = UserAccessLog::factory()->create();
        $response = $this->getJson("/api/user-access-logs/{$log->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $log->id]);
    }

    public function test_can_delete_access_log()
    {
        $log = UserAccessLog::factory()->create();
        $response = $this->deleteJson("/api/user-access-logs/{$log->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_access_logs", ["id" => $log->id]);
    }

    public function test_cannot_create_log_with_invalid_access_method()
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();
        $device = Device::factory()->create();
        
        $logData = [
            "user_id" => $user->id,
            "room_id" => $room->id,
            "device_id" => $device->id,
            "access_used" => "INVALID_METHOD"
        ];
        
        $response = $this->postJson("/api/user-access-logs", $logData);
        $response->assertStatus(422)->assertJsonValidationErrors(["access_used"]);
    }

    public function test_requires_all_fields()
    {
        $response = $this->postJson("/api/user-access-logs", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "room_id", "device_id", "access_used"]);
    }
}
