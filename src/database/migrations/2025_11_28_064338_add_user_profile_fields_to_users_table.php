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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['ADMIN', 'STAFF', 'STUDENT', 'FACULTY'])->default('STUDENT');
            $table->boolean('active')->default(true);
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('student_id')->nullable();
            $table->string('faculty_id')->nullable();
            $table->string('course')->nullable();
            $table->string('year_level')->nullable();
            $table->decimal('attendance_rate', 5, 2)->nullable();
            $table->string('department')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'active',
                'last_accessed_at',
                'student_id',
                'faculty_id',
                'course',
                'year_level',
                'attendance_rate',
                'department'
            ]);
        });
    }
};
