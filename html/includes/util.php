<?php

/**
 * Mediabrain App Utility Functions
 */

use MediaBrain\Includes\Services\MessagingService;

function render($filename, $vars = array(), $return = false, $cascading = true)
{
  $mb = App::getInstance();
  if ($return) {
    return $mb->render($filename, $vars, $return, $cascading);
  } else {
    echo $mb->render($filename, $vars, $return, $cascading);
  }
}


function setJsonHeader()
{
  // Set content type and CORS headers
  header('Content-Type: application/json');
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
}


function config($key = null, $default = null)
{
  $config =  App::getInstance()->config;

  if (!isset($key))
    return $config;

  return ((isset($key)) && (isset($config[$key])))
    ? $config[$key]
    : $default;
}


function protocol()
{
  return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'))) ? 'https' : 'http';
}


/**
 * Detect if the application is running in development environment
 */
function is_development()
{
  $host = $_SERVER['HTTP_HOST'] ?? '';
  return (bool)preg_match('/localhost|127\\.0\\.0\\.1|\\.local|:8080|:3000|:8000/', $host);
}

/**
 * Detect if the application is running in production environment
 */
function is_production()
{
  return !is_development();
}


/**
 * Detect if the application is running in Cloud Run environment
 */
function isCloudRun()
{
  return getenv('K_SERVICE') !== false || getenv('GOOGLE_CLOUD_PROJECT') !== false;
}


/**
 * Get the appropriate base URL for the current environment
 */
function get_base_url()
{
  $protocol = protocol();
  $host = $_SERVER['HTTP_HOST'] ?? 'mediabrain.app';

  // If in development, use the actual host
  if (is_development()) {
    return $protocol . '://' . $host;
  }

  // Production - always use the main domain with HTTPS
  return 'https://mediabrain.app';
}


function get_var($name, $default = null)
{
  if (!isset($_GET[$name])) {
    return $default;
  }

  $value = $_GET[$name];

  // Sanitize if the variable is 'app' or 'api' to prevent traversal.
  // This is a targeted fix; a broader sanitization strategy is advisable.
  if ($name === 'app' || $name === 'api') {
    // Allow only alphanumeric characters and underscores.
    // This ensures the value is a valid app name and not a path.
    $sanitized_value = preg_replace('/[^a-zA-Z0-9_]/', '', $value);
    if ($sanitized_value !== $value) {
      // If sanitization changed the value, it was likely malicious.
      // Log the attempt and return a safe default.
      error_log("Potential directory traversal attempt blocked. Original value: $value");
      return $default;
    }
    return $sanitized_value;
  }

  return $value;
}


function get_path($type, $name)
{
  return dirname(drupal_get_filename($type, $name));
}


function debug($object, $message = 'Debug Output')
{
  //$log_file = 'c:/wamp64/logs/php_error.log';
  $backtrace = debug_backtrace();
  $caller = array_shift($backtrace);
  $caller['args'] = array_shift($caller['args']);
  error_log('------------------------------------------' . "\n\n");
  error_log($message . "\n\n");
  error_log(date("D M j G:i:s T Y") . "\n");
  error_log($_SERVER['HTTP_USER_AGENT'] . "\n");
  error_log($_SERVER['REMOTE_ADDR'] . "\n");
  error_log(print_r($caller, true) . "\n");
  error_log('------------------------------------------' . "\n\n");
}


function app_dir()
{
  return App::getInstance()->app_dir;
}



// CSRF helpers

function validate_csrf_request()
{
  if (!isset($_SESSION['csrf_token'])) return false;
  // 1. Get all headers (Helper for Apache/Nginx)
  $headers = getallheaders();
  $token = null;

  // 2. Check for the Authorization header
  $authHeader = $headers['Authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? null;

  if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["error" => "Authorization header missing"]);
    exit;
  }

  // 3. Extract the token (removing "Bearer " from the start)
  if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
  } else {
    http_response_code(401);
    echo json_encode(["error" => "Invalid token format"]);
    exit;
  }

  // 4. Now validate your $token
  if (!hash_equals($_SESSION['csrf_token'], (string)$token)) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid token"]);
    exit;
  }

  return true;
}


function csrf_token()
{
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  }
  return $_SESSION['csrf_token'];
}

function api_require_admin()
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
 * Require a file relative to the current app directory.
 * Example: app_require('auth/check_auth.php');
 */
function app_require($rel_path)
{
  $rel_path = ltrim($rel_path, '/\\');
  $base = App::getInstance()->app_dir;
  $file = $base . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $rel_path);
  if (file_exists($file)) {
    require_once $file;
    return true;
  }
  // If file missing, trigger a warning but allow caller to handle it.
  trigger_error("app_require: file not found: $file", E_USER_WARNING);
  return false;
}

