<?php
/**
 * File Storage System Usage Examples
 * Demonstrates how to use the scalable file storage system
 */

require_once __DIR__ . '/includes/storage/FileStorageManager.php';

// Example 1: Basic file upload using default storage provider
function uploadUserDocument($file, $userId) {
    $storage = FileStorageManager::getInstance();
    
    $result = $storage->uploadFile(
        $file,
        FileStorageManager::CATEGORY_DOCUMENTS,
        null, // Auto-generate filename
        [
            'prefix' => 'user_' . $userId,
            'suffix' => 'doc'
        ]
    );
    
    if ($result['success']) {
        echo "File uploaded successfully: " . $result['url'] . "\n";
        return $result;
    } else {
        echo "Upload failed: " . $result['error'] . "\n";
        return false;
    }
}

// Example 2: Using specific storage provider
function uploadToCloud($file) {
    $storage = new FileStorageManager(FileStorageManager::STORAGE_GOOGLE_CLOUD);
    
    return $storage->uploadFile(
        $file,
        FileStorageManager::CATEGORY_USER_UPLOADS
    );
}

// Example 3: Profile image management
function manageProfileImage($file, $username) {
    $profileManager = new ProfileImageManager();
    
    // Upload new profile image
    $result = $profileManager->uploadProfileImage($file, $username);
    
    if ($result['success']) {
        echo "Profile image uploaded: " . $result['url'] . "\n";
        
        // Get the URL for display
        $imageUrl = $profileManager->getProfileImageUrl($result['filename']);
        echo "Image URL: " . $imageUrl . "\n";
        
        return $result;
    } else {
        echo "Profile image upload failed: " . $result['error'] . "\n";
        return false;
    }
}

// Example 4: Migration between providers
function migrateFiles($fromProvider, $toProvider) {
    require_once __DIR__ . '/includes/storage/StorageMigrationManager.php';
    
    $migrationManager = new StorageMigrationManager($fromProvider, $toProvider);
    
    // Get estimate first
    $estimate = $migrationManager->estimateMigration();
    echo "Migration estimate: {$estimate['total_files']} files, " . 
         round($estimate['total_size'] / 1048576, 2) . " MB\n";
    
    // Start migration with progress callback
    $result = $migrationManager->migrateAllFiles([
        'progress_callback' => function($progress) {
            if (isset($progress['overall_progress'])) {
                $overall = $progress['overall_progress'];
                echo "Progress: {$overall['migrated']}/{$overall['total']} files\n";
            }
        }
    ]);
    
    if ($result['success']) {
        echo "Migration completed: {$result['migrated']} files migrated, {$result['failed']} failed\n";
    } else {
        echo "Migration failed: " . $result['error'] . "\n";
    }
    
    return $result;
}

// Example 5: App-specific file storage
function storeAppAsset($file, $appName) {
    $storage = FileStorageManager::getInstance();
    
    $result = $storage->uploadFile(
        $file,
        FileStorageManager::CATEGORY_APP_ASSETS,
        null,
        [
            'prefix' => $appName,
            'cache_control' => 'public, max-age=86400' // Cache for 24 hours
        ]
    );
    
    if ($result['success']) {
        echo "App asset stored: " . $result['url'] . "\n";
    }
    
    return $result;
}

// Example 6: Administrative functions
function switchStorageProvider($newProvider) {
    $storage = FileStorageManager::getInstance();
    
    // Get current provider info
    $currentInfo = $storage->getProviderInfo();
    echo "Current provider: " . $currentInfo['type'] . "\n";
    
    // Switch to new provider
    $result = $storage->switchProvider($newProvider);
    
    if ($result['success']) {
        echo "Switched to provider: " . $newProvider . "\n";
    } else {
        echo "Provider switch failed\n";
    }
    
    return $result;
}

// Example 7: File listing and management
function listUserFiles($category, $limit = 10) {
    $storage = FileStorageManager::getInstance();
    
    $result = $storage->listFiles($category, $limit);
    
    if ($result['success']) {
        echo "Found " . count($result['files']) . " files in category '{$category}':\n";
        
        foreach ($result['files'] as $file) {
            echo "- {$file['name']} ({$file['size']} bytes) - {$file['url']}\n";
        }
    } else {
        echo "Failed to list files: " . $result['error'] . "\n";
    }
    
    return $result;
}

// Example usage (commented out to prevent execution):
/*
// Upload a user document
if (isset($_FILES['document'])) {
    uploadUserDocument($_FILES['document'], 123);
}

// Manage profile image
if (isset($_FILES['profile_pic'])) {
    manageProfileImage($_FILES['profile_pic'], 'john_doe');
}

// Switch storage provider
switchStorageProvider(FileStorageManager::STORAGE_GOOGLE_CLOUD);

// Migrate from local to cloud
migrateFiles(FileStorageManager::STORAGE_LOCAL, FileStorageManager::STORAGE_GOOGLE_CLOUD);

// List profile images
listUserFiles(FileStorageManager::CATEGORY_PROFILE_IMAGES);
*/

echo "File Storage System loaded successfully!\n";
echo "Available categories:\n";
echo "- " . FileStorageManager::CATEGORY_PROFILE_IMAGES . "\n";
echo "- " . FileStorageManager::CATEGORY_APP_ASSETS . "\n";
echo "- " . FileStorageManager::CATEGORY_USER_UPLOADS . "\n";
echo "- " . FileStorageManager::CATEGORY_DOCUMENTS . "\n";
echo "- " . FileStorageManager::CATEGORY_BACKUPS . "\n";
?>