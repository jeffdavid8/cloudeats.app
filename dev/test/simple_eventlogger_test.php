<?php
echo "EventLogger Test\n";
echo "================\n\n";

try {
    require_once 'includes/EventLogger.php';
    $eventLogger = EventLogger::getInstance();
    
    echo "EventLogger created successfully\n";
    echo "Enabled: " . ($eventLogger->isEnabled() ? 'YES' : 'NO') . "\n";
    
    // Get log file path using reflection
    $reflection = new ReflectionClass($eventLogger);
    $property = $reflection->getProperty('logFile');
    $property->setAccessible(true);
    $logFile = $property->getValue($eventLogger);
    
    echo "Log file path: $logFile\n";
    echo "Log file exists: " . (file_exists($logFile) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($logFile)) {
        echo "File size: " . filesize($logFile) . " bytes\n";
        
        // Try to read some entries
        echo "\nTesting getRecentEntries(5):\n";
        $entries = $eventLogger->getRecentEntries(5);
        echo "Entries count: " . count($entries) . "\n";
        
        if (count($entries) > 0) {
            echo "Sample entry:\n";
            echo json_encode($entries[0], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>