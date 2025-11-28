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
        UserRfid::factory()->count(3)->create();
        $response = $this->getJson("/api/user-rfids");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_fingerprint()
    {
        $user = User::factory()->create();
        $fingerprintData = ["user_id" => $user->id, "card_id" => "ABC123", "active" => true];
        $response = $this->postJson("/api/user-rfids", $fingerprintData);
        $response->assertStatus(201)->assertJsonFragment(["card_id" => "ABC123"]);
        $this->assertDatabaseHas("user_rfids", ["card_id" => "ABC123"]);
    }

    public function test_can_show_fingerprint()
    {
        $fingerprint = UserRfid::factory()->create();
        $response = $this->getJson("/api/user-rfids/{$fingerprint->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $fingerprint->id, "card_id" => $fingerprint->card_id]);
    }

    public function test_can_update_fingerprint()
    {
        $fingerprint = UserRfid::factory()->create();
        $updateData = ["active" => false];
        $response = $this->putJson("/api/user-rfids/{$fingerprint->id}", $updateData);
        $response->assertStatus(200)->assertJsonFragment(["active" => false]);
        $this->assertDatabaseHas("user_rfids", ["id" => $fingerprint->id, "active" => false]);
    }

    public function test_can_delete_fingerprint()
    {
        $fingerprint = UserRfid::factory()->create();
        $response = $this->deleteJson("/api/user-rfids/{$fingerprint->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_rfids", ["id" => $fingerprint->id]);
    }

    public function test_cannot_create_fingerprint_with_duplicate_card_id()
    {
        $user = User::factory()->create();
        UserRfid::factory()->create(["card_id" => "ABC123"]);
        $fingerprintData = ["user_id" => $user->id, "card_id" => "ABC123"];
        $response = $this->postJson("/api/user-rfids", $fingerprintData);
        $response->assertStatus(422)->assertJsonValidationErrors(["card_id"]);
    }

    public function test_requires_user_id_and_card_id()
    {
        $response = $this->postJson("/api/user-rfids", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "card_id"]);
    }
}
