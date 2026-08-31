<?php
if (!defined('MB_RUNNING')) exit;



/**
 * Vault Manager App Integration
 */


function vault_info()
{
  $app = App::getInstance();
  return array(
    'title' => "Vault Manager",
    'description' => "Secure storage and management of memory anchors, nexus links, and personal architectural data.",
    'image' => $app->config['base_url'] . '/apps/vault/images/vault-app-icon.jpg',
    'image_height' => '1024',
    'image_width' => '1024',
    'requires_auth' => true,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'version' => "0.1",
    'styles' => array(
      'apps/vault/css/vault.css',
    ),
    'scripts' => array(
      "apps/vault/js/vault.js",
    ),
  );
}

/**
 * 
 * 
 * 
 */
function vault_require_admin()
{
  // Check if ancestry auth is available for legacy compatibility
  if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required for stitch management']);
    exit;
  }
  $username = $_SESSION['user']['username'] ?? 'none';
  $role = $_SESSION['user']['role'] ?? 'none';
  // Check admin privileges using ancestry auth functions
  if (function_exists('user_is_admin') && !user_is_admin($username)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Admin privileges required for stitch management', 'code' => 'INSUFFICIENT_PERMISSIONS',]);
    exit;
  }
  // Fallback: no auth system available, allow access
  return true;
}
/**
 * Check if current user can edit stitches (non-fatal check)
 */
function vault_user_can_edit()
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

function vault_init(&$app)
{
  $app->includeModel('vault');

  $meta = [
    'title' => $app->app_info['title'],
    'description' => $app->app_info['description'],
    'type' => 'article',
    'image' => $app->app_info['image'],
    'image_width' => $app->app_info['image_width'],
    'image_height' => $app->app_info['image_height']
  ];
  $app->set('meta', $meta);

  // Handle page routing with permission checks for add/edit pages
  $page = get_var('p', 'list');
  $app->set('page', $page);

  // Page-specific data
  switch ($page) {

    case 'view':
    case 'edit':
      break;
    case 'list':
    default:
  }
}

function vault_render_body(&$app)
{
  $user = $app->user;
  $db = $app->db;
  // Render the header with navigation
  mb_require('apps/admin/includes/permissions_helper.php');

  // Render the stitch app content
  $page = $app->get('p', 'lobby');
  $isCommander = $user && $user->is_admin;
  $announcement = [
    "intel" => "Sovereign Auth Verified: " . $user->username,
    "mood" => $user->is_admin ? "gold-pulse" : "standard-blue",
    "intensity" => 1.0,
    "pilot" => $isCommander
  ];

  echo '<div class="app-container vault-app-container">';

  switch ($page) {

    case 'list':
      $user = $app->user;
      $db = $app->db;
      $balance = Vault::get_balance($user->id);
      $history = Vault::get_history($user->id);

      render('list.php', array('balance' => $balance, 'history' => $history));

      break;

    case 'db_viewer':
      vault_require_admin(); // Keep the un-cooperative out!

      // 1. Get high-level vitals
      $vitals = [
        'total' => $app->db->query("SELECT COUNT(*) FROM memory_anchors")->fetchColumn(),
        'nexus_links' => $app->db->query("SELECT COUNT(*) FROM stitch_nexus")->fetchColumn(),
        'vouches' => $app->db->query("SELECT COUNT(*) FROM vouches")->fetchColumn()
      ];

      // 2. Get distribution of types
      $types = $app->db->query("SELECT content_type, COUNT(*) as count FROM memory_anchors GROUP BY content_type")->fetchAll(PDO::FETCH_ASSOC);

      render('pages/stitch_db_viewer.php', [
        'vitals' => $vitals,
        'types' => $types
      ]);
      break;
    case 'db_exec':

      echo "<h1>DISABLED</h1>";
      break;

      echo "<h1>DB_EXEC_MODE</h1>";
      vault_require_admin();
      $res = $app->db->query("SELECT count(*) FROM memory_anchors;");
      echo "Number of rows in memory_anchors: " . $res->fetchColumn() . "<br>";
      $res = $app->db->query("SELECT * FROM memory_anchors;");
      echo '<pre class="debugger-info">';
      var_dump($res->fetchAll(PDO::FETCH_ASSOC));
      echo '</pre>';

      echo "<br>Done...<br>";
      break;

    case 'populate_dev_db':
      break;

    case 'populate_production_db':
      // Production DB population logic would go here
      // 1. CLEAR THE FIELD (Optional: only if you want to start fresh)
      echo "<h1>DISABLED</h1>";
      break;



    case 'lobby':
    default:
      // 🎯 Force a fresh pull of the user to ensure we aren't hitting a NULL scope
      $app = App::getInstance('vault');
      $currentUser = $app->user;

      if (!$currentUser || !isset($currentUser->id)) {
        // If no user, redirect to login or show an error instead of crashing
        error_log("VAULT ERROR: No authenticated user found in lobby.");
        header("Location: /?p=login");
        exit;
      }

      $db = App::getInstance()->db;

      // Now use $currentUser->id explicitly
      $balance = Vault::get_balance($currentUser->id);
      $impact = Vault::get_impact($currentUser->id);
      $history = Vault::get_history($currentUser->id);

      render('pages/lobby.php', [
        'user'    => $currentUser, // Pass the user object to the view
        'balance' => $balance,
        'history' => $history,
        'impact'  => $impact
      ]);
      break;
  }

  echo '</div>';

  // Add required elements for TTS
  echo '<audio id="tts-audio" style="display: none;"></audio>';
}
