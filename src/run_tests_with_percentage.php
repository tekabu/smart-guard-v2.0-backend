#!/usr/bin/env php
<?php

// Run the Laravel tests and capture the output
$result = [];
$returnCode = 0;
exec('php artisan test 2>&1', $result, $returnCode);

// Print the original test results
foreach ($result as $line) {
    echo $line . "\n";
}

// Find the line with the test results to calculate percentage
$testsLine = null;
$assertions = 0;
$passedTests = 0;
$totalTests = 0;

foreach ($result as $line) {
    if (preg_match('/Tests:\s*([0-9]+)\s+passed/', $line, $matches)) {
        $passedTests = (int)$matches[1];
        $totalTests = $passedTests; // In PHPUnit, when only showing passed, total = passed
        break;
    }
    // Check if we have both passed and total
    if (preg_match('/Tests:\s*([0-9]+)\s+failed,\s*([0-9]+)\s+passed/', $line, $matches)) {
        $passedTests = (int)$matches[2];
        $failedTests = (int)$matches[1];
        $totalTests = $passedTests + $failedTests;
        break;
    }
    if (preg_match('/Tests:\s*([0-9]+)\s+passed,\s*([0-9]+)\s+failed/', $line, $matches)) {
        $passedTests = (int)$matches[1];
        $failedTests = (int)$matches[2];
        $totalTests = $passedTests + $failedTests;
        break;
    }
}

// Extract assertion count
foreach ($result as $line) {
    if (preg_match('/\(([0-9]+)\s+assertions?\)/', $line, $matches)) {
        $assertions = (int)$matches[1];
        break;
    }
}

// If we couldn't parse from the standard format, try to find "passed" and count manually
if ($totalTests == 0) {
    $passedCount = 0;
    foreach ($result as $line) {
        if (preg_match('/✓/', $line)) {
            $passedCount++;
        }
    }
    $totalTests = $passedCount; // Approximation
    $passedTests = $passedCount;
}

// Calculate percentage
$percentage = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;

// Print the custom summary with percentage
echo "\n" . str_repeat("-", 60) . "\n";
echo "Summary: $totalTests tests, $passedTests passed, " . ($totalTests - $passedTests) . " failed, " . number_format($percentage, 2) . "% success rate ($assertions assertions across all tests)\n";
echo str_repeat("-", 60) . "\n";

exit($returnCode);