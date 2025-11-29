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
        Schema::create('device_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->onDelete('cascade');
            $table->enum('board_type', ['FINGERPRINT', 'RFID', 'LOCK', 'CAMERA', 'DISPLAY'])->comment('Type of ESP32 board function');
            $table->string('mac_address')->unique()->comment('MAC address of ESP32 board');
            $table->string('firmware_version')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'board_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_boards');
    }
};
