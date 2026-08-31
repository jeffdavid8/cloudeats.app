<?php
/**
 * Cloud Data Uploader
 * Upload your backed up JSON data to Google Cloud Storage
 */

require_once 'includes/storage/FileStorageManager.php';

// Only run if admin is logged in
session_start();
if (!isset($_SESSION['admin_user'])) {
    die('Access denied. Admin login required.');
}

echo "<h1>Data Migration to Cloud Storage</h1>";

// Load backed up data files
$jsonData = [];

// Load users data
if (file_exists('users_backup.json')) {
    $usersJson = file_get_contents('users_backup.json');
    $usersData = json_decode($usersJson, true);
    if ($usersData) {
        $jsonData['users.json'] = $usersData;
        echo "<p>✓ Users backup data loaded: " . count($usersData) . " users found</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ No users backup file found</p>";
}

// Load recipes data
if (file_exists('recipes_backup.json')) {
    $recipesJson = file_get_contents('recipes_backup.json');
    $recipesData = json_decode($recipesJson, true);
    if ($recipesData) {
        $jsonData['recipes.json'] = $recipesData;
        echo "<p>✓ Recipes backup data loaded: " . count($recipesData) . " recipes found</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ No recipes backup file found</p>";
}

if (empty($jsonData)) {
    echo "<p style='color: red;'>No backup data found to migrate. Please copy your backup files to the html directory.</p>";
    exit;
}

try {
    $storage = FileStorageManager::getInstance();
    echo "<p>✓ FileStorageManager initialized</p>";
    
    $migrated = 0;
    
    foreach ($jsonData as $filename => $data) {
        echo "<h3>Migrating: $filename</h3>";
        
        $result = $storage->storeJsonData(
            FileStorageManager::CATEGORY_SYSTEM_DATA,
            $filename,
            $data
        );
        
        if ($result['success']) {
            echo "<p style='color: green;'>✓ Successfully migrated $filename to cloud storage</p>";
            $migrated++;
        } else {
            echo "<p style='color: red;'>✗ Failed to migrate $filename: " . ($result['error'] ?? 'Unknown error') . "</p>";
        }
    }
    
    echo "<h2>Migration Summary</h2>";
    echo "<p><strong>Migrated:</strong> $migrated files</p>";
    
    if ($migrated > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Migration completed successfully!</p>";
        echo "<p>Your user data has been migrated to Google Cloud Storage.</p>";
        echo "<p><a href='?app=admin&p=users'>View Users</a> | <a href='?app=admin&p=settings'>Storage Settings</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>