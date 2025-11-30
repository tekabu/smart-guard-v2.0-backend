<?php

use App\Models\DeviceBoard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ensure all existing device boards have Sanctum tokens.
     */
    public function up(): void
    {
        DeviceBoard::query()->each(function (DeviceBoard $board) {
            if (empty($board->api_token)) {
                $board->api_token = Str::random(64);
                $board->save();
                return;
            }

            $board->syncSanctumToken();
        });
    }

    public function down(): void
    {
        DB::table('personal_access_tokens')
            ->where('name', 'device-board-api-token')
            ->delete();
    }
};
