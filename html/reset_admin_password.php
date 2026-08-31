<?php
/**
 * Reset Admin Password Script
 * This script resets the admin password to use the ADMIN_PASSWORD environment variable
 */

echo "Reset Admin Password Script\n";
echo "===========================\n\n";

try {
    // Load environment
    require_once 'includes/app.php';
    $app = App::getInstance();
    
    // Get admin password from environment
    $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: 'admin';
    echo "Using admin password from environment: '$adminPassword'\n\n";
    
    // Load admin auth
    require_once 'apps/admin/includes/AdminAuth.php';
    $adminAuth = new AdminAuth();
    
    echo "Resetting admin password...\n";
    $result = $adminAuth->changePassword('admin', $adminPassword);
    
    if ($result['success']) {
        echo "✅ Admin password successfully reset!\n";
        echo "You can now login with: admin / $adminPassword\n";
    } else {
        echo "❌ Failed to reset password: " . $result['error'] . "\n";
        
        // If it failed, try to update directly in the storage file
        echo "\nTrying direct storage update...\n";
        
        require_once 'includes/storage/FileStorageManager.php';
        $storageManager = FileStorageManager::getInstance();
        
        $users = $storageManager->getJsonData('', 'users.json');
        if ($users && isset($users['admin'])) {
            $users['admin']['password'] = password_hash($adminPassword, PASSWORD_DEFAULT);
            $users['admin']['modified'] = date('c');
            
            if ($storageManager->storeJsonData('', 'users.json', $users)) {
                echo "✅ Admin password updated directly in storage!\n";
                echo "You can now login with: admin / $adminPassword\n";
            } else {
                echo "❌ Failed to update storage directly\n";
            }
        } else {
            echo "❌ Could not load users data\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nScript complete.\n";
?>