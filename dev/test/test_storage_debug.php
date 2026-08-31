<?php
/**
 * Test script to debug the storage API issue
 */

// Test the storage API endpoint directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing FileStorageManager...\n";

// Test if we can load the FileStorageManager
try {
    require_once '/var/www/html/html/includes/storage/FileStorageManager.php';
    echo "FileStorageManager loaded successfully!\n";
    
    $storage = FileStorageManager::getInstance();
    echo "FileStorageManager instance created!\n";
    
    $info = $storage->getProviderInfo();
    echo "Provider info retrieved: " . json_encode($info) . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nTesting admin API...\n";

// Simulate the admin API call
$_GET['action'] = 'storage_status';

// Start session
session_start();
$_SESSION['admin_user'] = 'test'; // Fake admin session

ob_start();
try {
    require '/var/www/html/html/apps/admin/api.php';
} catch (Exception $e) {
    echo "API ERROR: " . $e->getMessage() . "\n";
}
$output = ob_get_clean();

echo "API Output: " . $output . "\n";
?>