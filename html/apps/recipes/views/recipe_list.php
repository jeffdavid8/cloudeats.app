<?php
$app = App::getInstance();
$recipeApp = $app->get('recipeApp');
$recipes = $recipeApp->getRecipes();
$categories = $recipeApp->getCategories();

// Check if user can edit recipes
$canEdit = recipes_user_can_edit();

// Handle error messages
$errorMessage = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'auth_required':
            $errorMessage = 'Authentication required to manage recipes. <a href="?app=ancestry&p=admin" class="orange-text">Please sign in.</a>';
            break;
        case 'admin_required':
            $errorMessage = 'Admin privileges required to manage recipes.';
            break;
    }
}
?>

<link rel="stylesheet" href="apps/recipes/css/recipes.css">

<div class="row" data-component="recipe-list">
    <div class="col s12">
        <h2 class="recipe-collection-title">Recipe Collection</h2>

        <?php if ($errorMessage): ?>
            <div class="card-panel red lighten-4 red-text text-darken-2">
                <i class="material-icons left">warning</i>
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter -->
        <div class="search-filters">
            <div class="row" style="margin-bottom: 0;">
                <div class="input-field col s12 m6" style="margin-bottom: 0;">
                    <input type="text" id="search-input" placeholder="Search recipes...">
                    <label for="search-input">Search</label>
                </div>
                <div class="input-field col s12 m4" style="margin-bottom: 0;">
                    <select id="category-filter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>"><?= ucfirst(htmlspecialchars($category)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Category</label>
                </div>
                <?php if ($canEdit): ?>
                    <div class="col s12 m2">
                        <a href="?app=recipes&p=add" class="btn orange waves-effect waves-light" style="margin-top: 20px;">
                            <i class="material-icons left">add</i>Add Recipe
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recipe Grid -->
        <div id="recipe-grid" class="row">
            <?php if (empty($recipes)): ?>
                <div class="col s12">
                    <div class="card-panel center-align grey lighten-4">
                        <h5>No recipes yet!</h5>
                        <p>Start building your recipe collection by adding your first recipe.</p>
                        <?php if ($canEdit): ?>
                            <a href="?app=recipes&p=add" class="btn orange waves-effect waves-light">
                                <i class="material-icons left">add</i>Add Your First Recipe
                            </a>
                        <?php else: ?>
                            <p class="grey-text">Admin access required to add recipes.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($recipes as $recipe): ?>
                    <div class="col s12 m6 l4 recipe-item" data-category="<?= htmlspecialchars($recipe['category']) ?>">
                        <div class="card">
                            <?php if (!empty($recipe['imageUrl'])): ?>
                                <div class="card-image">
                                    <a href="?app=recipes&p=view&id=<?= $recipe['id'] ?>">
                                        <img src="<?= htmlspecialchars($recipe['imageUrl']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" style="height: 200px; object-fit: cover;">
                                        <span class="card-title" style="background: rgba(0,0,0,0.7); padding: 4px 8px; border-radius: 4px;"><?= htmlspecialchars($recipe['title']) ?></span>
                                    </a>
                                </div>
                                <div class="card-content" style="padding-top: 10px;">
                                <?php else: ?>
                                    <div class="card-content">
                                        <span class="card-title"><?= htmlspecialchars($recipe['title']) ?></span>
                                    <?php endif; ?>
                                    <p class="recipe-meta">
                                        <span class="category-tag"><?= ucfirst(htmlspecialchars($recipe['category'])) ?></span>
                                        <?php if ($recipe['prepTime'] || $recipe['cookTime']): ?>
                                            <br><i class="material-icons tiny">schedule</i>
                                            <?php if ($recipe['prepTime']): ?>Prep: <?= $recipe['prepTime'] ?>min <?php endif; ?>
                                        <?php if ($recipe['cookTime']): ?>Cook: <?= $recipe['cookTime'] ?>min<?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($recipe['servings']): ?>
                                        <br><i class="material-icons tiny">people</i> Serves <?= $recipe['servings'] ?>
                                    <?php endif; ?>
                                    </p>
                                    <p><?= htmlspecialchars(substr($recipe['description'], 0, 100)) ?><?= strlen($recipe['description']) > 100 ? '...' : '' ?></p>
                                    </div>
                                    <div class="card-action">
                                        <a href="?app=recipes&p=view&id=<?= $recipe['id'] ?>" class="btn-flat orange-text">View</a>
                                        <a href="?app=recipes&p=cook&id=<?= $recipe['id'] ?>" class="btn-flat green-text">Cook</a>
                                        <?php if ($canEdit): ?>
                                            <a href="?app=recipes&p=edit&id=<?= $recipe['id'] ?>" class="btn-flat blue-text">Edit</a>
                                            <a href="#" onclick="deleteRecipe('<?= $recipe['id'] ?>')" class="btn-flat red-text">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                    </div>
        </div>
    </div>