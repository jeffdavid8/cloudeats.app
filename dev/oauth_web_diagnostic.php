<?php
// Web diagnostic for OAuth configuration saving
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Web Context OAuth Diagnostic ===\n\n";

// Show environment info
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "\n";

// Test both potential config paths
$paths = [
    'C:\\var\\data\\mediabrain\\oauth_config.json',
    '/var/data/mediabrain/oauth_config.json'
];

foreach ($paths as $path) {
    echo "Testing path: $path\n";
    echo "  Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
    echo "  Readable: " . (is_readable($path) ? 'YES' : 'NO') . "\n";
    echo "  Writable: " . (is_writable($path) ? 'YES' : 'NO') . "\n";
    echo "\n";
}

// Test OAuthHandler in web context
echo "=== OAuthHandler Web Test ===\n";
try {
    require_once 'includes/OAuthHandler.php';
    
    $oauthHandler = new OAuthHandler();
    echo "✅ OAuthHandler loaded in web context\n";
    
    // Show which config path it's actually using
    $reflection = new ReflectionClass($oauthHandler);
    $configPathProperty = $reflection->getProperty('configPath');
    $configPathProperty->setAccessible(true);
    $actualPath = $configPathProperty->getValue($oauthHandler);
    echo "OAuthHandler using path: $actualPath\n";
    echo "Path exists: " . (file_exists($actualPath) ? 'YES' : 'NO') . "\n";
    echo "Path writable: " . (is_writable($actualPath) ? 'YES' : 'NO') . "\n";
    
    $config = $oauthHandler->getConfig();
    echo "✅ Config loaded successfully\n";
    
    // Test save operation in web context using environment variables
    $testData = [
        'facebook' => [
            'enabled' => true,
            'client_id' => $_ENV['FACEBOOK_CLIENT_ID'] ?? '561081350692034',
            'client_secret' => $_ENV['FACEBOOK_CLIENT_SECRET'] ?? 'ece546e2797032b5f8c07c69fb697b5c'
        ]
    ];
    
    echo "Attempting save in web context...\n";
    $oauthHandler->saveConfig($testData);
    echo "✅ Save successful in web context\n";
    
} catch (Exception $e) {
    echo "❌ Web context error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Web Diagnostic Complete ===\n";
?>