<?php

namespace Tests\Feature\Api;

use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_subjects()
    {
        Subject::factory()->count(3)->create();
        $response = $this->getJson("/api/subjects");
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_can_create_subject()
    {
        $subjectData = ["subject" => "Computer Programming", "active" => true];
        $response = $this->postJson("/api/subjects", $subjectData);
        $response->assertStatus(201)->assertJsonFragment(["subject" => "Computer Programming"]);
        $this->assertDatabaseHas("subjects", ["subject" => "Computer Programming"]);
    }

    public function test_can_show_subject()
    {
        $subject = Subject::factory()->create();
        $response = $this->getJson("/api/subjects/{$subject->id}");
        $response->assertStatus(200)->assertJsonFragment(["id" => $subject->id, "subject" => $subject->subject]);
    }

    public function test_can_update_subject()
    {
        $subject = Subject::factory()->create();
        $updateData = ["subject" => "Advanced Programming"];
        $response = $this->putJson("/api/subjects/{$subject->id}", $updateData);
        $response->assertStatus(200)->assertJsonFragment(["subject" => "Advanced Programming"]);
        $this->assertDatabaseHas("subjects", ["id" => $subject->id, "subject" => "Advanced Programming"]);
    }

    public function test_can_delete_subject()
    {
        $subject = Subject::factory()->create();
        $response = $this->deleteJson("/api/subjects/{$subject->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing("subjects", ["id" => $subject->id]);
    }

    public function test_cannot_create_subject_with_duplicate_name()
    {
        Subject::factory()->create(["subject" => "Math"]);
        $subjectData = ["subject" => "Math"];
        $response = $this->postJson("/api/subjects", $subjectData);
        $response->assertStatus(422)->assertJsonValidationErrors(["subject"]);
    }

    public function test_requires_subject()
    {
        $response = $this->postJson("/api/subjects", []);
        $response->assertStatus(422)->assertJsonValidationErrors(["subject"]);
    }
}
