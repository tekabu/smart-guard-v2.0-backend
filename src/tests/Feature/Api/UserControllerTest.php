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
        User::factory()->count(3)->create();
        $response = $this->getJson('/api/users');
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_user()
    {
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

        $response = $this->postJson('/api/users', $userData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.name', 'John Doe')
            ->assertJsonPath('data.email', 'john@example.com')
            ->assertJsonPath('data.role', 'STUDENT');
        $this->assertDatabaseHas('users', ['email' => 'john@example.com', 'role' => 'STUDENT']);
    }

    public function test_can_show_user()
    {
        $user = User::factory()->create();
        $response = $this->getJson('/api/users/' . $user->id);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_can_update_user()
    {
        $user = User::factory()->create();
        $updateData = ['name' => 'Jane Updated', 'role' => 'FACULTY'];
        $response = $this->putJson('/api/users/' . $user->id, $updateData);
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.name', 'Jane Updated')
            ->assertJsonPath('data.role', 'FACULTY');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Jane Updated']);
    }

    public function test_can_delete_user()
    {
        $user = User::factory()->create();
        $response = $this->deleteJson('/api/users/' . $user->id);
        $response->assertStatus(204);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_cannot_create_user_with_duplicate_email()
    {
        User::factory()->create(['email' => 'test@example.com']);
        $userData = ['name' => 'Another User', 'email' => 'test@example.com', 'password' => 'password123', 'role' => 'STUDENT'];
        $response = $this->postJson('/api/users', $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_cannot_create_user_with_invalid_role()
    {
        $userData = ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password123', 'role' => 'INVALID_ROLE'];
        $response = $this->postJson('/api/users', $userData);
        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_requires_name_email_password_role()
    {
        $response = $this->postJson('/api/users', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    }

    public function test_show_user_that_does_not_exist()
    {
        $response = $this->getJson('/api/users/99999');
        $response->assertStatus(404);
    }

    public function test_update_user_that_does_not_exist()
    {
        $updateData = ['name' => 'Jane Updated', 'role' => 'FACULTY'];
        $response = $this->putJson('/api/users/99999', $updateData);
        $response->assertStatus(404);
    }

    public function test_delete_user_that_does_not_exist()
    {
        $response = $this->deleteJson('/api/users/99999');
        $response->assertStatus(404);
    }

    public function test_update_user_with_duplicate_email()
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        // Try to update user1 to use user2's email (which already exists)
        $updateData = ['email' => 'user2@example.com'];
        $response = $this->putJson('/api/users/' . $user1->id, $updateData);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
