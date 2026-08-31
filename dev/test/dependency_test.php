<?php
echo "<h1>Dependency Check</h1>";

echo "<h2>Autoloader</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "<p>✓ vendor/autoload.php exists</p>";
    require_once 'vendor/autoload.php';
    echo "<p>✓ Autoloader loaded</p>";
} else {
    echo "<p style='color: red;'>✗ vendor/autoload.php not found</p>";
    echo "<p>Current directory: " . getcwd() . "</p>";
    echo "<p>Files in current directory:</p>";
    $files = scandir('.');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<p>- " . $file . "</p>";
        }
    }
    exit;
}

echo "<h2>Google Cloud Storage</h2>";
try {
    if (class_exists('Google\Cloud\Storage\StorageClient')) {
        echo "<p>✓ Google\\Cloud\\Storage\\StorageClient class available</p>";
        
        // Test creating storage client
        $storage = new Google\Cloud\Storage\StorageClient([
            'projectId' => 'mediabrain'
        ]);
        echo "<p>✓ StorageClient instantiated</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Google\\Cloud\\Storage\\StorageClient class not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Storage Client Error: " . $e->getMessage() . "</p>";
}

echo "<h2>FileStorageManager</h2>";
try {
    if (file_exists('includes/storage/FileStorageManager.php')) {
        echo "<p>✓ FileStorageManager.php exists</p>";
        require_once 'includes/storage/FileStorageManager.php';
        echo "<p>✓ FileStorageManager loaded</p>";
        
        $storage = FileStorageManager::getInstance();
        echo "<p>✓ FileStorageManager instantiated</p>";
        
        echo "<p><strong>Provider type:</strong> " . get_class($storage->getStorageProvider()) . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ FileStorageManager.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>FileStorageManager Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Environment</h2>";
echo "<p><strong>K_SERVICE:</strong> " . (getenv('K_SERVICE') ?: 'not set') . "</p>";
echo "<p><strong>GOOGLE_CLOUD_PROJECT:</strong> " . (getenv('GOOGLE_CLOUD_PROJECT') ?: 'not set') . "</p>";
?>