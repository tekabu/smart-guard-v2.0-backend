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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->foreignId('device_id')->nullable()->constrained('devices')->onDelete('set null');
            $table->boolean('active')->default(true);
            $table->foreignId('last_opened_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_opened_at')->nullable();
            $table->foreignId('last_closed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
