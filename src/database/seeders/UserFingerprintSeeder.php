<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserFingerprint;
use Illuminate\Database\Seeder;

class UserFingerprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users except admins (students, faculty, and staff need fingerprints)
        $users = User::whereIn('role', ['STUDENT', 'FACULTY', 'STAFF'])->get();

        foreach ($users as $user) {
            // Each user gets 1-2 fingerprints registered
            $fingerprintCount = rand(1, 2);

            for ($i = 0; $i < $fingerprintCount; $i++) {
                UserFingerprint::create([
                    'user_id' => $user->id,
                    'fingerprint_id' => fake()->unique()->numberBetween(10000, 99999),
                    'active' => true,
                ]);
            }
        }
    }
}
