<?php
$app = App::getInstance();
$recipe = $app->get('recipe');
if (!$recipe) {
    echo '<div class="red-text">Recipe not found</div>';
    exit;
}

// Check if user can edit recipes
$canEdit = recipes_user_can_edit();
?>

<link rel="stylesheet" href="apps/recipes/css/recipes.css">

<!-- Breadcrumb Navigation -->
<div class="row">
    <div class="col s12">
        <? render('components/navbar.php', array('recipe' => $recipe)) ?>
    </div>
</div>

<div class="row">
    <div class="col s12">
        <div class="card" style="margin-top: 0;">
            <div class="card-content" style="padding: 0;">
                <?php if (!empty($recipe['imageUrl'])): ?>
                    <div class="recipe-image" style="text-align: center; margin-bottom: 20px;">
                        <img src="<?= htmlspecialchars($recipe['imageUrl']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>
                
                <h3 class="card-title"><?= htmlspecialchars($recipe['title']) ?></h3>
                
                <div class="recipe-meta">
                    <span class="category-tag"><?= ucfirst(htmlspecialchars($recipe['category'])) ?></span>
                    <?php if ($recipe['prepTime'] || $recipe['cookTime']): ?>
                        <p><i class="material-icons tiny">schedule</i>
                        <?php if ($recipe['prepTime']): ?>Prep: <?= $recipe['prepTime'] ?>min <?php endif; ?>
                        <?php if ($recipe['cookTime']): ?>Cook: <?= $recipe['cookTime'] ?>min<?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($recipe['servings']): ?>
                        <p><i class="material-icons tiny">people</i> Serves <?= $recipe['servings'] ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($recipe['description']): ?>
                    <p class="recipe-description"><?= htmlspecialchars($recipe['description']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card-action">
                <a href="?app=recipes&p=cook&id=<?= $recipe['id'] ?>" class="btn green waves-effect waves-light">
                    <i class="material-icons left">restaurant</i>Cook Mode
                </a>
                <?php if ($canEdit): ?>
                    <a href="?app=recipes&p=edit&id=<?= $recipe['id'] ?>" class="btn orange waves-effect waves-light">
                        <i class="material-icons left">edit</i>Edit
                    </a>
                <?php endif; ?>
                <a href="?app=recipes&p=list" class="btn grey waves-effect waves-light">
                    <i class="material-icons left">list</i>All Recipes
                </a>
            </div>
        </div>
        
        <!-- Ingredients -->
        <?php if (!empty($recipe['ingredients'])): ?>
            <div class="card">
                <div class="card-content">
                    <h5><i class="material-icons left">shopping_cart</i>Ingredients</h5>
                    <div class="recipe-ingredients">
                        <ul>
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <li>• <?= htmlspecialchars($ingredient) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Instructions -->
        <?php if (!empty($recipe['steps'])): ?>
            <div class="card">
                <div class="card-content">
                    <h5><i class="material-icons left">list_alt</i>Instructions</h5>
                    <div class="recipe-steps">
                        <?php foreach ($recipe['steps'] as $index => $step): ?>
                            <div class="recipe-step">
                                <?= nl2br(htmlspecialchars($step)) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Notes -->
        <?php if ($recipe['notes']): ?>
            <div class="card">
                <div class="card-content">
                    <h5><i class="material-icons left">note</i>Notes</h5>
                    <p><?= nl2br(htmlspecialchars($recipe['notes'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>