/**
 * Require a file relative to the document root.
 * Example: mb_require('includes/SomeClass.php');
 */
function mb_require($rel_path)
{
  $rel_path = ltrim($rel_path, '/\\');
  $base = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__ . '/..';
  $file = $base . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $rel_path);
  if (file_exists($file)) {
    require_once $file;
    return true;
  }
  // If file missing, trigger a warning but allow caller to handle it.
  trigger_error("mb_require: file not found: $file", E_USER_WARNING);
  return false;
}


function app_path()
{
  return App::getInstance()->app_path;
}


function app_root_url()
{
  return App::getInstance()->root_url;
}


function app_invoke($app = '', $hook = '')
{
  $args = func_get_args();
  // Remove $app and $hook from the arguments.
  app_load($app);
  if (app_hook($app, $hook)) {
    unset($args[0], $args[1]);
    return call_user_func_array($app . '_' . $hook, $args);
  } else {
    return array('error' => 'Function does not exist - ' . $app . '_' . $hook);
  }
}


function app_load($app)
{
  $filename = __DIR__ . '/../apps/' . $app . '/' . $app . '.app.php';
  if (file_exists($filename)) {
    require_once($filename);
    return true;
  } else {
    return false;
  }
}


function app_hook($module, $hook)
{
  $function = $module . '_' . $hook;
  if (function_exists($function)) {
    return TRUE;
  }

  // If the hook implementation does not exist, check whether it may live in an
  // optional include file registered via hook_hook_info().
  /* - Disabling due to not being currently needed
   $hook_info = app_hook_info();
   if (isset($hook_info[$hook]['group'])) {
      app_load_include('inc', $module, $module . '.' . $hook_info[$hook]['group']);
      if (function_exists($function)) {
         return TRUE;
      }
   }
   */
  return FALSE;
}


function app_hook_info()
{

  // This function is indirectly invoked from bootstrap_invoke_all(), in which
  // case common.inc, subsystems, and modules are not loaded yet, so it does not
  // make sense to support hook groups resp. lazy-loaded include files prior to
  // full bootstrap.
  if (drupal_bootstrap(NULL, FALSE) != DRUPAL_BOOTSTRAP_FULL) {
    return array();
  }
  $hook_info = &drupal_static(__FUNCTION__);
  if (!isset($hook_info)) {
    $hook_info = array();
    $cache = cache_get('hook_info', 'cache_bootstrap');
    if ($cache === FALSE) {

      // Rebuild the cache and save it.
      // We can't use app_invoke_all() here or it would cause an infinite
      // loop.
      foreach (app_list() as $module) {
        $function = $module . '_hook_info';
        if (function_exists($function)) {
          $result = $function();
          if (isset($result) && is_array($result)) {
            $hook_info = array_merge_recursive($hook_info, $result);
          }
        }
      }

      // We can't use drupal_alter() for the same reason as above.
      foreach (app_list() as $module) {
        $function = $module . '_hook_info_alter';
        if (function_exists($function)) {
          $function($hook_info);
        }
      }
      cache_set('hook_info', $hook_info, 'cache_bootstrap');
    } else {
      $hook_info = $cache->data;
    }
  }
  return $hook_info;
}


function app_list($refresh = FALSE, $bootstrap_refresh = FALSE, $sort = FALSE, $fixed_list = NULL)
{
  static $list = array();
  if (!empty($list) && !$refresh && !$bootstrap_refresh) {
    return $list;
  }

  $appsDir = 'apps/';

  if (is_dir($appsDir)) {
    $dirs = scandir($appsDir);
    foreach ($dirs as $dir) {
      if ($dir !== '.' && $dir !== '..' && is_dir($appsDir . $dir)) {
        $appFile = $appsDir . $dir . '/' . $dir . '.app.php';
        if (file_exists($appFile)) {
          $list[] = $dir;
        }
      }
    }
  }
  return $list;
}


function app_info($app = false)
{

  static $all_app_info = array();

  if (empty($all_app_info)) {
    $appsDir = 'apps/';
    $dirs = app_list();
    foreach ($dirs as $dir) {
      if ($dir !== '.' && $dir !== '..' && is_dir($appsDir . $dir)) {
        $appFile = $appsDir . $dir . '/' . $dir . '.app.php';
        require_once($appFile);
        if (function_exists($dir . '_info')) {
          $all_app_info[$dir] = call_user_func($dir . '_info');
        }
      }
    }
  }

  if ($app) {
    return $all_app_info[$app] ?? null;
  } else {
    return $all_app_info;
  }
}

