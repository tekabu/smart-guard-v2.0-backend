<?php

namespace Database\Factories;

use App\Models\UserAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserAuditLogFactory extends Factory
{
    protected $model = UserAuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
        ];
    }
}
