<?php
// Test Facebook provider configuration status
require_once 'includes/OAuthHandler.php';

try {
    echo "=== Facebook OAuth Configuration Test ===\n\n";
    
    $oauthHandler = new OAuthHandler();
    
    // Test configuration loading
    $config = $oauthHandler->getConfig();
    echo "Facebook config loaded: " . (isset($config['facebook']) ? 'YES' : 'NO') . "\n";
    
    if (isset($config['facebook'])) {
        echo "Facebook enabled: " . ($config['facebook']['enabled'] ? 'true' : 'false') . "\n";
        echo "Facebook client_id: " . ($config['facebook']['client_id'] ?? 'NOT SET') . "\n";
        echo "Facebook client_secret: " . (isset($config['facebook']['client_secret']) && !empty($config['facebook']['client_secret']) ? 'SET' : 'NOT SET') . "\n";
        echo "Facebook redirect_uri: " . ($config['facebook']['redirect_uri'] ?? 'NOT SET') . "\n";
        echo "Facebook scopes: " . (isset($config['facebook']['scopes']) ? implode(', ', $config['facebook']['scopes']) : 'NOT SET') . "\n";
    }
    
    echo "\n";
    
    // Test isProviderConfigured
    $isConfigured = $oauthHandler->isProviderConfigured('facebook');
    echo "isProviderConfigured('facebook'): " . ($isConfigured ? 'true' : 'false') . "\n";
    
    // Test the combined check (what the API uses)
    $isEnabled = $config['facebook']['enabled'] && $oauthHandler->isProviderConfigured('facebook');
    echo "Combined check (enabled && configured): " . ($isEnabled ? 'true' : 'false') . "\n";
    
    echo "\n=== API Response Simulation ===\n";
    $providers = [
        'google' => [
            'enabled' => $config['google']['enabled'] && $oauthHandler->isProviderConfigured('google')
        ],
        'apple' => [
            'enabled' => $config['apple']['enabled'] && $oauthHandler->isProviderConfigured('apple')
        ],
        'facebook' => [
            'enabled' => $config['facebook']['enabled'] && $oauthHandler->isProviderConfigured('facebook')
        ]
    ];
    
    echo json_encode(['success' => true, 'providers' => $providers], JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>