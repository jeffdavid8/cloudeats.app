<?php
// Test the logs AJAX endpoint directly
echo "Testing Logs AJAX Endpoint\n";
echo "==========================\n\n";

// Set up POST data to simulate AJAX call
$_POST['action'] = 'get_event_logs';
$_POST['lines'] = '50';

echo "Simulating AJAX call with:\n";
echo "action: get_event_logs\n";
echo "lines: 50\n\n";

// Load the admin app
try {
    require_once 'includes/app.php';
    $app = App::getInstance();
    echo "✓ App loaded\n";
    
    $eventLogger = $app->getEventLogger();
    if (!$eventLogger) {
        echo "✗ EventLogger not available\n";
        exit;
    }
    echo "✓ EventLogger available\n";
    
    echo "EventLogger enabled: " . ($eventLogger->isEnabled() ? 'YES' : 'NO') . "\n";
    echo "Log file exists: " . (file_exists('/var/www/mediabrain.app.local/logs/event.log') ? 'YES' : 'NO') . "\n";
    
    // Test getRecentEntries directly
    echo "\nTesting getRecentEntries():\n";
    $entries = $eventLogger->getRecentEntries(10);
    echo "Entries returned: " . count($entries) . "\n";
    
    if (count($entries) > 0) {
        echo "\nFirst entry:\n";
        print_r($entries[0]);
    }
    
    echo "\nRaw AJAX response:\n";
    header('Content-Type: application/json');
    echo json_encode(['entries' => $entries]);
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>