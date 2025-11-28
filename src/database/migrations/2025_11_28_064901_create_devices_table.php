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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->unsignedInteger('door_open_duration_seconds')->default(5);
            $table->boolean('active')->default(true);
            $table->foreignId('last_accessed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_accessed_at')->nullable();
            $table->enum('last_accessed_used', ['FINGERPRINT', 'RFID', 'ADMIN', 'MANUAL'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
