<?php
echo "Fresh EventLogger Test\n";
echo "======================\n\n";

// Clear any existing instances by unsetting static variables if possible
if (class_exists('EventLogger')) {
    echo "EventLogger class already loaded, testing current instance...\n";
}

// Force reload the class file
require_once 'includes/EventLogger.php';

echo "Creating new EventLogger instance...\n";
$eventLogger = EventLogger::getInstance();

// Use reflection to get the actual logFile value
$reflection = new ReflectionClass($eventLogger);
$property = $reflection->getProperty('logFile');
$property->setAccessible(true);
$actualLogFile = $property->getValue($eventLogger);

echo "Actual log file path: $actualLogFile\n";
echo "File exists: " . (file_exists($actualLogFile) ? 'YES' : 'NO') . "\n";

if (file_exists($actualLogFile)) {
    echo "File size: " . filesize($actualLogFile) . " bytes\n";
    echo "File is readable: " . (is_readable($actualLogFile) ? 'YES' : 'NO') . "\n";
    
    // Try to read directly
    echo "Reading last 2 lines directly:\n";
    $lines = file($actualLogFile);
    if ($lines) {
        $lastLines = array_slice($lines, -2);
        foreach ($lastLines as $line) {
            echo "  " . trim($line) . "\n";
        }
    }
    
    // Now try EventLogger method
    echo "\nTesting getRecentEntries(2):\n";
    $entries = $eventLogger->getRecentEntries(2);
    echo "Entries returned: " . count($entries) . "\n";
    
    if (count($entries) > 0) {
        echo "First entry keys: " . implode(', ', array_keys($entries[0])) . "\n";
    }
}

echo "\nDone.\n";
?>