<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_rooms()
    {
        Room::factory()->count(3)->create();
        $response = $this->getJson('/api/rooms');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_room()
    {
        $device = Device::factory()->create();
        $roomData = ['room_number' => '101', 'device_id' => $device->id, 'active' => true];
        $response = $this->postJson('/api/rooms', $roomData);
        $response->assertStatus(201)->assertJsonFragment(['room_number' => '101']);
        $this->assertDatabaseHas('rooms', ['room_number' => '101']);
    }

    public function test_can_show_room()
    {
        $room = Room::factory()->create();
        $response = $this->getJson('/api/rooms/' . $room->id);
        $response->assertStatus(200)->assertJsonFragment(['id' => $room->id, 'room_number' => $room->room_number]);
    }

    public function test_can_update_room()
    {
        $room = Room::factory()->create();
        $updateData = ['room_number' => '202', 'active' => false];
        $response = $this->putJson('/api/rooms/' . $room->id, $updateData);
        $response->assertStatus(200)->assertJsonFragment(['room_number' => '202', 'active' => false]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'room_number' => '202']);
    }

    public function test_can_delete_room()
    {
        $room = Room::factory()->create();
        $response = $this->deleteJson('/api/rooms/' . $room->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_requires_room_number()
    {
        $response = $this->postJson('/api/rooms', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }

    public function test_show_room_that_does_not_exist()
    {
        $response = $this->getJson('/api/rooms/99999');
        $response->assertStatus(404);
    }

    public function test_update_room_that_does_not_exist()
    {
        $updateData = ['room_number' => '202', 'active' => false];
        $response = $this->putJson('/api/rooms/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_room_that_does_not_exist()
    {
        $response = $this->deleteJson('/api/rooms/99999');
        $response->assertStatus(404);
    }

    public function test_cannot_create_room_with_duplicate_room_number()
    {
        Room::factory()->create(['room_number' => '101']);
        $roomData = ['room_number' => '101', 'device_id' => Device::factory()->create()->id];
        $response = $this->postJson('/api/rooms', $roomData);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }

    public function test_update_room_with_duplicate_room_number()
    {
        $room1 = Room::factory()->create(['room_number' => '101']);
        $room2 = Room::factory()->create(['room_number' => '102']);
        
        // Try to update room2 to use room1's room number (which already exists)
        $updateData = ['room_number' => '101'];
        $response = $this->putJson('/api/rooms/' . $room2->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }
}
