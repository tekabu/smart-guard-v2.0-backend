<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DeviceBoard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceBoard>
 */
class DeviceBoardFactory extends Factory
{
    protected $model = DeviceBoard::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'board_type' => fake()->randomElement(['FINGERPRINT', 'RFID', 'LOCK', 'CAMERA', 'DISPLAY']),
            'api_token' => Str::random(64),
            'mac_address' => strtoupper(fake()->unique()->macAddress()),
            'firmware_version' => 'v' . fake()->numberBetween(1, 3) . '.' . fake()->numberBetween(0, 9) . '.' . fake()->numberBetween(0, 9),
            'active' => true,
            'last_seen_at' => fake()->optional(0.8)->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function fingerprint(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_type' => 'FINGERPRINT',
        ]);
    }

    public function rfid(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_type' => 'RFID',
        ]);
    }

    public function lock(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_type' => 'LOCK',
        ]);
    }

    public function camera(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_type' => 'CAMERA',
        ]);
    }

    public function display(): static
    {
        return $this->state(fn (array $attributes) => [
            'board_type' => 'DISPLAY',
        ]);
    }

    public function withoutMacAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'mac_address' => null,
        ]);
    }
}
