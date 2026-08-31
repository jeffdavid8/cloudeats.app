<?php
// Test dynamic redirect URIs for different environments
require_once 'html/includes/OAuthHandler.php';

echo "Testing OAuth configuration with dynamic redirect URIs...\n\n";

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
    foreach ($env as $key => $value) {
        $_SERVER[$key] = $value;
    }
    
    if (!isset($env['HTTPS'])) {
        unset($_SERVER['HTTPS']);
    }
    
    try {
        $oauthHandler = new OAuthHandler();
        $config = $oauthHandler->getConfig();
        
        echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
        echo "HTTPS: " . (isset($_SERVER['HTTPS']) ? 'yes' : 'no') . "\n";
        echo "Google redirect: " . $config['google']['redirect_uri'] . "\n";
        echo "Apple redirect: " . $config['apple']['redirect_uri'] . "\n";
        echo "Facebook redirect: " . $config['facebook']['redirect_uri'] . "\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test saving configuration still works
echo "=== Testing Configuration Save ===\n";
try {
    $_SERVER['HTTP_HOST'] = 'mediabrain.app';
    $_SERVER['HTTPS'] = 'on';
    
    $oauthHandler = new OAuthHandler();
    
    // Test that we can still save Facebook config
    $facebookUpdate = [
        'facebook' => [
            'enabled' => true,
            'client_id' => '561081350692034',
            'client_secret' => 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    $oauthHandler->saveConfig($facebookUpdate);
    echo "✅ Configuration save successful\n";
    
    // Verify the saved config preserves all fields
    $savedConfig = $oauthHandler->getConfig('facebook');
    if (isset($savedConfig['redirect_uri']) && isset($savedConfig['scopes'])) {
        echo "✅ All Facebook fields preserved after save\n";
        echo "Redirect URI: " . $savedConfig['redirect_uri'] . "\n";
    } else {
        echo "❌ Some fields missing after save\n";
    }
    
} catch (Exception $e) {
    echo "❌ Save test failed: " . $e->getMessage() . "\n";
}
?>