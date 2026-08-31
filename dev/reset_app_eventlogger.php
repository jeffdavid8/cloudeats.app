<?php
echo "Resetting App EventLogger\n";
echo "=========================\n\n";

// Load the app
require_once 'includes/app.php';
$app = App::getInstance();

echo "Current app loaded\n";

// Reset the EventLogger
echo "Resetting EventLogger singleton...\n";
$newEventLogger = EventLogger::resetInstance();

echo "New EventLogger created with correct path\n";

// Test it
$entries = $newEventLogger->getRecentEntries(2);
echo "Recent entries: " . count($entries) . "\n";

if (count($entries) > 0) {
    echo "Latest event: " . $entries[count($entries)-1]['timestamp'] . " - " . $entries[count($entries)-1]['event'] . "\n";
}

// Log a test event
$newEventLogger->log('DEBUG', 'App EventLogger reset test', [], ['reset' => true]);
echo "Test event logged\n";

echo "\nEventLogger reset complete. Admin logs page should now work.\n";
?>