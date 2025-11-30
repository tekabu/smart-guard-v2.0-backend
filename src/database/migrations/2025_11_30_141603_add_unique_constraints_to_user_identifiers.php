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
            $table->unique(['student_id', 'role'], 'users_student_id_role_unique');
            $table->unique(['faculty_id', 'role'], 'users_faculty_id_role_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_student_id_role_unique');
            $table->dropUnique('users_faculty_id_role_unique');
        });
    }
};
