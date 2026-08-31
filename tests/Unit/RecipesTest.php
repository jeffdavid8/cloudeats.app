<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Simple test-focused RecipeApp implementation
 */
class TestRecipeApp {
    private $dataFile;
    
    public function __construct($testDir = null) {
        if ($testDir === null) {
            $testDir = sys_get_temp_dir() . '/mediabrain_test_' . uniqid();
        }
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        $this->dataFile = $testDir . '/recipes.json';
        $this->ensureDataFile();
    }
    
    private function ensureDataFile() {
        if (!file_exists($this->dataFile)) {
            file_put_contents($this->dataFile, '[]');
        }
    }
    
    public function getRecipes() {
        if (file_exists($this->dataFile)) {
            $data = file_get_contents($this->dataFile);
            return json_decode($data, true) ?: [];
        }
        return [];
    }
    
    public function saveRecipes($recipes) {
        return file_put_contents($this->dataFile, json_encode($recipes, JSON_PRETTY_PRINT)) !== false;
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
    
    public function cleanup() {
        if (file_exists($this->dataFile)) {
            unlink($this->dataFile);
            $dir = dirname($this->dataFile);
            if (is_dir($dir) && count(scandir($dir)) == 2) {
                rmdir($dir);
            }
        }
    }
}

/**
 * Comprehensive Recipes App Unit Tests
 */
class RecipesTest extends TestCase
{
    private $recipeApp;
    
    public function setUp(): void
    {
        $this->recipeApp = new TestRecipeApp();
    }
    
    public function tearDown(): void
    {
        $this->recipeApp->cleanup();
    }

    #[Test]
    public function testRecipeAppInitialization()
    {
        $this->assertInstanceOf(TestRecipeApp::class, $this->recipeApp);
        $recipes = $this->recipeApp->getRecipes();
        $this->assertIsArray($recipes);
        $this->assertEmpty($recipes);
    }

    #[Test]
    public function testAddRecipe()
    {
        $recipeData = [
            'title' => 'Chocolate Chip Cookies',
            'description' => 'Delicious homemade chocolate chip cookies',
            'category' => 'dessert',
            'prepTime' => 15,
            'cookTime' => 12,
            'servings' => 24,
            'ingredients' => [
                '2 cups all-purpose flour',
                '1 cup butter',
                '1/2 cup sugar',
                '1 cup chocolate chips'
            ],
            'steps' => [
                'Preheat oven to 375°F',
                'Mix dry ingredients',
                'Add wet ingredients',
                'Drop onto baking sheet',
                'Bake for 12 minutes'
            ],
            'notes' => 'Best served warm'
        ];
        
        $recipe = $this->recipeApp->addRecipe($recipeData);
        
        $this->assertIsArray($recipe);
        $this->assertNotEmpty($recipe['id']);
        $this->assertEquals('Chocolate Chip Cookies', $recipe['title']);
        $this->assertEquals('dessert', $recipe['category']);
        $this->assertEquals(24, $recipe['servings']);
        $this->assertCount(4, $recipe['ingredients']);
        $this->assertCount(5, $recipe['steps']);
    }

    #[Test]
    public function testUpdateRecipe()
    {
        $originalData = ['title' => 'Original Recipe', 'category' => 'main'];
        $recipe = $this->recipeApp->addRecipe($originalData);
        $recipeId = $recipe['id'];
        
        $updateData = ['title' => 'Updated Recipe', 'category' => 'appetizer'];
        $updatedRecipe = $this->recipeApp->updateRecipe($recipeId, $updateData);
        
        $this->assertNotNull($updatedRecipe);
        $this->assertEquals('Updated Recipe', $updatedRecipe['title']);
        $this->assertEquals('appetizer', $updatedRecipe['category']);
    }

    #[Test]
    public function testDeleteRecipe()
    {
        $recipe = $this->recipeApp->addRecipe(['title' => 'Recipe to Delete']);
        $recipeId = $recipe['id'];
        
        $foundRecipe = $this->recipeApp->getRecipe($recipeId);
        $this->assertNotNull($foundRecipe);
        
        $result = $this->recipeApp->deleteRecipe($recipeId);
        $this->assertTrue($result);
        
        $notFoundRecipe = $this->recipeApp->getRecipe($recipeId);
        $this->assertNull($notFoundRecipe);
    }

    #[Test]
    public function testSearchRecipes()
    {
        $this->recipeApp->addRecipe(['title' => 'Chocolate Cake', 'category' => 'dessert']);
        $this->recipeApp->addRecipe(['title' => 'Vanilla Cookies', 'category' => 'dessert']);
        $this->recipeApp->addRecipe(['title' => 'Beef Stew', 'category' => 'main']);
        
        $chocolateRecipes = $this->recipeApp->searchRecipes('chocolate');
        $this->assertCount(1, $chocolateRecipes);
        
        $desserts = $this->recipeApp->searchRecipes('', 'dessert');
        $this->assertCount(2, $desserts);
    }

    #[Test]
    public function testGetCategories()
    {
        $this->recipeApp->addRecipe(['title' => 'Cake', 'category' => 'dessert']);
        $this->recipeApp->addRecipe(['title' => 'Pasta', 'category' => 'main']);
        $this->recipeApp->addRecipe(['title' => 'Soup', 'category' => 'appetizer']);
        
        $categories = $this->recipeApp->getCategories();
        $this->assertCount(3, $categories);
        $this->assertEquals(['appetizer', 'dessert', 'main'], $categories);
    }

    #[Test]
    #[DataProvider('recipeDataProvider')]
    public function testAddRecipeValidation($data, $expectedValid)
    {
        $recipe = $this->recipeApp->addRecipe($data);
        $this->assertIsArray($recipe);
        $this->assertArrayHasKey('id', $recipe);
    }

    public static function recipeDataProvider(): array
    {
        return [
            'Valid recipe' => [['title' => 'Test Recipe'], true],
            'Empty data' => [[], true],
            'HTML in title' => [['title' => '<script>alert("xss")</script>'], true]
        ];
    }

    #[Test]
    public function testSpecialCharacters()
    {
        $specialRecipe = [
            'title' => 'Café Français',
            'ingredients' => ['Açaí berries']
        ];
        
        $recipe = $this->recipeApp->addRecipe($specialRecipe);
        $this->assertEquals('Café Français', $recipe['title']);
        $this->assertContains('Açaí berries', $recipe['ingredients']);
    }

    #[Test]
    public function testLargeDataset()
    {
        $startTime = microtime(true);
        
        for ($i = 1; $i <= 50; $i++) {
            $this->recipeApp->addRecipe([
                'title' => "Recipe {$i}",
                'category' => ['main', 'dessert', 'appetizer'][$i % 3]
            ]);
        }
        
        $addTime = microtime(true) - $startTime;
        
        $searchStart = microtime(true);
        $results = $this->recipeApp->searchRecipes('Recipe');
        $searchTime = microtime(true) - $searchStart;
        
        $this->assertCount(50, $results);
        $this->assertLessThan(1.0, $addTime);
        $this->assertLessThan(0.1, $searchTime);
    }
}