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
        Schema::create('schedule_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_session_id')
                ->constrained('schedule_sessions')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->date('date_in')->nullable();
            $table->time('time_in')->nullable();
            $table->date('date_out')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('attendance_status', ['PRESENT', 'LATE', 'ABSENT']);
            $table->timestamps();

            $table->unique(
                ['schedule_session_id', 'student_id', 'date_in'],
                'schedule_attendance_unique_session_student_date'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_attendance');
    }
};
