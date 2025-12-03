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
        $user = User::factory()->create(); // Acting user
        UserFingerprint::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson('/api/user-fingerprints');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_fingerprint()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $fingerprintData = ['user_id' => $user->id, 'fingerprint_id' => 12345, 'active' => true];
        $response = $this->actingAs($authUser)->postJson('/api/user-fingerprints', $fingerprintData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.fingerprint_id', 12345);
        $this->assertDatabaseHas('user_fingerprints', ['fingerprint_id' => 12345]);
    }

    public function test_can_show_fingerprint()
    {
        $user = User::factory()->create(); // Acting user
        $fingerprint = UserFingerprint::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/user-fingerprints/' . $fingerprint->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $fingerprint->id)
            ->assertJsonPath('data.fingerprint_id', $fingerprint->fingerprint_id);
    }

    public function test_can_show_fingerprint_by_fingerprint_id()
    {
        $user = User::factory()->create(); // Acting user
        $fingerprint = UserFingerprint::factory()->create(['fingerprint_id' => 777]);

        $response = $this->actingAs($user)->getJson('/api/user-fingerprints/fingerprint/' . $fingerprint->fingerprint_id);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $fingerprint->id)
            ->assertJsonPath('data.fingerprint_id', 777);
    }

    public function test_can_update_fingerprint()
    {
        $user = User::factory()->create(); // Acting user
        $fingerprint = UserFingerprint::factory()->create();
        $updateData = ['active' => false];
        $response = $this->actingAs($user)->putJson('/api/user-fingerprints/' . $fingerprint->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.active', false);
        $this->assertDatabaseHas('user_fingerprints', ['id' => $fingerprint->id, 'active' => false]);
    }

    public function test_can_delete_fingerprint()
    {
        $user = User::factory()->create(); // Acting user
        $fingerprint = UserFingerprint::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/user-fingerprints/' . $fingerprint->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('user_fingerprints', ['id' => $fingerprint->id]);
    }

    public function test_cannot_create_fingerprint_with_duplicate_fingerprint_id()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        UserFingerprint::factory()->create(['fingerprint_id' => 12345]);
        $fingerprintData = ['user_id' => $user->id, 'fingerprint_id' => 12345];
        $response = $this->actingAs($authUser)->postJson('/api/user-fingerprints', $fingerprintData);
        $response->assertStatus(422)->assertJsonValidationErrors(['fingerprint_id']);
    }

    public function test_requires_user_id_and_fingerprint_id()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/user-fingerprints', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'fingerprint_id']);
    }

    public function test_show_fingerprint_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/user-fingerprints/99999');
        $response->assertStatus(404);
    }

    public function test_update_fingerprint_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['active' => false];
        $response = $this->actingAs($user)->putJson('/api/user-fingerprints/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_fingerprint_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/user-fingerprints/99999');
        $response->assertStatus(404);
    }

    public function test_update_fingerprint_with_duplicate_fingerprint_id()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $fingerprint1 = UserFingerprint::factory()->create(['fingerprint_id' => 11111]);
        $fingerprint2 = UserFingerprint::factory()->create(['fingerprint_id' => 22222]);

        // Try to update fingerprint1 to use fingerprint2's fingerprint_id (which already exists)
        $updateData = ['fingerprint_id' => 22222];
        $response = $this->actingAs($authUser)->putJson('/api/user-fingerprints/' . $fingerprint1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['fingerprint_id']);
    }

    public function test_can_get_user_fingerprints_count()
    {
        $user = User::factory()->create();
        UserFingerprint::factory()->count(12)->create();

        $response = $this->actingAs($user)->getJson('/api/user-fingerprints/count');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['count']])
            ->assertJsonPath('data.count', 12);
    }
}
