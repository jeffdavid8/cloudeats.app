<?php
echo "EventLogger Reset Test\n";
echo "======================\n\n";

require_once 'includes/EventLogger.php';

echo "Resetting EventLogger singleton...\n";
$eventLogger = EventLogger::resetInstance();

echo "Testing new instance...\n";

// Use reflection to get the actual logFile value
$reflection = new ReflectionClass($eventLogger);
$property = $reflection->getProperty('logFile');
$property->setAccessible(true);
$actualLogFile = $property->getValue($eventLogger);

echo "Log file path: $actualLogFile\n";
echo "File exists: " . (file_exists($actualLogFile) ? 'YES' : 'NO') . "\n";
echo "EventLogger enabled: " . ($eventLogger->isEnabled() ? 'YES' : 'NO') . "\n";

if (file_exists($actualLogFile)) {
    echo "File size: " . filesize($actualLogFile) . " bytes\n";
    
    echo "\nTesting getRecentEntries(3):\n";
    $entries = $eventLogger->getRecentEntries(3);
    echo "Entries returned: " . count($entries) . "\n";
    
    if (count($entries) > 0) {
        echo "Sample entry timestamp: " . $entries[0]['timestamp'] . "\n";
        echo "Sample entry event: " . $entries[0]['level'] . " - " . $entries[0]['event'] . "\n";
    }
    
    // Test logging a new event
    echo "\nTesting logging new event...\n";
    $eventLogger->log('DEBUG', 'Path fix test', [], ['test' => true]);
    echo "New event logged successfully\n";
}

echo "\nTest complete.\n";
?>