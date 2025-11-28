<?php

$basePath = '/var/www/html';

$controllers = [
    'UserController' => [
        'model' => 'User',
        'with' => '',
        'rules' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'boolean',
            'student_id' => 'nullable|string',
            'faculty_id' => 'nullable|string',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
        ],
        'updateRules' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,{id}',
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:ADMIN,STAFF,STUDENT,FACULTY',
            'active' => 'sometimes|boolean',
            'student_id' => 'nullable|string',
            'faculty_id' => 'nullable|string',
            'course' => 'nullable|string',
            'year_level' => 'nullable|integer',
            'attendance_rate' => 'nullable|numeric',
            'department' => 'nullable|string',
        ],
        'hashPassword' => true,
    ],
    'DeviceController' => [
        'model' => 'Device',
        'with' => "'lastAccessedByUser', 'rooms'",
        'rules' => [
            'device_id' => 'required|string|unique:devices,device_id',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'device_id' => 'sometimes|string|unique:devices,device_id,{id}',
            'door_open_duration_seconds' => 'nullable|integer|min:1',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'RoomController' => [
        'model' => 'Room',
        'with' => "'device', 'lastOpenedByUser', 'lastClosedByUser'",
        'rules' => [
            'room_number' => 'required|string',
            'device_id' => 'nullable|exists:devices,id',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'room_number' => 'sometimes|string',
            'device_id' => 'nullable|exists:devices,id',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'SubjectController' => [
        'model' => 'Subject',
        'with' => '',
        'rules' => [
            'subject' => 'required|string|unique:subjects,subject',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'subject' => 'sometimes|string|unique:subjects,subject,{id}',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'UserFingerprintController' => [
        'model' => 'UserFingerprint',
        'with' => "'user'",
        'rules' => [
            'user_id' => 'required|exists:users,id',
            'fingerprint_id' => 'required|integer|unique:user_fingerprints,fingerprint_id',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'user_id' => 'sometimes|exists:users,id',
            'fingerprint_id' => 'sometimes|integer|unique:user_fingerprints,fingerprint_id,{id}',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'UserRfidController' => [
        'model' => 'UserRfid',
        'with' => "'user'",
        'rules' => [
            'user_id' => 'required|exists:users,id',
            'card_id' => 'required|string|unique:user_rfids,card_id',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'user_id' => 'sometimes|exists:users,id',
            'card_id' => 'sometimes|string|unique:user_rfids,card_id,{id}',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'ScheduleController' => [
        'model' => 'Schedule',
        'with' => "'user', 'room', 'subject', 'periods'",
        'rules' => [
            'user_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:SUNDAY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY',
            'room_id' => 'required|exists:rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'user_id' => 'sometimes|exists:users,id',
            'day_of_week' => 'sometimes|in:SUNDAY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY',
            'room_id' => 'sometimes|exists:rooms,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'SchedulePeriodController' => [
        'model' => 'SchedulePeriod',
        'with' => "'schedule'",
        'rules' => [
            'schedule_id' => 'required|exists:schedules,id',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'active' => 'boolean',
        ],
        'updateRules' => [
            'schedule_id' => 'sometimes|exists:schedules,id',
            'start_time' => 'sometimes|date_format:H:i:s',
            'end_time' => 'sometimes|date_format:H:i:s|after:start_time',
            'active' => 'sometimes|boolean',
        ],
        'hashPassword' => false,
    ],
    'UserAccessLogController' => [
        'model' => 'UserAccessLog',
        'with' => "'user', 'room', 'device'",
        'rules' => [
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'device_id' => 'required|exists:devices,id',
            'access_used' => 'required|in:FINGERPRINT,RFID,ADMIN,MANUAL',
        ],
        'updateRules' => [
            'user_id' => 'sometimes|exists:users,id',
            'room_id' => 'sometimes|exists:rooms,id',
            'device_id' => 'sometimes|exists:devices,id',
            'access_used' => 'sometimes|in:FINGERPRINT,RFID,ADMIN,MANUAL',
        ],
        'hashPassword' => false,
    ],
    'UserAuditLogController' => [
        'model' => 'UserAuditLog',
        'with' => "'user'",
        'rules' => [
            'user_id' => 'required|exists:users,id',
            'description' => 'required|string',
        ],
        'updateRules' => [
            'user_id' => 'sometimes|exists:users,id',
            'description' => 'sometimes|string',
        ],
        'hashPassword' => false,
    ],
];

function generateRulesArray($rules, $indent = '            ') {
    $lines = [];
    foreach ($rules as $field => $rule) {
        $lines[] = "'$field' => '$rule',";
    }
    return $indent . implode("\n$indent", $lines);
}

foreach ($controllers as $controllerName => $config) {
    $modelName = $config['model'];
    $withClause = $config['with'] ? "->with([{$config['with']}])" : '';
    $rules = generateRulesArray($config['rules']);
    $updateRules = generateRulesArray($config['updateRules']);

    $hashPasswordCode = '';
    if ($config['hashPassword']) {
        $hashPasswordCode = "\n        if (isset(\$validated['password'])) {\n            \$validated['password'] = Hash::make(\$validated['password']);\n        }\n";
    }

    $hashPasswordImport = $config['hashPassword'] ? "\nuse Illuminate\\Support\\Facades\\Hash;" : '';

    $content = "<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{$modelName};
use Illuminate\\Http\\Request;{$hashPasswordImport}

class {$controllerName} extends Controller
{
    public function index()
    {
        \$records = {$modelName}::query(){$withClause}->get();
        return response()->json(\$records);
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate([
{$rules}
        ]);
{$hashPasswordCode}
        \$record = {$modelName}::create(\$validated);
        return response()->json(\$record, 201);
    }

    public function show(string \$id)
    {
        \$record = {$modelName}::query(){$withClause}->findOrFail(\$id);
        return response()->json(\$record);
    }

    public function update(Request \$request, string \$id)
    {
        \$record = {$modelName}::findOrFail(\$id);

        \$updateRules = [
{$updateRules}
        ];

        // Replace {id} placeholder in unique rules
        foreach (\$updateRules as \$field => \$rule) {
            \$updateRules[\$field] = str_replace('{id}', \$id, \$rule);
        }

        \$validated = \$request->validate(\$updateRules);
{$hashPasswordCode}
        \$record->update(\$validated);
        return response()->json(\$record);
    }

    public function destroy(string \$id)
    {
        \$record = {$modelName}::findOrFail(\$id);
        \$record->delete();
        return response()->json(null, 204);
    }
}
";

    file_put_contents("$basePath/app/Http/Controllers/Api/{$controllerName}.php", $content);
    echo "{$controllerName}.php created\n";
}

echo "All controllers fixed!\n";
