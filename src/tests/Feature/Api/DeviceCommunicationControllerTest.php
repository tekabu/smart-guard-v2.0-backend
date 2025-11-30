<?php

namespace Tests\Feature\Api;

use App\Models\DeviceBoard;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserFingerprint;
use App\Models\UserRfid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class DeviceCommunicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2024-01-05 09:00:00')); // Friday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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

    public function test_device_board_can_validate_card_id()
    {
        $board = DeviceBoard::factory()->create();
        $rfid = UserRfid::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/validate-card', [
                'card_id' => $rfid->card_id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.user_id', $rfid->user_id);
    }

    public function test_device_board_can_validate_fingerprint_id()
    {
        $board = DeviceBoard::factory()->create();
        $fingerprint = UserFingerprint::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/validate-fingerprint', [
                'fingerprint_id' => (string) $fingerprint->fingerprint_id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.user_id', $fingerprint->user_id);
    }

    public function test_device_board_can_scan_card()
    {
        $board = DeviceBoard::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/scan-card', [
                'card_id' => 'CARD-123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.scanned', true)
            ->assertJsonPath('data.card_id', 'CARD-123');
    }

    public function test_device_board_can_scan_fingerprint()
    {
        $board = DeviceBoard::factory()->create();

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/scan-fingerprint', [
                'fingerprint_id' => 'FP-123',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.scanned', true)
            ->assertJsonPath('data.fingerprint_id', 'FP-123');
    }

    public function test_device_board_can_create_class_session_from_card()
    {
        $board = DeviceBoard::factory()->create();
        $faculty = User::factory()->create(['role' => 'FACULTY']);
        $rfid = UserRfid::factory()->create(['user_id' => $faculty->id]);
        $schedulePeriod = $this->prepareSchedulePeriod($faculty);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/class-sessions/from-card', [
                'card_id' => $rfid->card_id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.schedule_period_id', $schedulePeriod->id);

        $this->assertDatabaseHas('class_sessions', [
            'schedule_period_id' => $schedulePeriod->id,
        ]);
    }

    public function test_device_board_can_create_class_session_from_fingerprint()
    {
        $board = DeviceBoard::factory()->create();
        $faculty = User::factory()->create(['role' => 'FACULTY']);
        $fingerprint = UserFingerprint::factory()->create(['user_id' => $faculty->id]);
        $schedulePeriod = $this->prepareSchedulePeriod($faculty);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/class-sessions/from-fingerprint', [
                'fingerprint_id' => (string) $fingerprint->fingerprint_id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.schedule_period_id', $schedulePeriod->id);
    }

    public function test_cannot_create_class_session_from_card_for_non_faculty()
    {
        $board = DeviceBoard::factory()->create();
        $student = User::factory()->create(['role' => 'STUDENT']);
        $rfid = UserRfid::factory()->create(['user_id' => $student->id]);
        $schedulePeriod = $this->prepareSchedulePeriod($student);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/class-sessions/from-card', [
                'card_id' => $rfid->card_id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    public function test_cannot_create_class_session_from_card_without_schedule_period()
    {
        $board = DeviceBoard::factory()->create();
        $faculty = User::factory()->create(['role' => 'FACULTY']);
        $rfid = UserRfid::factory()->create(['user_id' => $faculty->id]);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $board->api_token)
            ->postJson('/api/device-communications/class-sessions/from-card', [
                'card_id' => $rfid->card_id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false);
    }

    private function prepareSchedulePeriod(User $user): SchedulePeriod
    {
        $room = Room::factory()->create();
        $subject = Subject::factory()->create();
        $schedule = Schedule::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'FRIDAY',
        ]);

        return SchedulePeriod::factory()->create([
            'schedule_id' => $schedule->id,
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);
    }
}
