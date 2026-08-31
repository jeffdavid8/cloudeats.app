<?php
// Detailed diagnostic for OAuth configuration saving issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== OAuth Configuration Save Diagnostic ===\n\n";

// Check file permissions and writability
$configPath = 'C:\\var\\data\\mediabrain\\oauth_config.json';
echo "Config path: $configPath\n";
echo "File exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n";
echo "File readable: " . (is_readable($configPath) ? 'YES' : 'NO') . "\n";
echo "File writable: " . (is_writable($configPath) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable(dirname($configPath)) ? 'YES' : 'NO') . "\n";

// Check current permissions
if (file_exists($configPath)) {
    $perms = fileperms($configPath);
    echo "File permissions: " . substr(sprintf('%o', $perms), -4) . "\n";
}

echo "\n";

// Test basic file operations
echo "=== File Operation Test ===\n";
try {
    $content = file_get_contents($configPath);
    echo "✅ Can read file (" . strlen($content) . " bytes)\n";
    
    $testContent = json_encode(['test' => true, 'timestamp' => time()], JSON_PRETTY_PRINT);
    $tempFile = $configPath . '.test';
    
    $result = file_put_contents($tempFile, $testContent);
    if ($result) {
        echo "✅ Can write to directory\n";
        unlink($tempFile);
    } else {
        echo "❌ Cannot write to directory\n";
    }
    
} catch (Exception $e) {
    echo "❌ File operation error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test OAuthHandler in isolated context
echo "=== OAuthHandler Test ===\n";
try {
    require_once 'html/includes/OAuthHandler.php';
    
    $oauthHandler = new OAuthHandler();
    echo "✅ OAuthHandler loaded\n";
    
    $config = $oauthHandler->getConfig();
    echo "✅ Config loaded\n";
    
    // Test the OAuth configuration from environment
    $updateData = [
        'facebook' => [
            'enabled' => true,
            'client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '561081350692034',
            'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    echo "Attempting to save Facebook config...\n";
    $oauthHandler->saveConfig($updateData);
    echo "✅ Config save successful\n";
    
    // Verify
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig('facebook');
    echo "✅ Config reload successful\n";
    echo "Facebook enabled: " . ($savedConfig['enabled'] ? 'true' : 'false') . "\n";
    echo "Facebook client_id: " . $savedConfig['client_id'] . "\n";
    
} catch (Exception $e) {
    echo "❌ OAuthHandler error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test admin API component isolation
echo "=== Admin API Test ===\n";
try {
    // Simulate the exact admin API call using environment variables
    $_POST = [
        'apple_client_id' => $_ENV['APPLE_CLIENT_ID'] ?? '',
        'apple_key_id' => $_ENV['APPLE_KEY_ID'] ?? '',
        'apple_team_id' => $_ENV['APPLE_TEAM_ID'] ?? '',
        'facebook_client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '561081350692034',
        'facebook_client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? 'ece546e2797032b5f8c07c69fb697b5c',
        'facebook_oauth_enabled' => true,
        'google_client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'google_client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? ''
    ];
    
    $input = $_POST;
    $config = [];
    
    // Process Facebook configuration (copy from admin API)
    if (isset($input['facebook_oauth_enabled'])) {
        echo "Processing Facebook config from admin API data...\n";
        $config['facebook'] = [
            'enabled' => $input['facebook_oauth_enabled']
        ];
        
        if (!empty($input['facebook_client_id'])) {
            $config['facebook']['client_id'] = $input['facebook_client_id'];
        }
        
        if (!empty($input['facebook_client_secret'])) {
            $config['facebook']['client_secret'] = $input['facebook_client_secret'];
        }
        
        echo "Config to save: " . json_encode($config, JSON_PRETTY_PRINT) . "\n";
    }
    
    $oauthHandler = new OAuthHandler();
    $oauthHandler->saveConfig($config);
    echo "✅ Admin API simulation successful\n";
    
} catch (Exception $e) {
    echo "❌ Admin API simulation error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
?>