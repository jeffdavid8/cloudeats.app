<?php
/**
 * Storage System Debug
 */

echo "<h1>Storage System Debug</h1>";

// Test environment detection
echo "<h2>Environment Detection</h2>";
echo "<p><strong>Is Cloud Run:</strong> " . (getenv('K_SERVICE') ? 'YES' : 'NO') . "</p>";
echo "<p><strong>K_SERVICE:</strong> " . (getenv('K_SERVICE') ?: 'not set') . "</p>";
echo "<p><strong>GOOGLE_CLOUD_PROJECT:</strong> " . (getenv('GOOGLE_CLOUD_PROJECT') ?: 'not set') . "</p>";

// Test FileStorageManager
echo "<h2>FileStorageManager Test</h2>";
try {
    require_once 'includes/storage/FileStorageManager.php';
    $storage = FileStorageManager::getInstance();
    echo "<p>✓ FileStorageManager initialized</p>";
    
    // Test system data retrieval
    echo "<h3>System Data Test</h3>";
    $result = $storage->getJsonData('', 'recipes.json');
    
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Successfully retrieved recipes.json from storage</p>";
        echo "<p><strong>Data type:</strong> " . gettype($result['data']) . "</p>";
        if (is_array($result['data'])) {
            echo "<p><strong>Recipe count:</strong> " . count($result['data']) . "</p>";
            if (!empty($result['data'])) {
                echo "<h4>First Recipe:</h4>";
                $firstRecipe = $result['data'][0];
                echo "<pre>" . json_encode($firstRecipe, JSON_PRETTY_PRINT) . "</pre>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Data is not an array: " . var_export($result['data'], true) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Failed to retrieve recipes.json: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
    
    // Test storage info
    echo "<h3>Storage Provider Info</h3>";
    $provider = $storage->getStorageProvider();
    echo "<p><strong>Provider type:</strong> " . get_class($provider) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test direct RecipeApp
echo "<h2>RecipeApp Direct Test</h2>";
try {
    require_once 'apps/recipes/includes/RecipeApp.php';
    $recipeApp = new RecipeApp();
    echo "<p>✓ RecipeApp instantiated</p>";
    
    $recipes = $recipeApp->getRecipes();
    echo "<p><strong>Recipes returned:</strong> " . count($recipes) . "</p>";
    
    if (!empty($recipes)) {
        echo "<h4>Recipe Titles:</h4>";
        foreach ($recipes as $recipe) {
            echo "<p>- " . htmlspecialchars($recipe['title'] ?? 'No title') . " (ID: " . htmlspecialchars($recipe['id'] ?? 'No ID') . ")</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>RecipeApp Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Test Google Cloud Storage Provider directly
echo "<h2>Google Cloud Storage Direct Test</h2>";
try {
    require_once 'includes/storage/GoogleCloudStorageProvider.php';
    $gcsProvider = new GoogleCloudStorageProvider();
    echo "<p>✓ GoogleCloudStorageProvider instantiated</p>";
    
    $result = $gcsProvider->getJsonData('system_data/recipes.json');
    if ($result['success']) {
        echo "<p style='color: green;'>✓ Direct GCS retrieval successful</p>";
        echo "<p><strong>Data count:</strong> " . (is_array($result['data']) ? count($result['data']) : 'Not array') . "</p>";
    } else {
        echo "<p style='color: red;'>✗ Direct GCS retrieval failed: " . ($result['error'] ?? 'Unknown') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>GCS Provider Error: " . $e->getMessage() . "</p>";
}
?>