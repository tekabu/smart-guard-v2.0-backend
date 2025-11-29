<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\DeviceBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceBoardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_device_boards()
    {
        DeviceBoard::factory()->count(3)->create();
        
        $response = $this->getJson('/api/device-boards');
        
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_list_device_boards_with_filters()
    {
        $device1 = Device::factory()->create();
        $device2 = Device::factory()->create();
        
        DeviceBoard::factory()->create(['device_id' => $device1->id, 'board_type' => 'FINGERPRINT', 'active' => true]);
        DeviceBoard::factory()->create(['device_id' => $device2->id, 'board_type' => 'RFID', 'active' => false]);
        DeviceBoard::factory()->create(['device_id' => $device1->id, 'board_type' => 'LOCK', 'active' => true]);
        
        // Filter by device_id
        $response = $this->getJson('/api/device-boards?device_id=' . $device1->id);
        $response->assertStatus(200)->assertJsonCount(2, 'data');
        
        // Filter by board_type
        $response = $this->getJson('/api/device-boards?board_type=FINGERPRINT');
        $response->assertStatus(200)->assertJsonCount(1, 'data');
        
        // Filter by active status
        $response = $this->getJson('/api/device-boards?active=true');
        $response->assertStatus(200)->assertJsonCount(2, 'data');
        
        $response = $this->getJson('/api/device-boards?active=false');
        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_can_create_device_board()
    {
        $device = Device::factory()->create();
        
        $boardData = [
            'device_id' => $device->id,
            'board_type' => 'FINGERPRINT',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'firmware_version' => 'v1.2.3',
            'active' => true,
        ];
        
        $response = $this->postJson('/api/device-boards', $boardData);
        
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.device_id', $device->id)
            ->assertJsonPath('data.board_type', 'FINGERPRINT')
            ->assertJsonPath('data.mac_address', 'AA:BB:CC:DD:EE:FF');
            
        $this->assertDatabaseHas('device_boards', ['mac_address' => 'AA:BB:CC:DD:EE:FF']);
    }

    public function test_cannot_create_device_board_with_invalid_board_type()
    {
        $device = Device::factory()->create();
        
        $boardData = [
            'device_id' => $device->id,
            'board_type' => 'INVALID_TYPE',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ];
        
        $response = $this->postJson('/api/device-boards', $boardData);
        
        $response->assertStatus(422)->assertJsonValidationErrors(['board_type']);
    }

    public function test_cannot_create_device_board_with_duplicate_mac_address()
    {
        $device = Device::factory()->create();
        DeviceBoard::factory()->create(['mac_address' => 'AA:BB:CC:DD:EE:FF']);
        
        $boardData = [
            'device_id' => $device->id,
            'board_type' => 'FINGERPRINT',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ];
        
        $response = $this->postJson('/api/device-boards', $boardData);
        
        $response->assertStatus(422)->assertJsonValidationErrors(['mac_address']);
    }

    public function test_requires_device_id_and_board_type_and_mac_address()
    {
        $response = $this->postJson('/api/device-boards', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_id', 'board_type', 'mac_address']);
    }

    public function test_can_show_device_board()
    {
        $board = DeviceBoard::factory()->create();
        
        $response = $this->getJson('/api/device-boards/' . $board->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $board->id)
            ->assertJsonPath('data.mac_address', $board->mac_address);
    }

    public function test_show_device_board_includes_device()
    {
        $board = DeviceBoard::factory()->create();
        
        $response = $this->getJson('/api/device-boards/' . $board->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $board->id)
            ->assertJsonPath('data.device.id', $board->device->id);
    }

    public function test_show_device_board_that_does_not_exist()
    {
        $response = $this->getJson('/api/device-boards/99999');
        
        $response->assertStatus(404);
    }

    public function test_can_update_device_board()
    {
        $board = DeviceBoard::factory()->create(['board_type' => 'FINGERPRINT', 'active' => true]);
        $newDevice = Device::factory()->create();
        
        $updateData = [
            'device_id' => $newDevice->id,
            'board_type' => 'RFID',
            'active' => false,
            'firmware_version' => 'v2.0.0',
        ];
        
        $response = $this->putJson('/api/device-boards/' . $board->id, $updateData);
        
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.device_id', $newDevice->id)
            ->assertJsonPath('data.board_type', 'RFID')
            ->assertJsonPath('data.active', false)
            ->assertJsonPath('data.firmware_version', 'v2.0.0');
            
        $this->assertDatabaseHas('device_boards', [
            'id' => $board->id,
            'device_id' => $newDevice->id,
            'board_type' => 'RFID',
            'active' => false,
            'firmware_version' => 'v2.0.0',
        ]);
    }

    public function test_update_device_board_with_duplicate_mac_address()
    {
        $board1 = DeviceBoard::factory()->create(['mac_address' => 'AA:BB:CC:DD:EE:FF']);
        $board2 = DeviceBoard::factory()->create(['mac_address' => '11:22:33:44:55:66']);
        
        // Try to update board2 to use board1's MAC address
        $updateData = ['mac_address' => 'AA:BB:CC:DD:EE:FF'];
        $response = $this->putJson('/api/device-boards/' . $board2->id, $updateData);
        
        $response->assertStatus(422)->assertJsonValidationErrors(['mac_address']);
    }

    public function test_update_device_board_that_does_not_exist()
    {
        $updateData = ['board_type' => 'RFID'];
        $response = $this->putJson('/api/device-boards/99999', $updateData);
        
        $response->assertStatus(404);
    }

    public function test_can_delete_device_board()
    {
        $board = DeviceBoard::factory()->create();
        
        $response = $this->deleteJson('/api/device-boards/' . $board->id);
        
        $response->assertStatus(204);
        $this->assertDatabaseMissing('device_boards', ['id' => $board->id]);
    }

    public function test_delete_device_board_that_does_not_exist()
    {
        $response = $this->deleteJson('/api/device-boards/99999');
        
        $response->assertStatus(404);
    }
}