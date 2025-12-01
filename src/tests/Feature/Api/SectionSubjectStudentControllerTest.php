<?php

namespace Tests\Feature\Api;

use App\Models\SectionSubject;
use App\Models\SectionSubjectStudent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionSubjectStudentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_section_subject_student()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $student = User::factory()->create(['role' => 'STUDENT']);

        $payload = [
            'section_subject_id' => $sectionSubject->id,
            'student_id' => $student->id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-students', $payload);
        $response->assertStatus(201)->assertJsonPath('data.student_id', $student->id);
        $this->assertDatabaseHas('section_subject_students', $payload);
    }

    public function test_cannot_duplicate_section_subject_student_combination()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubjectStudent = SectionSubjectStudent::factory()->create();

        $payload = [
            'section_subject_id' => $sectionSubjectStudent->section_subject_id,
            'student_id' => $sectionSubjectStudent->student_id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-students', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['section_subject_id']);
    }

    public function test_requires_student_role_for_student_id()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubject = SectionSubject::factory()->create();
        $nonStudent = User::factory()->create(['role' => 'FACULTY']);

        $payload = [
            'section_subject_id' => $sectionSubject->id,
            'student_id' => $nonStudent->id,
        ];

        $response = $this->actingAs($actingUser)->postJson('/api/section-subject-students', $payload);
        $response->assertStatus(422)->assertJsonValidationErrors(['student_id']);
    }

    public function test_can_delete_section_subject_student()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubjectStudent = SectionSubjectStudent::factory()->create();

        $response = $this->actingAs($actingUser)->deleteJson("/api/section-subject-students/{$sectionSubjectStudent->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('section_subject_students', ['id' => $sectionSubjectStudent->id]);
    }

    public function test_section_subject_student_responses_include_section_subject_and_faculty()
    {
        $actingUser = User::factory()->create(['role' => 'ADMIN']);
        $sectionSubjectStudent = SectionSubjectStudent::factory()->create();

        $response = $this->actingAs($actingUser)->getJson("/api/section-subject-students/{$sectionSubjectStudent->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.section_subject.section.id', $sectionSubjectStudent->sectionSubject->section->id)
            ->assertJsonPath('data.section_subject.subject.id', $sectionSubjectStudent->sectionSubject->subject->id)
            ->assertJsonPath('data.section_subject.faculty.id', $sectionSubjectStudent->sectionSubject->faculty->id);
    }
}
