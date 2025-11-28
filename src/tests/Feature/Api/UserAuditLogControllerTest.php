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
        UserAuditLog::factory()->count(3)->create();
        $response = $this->getJson("/api/user-audit-logs");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_audit_log()
    {
        $user = User::factory()->create();
        $logData = [
            "user_id" => $user->id,
            "description" => "User logged in from web portal"
        ];
        
        $response = $this->postJson("/api/user-audit-logs", $logData);
        $response->assertStatus(201)->assertJsonFragment(["description" => "User logged in from web portal"]);
        $this->assertDatabaseHas("user_audit_logs", ["description" => "User logged in from web portal"]);
    }

    public function test_can_show_audit_log()
    {
        $log = UserAuditLog::factory()->create();
        $response = $this->getJson("/api/user-audit-logs/{$log->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $log->id]);
    }

    public function test_can_delete_audit_log()
    {
        $log = UserAuditLog::factory()->create();
        $response = $this->deleteJson("/api/user-audit-logs/{$log->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("user_audit_logs", ["id" => $log->id]);
    }

    public function test_requires_user_id_and_description()
    {
        $response = $this->postJson("/api/user-audit-logs", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["user_id", "description"]);
    }
}
