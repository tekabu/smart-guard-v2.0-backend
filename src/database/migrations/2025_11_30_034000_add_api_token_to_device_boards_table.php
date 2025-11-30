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
        Schema::table('device_boards', function (Blueprint $table) {
            $table->string('api_token', 80)->unique()->nullable()->after('board_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_boards', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
