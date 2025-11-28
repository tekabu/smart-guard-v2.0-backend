<?php

$basePath = '/var/www/html';

// All model definitions
$models = [
    'Device' => [
        'fillable' => ['device_id', 'door_open_duration_seconds', 'active', 'last_accessed_by_user_id', 'last_accessed_at', 'last_accessed_used'],
        'casts' => ['active' => 'boolean', 'last_accessed_at' => 'datetime'],
        'relations' => [
            'lastAccessedByUser' => 'belongsTo|User|last_accessed_by_user_id',
            'rooms' => 'hasMany|Room',
            'accessLogs' => 'hasMany|UserAccessLog',
        ]
    ],
    'Room' => [
        'fillable' => ['room_number', 'device_id', 'active', 'last_opened_by_user_id', 'last_opened_at', 'last_closed_by_user_id', 'last_closed_at'],
        'casts' => ['active' => 'boolean', 'last_opened_at' => 'datetime', 'last_closed_at' => 'datetime'],
        'relations' => [
            'device' => 'belongsTo|Device',
            'lastOpenedByUser' => 'belongsTo|User|last_opened_by_user_id',
            'lastClosedByUser' => 'belongsTo|User|last_closed_by_user_id',
            'schedules' => 'hasMany|Schedule',
            'accessLogs' => 'hasMany|UserAccessLog',
        ]
    ],
    'Subject' => [
        'fillable' => ['subject', 'active'],
        'casts' => ['active' => 'boolean'],
        'relations' => [
            'schedules' => 'hasMany|Schedule',
        ]
    ],
    'UserFingerprint' => [
        'fillable' => ['user_id', 'fingerprint_id', 'active'],
        'casts' => ['active' => 'boolean'],
        'relations' => [
            'user' => 'belongsTo|User',
        ]
    ],
    'UserRfid' => [
        'fillable' => ['user_id', 'card_id', 'active'],
        'casts' => ['active' => 'boolean'],
        'relations' => [
            'user' => 'belongsTo|User',
        ]
    ],
    'Schedule' => [
        'fillable' => ['user_id', 'day_of_week', 'room_id', 'subject_id', 'active'],
        'casts' => ['active' => 'boolean'],
        'relations' => [
            'user' => 'belongsTo|User',
            'room' => 'belongsTo|Room',
            'subject' => 'belongsTo|Subject',
            'periods' => 'hasMany|SchedulePeriod',
        ]
    ],
    'SchedulePeriod' => [
        'fillable' => ['schedule_id', 'start_time', 'end_time', 'active'],
        'casts' => ['active' => 'boolean'],
        'relations' => [
            'schedule' => 'belongsTo|Schedule',
        ]
    ],
    'UserAccessLog' => [
        'fillable' => ['user_id', 'room_id', 'device_id', 'access_used'],
        'casts' => [],
        'relations' => [
            'user' => 'belongsTo|User',
            'room' => 'belongsTo|Room',
            'device' => 'belongsTo|Device',
        ]
    ],
    'UserAuditLog' => [
        'fillable' => ['user_id', 'description'],
        'casts' => [],
        'relations' => [
            'user' => 'belongsTo|User',
        ]
    ],
];

// Generate each model
foreach ($models as $modelName => $config) {
    $fillableStr = "'" . implode("', '", $config['fillable']) . "'";

    $castsLines = [];
    foreach ($config['casts'] as $field => $type) {
        $castsLines[] = "            '$field' => '$type',";
    }
    $castsStr = implode("\n", $castsLines);

    $relationsStr = '';
    foreach ($config['relations'] as $method => $relDef) {
        $parts = explode('|', $relDef);
        $relType = $parts[0];
        $relModel = $parts[1];
        $foreignKey = isset($parts[2]) ? ", '$parts[2]'" : '';

        $relationsStr .= "\n    public function $method()\n";
        $relationsStr .= "    {\n";
        $relationsStr .= "        return \$this->{$relType}($relModel::class$foreignKey);\n";
        $relationsStr .= "    }\n";
    }

    $content = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class $modelName extends Model
{
    use HasFactory;

    protected \$fillable = [$fillableStr];

    protected function casts(): array
    {
        return [
$castsStr
        ];
    }
$relationsStr}
";

    file_put_contents("$basePath/app/Models/$modelName.php", $content);
    echo "$modelName.php created\n";
}

echo "All models fixed!\n";
