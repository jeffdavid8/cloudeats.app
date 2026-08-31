<?php

echo "Testing EventLogger Integration...\n\n";

// Change to the html directory for proper includes
chdir('./html');

// Capture any errors
$errors = [];
set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
    $errors[] = "Error: $message in $file on line $line";
});

try {
    echo "1. Testing App initialization...\n";
    require_once('includes/app.php');
    
    $app = App::getInstance();
    echo "✓ App instance created successfully\n";
    
    echo "\n2. Testing EventLogger availability...\n";
    $eventLogger = $app->getEventLogger();
    
    if ($eventLogger) {
        echo "✓ EventLogger is available\n";
        
        echo "\n3. Testing EventLogger logging...\n";
        $eventLogger->log('test', 'Integration test', ['test_id' => 123]);
        echo "✓ EventLogger log call completed\n";
        
    } else {
        echo "! EventLogger is null (likely due to error handling)\n";
    }
    
    echo "\n4. Testing authentication system...\n";
    $authManager = $app->getAuthManager();
    if ($authManager) {
        echo "✓ AuthManager is available\n";
    } else {
        echo "✗ AuthManager is null\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

if (!empty($errors)) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "- $error\n";
    }
}

echo "\nTest completed.\n";

?>