<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_healthy_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'checks'])
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('checks.database', 'ok');
    }

    public function test_health_endpoint_is_accessible_without_authentication()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }
}
