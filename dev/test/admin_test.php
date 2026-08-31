<?php

// Simple admin login test
header('Content-Type: text/plain');

echo "Admin Login Test\n";
echo "================\n\n";

try {
    // Initialize the app 
    require_once('includes/app.php');
    echo "✓ App initialized successfully\n";
    
    $app = App::getInstance();
    echo "✓ App instance created\n";
    
    // Check if EventLogger is working
    $eventLogger = $app->getEventLogger();
    if ($eventLogger) {
        echo "✓ EventLogger available\n";
    } else {
        echo "! EventLogger not available (may be disabled due to errors)\n";
    }
    
    // Test AuthManager
    $authManager = $app->getAuthManager();
    if ($authManager) {
        echo "✓ AuthManager available\n";
        
        // Try admin authentication
        echo "\nTesting admin login...\n";
        $result = $authManager->authenticateUser('admin', 'password123');
        
        if ($result) {
            echo "✓ Admin authentication successful!\n";
            echo "User data: " . print_r($result, true) . "\n";
        } else {
            echo "✗ Admin authentication failed\n";
        }
    } else {
        echo "✗ AuthManager not available\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>