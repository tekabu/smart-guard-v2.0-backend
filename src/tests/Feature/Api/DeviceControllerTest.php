<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_devices()
    {
        $user = User::factory()->create(); // Acting user
        Device::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/devices');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_device()
    {
        $user = User::factory()->create(); // Acting user
        $deviceData = ['device_id' => 'DEV-001', 'door_open_duration_seconds' => 5, 'active' => true];
        $response = $this->actingAs($user)->postJson('/api/devices', $deviceData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.device_id', 'DEV-001')
            ->assertJsonPath('data.door_open_duration_seconds', 5);
        $this->assertDatabaseHas('devices', ['device_id' => 'DEV-001']);
    }

    public function test_can_show_device()
    {
        $user = User::factory()->create(); // Acting user
        $device = Device::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/devices/' . $device->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $device->id)
            ->assertJsonPath('data.device_id', $device->device_id);
    }

    public function test_can_update_device()
    {
        $user = User::factory()->create(); // Acting user
        $device = Device::factory()->create();
        $updateData = ['door_open_duration_seconds' => 10];
        $response = $this->actingAs($user)->putJson('/api/devices/' . $device->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.door_open_duration_seconds', 10);
        $this->assertDatabaseHas('devices', ['id' => $device->id, 'door_open_duration_seconds' => 10]);
    }

    public function test_can_delete_device()
    {
        $user = User::factory()->create(); // Acting user
        $device = Device::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/devices/' . $device->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_cannot_create_device_with_duplicate_device_id()
    {
        $user = User::factory()->create(); // Acting user
        Device::factory()->create(['device_id' => 'DEV-001']);
        $deviceData = ['device_id' => 'DEV-001', 'door_open_duration_seconds' => 5];
        $response = $this->actingAs($user)->postJson('/api/devices', $deviceData);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }

    public function test_requires_device_id()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/devices', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }

    public function test_show_device_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/devices/99999');
        $response->assertStatus(404);
    }

    public function test_update_device_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['door_open_duration_seconds' => 10];
        $response = $this->actingAs($user)->putJson('/api/devices/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_device_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/devices/99999');
        $response->assertStatus(404);
    }

    public function test_update_device_with_duplicate_device_id()
    {
        $user = User::factory()->create(); // Acting user
        $device1 = Device::factory()->create(['device_id' => 'DEVICE-001']);
        $device2 = Device::factory()->create(['device_id' => 'DEVICE-002']);

        // Try to update device1 to use device2's device_id (which already exists)
        $updateData = ['device_id' => 'DEVICE-002'];
        $response = $this->actingAs($user)->putJson('/api/devices/' . $device1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }

    public function test_can_get_devices_count()
    {
        $user = User::factory()->create();
        Device::factory()->count(6)->create();

        $response = $this->actingAs($user)->getJson('/api/devices/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 6);
    }

    public function test_api_token_is_generated_on_create()
    {
        $user = User::factory()->create();
        $deviceData = ['device_id' => 'DEV-TOKEN-001', 'door_open_duration_seconds' => 5, 'active' => true];

        $response = $this->actingAs($user)->postJson('/api/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data' => ['api_token']]);

        $apiToken = $response->json('data.api_token');
        $this->assertNotNull($apiToken);
        $this->assertEquals(64, strlen($apiToken));
        $this->assertDatabaseHas('devices', ['device_id' => 'DEV-TOKEN-001', 'api_token' => $apiToken]);
    }

    public function test_api_token_is_unique_for_each_device()
    {
        $user = User::factory()->create();

        $device1Data = ['device_id' => 'DEV-001', 'door_open_duration_seconds' => 5];
        $device2Data = ['device_id' => 'DEV-002', 'door_open_duration_seconds' => 5];

        $response1 = $this->actingAs($user)->postJson('/api/devices', $device1Data);
        $response2 = $this->actingAs($user)->postJson('/api/devices', $device2Data);

        $token1 = $response1->json('data.api_token');
        $token2 = $response2->json('data.api_token');

        $this->assertNotNull($token1);
        $this->assertNotNull($token2);
        $this->assertNotEquals($token1, $token2);
    }

    public function test_api_token_can_be_manually_set()
    {
        $user = User::factory()->create();
        $customToken = 'custom-token-12345678901234567890123456789012345678901234567890';
        $deviceData = [
            'device_id' => 'DEV-CUSTOM-001',
            'api_token' => $customToken,
            'door_open_duration_seconds' => 5
        ];

        $response = $this->actingAs($user)->postJson('/api/devices', $deviceData);

        $response->assertStatus(201)
            ->assertJsonPath('data.api_token', $customToken);

        $this->assertDatabaseHas('devices', ['device_id' => 'DEV-CUSTOM-001', 'api_token' => $customToken]);
    }
}
