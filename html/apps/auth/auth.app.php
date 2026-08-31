<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Auth App Integration
 */

function auth_info()
{
  return array(
    'title' => "Auth Manager",
    'description' => "",
    'image' => 'images/mb-logo-black-circle-2020-600.png',
    'image_height' => '630',
    'image_width' => '1200',
    'requires_auth' => false,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'version' => "0.1",
  );
}

function auth_init() {

  $action = get_var('action', '');
  $app = App::getInstance();

  switch ($action) {
    case 'login':
      break; 
    case 'logout':
      auth_handle_logout();
      break;
    default:
      break;
  }
}

function auth_render_body() {
}



/**
 * Handle logout request
 */
function auth_handle_logout()
{
    $redirect_url = get_var('redirect', '/?p=login');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $username = $_SESSION['user']['username'] ?? 'unknown';

    // Log logout
    $app = App::getInstance();
    if ($app->getEventLogger()) {
        $app->getEventLogger()->log('INFO', 'login', 'User logged out', [
            'username' => $username
        ]);
    }
    $_SESSION['oauth_provider'] = null;

    // Destroy session
    session_destroy();
    session_start();

    header('Location: ' . $redirect_url);
    exit;
}
