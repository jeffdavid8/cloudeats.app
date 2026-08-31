<?php
// Test environment detection and OAuth configuration
require_once 'html/includes/util.php';
require_once 'html/includes/OAuthHandler.php';

echo "=== Environment Detection Test ===\n\n";

// Test different server environments
$testEnvironments = [
    ['HTTP_HOST' => 'mediabrain.app', 'HTTPS' => 'on'],
    ['HTTP_HOST' => 'mediabrain.app.local'],
    ['HTTP_HOST' => 'localhost:8080'],
    ['HTTP_HOST' => 'localhost']
];

foreach ($testEnvironments as $i => $env) {
    echo "=== Test Environment " . ($i + 1) . " ===\n";
    
    // Simulate server environment
    $originalServer = $_SERVER;
    foreach ($env as $key => $value) {
        $_SERVER[$key] = $value;
    }
    
    if (!isset($env['HTTPS'])) {
        unset($_SERVER['HTTPS']);
    }
    
    try {
        echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
        echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'yes' : 'no') . "\n";
        echo "is_development(): " . (is_development() ? 'true' : 'false') . "\n";
        echo "is_production(): " . (is_production() ? 'true' : 'false') . "\n";
        echo "get_base_url(): " . get_base_url() . "\n";
        
        // Test OAuth configuration
        $oauthHandler = new OAuthHandler();
        $config = $oauthHandler->getConfig();
        
        echo "Facebook redirect: " . $config['facebook']['redirect_uri'] . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    // Restore original server
    $_SERVER = $originalServer;
    echo "\n";
}

// Test saving configuration with your specific environment
echo "=== Testing Save with mediabrain.app.local ===\n";
$_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
unset($_SERVER['HTTPS']);

try {
    $oauthHandler = new OAuthHandler();
    
    // Test that we can still save Facebook config
    $facebookUpdate = [
        'facebook' => [
            'enabled' => true,
            'client_id' => '561081350692034',
            'client_secret' => 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    echo "Base URL: " . get_base_url() . "\n";
    echo "Is development: " . (is_development() ? 'true' : 'false') . "\n";
    
    $oauthHandler->saveConfig($facebookUpdate);
    echo "✅ Configuration save successful\n";
    
    // Verify the configuration has correct redirect URI
    $savedConfig = $oauthHandler->getConfig('facebook');
    echo "Facebook redirect URI: " . $savedConfig['redirect_uri'] . "\n";
    echo "Facebook enabled: " . ($savedConfig['enabled'] ? 'true' : 'false') . "\n";
    echo "Facebook client_id: " . $savedConfig['client_id'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Save test failed: " . $e->getMessage() . "\n";
}
?>