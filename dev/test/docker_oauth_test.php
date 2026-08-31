<?php
// Test OAuth handler inside Docker container
require_once '/var/www/html/includes/OAuthHandler.php';

try {
    echo "Testing OAuth Handler in Docker container...\n";
    
    $oauthHandler = new OAuthHandler();
    echo "✅ OAuth Handler loaded successfully\n";
    
    // Test path detection
    $reflection = new ReflectionClass($oauthHandler);
    $configPathProperty = $reflection->getProperty('configPath');
    $configPathProperty->setAccessible(true);
    $actualPath = $configPathProperty->getValue($oauthHandler);
    echo "Using config path: $actualPath\n";
    echo "Path exists: " . (file_exists($actualPath) ? 'YES' : 'NO') . "\n";
    echo "Path writable: " . (is_writable($actualPath) ? 'YES' : 'NO') . "\n";
    
    // Test save
    $testConfig = [
        'facebook' => [
            'enabled' => true,
            'client_id' => '561081350692034',
            'client_secret' => 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    echo "Attempting to save Facebook config...\n";
    $oauthHandler->saveConfig($testConfig);
    echo "✅ Save successful\n";
    
    // Verify
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig('facebook');
    echo "Facebook enabled: " . ($savedConfig['enabled'] ? 'true' : 'false') . "\n";
    echo "Facebook client_id: " . $savedConfig['client_id'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>