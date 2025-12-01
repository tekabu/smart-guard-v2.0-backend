<?php

namespace Tests\Feature\Api;

use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionSubjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_section_subject()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();
        $subject = Subject::factory()->create();
        $faculty = User::factory()->create(['role' => 'FACULTY']);

        $payload = [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subjects', $payload);
        $response->assertStatus(201)->assertJsonPath('data.section_id', $section->id);
        $this->assertDatabaseHas('section_subjects', $payload);
    }

    public function test_cannot_duplicate_section_subject_combination()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();
        $subject = Subject::factory()->create();
        $faculty = User::factory()->create(['role' => 'FACULTY']);

        SectionSubject::create([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
        ]);

        $payload = [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $faculty->id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subjects', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['section_id']);
    }

    public function test_requires_faculty_role_for_faculty_id()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create();
        $subject = Subject::factory()->create();
        $nonFaculty = User::factory()->create(['role' => 'STUDENT']);

        $payload = [
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'faculty_id' => $nonFaculty->id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subjects', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);
    }

    public function test_can_update_section_subject()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $newSubject = Subject::factory()->create();

        $response = $this->actingAs($actingUser)->putJson(
            "/api/section-subjects/{$sectionSubject->id}",
            ['subject_id' => $newSubject->id]
        );

        $response->assertStatus(200)->assertJsonPath('data.subject_id', $newSubject->id);
        $this->assertDatabaseHas('section_subjects', [
            'id' => $sectionSubject->id,
            'subject_id' => $newSubject->id,
        ]);
    }

    public function test_can_delete_section_subject()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();

        $response = $this->actingAs($actingUser)->deleteJson("/api/section-subjects/{$sectionSubject->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('section_subjects', ['id' => $sectionSubject->id]);
    }

    public function test_can_get_section_subject_options()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $section = Section::factory()->create(['section' => 'SECTION A']);
        $subject = Subject::factory()->create(['subject' => 'SUBJECT 1']);
        $sectionSubject = SectionSubject::factory()->create([
            'section_id' => $section->id,
            'subject_id' => $subject->id,
        ]);
        $sectionSubject->load('faculty');

        $response = $this->actingAs($actingUser)->getJson('/api/section-subjects/options');
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $sectionSubject->id,
                'label' => sprintf('SECTION A - SUBJECT 1 - %s', $sectionSubject->faculty->name),
            ]);
    }
}
