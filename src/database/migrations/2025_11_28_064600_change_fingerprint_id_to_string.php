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
        Schema::table('user_fingerprints', function (Blueprint $table) {
            $table->string('fingerprint_id', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_fingerprints', function (Blueprint $table) {
            $table->integer('fingerprint_id')->change();
        });
    }
};
