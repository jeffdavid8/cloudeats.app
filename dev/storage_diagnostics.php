<?php
/**
 * Cloud Run Storage Diagnostic Script
 * Diagnoses Google Cloud Storage issues in production
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Cloud Run Storage Diagnostics ===\n\n";

// Check environment
echo "Environment Variables:\n";
echo "  K_SERVICE: " . (getenv('K_SERVICE') ?: 'Not set') . "\n";
echo "  GOOGLE_CLOUD_PROJECT: " . (getenv('GOOGLE_CLOUD_PROJECT') ?: 'Not set') . "\n";
echo "  STORAGE_PROVIDER: " . (getenv('STORAGE_PROVIDER') ?: 'Not set') . "\n";
echo "  STORAGE_BUCKET_PREFIX: " . (getenv('STORAGE_BUCKET_PREFIX') ?: 'Not set') . "\n";
echo "  STORAGE_LOCATION: " . (getenv('STORAGE_LOCATION') ?: 'Not set') . "\n\n";

// Check if we're in Cloud Run
$isCloudRun = (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false);
echo "Detected Cloud Run Environment: " . ($isCloudRun ? 'YES' : 'NO') . "\n\n";

// Check Google Cloud dependencies
echo "Google Cloud Dependencies:\n";
try {
    require_once '../vendor/autoload.php';
    echo "  ✓ Composer autoload loaded\n";
} catch (Exception $e) {
    echo "  ✗ Composer autoload error: " . $e->getMessage() . "\n";
}

try {
    if (class_exists('Google\Cloud\Storage\StorageClient')) {
        echo "  ✓ Google Cloud Storage client available\n";
    } else {
        echo "  ✗ Google Cloud Storage client not found\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error checking Google Cloud Storage: " . $e->getMessage() . "\n";
}

// Check file storage manager
echo "\nFile Storage Manager:\n";
try {
    require_once 'includes/storage/FileStorageManager.php';
    echo "  ✓ FileStorageManager loaded\n";
    
    $storage = FileStorageManager::getInstance();
    echo "  ✓ FileStorageManager instance created\n";
    
    $config = $storage->getProviderInfo();
    echo "  Provider Type: " . ($config['type'] ?? 'Unknown') . "\n";
    echo "  Provider Config: " . json_encode($config['config'] ?? [], JSON_PRETTY_PRINT) . "\n";
    
    $status = $config['status'] ?? [];
    echo "  Status Details:\n";
    echo "    Available: " . ($status['available'] ? 'Yes' : 'No') . "\n";
    echo "    Healthy: " . ($status['healthy'] ? 'Yes' : 'No') . "\n";
    echo "    Authenticated: " . ($status['authenticated'] ?? 'Unknown') . "\n";
    if (isset($status['error'])) {
        echo "    Error: " . $status['error'] . "\n";
    }
    if (isset($status['project_id'])) {
        echo "    Project ID: " . $status['project_id'] . "\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ FileStorageManager error: " . $e->getMessage() . "\n";
    echo "  Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test Google Cloud Storage directly
echo "\nDirect Google Cloud Storage Test:\n";
try {
    require_once '../vendor/autoload.php';
    
    $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: 'mediabrain-app';
    echo "  Using Project ID: $projectId\n";
    
    // Test with ADC
    $storage = new Google\Cloud\Storage\StorageClient([
        'projectId' => $projectId
    ]);
    echo "  ✓ StorageClient initialized with ADC\n";
    
    // Test listing buckets
    $buckets = $storage->buckets(['maxResults' => 1]);
    $bucketCount = 0;
    foreach ($buckets as $bucket) {
        $bucketCount++;
        echo "  ✓ Found bucket: " . $bucket->name() . "\n";
        break; // Just check the first one
    }
    
    if ($bucketCount === 0) {
        echo "  ⚠ No buckets found (this might be normal)\n";
    }
    
    echo "  ✓ Google Cloud Storage API test successful\n";
    
} catch (Exception $e) {
    echo "  ✗ Google Cloud Storage direct test failed: " . $e->getMessage() . "\n";
    echo "  Error Code: " . $e->getCode() . "\n";
    
    // Check if it's an authentication error
    if (strpos($e->getMessage(), 'authentication') !== false || 
        strpos($e->getMessage(), 'credentials') !== false ||
        strpos($e->getMessage(), 'unauthorized') !== false) {
        echo "  → This appears to be an authentication/credentials issue\n";
    }
    
    if (strpos($e->getMessage(), 'permission') !== false) {
        echo "  → This appears to be a permissions issue\n";
    }
}

// Check service account
echo "\nService Account Information:\n";
try {
    $metadata = [];
    
    // Try to get service account email from metadata server
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/email');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Metadata-Flavor: Google']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $serviceAccount = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $serviceAccount) {
        echo "  Service Account: $serviceAccount\n";
    } else {
        echo "  ⚠ Could not retrieve service account from metadata server\n";
    }
    
} catch (Exception $e) {
    echo "  ✗ Service account check error: " . $e->getMessage() . "\n";
}

echo "\n=== End Diagnostics ===\n";

// Add manual migration option if we're in diagnostic mode
if (isset($_GET['migrate']) && $_GET['migrate'] === 'json') {
    echo "\n=== Starting JSON Data Migration ===\n";
    
    try {
        // Use the FileStorageManager to migrate data
        $storage = FileStorageManager::getInstance();
        
        // Check if we can find any local JSON files to migrate
        $localPaths = [
            '/var/data/mediabrain' => [
                'oauth_config.json',
                'users.json', 
                'permissions.json',
                'user_permissions.json',
                'storage_config.json'
            ],
            '/tmp' => [
                'oauth_config.json',
                'users.json'
            ]
        ];
        
        $migrated = 0;
        $errors = 0;
        
        foreach ($localPaths as $basePath => $files) {
            foreach ($files as $filename) {
                $fullPath = $basePath . '/' . $filename;
                
                if (file_exists($fullPath)) {
                    echo "Found: $fullPath\n";
                    
                    try {
                        $content = file_get_contents($fullPath);
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
                }
            }
        }
        
        echo "\nMigration Summary:\n";
        echo "  Migrated: $migrated files\n";
        echo "  Errors: $errors files\n";
        
        if ($migrated > 0) {
            echo "\n✓ JSON data migration completed!\n";
        } else {
            echo "\n⚠ No JSON files found to migrate\n";
        }
        
    } catch (Exception $e) {
        echo "Migration error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== End Migration ===\n";
}
?>