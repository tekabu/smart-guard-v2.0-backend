<?php

namespace Tests\Feature\Api;

use App\Models\Room;
use App\Models\Device;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_rooms()
    {
        $user = User::factory()->create(); // Acting user
        Room::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/rooms');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_room()
    {
        $user = User::factory()->create(); // Acting user
        $device = Device::factory()->create();
        $roomData = ['room_number' => '101', 'device_id' => $device->id, 'active' => true];
        $response = $this->actingAs($user)->postJson('/api/rooms', $roomData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.room_number', '101');
        $this->assertDatabaseHas('rooms', ['room_number' => '101']);
    }

    public function test_can_show_room()
    {
        $user = User::factory()->create(); // Acting user
        $room = Room::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/rooms/' . $room->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.room_number', $room->room_number);
    }

    public function test_can_update_room()
    {
        $user = User::factory()->create(); // Acting user
        $room = Room::factory()->create();
        $updateData = ['room_number' => '202', 'active' => false];
        $response = $this->actingAs($user)->putJson('/api/rooms/' . $room->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.room_number', '202')
            ->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'room_number' => '202']);
    }

    public function test_can_delete_room()
    {
        $user = User::factory()->create(); // Acting user
        $room = Room::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/rooms/' . $room->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_requires_room_number()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/rooms', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }

    public function test_show_room_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/rooms/99999');
        $response->assertStatus(404);
    }

    public function test_update_room_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['room_number' => '202', 'active' => false];
        $response = $this->actingAs($user)->putJson('/api/rooms/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_room_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/rooms/99999');
        $response->assertStatus(404);
    }

    public function test_cannot_create_room_with_duplicate_room_number()
    {
        $user = User::factory()->create(); // Acting user
        Room::factory()->create(['room_number' => '101']);
        $roomData = ['room_number' => '101', 'device_id' => Device::factory()->create()->id];
        $response = $this->actingAs($user)->postJson('/api/rooms', $roomData);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }

    public function test_update_room_with_duplicate_room_number()
    {
        $user = User::factory()->create(); // Acting user
        $room1 = Room::factory()->create(['room_number' => '101']);
        $room2 = Room::factory()->create(['room_number' => '102']);

        // Try to update room2 to use room1's room number (which already exists)
        $updateData = ['room_number' => '101'];
        $response = $this->actingAs($user)->putJson('/api/rooms/' . $room2->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['room_number']);
    }

    public function test_can_get_rooms_count()
    {
        $user = User::factory()->create();
        Room::factory()->count(5)->create();

        $response = $this->actingAs($user)->getJson('/api/rooms/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 5);
    }
}
