<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['schedule_period_id']);
            // Drop unique constraint
            $table->dropUnique(['schedule_period_id']);
            // Recreate foreign key without unique constraint
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['schedule_period_id']);
            // Add unique constraint
            $table->unique('schedule_period_id');
            // Recreate foreign key
            $table->foreign('schedule_period_id')->references('id')->on('schedule_periods')->onDelete('cascade');
        });
    }
};
