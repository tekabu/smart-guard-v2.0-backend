<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRfid;
use Illuminate\Database\Seeder;

class UserRfidSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users except admins (students, faculty, and staff need RFID cards)
        $users = User::whereIn('role', ['STUDENT', 'FACULTY', 'STAFF'])->get();

        foreach ($users as $user) {
            // Generate realistic RFID card ID (10-digit hexadecimal, common for RFID systems)
            // Format: XXXXXXXXXX (e.g., 04A3B2C1D5)
            $cardId = strtoupper(fake()->unique()->regexify('[0-9A-F]{10}'));

            UserRfid::create([
                'user_id' => $user->id,
                'card_id' => $cardId,
                'active' => true,
            ]);
        }
    }
}
