<?php
// Simulate admin API call for saving Facebook OAuth config
session_start();
$_SESSION['admin_user'] = 'test_admin'; // Simulate logged in admin

require_once 'html/apps/admin/includes/AdminAuth.php';
require_once 'html/apps/admin/includes/UserManager.php';

// Simulate POST data
$_POST = [
    'action' => 'save_oauth_config',
    'facebook_oauth_enabled' => true,
    'facebook_client_id' => 'test_app_id_123',
    'facebook_client_secret' => 'test_app_secret_456'
];

// Include the admin API
$input = $_POST;
$action = $input['action'];

echo "Testing admin API save_oauth_config with Facebook data...\n";
echo "Input data: " . json_encode($input) . "\n\n";

try {
    require_once 'html/includes/OAuthHandler.php';
    $oauthHandler = new OAuthHandler();
    
    $config = [];
    
    // Process Facebook configuration (copied from admin API)
    if (isset($input['facebook_oauth_enabled'])) {
        echo "Processing Facebook config...\n";
        $config['facebook'] = [
            'enabled' => $input['facebook_oauth_enabled']
        ];
        
        if (!empty($input['facebook_client_id'])) {
            $config['facebook']['client_id'] = $input['facebook_client_id'];
            echo "Facebook client ID: " . $input['facebook_client_id'] . "\n";
        }
        
        if (!empty($input['facebook_client_secret'])) {
            $config['facebook']['client_secret'] = $input['facebook_client_secret'];
            echo "Facebook client secret: " . $input['facebook_client_secret'] . "\n";
        }
        
        echo "Facebook config to save: " . json_encode($config['facebook']) . "\n";
    }
    
    $oauthHandler->saveConfig($config);
    echo "SUCCESS: Facebook OAuth configuration saved successfully!\n";
    
    // Verify the save
    $newHandler = new OAuthHandler();
    $savedConfig = $newHandler->getConfig('facebook');
    echo "Saved Facebook config: " . json_encode($savedConfig) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: Failed to save OAuth configuration: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>