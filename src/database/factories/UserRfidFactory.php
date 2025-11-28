<?php

namespace Database\Factories;

use App\Models\UserRfid;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class UserRfidFactory extends Factory
{
    protected $model = UserRfid::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'card_id' => fake()->unique()->bothify('??###??###'),
            'active' => true,
        ];
    }
}
