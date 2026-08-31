<?php
/**
 * JSON Data Migration Script
 * Migrates existing JSON data files from local filesystem to cloud storage
 */

require_once 'html/includes/storage/FileStorageManager.php';

echo "Starting JSON data migration...\n\n";

// Files that might need migration
$dataFiles = [
    // OAuth configuration
    'C:/var/data/mediabrain/oauth_config.json' => 'oauth_config.json',
    '/var/data/mediabrain/oauth_config.json' => 'oauth_config.json',
    
    // User data
    'C:/var/data/mediabrain/users.json' => 'users.json',
    '/var/data/mediabrain/users.json' => 'users.json',
    
    // Permissions
    'C:/var/data/mediabrain/permissions.json' => 'permissions.json',
    '/var/data/mediabrain/permissions.json' => 'permissions.json',
    'C:/var/data/mediabrain/user_permissions.json' => 'user_permissions.json',
    '/var/data/mediabrain/user_permissions.json' => 'user_permissions.json',
    
    // Storage configuration
    'C:/var/data/mediabrain/storage_config.json' => 'storage_config.json',
    '/var/data/mediabrain/storage_config.json' => 'storage_config.json',
    
    // Apps data
    'html/json/structure.json' => 'app_structure.json',
    
    // Bible data
    'html/apps/bibleBot/json/share_images.json' => 'biblebot_share_images.json',
];

try {
    // Initialize storage manager with cloud storage
    $storage = FileStorageManager::getInstance(FileStorageManager::STORAGE_GOOGLE_CLOUD);
    
    $migrated = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($dataFiles as $localPath => $cloudFilename) {
        echo "Checking: $localPath\n";
        
        if (file_exists($localPath)) {
            echo "  → Found file, migrating to cloud as: $cloudFilename\n";
            
            try {
                $content = file_get_contents($localPath);
                $data = json_decode($content, true);
                
                if ($data !== null) {
                    // Valid JSON - store using storeJsonData
                    $result = $storage->storeJsonData(
                        FileStorageManager::CATEGORY_SYSTEM_DATA,
                        $cloudFilename,
                        $data
                    );
                    
                    if ($result['success']) {
                        echo "  ✓ Migrated successfully\n";
                        $migrated++;
                        
                        // Optionally backup the original file
                        $backupPath = $localPath . '.migrated.' . date('Y-m-d-H-i-s');
                        copy($localPath, $backupPath);
                        echo "  ✓ Backed up original to: $backupPath\n";
                    } else {
                        echo "  ✗ Migration failed: " . ($result['error'] ?? 'Unknown error') . "\n";
                        $errors++;
                    }
                } else {
                    echo "  ✗ Invalid JSON content\n";
                    $errors++;
                }
            } catch (Exception $e) {
                echo "  ✗ Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "  - File not found, skipping\n";
            $skipped++;
        }
        
        echo "\n";
    }
    
    echo "Migration Summary:\n";
    echo "  Migrated: $migrated files\n";
    echo "  Skipped: $skipped files\n";
    echo "  Errors: $errors files\n\n";
    
    if ($migrated > 0) {
        echo "✓ Migration completed successfully!\n";
        echo "Your JSON data has been migrated to cloud storage.\n";
        echo "Original files have been backed up with .migrated.timestamp extensions.\n\n";
        
        echo "Testing cloud storage access...\n";
        
        // Test reading back one of the migrated files
        try {
            $testResult = $storage->getJsonData(
                FileStorageManager::CATEGORY_SYSTEM_DATA,
                'oauth_config.json'
            );
            
            if ($testResult['success']) {
                echo "✓ Cloud storage read test successful\n";
            } else {
                echo "⚠ Cloud storage read test failed: " . ($testResult['error'] ?? 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "⚠ Cloud storage read test error: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nMigration script completed.\n";
?>