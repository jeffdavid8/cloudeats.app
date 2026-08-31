<?php
// Test the fixed URL detection for mediabrain.app.local
require_once 'html/includes/OAuthHandler.php';

echo "Testing OAuth configuration with mediabrain.app.local...\n\n";

// Simulate your local environment
$_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
unset($_SERVER['HTTPS']); // Assuming HTTP for local

try {
    $oauthHandler = new OAuthHandler();
    $config = $oauthHandler->getConfig();
    
    echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
    echo "HTTPS: no\n";
    echo "Google redirect: " . $config['google']['redirect_uri'] . "\n";
    echo "Apple redirect: " . $config['apple']['redirect_uri'] . "\n";
    echo "Facebook redirect: " . $config['facebook']['redirect_uri'] . "\n";
    
    echo "\n=== Test Save Operation ===\n";
    
    // Test saving Facebook config with your actual data
    $facebookUpdate = [
        'facebook' => [
            'enabled' => true,
            'client_id' => '561081350692034',
            'client_secret' => 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    $oauthHandler->saveConfig($facebookUpdate);
    echo "✅ Facebook config save successful\n";
    
    // Verify the saved config has correct redirect URI
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig('facebook');
    echo "Saved Facebook redirect URI: " . $savedConfig['redirect_uri'] . "\n";
    echo "Facebook enabled: " . ($savedConfig['enabled'] ? 'true' : 'false') . "\n";
    echo "Facebook client_id: " . $savedConfig['client_id'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>