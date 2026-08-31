<?php
require_once 'html/includes/OAuthHandler.php';

try {
    echo "Testing OAuth Handler...\n";
    $oauthHandler = new OAuthHandler();
    echo "OAuth Handler loaded successfully\n";
    
    $config = $oauthHandler->getConfig();
    echo "Current config loaded\n";
    
    // Test if Facebook is properly configured
    echo "Facebook config: " . json_encode($config['facebook'] ?? 'NOT FOUND') . "\n";
    
    // Test saving Facebook config
    echo "\nTesting Facebook config save...\n";
    $testConfig = [
        'facebook' => [
            'enabled' => true,
            'client_id' => 'test123',
            'client_secret' => 'secret123'
        ]
    ];
    
    $oauthHandler->saveConfig($testConfig);
    echo "Facebook config saved successfully\n";
    
    // Reload and verify
    $newHandler = new OAuthHandler();
    $newConfig = $newHandler->getConfig();
    echo "Facebook config after save: " . json_encode($newConfig['facebook']) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>