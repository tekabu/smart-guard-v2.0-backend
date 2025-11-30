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
        Schema::table('device_boards', function (Blueprint $table) {
            // Only drop the unique index if it exists
            if (Schema::hasIndex('device_boards', 'device_boards_mac_address_unique')) {
                $table->dropUnique('device_boards_mac_address_unique');
            }
            
            // Make the column nullable
            $table->string('mac_address')->nullable()->change();
        });
        
        // Don't create the index - we'll rely on validation at the application level
        // to ensure uniqueness when the value is not null
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make the column not nullable
        Schema::table('device_boards', function (Blueprint $table) {
            $table->string('mac_address')->nullable(false)->change();
        });
        
        // Add the standard unique index back
        Schema::table('device_boards', function (Blueprint $table) {
            $table->unique('mac_address');
        });
    }
};
