<?php
/**
 * Recipe Manager App Class
 * Cloud-compatible recipe storage and management system
 */

class RecipeApp {
    private $dataFile;
    private $storageManager;
    private $useCloudStorage;
    
    public function __construct() {
        $this->dataFile = __DIR__ . '/../data/recipes.json';
        
        // Always try to use storage manager - it handles provider switching
        try {
            require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';
            $this->storageManager = FileStorageManager::getInstance();
            $this->useCloudStorage = true;
        } catch (Exception $e) {
            log_error('RecipeApp: Storage manager not available, using local files: ' . $e->getMessage());
            $this->useCloudStorage = false;
        }
        
        $this->ensureDataFile();
    }
    
    private function ensureDataFile() {
        if ($this->storageManager) {
            // Check if recipes exist in storage, create empty if not
            $result = $this->storageManager->getJsonData('', 'recipes.json');
            if (!$result['success']) {
                $this->storageManager->storeJsonData('', 'recipes.json', []);
            }
        } else {
            // Local file fallback only if storage manager unavailable
            if (!file_exists($this->dataFile)) {
                $dir = dirname($this->dataFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($this->dataFile, '[]');
            }
        }
    }
    
    public function getRecipes() {
        if ($this->storageManager) {
            $result = $this->storageManager->getJsonData('', 'recipes.json');
            return $result['success'] ? $result['data'] : [];
        } else {
            // Fallback to local file only if storage manager is unavailable
            if (file_exists($this->dataFile)) {
                $data = file_get_contents($this->dataFile);
                return json_decode($data, true) ?: [];
            }
            return [];
        }
    }
    
    public function saveRecipes($recipes) {
        if ($this->storageManager) {
            $result = $this->storageManager->storeJsonData('', 'recipes.json', $recipes);
            return $result['success'];
        } else {
            // Fallback to local file only if storage manager is unavailable
            return file_put_contents($this->dataFile, json_encode($recipes, JSON_PRETTY_PRINT));
        }
    }
    
    public function getRecipe($id) {
        $recipes = $this->getRecipes();
        foreach ($recipes as $recipe) {
            if ($recipe['id'] === $id) {
                return $recipe;
            }
        }
        return null;
    }
    
    public function addRecipe($data) {
        $recipes = $this->getRecipes();
        $newRecipe = [
            'id' => uniqid(),
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'imageUrl' => $data['imageUrl'] ?? '',
            'category' => $data['category'] ?? 'main',
            'prepTime' => $data['prepTime'] ?? 0,
            'cookTime' => $data['cookTime'] ?? 0,
            'servings' => $data['servings'] ?? 1,
            'ingredients' => $data['ingredients'] ?? [],
            'steps' => $data['steps'] ?? [],
            'notes' => $data['notes'] ?? '',
            'created' => date('c'),
            'modified' => date('c')
        ];
        $recipes[] = $newRecipe;
        $this->saveRecipes($recipes);
        return $newRecipe;
    }
    
    public function updateRecipe($id, $data) {
        $recipes = $this->getRecipes();
        foreach ($recipes as &$recipe) {
            if ($recipe['id'] === $id) {
                $recipe['title'] = $data['title'] ?? $recipe['title'];
                $recipe['description'] = $data['description'] ?? $recipe['description'];
                $recipe['imageUrl'] = $data['imageUrl'] ?? $recipe['imageUrl'];
                $recipe['category'] = $data['category'] ?? $recipe['category'];
                $recipe['prepTime'] = $data['prepTime'] ?? $recipe['prepTime'];
                $recipe['cookTime'] = $data['cookTime'] ?? $recipe['cookTime'];
                $recipe['servings'] = $data['servings'] ?? $recipe['servings'];
                $recipe['ingredients'] = $data['ingredients'] ?? $recipe['ingredients'];
                $recipe['steps'] = $data['steps'] ?? $recipe['steps'];
                $recipe['notes'] = $data['notes'] ?? $recipe['notes'];
                $recipe['modified'] = date('c');
                $this->saveRecipes($recipes);
                return $recipe;
            }
        }
        return null;
    }
    
    public function deleteRecipe($id) {
        $recipes = $this->getRecipes();
        $recipes = array_filter($recipes, function($recipe) use ($id) {
            return $recipe['id'] !== $id;
        });
        $this->saveRecipes(array_values($recipes));
        return true;
    }
    
    public function searchRecipes($query = '', $category = '') {
        $recipes = $this->getRecipes();
        
        if ($query) {
            $recipes = array_filter($recipes, function($recipe) use ($query) {
                $searchText = strtolower($recipe['title'] . ' ' . $recipe['description'] . ' ' . implode(' ', $recipe['ingredients']));
                return strpos($searchText, strtolower($query)) !== false;
            });
        }
        
        if ($category) {
            $recipes = array_filter($recipes, function($recipe) use ($category) {
                return $recipe['category'] === $category;
            });
        }
        
        return array_values($recipes);
    }
    
    public function getCategories() {
        $recipes = $this->getRecipes();
        $categories = [];
        foreach ($recipes as $recipe) {
            if (!empty($recipe['category']) && !in_array($recipe['category'], $categories)) {
                $categories[] = $recipe['category'];
            }
        }
        sort($categories);
        return $categories;
    }
}
?>