function app_load_include($type, $module, $name = NULL)
{
  static $files = array();
  if (!isset($name)) {
    $name = $module;
  }
  $key = $type . ':' . $module . ':' . $name;
  if (isset($files[$key])) {
    return $files[$key];
  }
  if (function_exists('get_path')) {
    $file = APP_ROOT . '/' . get_path('module', $module) . "/{$name}.{$type}";
    if (is_file($file)) {
      require_once $file;
      $files[$key] = $file;
      return $file;
    } else {
      $files[$key] = FALSE;
    }
  }
  return FALSE;
}




function get_dir_contents($dir, &$results = array())
{
  $files = scandir($dir);

  foreach ($files as $key => $value) {
    $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
    if (!is_dir($path)) {
      $results[] = $path;
    } else if ($value != "." && $value != "..") {
      get_dir_contents($path, $results);
      $results[] = $path;
    }
  }

  return $results;
}

function json_read_file($json_file)
{
  $json_string = file_get_contents($json_file);
  if (!empty($json_string)) {
    $data = json_decode($json_string, true);
    return $data;
  }
}

function logger($message) {
  error_log(print_r($message, true));
}

function log_error($message)
{
  $backtrace = debug_backtrace();
  error_log(print_r(array('message' => $message, 'backtrace' => $backtrace), true));
}

function error($message, $object = null)
{
  $mb = App::getInstance();
  $backtrace = debug_backtrace();
  $mb->registerError(array('message' => $message, 'backtrace' => $backtrace));
}


function getSynonyms($string)
{
  return array();
}

/**
 * Invoke a hook from all apps that implement it
 * 
 * @param string $hook The hook name to invoke
 * @param mixed ...$args Arguments to pass to hook functions
 * @return array Array of results keyed by app name
 */
function app_invoke_all($hook)
{
  $args = func_get_args();
  array_shift($args); // Remove the hook name from args

  $results = [];

  // Find all app directories
  $appsDir = __DIR__ . '/../apps/';
  if (is_dir($appsDir)) {
    $appDirs = glob($appsDir . '*', GLOB_ONLYDIR);

    foreach ($appDirs as $appDir) {
      $appName = basename($appDir);

      // Skip admin app to avoid recursion, except for dashboard hooks
      if ($appName === 'admin' && $hook !== 'hook_admin_dashboard') {
        continue;
      }

      // Try to invoke the hook from this app
      $result = app_invoke($appName, $hook, ...$args);

      if (!isset($result['error'])) {
        $results[$appName] = $result;
      }
    }
  }

  return $results;
}

/**
 * Include theme CSS file(s)
 * @param string $theme Theme name (e.g. 'default', 'startrek')
 * @param array $files List of CSS files to include
 */
function include_theme_css($theme, $files = ['components.css'])
{
  foreach ($files as $file) {
    $path = "/themes/$theme/$file";
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($path) . '">' . "\n";
  }
}

/**
 * Include theme JS file(s)
 * @param string $theme Theme name
 * @param array $files List of JS files
 */
function include_theme_js($theme, $files = ['theme.js'])
{
  foreach ($files as $file) {
    $path = "/themes/$theme/$file";
    echo '<script type="text/javascript" src="' . htmlspecialchars($path) . '"></script>' . "\n";
  }
}

/**
 * Include theme audio file(s)
 * @param string $theme Theme name
 * @param array $files List of audio files
 * @param string $type Audio type (e.g. 'mp3', 'wav')
 */
function include_theme_audio($theme, $files = [], $type = 'mp3')
{
  foreach ($files as $file) {
    $path = "/themes/$theme/audio/$file.$type";
    echo '<audio src="' . htmlspecialchars($path) . '" preload="auto"></audio>' . "\n";
  }
}

/**
 * Find theme file with override logic
 * @param string $theme Theme name
 * @param string $file Relative file path (e.g. 'audio/click.mp3')
 * @param string|null $app Optional app name for override
 * @return string Path to file
 */
function get_theme_file($theme, $file, $app = null)
{
  if ($app) {
    $appPath = __DIR__ . "/../apps/$app/themes/$theme/$file";
    if (file_exists($appPath)) {
      return "/apps/$app/themes/$theme/$file";
    }
  }
  $themePath = "/themes/$theme/$file";
  return $themePath;
}

function send_message($sender_id, $recipient_ids, $subject, $body, $attachments = [])
{
  $mb = App::getInstance();
  if (!isset($mb->messagingService)) {
    mb_require('includes/services/MessagingService.php');
    $storageManager = FileStorageManager::getInstance();
    $mb->messagingService = new MessagingService($storageManager);
  }
  return $mb->messagingService->sendMessage($sender_id, $recipient_ids, $subject, $body, $attachments);
}
