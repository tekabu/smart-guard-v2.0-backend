<?php

namespace Database\Factories;

use App\Models\UserFingerprint;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserFingerprintFactory extends Factory
{
    protected $model = UserFingerprint::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fingerprint_id' => strtoupper(fake()->unique()->bothify('FP-########')),
            'active' => true,
        ];
    }
}
