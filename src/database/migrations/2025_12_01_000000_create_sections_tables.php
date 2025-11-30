<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('section')->unique();
            $table->timestamps();
        });

        Schema::create('section_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['section_id', 'subject_id', 'faculty_id'], 'section_subject_unique');
            $table->timestamps();
        });

        Schema::create('section_subject_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_subject_id')->constrained('section_subjects')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['section_subject_id', 'student_id'], 'section_subject_student_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_subject_students');
        Schema::dropIfExists('section_subjects');
        Schema::dropIfExists('sections');
    }
};
