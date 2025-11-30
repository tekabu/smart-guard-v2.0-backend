<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_api_token()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tokens', [
            'token_name' => 'Test API Token',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['token', 'abilities']])
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.abilities', ['*']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => 'App\Models\User',
            'tokenable_id' => $admin->id,
            'name' => 'Test API Token',
        ]);
    }

    public function test_non_admin_cannot_create_api_token()
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.com',
            'role' => 'STAFF',
            'active' => true,
        ]);

        Sanctum::actingAs($staff);

        $response = $this->postJson('/api/tokens', [
            'token_name' => 'Test API Token',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Only admin users can create API tokens.');
    }

    public function test_admin_can_list_tokens()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        $token1 = $admin->createToken('Token 1');
        $token2 = $admin->createToken('Token 2');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/tokens');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('status', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_revoke_token()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        $token = $admin->createToken('Test Token');

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/tokens/{$token->accessToken->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.message', 'Token revoked successfully');

        // Verify token is actually deleted (not soft deleted)
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_can_use_bearer_token_to_access_protected_routes()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        $token = $admin->createToken('Test Token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    public function test_can_use_bearer_token_to_access_full_system()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        $token = $admin->createToken('Test Token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);
    }

    public function test_token_creation_requires_name()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'ADMIN',
            'active' => true,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/tokens', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token_name']);
    }
}