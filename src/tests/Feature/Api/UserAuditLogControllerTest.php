<?php

namespace Tests\Feature\Api;

use App\Models\UserAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_audit_logs()
    {
        $user = User::factory()->create(); // Acting user
        UserAuditLog::factory()->count(3)->create();
        $response = $this->actingAs($user)->getJson("/api/user-audit-logs");
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_audit_log()
    {
        $authUser = User::factory()->create(); // Acting user
        $user = User::factory()->create();
        $logData = [
            "user_id" => $user->id,
            "description" => "User logged in from web portal"
        ];

        $response = $this->actingAs($authUser)->postJson("/api/user-audit-logs", $logData);
        $response->assertStatus(201)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.description', 'User logged in from web portal');
        $this->assertDatabaseHas("user_audit_logs", ["description" => "User logged in from web portal"]);
    }

    public function test_can_show_audit_log()
    {
        $user = User::factory()->create(); // Acting user
        $log = UserAuditLog::factory()->create();
        $response = $this->actingAs($user)->getJson("/api/user-audit-logs/{$log->id}");
        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data'])
            ->assertJsonPath('data.id', $log->id);
    }

    public function test_can_delete_audit_log()
    {
        $user = User::factory()->create(); // Acting user
        $log = UserAuditLog::factory()->create();
        $response = $this->actingAs($user)->deleteJson("/api/user-audit-logs/{$log->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_audit_logs", ["id" => $log->id]);
    }

    public function test_requires_user_id_and_description()
    {
        $user = User::factory()->create(); // Acting user
        $response = $this->actingAs($user)->postJson("/api/user-audit-logs", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "description"]);
    }
}
