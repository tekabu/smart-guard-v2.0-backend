<?php

namespace Tests\Feature\Api;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_section()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $payload = ['section' => 'SECTION A'];

        $response = $this->actingAs($actingUser)->postJson('/api/sections', $payload);
        $response->assertStatus(201)->assertJsonPath('data.section', 'SECTION A');
        $this->assertDatabaseHas('sections', ['section' => 'SECTION A']);
    }

    public function test_can_list_sections()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        Section::factory()->count(2)->create();

        $response = $this->actingAs($actingUser)->getJson('/api/sections');
        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_can_update_section()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create(['section' => 'SECTION A']);

        $response = $this->actingAs($actingUser)->putJson("/api/sections/{$section->id}", ['section' => 'SECTION B']);
        $response->assertStatus(200)->assertJsonPath('data.section', 'SECTION B');
        $this->assertDatabaseHas('sections', ['id' => $section->id, 'section' => 'SECTION B']);
    }

    public function test_cannot_create_duplicate_section()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        Section::factory()->create(['section' => 'SECTION A']);

        $response = $this->actingAs($actingUser)->postJson('/api/sections', ['section' => 'SECTION A']);
        $response->assertStatus(422)->assertJsonValidationErrors(['section']);
    }

    public function test_can_delete_section()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();

        $response = $this->actingAs($actingUser)->deleteJson("/api/sections/{$section->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}
