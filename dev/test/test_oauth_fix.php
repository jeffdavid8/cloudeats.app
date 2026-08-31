<?php
// Test the fixed OAuth configuration saving
require_once 'html/includes/OAuthHandler.php';

try {
    echo "Testing fixed OAuth configuration saving...\n";
    
    $oauthHandler = new OAuthHandler();
    echo "OAuth Handler loaded successfully\n";
    
    // Display current Facebook config
    $currentConfig = $oauthHandler->getConfig('facebook');
    echo "Current Facebook config: " . json_encode($currentConfig, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test saving Facebook config (similar to what admin API sends)
    $facebookUpdate = [
        'facebook' => [
            'enabled' => true,
            'client_id' => '561081350692034',
            'client_secret' => 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    echo "Saving Facebook configuration...\n";
    $oauthHandler->saveConfig($facebookUpdate);
    echo "Save successful!\n\n";
    
    // Verify the saved config preserves all fields
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig('facebook');
    echo "Saved Facebook config: " . json_encode($savedConfig, JSON_PRETTY_PRINT) . "\n";
    
    // Check that redirect_uri and scopes are preserved
    if (isset($savedConfig['redirect_uri']) && isset($savedConfig['scopes'])) {
        echo "✅ SUCCESS: redirect_uri and scopes preserved\n";
    } else {
        echo "❌ ERROR: redirect_uri or scopes missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>