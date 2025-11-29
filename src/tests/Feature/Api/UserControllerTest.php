<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_list_users()
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create();
        
        $response = $this->actingAs($user)->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(4, 'data'); // 3 created + 1 for acting user
    }

    public function test_can_filter_users_by_role()
    {
        $user = User::factory()->create(['role' => 'ADMIN']); // Acting user with ADMIN role
        User::factory()->create(['role' => 'ADMIN']);
        User::factory()->create(['role' => 'STUDENT']);
        User::factory()->create(['role' => 'FACULTY']);
        
        // Filter by ADMIN role
        $response = $this->actingAs($user)->getJson('/api/users?role=ADMIN');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(2, 'data') // Acting user + created admin
            ->assertJsonPath('data.0.role', 'ADMIN');
        
        // Filter by STUDENT role
        $response = $this->actingAs($user)->getJson('/api/users?role=STUDENT');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'STUDENT');
        
        // Filter by non-existent role
        $response = $this->actingAs($user)->getJson('/api/users?role=NONEXISTENT');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(0, 'data');
    }

    public function test_can_create_user()
    {
        $user = User::factory()->create(); // Acting user
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'role' => 'STUDENT',
            'active' => true,
            'student_id' => '2024-001',
            'course' => 'Computer Science',
            'year_level' => '4',
            'department' => 'Engineering',
        ];

        $response = $this->actingAs($user)->postJson('/api/users', $userData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.role', 'STUDENT');
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'role' => 'STUDENT']);
    }

    public function test_can_show_user()
    {
        $user = User::factory()->create(); // Acting user
        $showUser = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/users/' . $showUser->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $showUser->id)
            ->assertJsonPath('data.email', $showUser->email);
    }

    public function test_can_update_user()
    {
        $user = User::factory()->create(); // Acting user
        $updateUser = User::factory()->create();
        $updateData = ['name' => 'Jane Updated', 'role' => 'FACULTY'];
        $response = $this->actingAs($user)->putJson('/api/users/' . $updateUser->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.name', 'Jane Updated')
            ->assertJsonPath('data.role', 'FACULTY');
        $this->assertDatabaseHas('users', ['id' => $updateUser->id, 'name' => 'Jane Updated']);
    }

    public function test_can_delete_user()
    {
        $user = User::factory()->create(); // Acting user
        $deleteUser = User::factory()->create();
        $response = $this->actingAs($user)->deleteJson('/api/users/' . $deleteUser->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $deleteUser->id]);
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        $user = User::factory()->create(); // Acting user
        User::factory()->create(['email' => 'test@example.com']);
        $userData = ['name' => 'Another User', 'email' => 'test@example.com', 'password' => 'password123', 'role' => 'STUDENT'];
        $response = $this->actingAs($user)->postJson('/api/users', $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_create_user_with_invalid_role()
    {
        $user = User::factory()->create(); // Acting user
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123', 'role' => 'INVALID_ROLE'];
        $response = $this->actingAs($user)->postJson('/api/users', $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_requires_name_email_password_role()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson('/api/users', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_show_user_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->getJson('/api/users/99999');
        $response->assertStatus(404);
    }

    public function test_update_user_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $updateData = ['name' => 'Jane Updated', 'role' => 'FACULTY'];
        $response = $this->actingAs($user)->putJson('/api/users/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_user_that_does_not_exist()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->deleteJson('/api/users/99999');
        $response->assertStatus(404);
    }

    public function test_update_user_with_duplicate_email()
    {
        $user = User::factory()->create(); // Acting user
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        // Try to update user1 to use user2's email (which already exists)
        $updateData = ['email' => 'user2@example.com'];
        $response = $this->actingAs($user)->putJson('/api/users/' . $user1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
