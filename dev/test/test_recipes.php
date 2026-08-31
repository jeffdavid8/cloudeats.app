<?php
/**
 * Test Recipe App Functionality
 */

require_once 'apps/recipes/includes/RecipeApp.php';

echo "<h1>Recipe App Test</h1>";

try {
    $recipeApp = new RecipeApp();
    echo "<p>✓ RecipeApp instantiated</p>";
    
    $recipes = $recipeApp->getRecipes();
    echo "<h2>Current Recipes</h2>";
    echo "<p>Found " . count($recipes) . " recipes</p>";
    
    if (empty($recipes)) {
        echo "<p style='color: orange;'>No recipes found. Let's check storage...</p>";
        
        // Check if backup files exist
        if (file_exists('recipes_backup.json')) {
            echo "<p>✓ recipes_backup.json exists</p>";
            $backupData = json_decode(file_get_contents('recipes_backup.json'), true);
            echo "<p>Backup contains " . count($backupData) . " recipes</p>";
            
            // Try to restore the recipe manually
            if (!empty($backupData)) {
                echo "<h3>Restoring recipes from backup...</h3>";
                $success = $recipeApp->saveRecipes($backupData);
                if ($success) {
                    echo "<p style='color: green;'>✓ Recipes restored successfully!</p>";
                    $recipes = $recipeApp->getRecipes();
                    echo "<p>Now have " . count($recipes) . " recipes</p>";
                } else {
                    echo "<p style='color: red;'>✗ Failed to restore recipes</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ recipes_backup.json not found</p>";
        }
    }
    
    if (!empty($recipes)) {
        echo "<h3>Recipe Details:</h3>";
        foreach ($recipes as $recipe) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h4>" . htmlspecialchars($recipe['title']) . "</h4>";
            echo "<p><strong>ID:</strong> " . htmlspecialchars($recipe['id']) . "</p>";
            echo "<p><strong>Category:</strong> " . htmlspecialchars($recipe['category']) . "</p>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars($recipe['description']) . "</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>