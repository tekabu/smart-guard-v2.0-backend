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
        Device::factory()->count(3)->create();
        $response = $this->getJson('/api/devices');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_device()
    {
        $deviceData = ['device_id' => 'DEV-001', 'door_open_duration_seconds' => 5, 'active' => true];
        $response = $this->postJson('/api/devices', $deviceData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.device_id', 'DEV-001')
            ->assertJsonPath('data.door_open_duration_seconds', 5);
        $this->assertDatabaseHas('devices', ['device_id' => 'DEV-001']);
    }

    public function test_can_show_device()
    {
        $device = Device::factory()->create();
        $response = $this->getJson('/api/devices/' . $device->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $device->id)
            ->assertJsonPath('data.device_id', $device->device_id);
    }

    public function test_can_update_device()
    {
        $device = Device::factory()->create();
        $updateData = ['door_open_duration_seconds' => 10];
        $response = $this->putJson('/api/devices/' . $device->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.door_open_duration_seconds', 10);
        $this->assertDatabaseHas('devices', ['id' => $device->id, 'door_open_duration_seconds' => 10]);
    }

    public function test_can_delete_device()
    {
        $device = Device::factory()->create();
        $response = $this->deleteJson('/api/devices/' . $device->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_cannot_create_device_with_duplicate_device_id()
    {
        Device::factory()->create(['device_id' => 'DEV-001']);
        $deviceData = ['device_id' => 'DEV-001', 'door_open_duration_seconds' => 5];
        $response = $this->postJson('/api/devices', $deviceData);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }

    public function test_requires_device_id()
    {
        $response = $this->postJson('/api/devices', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }

    public function test_show_device_that_does_not_exist()
    {
        $response = $this->getJson('/api/devices/99999');
        $response->assertStatus(404);
    }

    public function test_update_device_that_does_not_exist()
    {
        $updateData = ['door_open_duration_seconds' => 10];
        $response = $this->putJson('/api/devices/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_device_that_does_not_exist()
    {
        $response = $this->deleteJson('/api/devices/99999');
        $response->assertStatus(404);
    }

    public function test_update_device_with_duplicate_device_id()
    {
        $device1 = Device::factory()->create(['device_id' => 'DEVICE-001']);
        $device2 = Device::factory()->create(['device_id' => 'DEVICE-002']);
        
        // Try to update device1 to use device2's device_id (which already exists)
        $updateData = ['device_id' => 'DEVICE-002'];
        $response = $this->putJson('/api/devices/' . $device1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['device_id']);
    }
}
