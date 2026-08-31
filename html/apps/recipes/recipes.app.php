<?php



/**
 * Recipe Manager App Integration
 */

mb_require('apps/recipes/includes/RecipeApp.php');
mb_require('apps/admin/includes/permissions_helper.php');

function recipes_info()
{
  $app = App::getInstance();
  return array(
    'title' => "Recipe Manager",
    'description' => "Manage your recipes with voice-guided cooking mode using Google Cloud Text-to-Speech.",
    'image' => $app->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png',
    'image_height' => '630',
    'image_width' => '1200',
    'requires_auth' => false,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'version' => "1.0",
    'styles' => array(
      "apps/recipes/css/recipes.css",
    ),
    'scripts' => array(
      "apps/recipes/js/recipes.js",
    ),
  );
}

/**
 * Check if current user has admin privileges for recipe management
 * Uses the same authentication system as ancestry app
 * Authentication now handled by AppController at routing level
 */
function recipes_require_admin()
{
  // Check if ancestry auth is available for legacy compatibility
    if (!isset($_SESSION['user'])) {
      http_response_code(401);
      echo json_encode(['error' => 'Authentication required for recipe management']);
      exit;
    }
    $username = $_SESSION['user']['username'] ?? 'none';
    $role = $_SESSION['user']['role'] ?? 'none';
    // Check admin privileges using ancestry auth functions
    if (function_exists('user_is_admin') && !user_is_admin($username)) {
      http_response_code(403);
      echo json_encode(['error' => 'Admin privileges required for recipe management']);
      exit;
    }
    return true;
  // Fallback: no auth system available, allow access
  return true;
}

/**
 * Check if current user can edit recipes (non-fatal check)
 */
function recipes_user_can_edit()
{
    if (!isset($_SESSION['user'])) {
      return false;
    }
    $username = $_SESSION['user']['username'] ?? 'none';
    $role = $_SESSION['user']['role'] ?? 'none';
    if (function_exists('user_is_admin')) {
      $isAdmin = user_is_admin($username);
      return $isAdmin;
    }
  // Fallback: no auth system, allow access
  return true;
}

function recipes_init()
{
  $app = App::getInstance();
  $recipeApp = new RecipeApp();

  // Handle API requests with admin authentication for write operations
  if (isset($_GET['action']) || isset($_POST['action'])) {
    $action = $_GET['action'] ?? $_POST['action'];
    header('Content-Type: application/json');

    // Require admin for write operations
    if (in_array($action, ['add', 'update', 'delete'])) {
      recipes_require_admin();
    }

    switch ($action) {
      case 'list':
        $query = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? '';
        echo json_encode($recipeApp->searchRecipes($query, $category));
        exit;

      case 'get':
        $id = $_GET['id'] ?? '';
        $recipe = $recipeApp->getRecipe($id);
        echo json_encode($recipe ?: ['error' => 'Recipe not found']);
        exit;

      case 'add':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $recipe = $recipeApp->addRecipe($data);
        echo json_encode($recipe);
        exit;

      case 'update':
        $id = $_GET['id'] ?? $_POST['id'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $recipe = $recipeApp->updateRecipe($id, $data);
        echo json_encode($recipe ?: ['error' => 'Recipe not found']);
        exit;

      case 'delete':
        $id = $_GET['id'] ?? $_POST['id'] ?? '';
        $result = $recipeApp->deleteRecipe($id);
        echo json_encode(['success' => $result]);
        exit;

      case 'categories':
        echo json_encode($recipeApp->getCategories());
        exit;
    }
  }

  // Store the recipe app instance for views
  $app->set('recipeApp', $recipeApp);

  // Handle page routing with permission checks for add/edit pages
  $page = get_var('p', 'list');
  $app->set('page', $page);

  // Require admin for add/edit pages
  if (in_array($page, ['add', 'edit'])) {
    recipes_require_admin();
  }

  // Page-specific data
  switch ($page) {
    case 'view':
    case 'edit':
    case 'cook':
      $id = get_var('id');
      if ($id) {
        $recipe = $recipeApp->getRecipe($id);
        $app->set('recipe', $recipe);
        // Set up meta array for head.php
        if ($recipe) {
          $meta = [
            'title' => $recipe['title'],
            'description' => $recipe['description'],
            'type' => 'article',
            'image' => $recipe['imageUrl'] ?? ($app->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png'),
            'image_width' => '1200',
            'image_height' => '630'
          ];
          $app->set('meta', $meta);
        }
      }
      break;
    case 'list':
    default:
      $meta = [
        'title' => $app->app_info['title'],
        'description' => $app->app_info['description'],
        'type' => 'article',
        'image' => $app->app_info['image'],
        'image_width' => $app->app_info['image_width'],
        'image_height' => $app->app_info['image_height']
      ];
      $app->set('meta', $meta);
  }
}

function recipes_render_body()
{
  $app = App::getInstance();

  // Render the header with navigation
      mb_require('apps/admin/includes/permissions_helper.php');

  // Render the recipe app content
  $page = $app->get('page', 'list');

  echo '<div class="container">';

  switch ($page) {
    case 'list':
      render('recipe_list.php');
      break;
    case 'add':
      render('recipe_form.php');
      break;
    case 'edit':
      render('recipe_form.php');
      break;
    case 'view':
      render('recipe_view.php');
      break;
    case 'cook':
      render('cook_mode.php');
      break;
    default:
      render('recipe_list.php');
  }

  echo '</div>';

  // Add required elements for TTS
  echo '<audio id="tts-audio" style="display: none;"></audio>';
}
