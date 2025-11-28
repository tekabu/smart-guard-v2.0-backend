<?php

$factories = [
    'Device' => [
        'use' => '',
        'definition' => "        return [
            'device_id' => fake()->unique()->bothify('DEV-####'),
            'door_open_duration_seconds' => fake()->numberBetween(3, 10),
            'active' => true,
        ];"
    ],
    'Room' => [
        'use' => "use App\Models\Device;",
        'definition' => "        return [
            'room_number' => fake()->unique()->numerify('###'),
            'device_id' => null,
            'active' => true,
        ];"
    ],
    'Subject' => [
        'use' => '',
        'definition' => "        return [
            'subject' => fake()->unique()->words(3, true),
            'active' => true,
        ];"
    ],
    'UserFingerprint' => [
        'use' => "use App\Models\User;",
        'definition' => "        return [
            'user_id' => User::factory(),
            'fingerprint_id' => fake()->unique()->numberBetween(10000, 99999),
            'active' => true,
        ];"
    ],
    'UserRfid' => [
        'use' => "use App\Models\User;",
        'definition' => "        return [
            'user_id' => User::factory(),
            'card_id' => fake()->unique()->bothify('??###??###'),
            'active' => true,
        ];"
    ],
    'Schedule' => [
        'use' => "use App\Models\User;\nuse App\Models\Room;\nuse App\Models\Subject;",
        'definition' => "        \$days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

        return [
            'user_id' => User::factory(),
            'day_of_week' => fake()->randomElement(\$days),
            'room_id' => Room::factory(),
            'subject_id' => Subject::factory(),
            'active' => true,
        ];"
    ],
    'SchedulePeriod' => [
        'use' => "use App\Models\Schedule;",
        'definition' => "        return [
            'schedule_id' => Schedule::factory(),
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'active' => true,
        ];"
    ],
    'UserAccessLog' => [
        'use' => "use App\Models\User;\nuse App\Models\Room;\nuse App\Models\Device;",
        'definition' => "        \$methods = ['FINGERPRINT', 'RFID', 'ADMIN', 'MANUAL'];

        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'device_id' => Device::factory(),
            'access_used' => fake()->randomElement(\$methods),
        ];"
    ],
    'UserAuditLog' => [
        'use' => "use App\Models\User;",
        'definition' => "        return [
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
        ];"
    ],
];

foreach ($factories as $name => $config) {
    $uses = $config['use'] ? "\n{$config['use']}" : '';
    $definition = $config['definition'];

    $content = "<?php

namespace Database\Factories;

use App\Models\\{$name};
use Illuminate\Database\Eloquent\Factories\Factory;{$uses}

class {$name}Factory extends Factory
{
    protected \$model = {$name}::class;

    public function definition(): array
    {
{$definition}
    }
}
";

    file_put_contents("/var/www/html/database/factories/{$name}Factory.php", $content);
    echo "{$name}Factory.php created\n";
}

echo "All factories fixed!\n";
