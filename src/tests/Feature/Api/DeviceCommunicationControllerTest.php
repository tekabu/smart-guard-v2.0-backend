<?php

namespace Tests\Feature\Api;

use App\Models\DeviceBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceCommunicationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_send_heartbeat()
    {
        $board = DeviceBoard::factory()->create([
            'firmware_version' => 'v1.0.0',
            'last_seen_at' => null,
            'last_ip' => null,
        ]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/heartbeat', [
                'firmware_version' => 'v2.1.0',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['board']])
            ->assertJsonPath('data.board.id', $board->id)
            ->assertJsonPath('data.board.firmware_version', 'v2.1.0');

        $board->refresh();
        $this->assertNotNull($board->last_seen_at);
        $this->assertNotEmpty($board->last_ip);
    }

    public function test_can_get_device_board_profile()
    {
        $board = DeviceBoard::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->getJson('/api/device-communications/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'data' => ['id', 'device']])
            ->assertJsonPath('data.id', $board->id);
    }

    public function test_cannot_access_without_authentication()
    {
        $response = $this->postJson('/api/device-communications/heartbeat');

        $response->assertStatus(401);
    }

    public function test_cannot_access_as_user()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/device-communications/heartbeat');

        $response->assertStatus(403)
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'This action is unauthorized.');
    }
}
