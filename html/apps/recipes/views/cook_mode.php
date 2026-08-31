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
<div class="row" style="margin-bottom: 0;">
    <div class="col s12">
        <? render('components/navbar.php', array('recipe' => $recipe)) ?>
    </div>
</div>

<div class="row" style="margin-bottom: 0;">
    <div class="col s12">
        <div class="card-panel orange lighten-5" style="margin: 0;">
            <h4 class="orange-text text-darken-2">🍳 Cook Mode</h4>
            <p class="orange-text text-darken-1">Hands-free cooking with voice guidance</p>
        </div>
    </div>
</div>

<div class="row cook-mode" data-component="cook-mode-page" data-recipe='<?= json_encode($recipe) ?>'>
    <div class="col s12">
        <div class="card">
            <div class="card-content" style="padding: 0;">
                <?php if (!empty($recipe['imageUrl'])): ?>
                    <div class="recipe-image" style="text-align: center; margin-bottom: 15px;">
                        <img src="<?= htmlspecialchars($recipe['imageUrl']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    </div>
                <?php endif; ?>
                
                <h3 class="card-title"><?= htmlspecialchars($recipe['title']) ?></h3>
                
                <div class="recipe-meta">
                    <?php if ($recipe['prepTime'] || $recipe['cookTime']): ?>
                        <p><strong>Time:</strong> 
                        <?php if ($recipe['prepTime']): ?>Prep <?= $recipe['prepTime'] ?>min <?php endif; ?>
                        <?php if ($recipe['cookTime']): ?>• Cook <?= $recipe['cookTime'] ?>min<?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($recipe['servings']): ?>
                        <p><strong>Servings:</strong> <?= $recipe['servings'] ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if ($recipe['description']): ?>
                    <p class="recipe-description"><?= htmlspecialchars($recipe['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- TTS Controls -->
        <div class="tts-controls">
            <button id="read-recipe" class="btn large green waves-effect waves-light">
                <i class="material-icons left">play_arrow</i>
                Read Recipe Aloud
            </button>
            <button id="stop-reading" class="btn large red waves-effect waves-light" style="display: none;">
                <i class="material-icons left">stop</i>
                Stop Reading
            </button>
            <button id="read-current-step" class="btn orange waves-effect waves-light">
                <i class="material-icons left">record_voice_over</i>
                Read Current Step
            </button>
        </div>
        
        <!-- Ingredients -->
        <?php if (!empty($recipe['ingredients'])): ?>
            <div class="card">
                <div class="card-content">
                    <h5><i class="material-icons left">shopping_cart</i>Ingredients</h5>
                    <div class="recipe-ingredients">
                        <ul class="collection">
                            <?php foreach ($recipe['ingredients'] as $ingredient): ?>
                                <li class="collection-item">
                                    <label>
                                        <input type="checkbox" class="ingredient-check">
                                        <span><?= htmlspecialchars($ingredient) ?></span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Cooking Steps -->
        <?php if (!empty($recipe['steps'])): ?>
            <div class="card">
                <div class="card-content">
                    <h5><i class="material-icons left">list_alt</i>Cooking Steps</h5>
                    <div class="recipe-steps">
                        <?php foreach ($recipe['steps'] as $index => $step): ?>
                            <div class="recipe-step" data-step="<?= $index + 1 ?>">
                                <div class="step-controls" style="float: right; margin-left: 10px;">
                                    <button class="btn-floating small orange waves-effect" onclick="readStep(<?= $index ?>)">
                                        <i class="material-icons">volume_up</i>
                                    </button>
                                    <label style="margin-left: 10px;">
                                        <input type="checkbox" class="step-check">
                                        <span>Done</span>
                                    </label>
                                </div>
                                <div class="step-content">
                                    <?= nl2br(htmlspecialchars($step)) ?>
                                </div>
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
        
        <!-- Navigation -->
        <div class="card-panel center-align">
            <a href="?app=recipes&p=view&id=<?= $recipe['id'] ?>" class="btn blue waves-effect waves-light">
                <i class="material-icons left">visibility</i>View Recipe
            </a>
            <?php if ($canEdit): ?>
                <a href="?app=recipes&p=edit&id=<?= $recipe['id'] ?>" class="btn orange waves-effect waves-light">
                    <i class="material-icons left">edit</i>Edit Recipe
                </a>
            <?php endif; ?>
            <a href="?app=recipes&p=list" class="btn grey waves-effect waves-light">
                <i class="material-icons left">list</i>All Recipes
            </a>
        </div>
    </div>
</div>

<style>
.cook-mode {
    font-size: 1.1em;
}

.recipe-step {
    transition: background-color 0.3s ease;
    border-radius: 8px;
    padding: 16px;
}

.step-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.step-content {
    clear: both;
    margin-top: 10px;
}

.ingredient-check:checked + span {
    text-decoration: line-through;
    opacity: 0.6;
}

.step-check:checked ~ .step-content {
    opacity: 0.6;
    text-decoration: line-through;
}

@media (max-width: 600px) {
    .step-controls {
        float: none !important;
        margin-bottom: 10px;
    }
}
</style>