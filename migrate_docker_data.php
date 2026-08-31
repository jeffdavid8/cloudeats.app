<?php
/**
 * Migrate Docker Container JSON Data to Cloud Storage
 * Run this in the Docker container to migrate existing JSON files
 */

require_once '/var/www/html/includes/storage/FileStorageManager.php';

echo "=== Docker to Cloud Migration ===\n";

try {
    $storage = FileStorageManager::getInstance();
    echo "✓ FileStorageManager initialized\n";
    
    $dataDir = '/var/data/mediabrain';
    $files = [
        'oauth_config.json',
        'permissions.json', 
        'user_permissions.json',
        'users.json'
    ];
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($files as $filename) {
        $filepath = $dataDir . '/' . $filename;
        
        if (file_exists($filepath)) {
            echo "Processing: $filename\n";
            
            try {
                $content = file_get_contents($filepath);
                $data = json_decode($content, true);
                
                if ($data !== null) {
                    $result = $storage->storeJsonData(
                        FileStorageManager::CATEGORY_SYSTEM_DATA,
                        $filename,
                        $data
                    );
                    
                    if ($result['success']) {
                        echo "  ✓ Migrated to cloud storage\n";
                        $migrated++;
                        
                        // Create backup
                        $backupPath = $filepath . '.migrated.' . date('Y-m-d-H-i-s');
                        copy($filepath, $backupPath);
                        echo "  ✓ Backed up as: $backupPath\n";
                    } else {
                        echo "  ✗ Migration failed: " . ($result['error'] ?? 'Unknown error') . "\n";
                        $errors++;
                    }
                } else {
                    echo "  ✗ Invalid JSON content in $filename\n";
                    $errors++;
                }
            } catch (Exception $e) {
                echo "  ✗ Error processing $filename: " . $e->getMessage() . "\n";
                $errors++;
            }
        } else {
            echo "  - File not found: $filename\n";
        }
        
        echo "\n";
    }
    
    echo "Migration Summary:\n";
    echo "  Migrated: $migrated files\n";
    echo "  Errors: $errors files\n";
    
    if ($migrated > 0) {
        echo "\n✓ Migration completed successfully!\n";
        echo "Your data has been migrated to Google Cloud Storage.\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}

echo "\n=== End Migration ===\n";
?>