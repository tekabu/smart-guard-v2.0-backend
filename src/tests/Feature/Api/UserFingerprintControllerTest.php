<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserFingerprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserFingerprintControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_fingerprints()
    {
        UserFingerprint::factory()->count(3)->create();
        $response = $this->getJson("/api/user-fingerprints");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_fingerprint()
    {
        $user = User::factory()->create();
        $fingerprintData = ["user_id" => $user->id, "fingerprint_id" => 12345, "active" => true];
        $response = $this->postJson("/api/user-fingerprints", $fingerprintData);
        $response->assertStatus(201)->assertJsonFragment(["fingerprint_id" => 12345]);
        $this->assertDatabaseHas("user_fingerprints", ["fingerprint_id" => 12345]);
    }

    public function test_can_show_fingerprint()
    {
        $fingerprint = UserFingerprint::factory()->create();
        $response = $this->getJson("/api/user-fingerprints/{$fingerprint->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $fingerprint->id, "fingerprint_id" => $fingerprint->fingerprint_id]);
    }

    public function test_can_update_fingerprint()
    {
        $fingerprint = UserFingerprint::factory()->create();
        $updateData = ["active" => false];
        $response = $this->putJson("/api/user-fingerprints/{$fingerprint->id}", $updateData);
        $response->assertStatus(200)->assertJsonFragment(["active" => false]);
        $this->assertDatabaseHas("user_fingerprints", ["id" => $fingerprint->id, "active" => false]);
    }

    public function test_can_delete_fingerprint()
    {
        $fingerprint = UserFingerprint::factory()->create();
        $response = $this->deleteJson("/api/user-fingerprints/{$fingerprint->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_fingerprints", ["id" => $fingerprint->id]);
    }

    public function test_cannot_create_fingerprint_with_duplicate_fingerprint_id()
    {
        $user = User::factory()->create();
        UserFingerprint::factory()->create(["fingerprint_id" => 12345]);
        $fingerprintData = ["user_id" => $user->id, "fingerprint_id" => 12345];
        $response = $this->postJson("/api/user-fingerprints", $fingerprintData);
        $response->assertStatus(422)->assertJsonValidationErrors(["fingerprint_id"]);
    }

    public function test_requires_user_id_and_fingerprint_id()
    {
        $response = $this->postJson("/api/user-fingerprints", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "fingerprint_id"]);
    }
}
