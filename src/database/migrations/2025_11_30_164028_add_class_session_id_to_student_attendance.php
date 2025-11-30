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
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->foreignId('class_session_id')
                ->constrained('class_sessions')
                ->cascadeOnDelete()
                ->after('user_id');

            $table->unique(
                ['user_id', 'class_session_id'],
                'student_attendance_user_session_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_attendance', function (Blueprint $table) {
            $table->dropUnique('student_attendance_user_session_unique');
            $table->dropConstrainedForeignId('class_session_id');
        });
    }
};
