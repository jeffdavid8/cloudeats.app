<?php
// Mirror the exact admin API OAuth save process for debugging
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Admin API OAuth Save Debug ===\n\n";

// Simulate the exact POST data from the failed request
$_POST = [
    'apple_client_id' => '',
    'apple_key_id' => '',
    'apple_team_id' => '',
    'facebook_client_id' => '561081350692034',
    'facebook_client_secret' => 'ece546e2797032b5f8c07c69fb697b5c',
    'facebook_oauth_enabled' => true,
    'google_client_id' => '',
    'google_client_secret' => 'admin'
];

echo "POST data: " . json_encode($_POST, JSON_PRETTY_PRINT) . "\n\n";

try {
    // Step 1: Load required files (exactly like admin API)
    echo "Step 1: Loading required files...\n";
    require_once __DIR__ . '/apps/admin/includes/AdminAuth.php';
    require_once __DIR__ . '/apps/admin/includes/UserManager.php';
    require_once __DIR__ . '/includes/OAuthHandler.php';
    echo "✅ Files loaded successfully\n\n";
    
    // Step 2: Process input (exactly like admin API)
    echo "Step 2: Processing input...\n";
    $input = $_POST;
    $config = [];
    
    // Process Google configuration
    if (isset($input['google_oauth_enabled'])) {
        echo "Processing Google config...\n";
        $config['google'] = [
            'enabled' => $input['google_oauth_enabled']
        ];
        
        if (!empty($input['google_client_id'])) {
            $config['google']['client_id'] = $input['google_client_id'];
        }
        
        if (!empty($input['google_client_secret'])) {
            $config['google']['client_secret'] = $input['google_client_secret'];
        }
    }
    
    // Process Apple configuration
    if (isset($input['apple_oauth_enabled'])) {
        echo "Processing Apple config...\n";
        $config['apple'] = [
            'enabled' => $input['apple_oauth_enabled']
        ];
        
        if (!empty($input['apple_client_id'])) {
            $config['apple']['client_id'] = $input['apple_client_id'];
        }
        
        if (!empty($input['apple_team_id'])) {
            $config['apple']['team_id'] = $input['apple_team_id'];
        }
        
        if (!empty($input['apple_key_id'])) {
            $config['apple']['key_id'] = $input['apple_key_id'];
        }
    }
    
    // Process Facebook configuration
    if (isset($input['facebook_oauth_enabled'])) {
        echo "Processing Facebook config...\n";
        $config['facebook'] = [
            'enabled' => $input['facebook_oauth_enabled']
        ];
        
        if (!empty($input['facebook_client_id'])) {
            $config['facebook']['client_id'] = $input['facebook_client_id'];
            echo "  Facebook client_id: " . $input['facebook_client_id'] . "\n";
        }
        
        if (!empty($input['facebook_client_secret'])) {
            $config['facebook']['client_secret'] = $input['facebook_client_secret'];
            echo "  Facebook client_secret: " . substr($input['facebook_client_secret'], 0, 8) . "...\n";
        }
    }
    
    echo "Config to save: " . json_encode($config, JSON_PRETTY_PRINT) . "\n\n";
    
    // Step 3: Create OAuthHandler and save
    echo "Step 3: Creating OAuthHandler...\n";
    $oauthHandler = new OAuthHandler();
    echo "✅ OAuthHandler created successfully\n";
    
    echo "Step 4: Saving configuration...\n";
    $oauthHandler->saveConfig($config);
    echo "✅ Configuration saved successfully\n\n";
    
    // Step 5: Verify save
    echo "Step 5: Verifying save...\n";
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig();
    echo "Facebook in saved config: " . (isset($savedConfig['facebook']) ? 'YES' : 'NO') . "\n";
    if (isset($savedConfig['facebook'])) {
        echo "Facebook enabled: " . ($savedConfig['facebook']['enabled'] ? 'true' : 'false') . "\n";
        echo "Facebook client_id: " . $savedConfig['facebook']['client_id'] . "\n";
        echo "Facebook redirect_uri: " . $savedConfig['facebook']['redirect_uri'] . "\n";
    }
    
    echo "\n=== SUCCESS: No errors found in debug mode ===\n";
    
} catch (Throwable $e) {
    echo "\n❌ ERROR CAUGHT:\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>