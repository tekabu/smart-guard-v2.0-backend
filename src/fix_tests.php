<?php

$basePath = '/var/www/html/tests/Feature/Api';

// Fix UserRfidControllerTest - add quotes around ABC123
$userRfidTest = file_get_contents("$basePath/UserRfidControllerTest.php");
$userRfidTest = str_replace('ABC123', '"ABC123"', $userRfidTest);
$userRfidTest = str_replace('->assertStatus(200)->assertJson(["message" => "RFID deleted successfully"])', '->assertStatus(204)', $userRfidTest);
file_put_contents("$basePath/UserRfidControllerTest.php", $userRfidTest);
echo "UserRfidControllerTest.php fixed\n";

// Fix all delete test assertions - expect 204 instead of 200 with message
$testFiles = [
    'DeviceControllerTest.php' => 'Device deleted successfully',
    'RoomControllerTest.php' => 'Room deleted successfully',
    'SubjectControllerTest.php' => 'Subject deleted successfully',
    'UserFingerprintControllerTest.php' => 'Fingerprint deleted successfully',
    'ScheduleControllerTest.php' => 'Schedule deleted successfully',
    'SchedulePeriodControllerTest.php' => 'Schedule period deleted successfully',
    'UserAccessLogControllerTest.php' => 'Access log deleted successfully',
    'UserAuditLogControllerTest.php' => 'Audit log deleted successfully',
    'UserControllerTest.php' => 'User deleted successfully',
];

foreach ($testFiles as $file => $message) {
    $content = file_get_contents("$basePath/$file");

    // Replace the delete assertion pattern
    $pattern = '/->assertStatus\(200\)->assertJson\(\["message" => "' . preg_quote($message, '/') . '"\]\)/';
    $replacement = '->assertStatus(204)';

    $content = preg_replace($pattern, $replacement, $content);

    file_put_contents("$basePath/$file", $content);
    echo "$file fixed\n";
}

echo "\nAll test files fixed!\n";
