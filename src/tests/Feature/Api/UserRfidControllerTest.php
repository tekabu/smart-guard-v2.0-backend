<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserRfid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRfidControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_rfids()
    {
        $user = User::factory()->create(); // Acting user
        UserRfid::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/user-rfids');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_rfid()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $rfidData = ['user_id' => $user->id, 'card_id' => 'ABC123', 'active' => true];
        $response = $this->actingAs($authUser)->postJson('/api/user-rfids', $rfidData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.card_id', 'ABC123');
        $this->assertDatabaseHas('user_rfids', ['card_id' => 'ABC123']);
    }

    public function test_can_show_rfid()
    {
        $user = User::factory()->create(); // Acting user
        $rfid = UserRfid::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/user-rfids/' . $rfid->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $rfid->id)
            ->assertJsonPath('data.card_id', $rfid->card_id);
    }

    public function test_can_show_rfid_by_card_id()
    {
        $user = User::factory()->create(); // Acting user
        $rfid = UserRfid::factory()->create(['card_id' => 'CARD123']);

        $response = $this->actingAs($user)->getJson('/api/user-rfids/card/' . $rfid->card_id);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $rfid->id)
            ->assertJsonPath('data.card_id', 'CARD123');
    }

    public function test_can_update_rfid()
    {
        $user = User::factory()->create(); // Acting user
        $rfid = UserRfid::factory()->create();
        $updateData = ['active' => false];
        $response = $this->actingAs($user)->putJson('/api/user-rfids/' . $rfid->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('user_rfids', ['id' => $rfid->id, 'active' => false]);
    }

    public function test_can_delete_rfid()
    {
        $user = User::factory()->create(); // Acting user
        $rfid = UserRfid::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/user-rfids/' . $rfid->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('user_rfids', ['id' => $rfid->id]);
    }

    public function test_cannot_create_rfid_with_duplicate_card_id()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        UserRfid::factory()->create(['card_id' => 'ABC123']);
        $rfidData = ['user_id' => $user->id, 'card_id' => 'ABC123'];
        $response = $this->actingAs($authUser)->postJson('/api/user-rfids', $rfidData);
        $response->assertStatus(422)->assertJsonValidationErrors(['card_id']);
    }

    public function test_requires_user_id_and_card_id()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/user-rfids', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'card_id']);
    }

    public function test_show_rfid_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/user-rfids/99999');
        $response->assertStatus(404);
    }

    public function test_update_rfid_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['active' => false];
        $response = $this->actingAs($user)->putJson('/api/user-rfids/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_rfid_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/user-rfids/99999');
        $response->assertStatus(404);
    }

    public function test_update_rfid_with_duplicate_card_id()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $rfid1 = UserRfid::factory()->create(['card_id' => 'RFID001']);
        $rfid2 = UserRfid::factory()->create(['card_id' => 'RFID002']);

        // Try to update rfid1 to use rfid2's card_id (which already exists)
        $updateData = ['card_id' => 'RFID002'];
        $response = $this->actingAs($authUser)->putJson('/api/user-rfids/' . $rfid1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['card_id']);
    }

    public function test_can_get_user_rfids_count()
    {
        $user = User::factory()->create();
        UserRfid::factory()->count(11)->create();

        $response = $this->actingAs($user)->getJson('/api/user-rfids/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 11);
    }
}
