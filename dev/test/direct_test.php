<?php
echo "<h1>Direct Cloud Storage Test</h1>";

echo "<h2>Step 1: Load required files</h2>";
try {
    require_once 'vendor/autoload.php';
    echo "<p>✓ Autoloader loaded</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Autoloader failed: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Step 2: Test Google Cloud Storage directly</h2>";
try {
    $storage = new Google\Cloud\Storage\StorageClient([
        'projectId' => 'mediabrain'
    ]);
    echo "<p>✓ Storage client created</p>";
    
    $bucket = $storage->bucket('mediabrain-system-data');
    echo "<p>✓ Bucket reference created</p>";
    
    if ($bucket->exists()) {
        echo "<p>✓ Bucket exists</p>";
        
        $object = $bucket->object('recipes.json');
        if ($object->exists()) {
            echo "<p>✓ recipes.json exists in bucket</p>";
            
            $content = $object->downloadAsString();
            echo "<p>✓ Content downloaded (" . strlen($content) . " bytes)</p>";
            
            $data = json_decode($content, true);
            if ($data) {
                echo "<p>✓ JSON decoded successfully</p>";
                echo "<p><strong>Recipe count:</strong> " . count($data) . "</p>";
                
                if (!empty($data)) {
                    $first = $data[0];
                    echo "<p><strong>First recipe title:</strong> " . ($first['title'] ?? 'No title') . "</p>";
                }
            } else {
                echo "<p style='color: red;'>✗ JSON decode failed</p>";
                echo "<pre>" . htmlspecialchars($content) . "</pre>";
            }
        } else {
            echo "<p style='color: red;'>✗ recipes.json does not exist in bucket</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Bucket does not exist</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Step 3: Test FileStorageManager</h2>";
try {
    require_once 'includes/storage/FileStorageManager.php';
    $manager = FileStorageManager::getInstance();
    echo "<p>✓ FileStorageManager created</p>";
    
    $result = $manager->getJsonData('', 'recipes.json');
    echo "<p><strong>Result success:</strong> " . ($result['success'] ? 'YES' : 'NO') . "</p>";
    
    if ($result['success']) {
        echo "<p><strong>Data type:</strong> " . gettype($result['data']) . "</p>";
        echo "<p><strong>Data count:</strong> " . (is_array($result['data']) ? count($result['data']) : 'N/A') . "</p>";
    } else {
        echo "<p><strong>Error:</strong> " . ($result['error'] ?? 'Unknown') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ FileStorageManager Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>