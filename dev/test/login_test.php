<?php
echo "Login Test\n";
echo "==========\n\n";

try {
    // Try to load the app
    require_once 'includes/app.php';
    $app = App::getInstance();
    echo "✓ App loaded successfully\n";

    // Get auth manager
    $authManager = $app->getAuthManager();
    if ($authManager) {
        echo "✓ Auth manager available\n";
        
        // Test admin login with various possible passwords
        $testPasswords = ['admin', 'password123', 'admin123', 'mediabrain'];
        
        foreach ($testPasswords as $password) {
            echo "Testing admin / $password: ";
            if ($authManager->authenticateUser('admin', $password)) {
                echo "✓ SUCCESS!\n";
                break;
            } else {
                echo "✗ Failed\n";
            }
        }
        
    } else {
        echo "✗ Auth manager not available\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>