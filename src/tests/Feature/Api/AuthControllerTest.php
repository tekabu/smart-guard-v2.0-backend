<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['user']])
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_email()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $this->assertGuest();
    }

    public function test_user_cannot_login_with_invalid_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'The provided credentials are incorrect.');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => false,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Your account is inactive.');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_requires_valid_email_format()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->withSession([])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.message', 'Successfully logged out');
    }

    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_get_their_info()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'active' => true,
        ]);

        $this->actingAs($user, 'web');

        $response = $this->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.email', 'test@example.com')
            ->assertJsonPath('data.name', 'Test User');
    }

    public function test_unauthenticated_user_cannot_get_user_info()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_user_can_login_and_access_protected_routes()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'active' => true,
        ]);

        // Login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);

        $this->assertAuthenticatedAs($user);

        // Access protected route
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $response->assertStatus(200)
            ->assertJsonPath('data.email', 'test@example.com');
    }
}
