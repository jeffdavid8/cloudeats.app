<?php
// Test the logs AJAX endpoint directly
echo "Testing Admin Logs AJAX Endpoint\n";
echo "=================================\n\n";

// Simulate the admin auth (since we're logged in)
session_start();

// Set up POST data to simulate AJAX call
$_POST['action'] = 'get_event_logs';
$_POST['lines'] = '10';

echo "Simulating AJAX call: get_event_logs (10 lines)\n\n";

// Load the admin app first
require_once 'includes/app.php';
$app = App::getInstance();
echo "✓ App loaded\n";

// Reset the EventLogger to ensure we have the correct path
require_once 'includes/EventLogger.php';
$eventLogger = EventLogger::resetInstance();
echo "✓ EventLogger reset\n";

echo "EventLogger enabled: " . ($eventLogger->isEnabled() ? 'YES' : 'NO') . "\n";

// Test getRecentEntries directly
$entries = $eventLogger->getRecentEntries(10);
echo "Direct getRecentEntries(10): " . count($entries) . " entries\n";

if (count($entries) > 0) {
    echo "\nSample entry:\n";
    print_r($entries[0]);
}

echo "\nNow testing the actual AJAX response:\n";
ob_start();

// Include the logs page which handles the AJAX
require 'apps/admin/views/logs.php';

$output = ob_get_clean();
echo "AJAX response:\n";
echo $output;

echo "\nTest complete.\n";
?>