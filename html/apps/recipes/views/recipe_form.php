<?php
$app = App::getInstance();
$recipeApp = $app->get('recipeApp');
$recipe = $app->get('recipe');
$isEdit = $_GET['p'] === 'edit';
if ($isEdit && !$recipe) {
    echo '<div class="red-text">Recipe not found</div>';
    exit;
}
?>

<link rel="stylesheet" href="apps/recipes/css/recipes.css">

<!-- Breadcrumb Navigation -->
<div class="row">
    <div class="col s12">
        <nav class="breadcrumb-nav">
            <div class="nav-wrapper grey lighten-4">
                <div class="col s12">
                    <a href="?" class="breadcrumb">Home</a>
                    <a href="?app=recipes" class="breadcrumb">Recipe Manager</a>
                    <span class="breadcrumb"><?= $isEdit ? 'Edit Recipe' : 'Add Recipe' ?></span>
                </div>
            </div>
        </nav>
    </div>
</div>

<div class="row" data-component="recipe-form">
    <div class="col s12">
        <h4><?= $isEdit ? 'Edit Recipe' : 'Add New Recipe' ?></h4>
        
        <form id="recipe-form" class="recipe-form">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $recipe['id'] ?>">
            <?php endif; ?>
            
            <!-- Basic Info -->
            <div class="row">
                <div class="input-field col s12">
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($recipe['title'] ?? '') ?>" required>
                    <label for="title" class="<?= $recipe ? 'active' : '' ?>">Recipe Title</label>
                </div>
            </div>
            
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="description" name="description" class="materialize-textarea"><?= htmlspecialchars($recipe['description'] ?? '') ?></textarea>
                    <label for="description" class="<?= $recipe ? 'active' : '' ?>">Description</label>
                </div>
            </div>
            
            <!-- Recipe Image -->
            <div class="row">
                <div class="input-field col s12">
                    <input type="url" id="imageUrl" name="imageUrl" value="<?= htmlspecialchars($recipe['imageUrl'] ?? '') ?>">
                    <label for="imageUrl" class="<?= $recipe && $recipe['imageUrl'] ? 'active' : '' ?>">Recipe Image URL</label>
                    <span class="helper-text">Optional: Enter a URL for a recipe image</span>
                </div>
                <?php if ($recipe && !empty($recipe['imageUrl'])): ?>
                    <div class="col s12">
                        <div class="image-preview">
                            <img src="<?= htmlspecialchars($recipe['imageUrl']) ?>" alt="Recipe preview" style="max-width: 200px; max-height: 150px; border-radius: 4px;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Category and Times -->
            <div class="row">
                <div class="input-field col s12 m4">
                    <select id="category" name="category">
                        <option value="appetizer" <?= ($recipe['category'] ?? '') === 'appetizer' ? 'selected' : '' ?>>Appetizer</option>
                        <option value="main" <?= ($recipe['category'] ?? 'main') === 'main' ? 'selected' : '' ?>>Main Course</option>
                        <option value="side" <?= ($recipe['category'] ?? '') === 'side' ? 'selected' : '' ?>>Side Dish</option>
                        <option value="dessert" <?= ($recipe['category'] ?? '') === 'dessert' ? 'selected' : '' ?>>Dessert</option>
                        <option value="beverage" <?= ($recipe['category'] ?? '') === 'beverage' ? 'selected' : '' ?>>Beverage</option>
                        <option value="snack" <?= ($recipe['category'] ?? '') === 'snack' ? 'selected' : '' ?>>Snack</option>
                    </select>
                    <label>Category</label>
                </div>
                
                <div class="input-field col s12 m3">
                    <input type="number" id="prepTime" name="prepTime" value="<?= $recipe['prepTime'] ?? '' ?>" min="0">
                    <label for="prepTime" class="<?= $recipe ? 'active' : '' ?>">Prep Time (min)</label>
                </div>
                
                <div class="input-field col s12 m3">
                    <input type="number" id="cookTime" name="cookTime" value="<?= $recipe['cookTime'] ?? '' ?>" min="0">
                    <label for="cookTime" class="<?= $recipe ? 'active' : '' ?>">Cook Time (min)</label>
                </div>
                
                <div class="input-field col s12 m2">
                    <input type="number" id="servings" name="servings" value="<?= $recipe['servings'] ?? '' ?>" min="1">
                    <label for="servings" class="<?= $recipe ? 'active' : '' ?>">Servings</label>
                </div>
            </div>
            
            <!-- Ingredients -->
            <div class="row">
                <div class="col s12">
                    <h5>Ingredients</h5>
                    <div id="ingredient-list" class="ingredient-list">
                        <?php if ($recipe && !empty($recipe['ingredients'])): ?>
                            <?php foreach ($recipe['ingredients'] as $index => $ingredient): ?>
                                <div class="ingredient-item">
                                    <input type="text" placeholder="Ingredient" value="<?= htmlspecialchars($ingredient) ?>" name="ingredients[]">
                                    <button type="button" class="btn-remove" onclick="removeIngredient(this)">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ingredient-item">
                                <input type="text" placeholder="Ingredient" name="ingredients[]">
                                <button type="button" class="btn-remove" onclick="removeIngredient(this)">Remove</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn orange waves-effect" onclick="addIngredient()">
                        <i class="material-icons left">add</i>Add Ingredient
                    </button>
                </div>
            </div>
            
            <!-- Steps -->
            <div class="row">
                <div class="col s12">
                    <h5>Cooking Steps</h5>
                    <div id="step-list" class="step-list">
                        <?php if ($recipe && !empty($recipe['steps'])): ?>
                            <?php foreach ($recipe['steps'] as $index => $step): ?>
                                <div class="step-item">
                                    <textarea placeholder="Step <?= $index + 1 ?>" name="steps[]" class="materialize-textarea"><?= htmlspecialchars($step) ?></textarea>
                                    <button type="button" class="btn-remove" onclick="removeStep(this)">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="step-item">
                                <textarea placeholder="Step 1" name="steps[]" class="materialize-textarea"></textarea>
                                <button type="button" class="btn-remove" onclick="removeStep(this)">Remove</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn orange waves-effect" onclick="addStep()">
                        <i class="material-icons left">add</i>Add Step
                    </button>
                </div>
            </div>
            
            <!-- Notes -->
            <div class="row">
                <div class="input-field col s12">
                    <textarea id="notes" name="notes" class="materialize-textarea"><?= htmlspecialchars($recipe['notes'] ?? '') ?></textarea>
                    <label for="notes" class="<?= $recipe ? 'active' : '' ?>">Notes</label>
                </div>
            </div>
            
            <!-- Submit -->
            <div class="row">
                <div class="col s12">
                    <button type="submit" class="btn orange waves-effect waves-light">
                        <i class="material-icons left">save</i>
                        <?= $isEdit ? 'Update Recipe' : 'Save Recipe' ?>
                    </button>
                    <a href="?app=recipes&p=list" class="btn grey waves-effect waves-light">
                        <i class="material-icons left">cancel</i>Cancel
                    </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>