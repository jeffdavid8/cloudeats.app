<?php
if (!defined('MB_RUNNING')) exit;

class App
{
  var $config = null;
  var $db = null;
  //var $db_type = 'sqlite';
  var $db_type = 'mysql';
  var $dir = null;
  var $appName = '';
  var $app_dir = null;
  var $app_path = null;
  var $root_url = null;
  var $includes_dir = null;
  var $app_classes_dir = null;
  var $app_info = array();
  var $meta = array();
  var $context = array();
  var $errors = array();
  var $authManager = null;
  var $user = null;
  var $messagingService = null;
  var $dbPath = null;
  var $debugInfo = array();
  var $csrf_token = null;
  private $logger = null;
  private $eventLogger = null;

  private $_structure = null;

  private static $_instance = null;

  private function __construct($app = '')
  {
    // Set security headers early in application lifecycle
    $dev = is_development();
    $this->appName = $app;
    SecurityHeaders::setHeaders([
      'development' => $dev, // Force development mode for cache busting
      'hsts_max_age' => $dev ? 0 : 31536000, // 1 year
      'hsts_include_subdomains' => true
    ]);

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    $this->_map($app);

    // Initialize Monolog if available
    if (class_exists('\\Monolog\\Logger')) {
      try {
        // determine logs dir at repo root
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
          @mkdir($logDir, 0755, true);
        }
        $this->logger = new \Monolog\Logger('mediabrain');
        // Use ERROR level to prevent memory bloat from excessive logging
        if (class_exists('Monolog\\Level')) {
          $level = \Monolog\Level::Error;
        } else {
          $level = \Monolog\Logger::ERROR;
        }
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', $level));
      } catch (Exception $e) {
        // ignore logger init failures, continue with default behavior
        $this->logger = null;
      }
    }

    $this->config = array(
      'version' => '0.5',
      'domain' => 'cloudeats.app',
      'site_name' => 'CloudEats',
      'site_description' => 'Local food, products, services, and businesses.',
      'fb_app_id' => '561081350692034',
      'site_name_short' => 'MB',
      'under_construction' => false,
      'request_donations' => false,
      'request_donations_timeout' => 15000,
      'coords' => '40.4211,-85.6538',
      'nws_station_id' => '',
      'base_url' => protocol() . '://' . $_SERVER["HTTP_HOST"],
      // Database
        'mysql' => array(
          'host' => $_ENV['DB_HOST'] ?? 'localhost',
          'port' => $_ENV['DB_PORT'] ?? 3306,
          'database' => $_ENV['DB_NAME'] ?? 'mediabrain',
          'username' => $_ENV['DB_USER'] ?? '',
          'password' => $_ENV['DB_PASS'] ?? '',
        ),
        // Secrets
        'app_key' => $_ENV['APP_KEY'] ?? '',
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
        'session_secret' => $_ENV['SESSION_SECRET'] ?? '',
        'admin_password' => $_ENV['ADMIN_PASSWORD'] ?? 'admin',
        'admin_email' => $_ENV['ADMIN_EMAIL'] ?? 'admin@mediabrain.app',
        // Google Cloud
        'google_cloud_project' => $_ENV['GOOGLE_CLOUD_PROJECT'] ?? '',
        'google_application_credentials' => $_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? '',
        // Mail
        'mail_host' => $_ENV['MAIL_HOST'] ?? '',
        'mail_user' => $_ENV['MAIL_USER'] ?? '',
        'mail_pass' => $_ENV['MAIL_PASS'] ?? '',      // Logging
      'log_level' => 'debug',
    );

    $this->csrf_token = csrf_token();
    $this->dir = getcwd();
    $this->includes_dir = dirname(__FILE__);
    $this->app_classes_dir = $this->includes_dir . '/classes/apps/' . $app;
    $this->root_url = (!empty($app)) ? '?app=' . $app : '/';
    $this->config['site_logo_url'] = 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/9/e838b992ed68c5403847421418fdcae3.png';

    $this->_connect_mysql();

    // Initialize AuthManager
    $this->authManager = new AuthManager($this->db, $this->logger);

    if (isset($_SESSION['user'])) {
      $this->user = new User($_SESSION['user']);

      // Masquerade as user if requested
      if ($this->user->is_admin && !empty($_GET['admin_test_user'])) {
        $id = $_GET['admin_test_user'];
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->user = new User($user);
      }
    }

    // Initialize EventLogger with error handling
    try {
      //$this->eventLogger = EventLogger::getInstance();
      //$this->eventLogger->log('app', 'App instance created', []);
    } catch (Exception $e) {
      error_log("Failed to initialize EventLogger: " . $e->getMessage());
      $this->eventLogger = null;
    }

    if (!empty($app)) {
      $this->app_dir = $this->dir . '/apps/' . $app;
      $this->app_path = 'apps/' . $app;
      $this->app_classes_dir = 'apps/' . $app . '/includes';
      require_once($this->dir . '/apps/' . $app . '/' . $app . '.app.php');
      $this->appName = $app;
    }
    $this->_loadStructure();

    $this->set('favicon', $this->app_info['favicon'] ?? []);
  }

  public static function getInstance($app = '')
  {
    // If no instance exists at all, create the initial one
    if (self::$_instance === null) {
      self::$_instance = new App($app);
      self::$_instance->handleInternalErrors();
    }
    // If an instance exists but the requested app is different, update the singleton tracking
    else if (!empty($app) && (self::$_instance->appName !== $app)) {
      self::$_instance = new App($app);
      self::$_instance->handleInternalErrors();
    }

    return self::$_instance;
  }

  /**
   * Helper to keep the instance code DRY and clean
   */
  private function handleInternalErrors()
  {
    if (!empty($this->errors)) {
      foreach ($this->errors as $error) {
        $this->registerError($error);
      }
    }
  }

  public function getAuthManager()
  {
    return $this->authManager;
  }

  public function getEventLogger()
  {
    return $this->eventLogger;
  }

  /**
   * Log an event (convenience method)
   */
  public function logEvent($level, $event, $message, $context = [])
  {
    if ($this->eventLogger) {
      $this->eventLogger->log($level, $event, $message, $context);
    }
  }

  public function getPageTitle()
  {
    return ((!$this->app) || (empty($this->app_info['title'])))
      ? config('site_title')
      : $this->app_info['title'];
  }

  public function structure()
  {
    return $this->_structure;
  }

  public function render($filename, $vars = null, $return = false, $cascading = true)
  {
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    ob_start();
    if (str_starts_with($filename, "/")) {
      include ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/views/' . $filename)) && ($cascading)) {
      include $this->app_dir . '/views/' . $filename;
    } else if (file_exists($this->dir . '/views/' . $filename)) {
      include $this->dir . '/views/' . $filename;
    }
    if (!$return) echo ob_get_clean();
    
    return ob_get_clean();
  }


  public function includeApi($apiName, $vars = null, $return = false, $cascading = true)
  {
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    $filename = strtolower($apiName) . '.api.php';
    if (str_starts_with($filename, "/")) {
      include ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/api/' . $filename)) && ($cascading)) {
      include $this->app_dir . '/api/' . $filename;
    } else if (file_exists($this->dir . '/api/' . $filename)) {
      include $this->dir . '/api/' . $filename;
    }
    return;
  }




  public function includeModel($modelName, $vars = null, $return = false, $cascading = true)
  {
    if (class_exists($modelName)) {
      return $modelName;
    }
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    $filename = strtolower($modelName) . '.model.php';

    if (str_starts_with($filename, "/")) {
      require_once ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/includes/models/' . $filename)) && ($cascading)) {
      require_once $this->app_dir . '/includes/models/' . $filename;
    } else if (file_exists($this->dir . '/includes/models/' . $filename)) {
      require_once $this->dir . '/includes/models/' . $filename;
    }

    return $result;
  }

  public function includeClass($className = '', $cascading = true)
  {
    if (empty($className)) return false;

    if (class_exists($className)) {
      return $className;
    }
    $filename = $className . '.class.php';
    if (str_starts_with($filename, "/")) {
      require_once ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/includes/classes/' . $filename)) && ($cascading)) {
      require_once $this->app_dir . '/includes/classes/' . $filename;
    } else if (file_exists($this->dir . '/includes/classes/' . $filename)) {
      require_once $this->dir . '/includes/classes/' . $filename;
    }
  }



  public function includeController($controllerName, $vars = null, $return = false, $cascading = true)
  {
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    $filename = strtolower($controllerName) . '.controller.php';
    ob_start();
    if (str_starts_with($filename, "/")) {
      include_once ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/includes/controllers/' . $filename)) && ($cascading)) {
      include_once $this->app_dir . '/includes/controllers/' . $filename;
    } else if (file_exists($this->dir . '/includes/controllers/' . $filename)) {
      include_once $this->dir . '/includes/controllers/' . $filename;
    }
    return ob_get_clean();
  }

  public function includeHelper($helperName, $vars = null, $return = false, $cascading = true)
  {
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    $filename = strtolower($helperName) . '.helpers.php';
    ob_start();
    if (str_starts_with($filename, "/")) {
      include_once ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/includes/helpers/' . $filename)) && ($cascading)) {
      include_once $this->app_dir . '/includes/helpers/' . $filename;
    } else if (file_exists($this->dir . '/includes/helpers/' . $filename)) {
      include_once $this->dir . '/includes/helpers/' . $filename;
    }
    return ob_get_clean();
  }

  /**
   * Get controller class instance
   * Used for class-based controllers (e.g., MembershipPurchaseController)
   */
  public function getControllerClass($className, $constructorArgs = [])
  {
    if (!class_exists($className)) {
      $filename = strtolower(str_replace('Controller', '', $className)) . '.controller.php';

      if (str_starts_with($filename, "/")) {
        include_once ROOT_PATH . $filename;
      } else if ($this->app_dir && (file_exists($this->app_dir . '/includes/controllers/' . $filename))) {
        include_once $this->app_dir . '/includes/controllers/' . $filename;
      } else if (file_exists($this->dir . '/includes/controllers/' . $filename)) {
        include_once $this->dir . '/includes/controllers/' . $filename;
      }
    }

    if (!class_exists($className)) {
      throw new Exception("Controller class not found: {$className}");
    }

    return new $className(...$constructorArgs);
  }


  public function processAction($action, $vars = null, $return = false, $cascading = true)
  {
    if (is_array($vars) && !empty($vars)) {
      extract($vars);
    }
    $filename = strtolower($action) . '.action.php';
    if (str_starts_with($filename, "/")) {
      include_once ROOT_PATH . $filename;
    } else if ($this->app_dir && (file_exists($this->app_dir . '/actions/' . $filename)) && ($cascading)) {
      include_once $this->app_dir . '/actions/' . $filename;
    } else if (file_exists($this->dir . '/actions/' . $filename)) {
      include_once $this->dir . '/actions/' . $filename;
    }
  }

  private function _loadStructure()
  {
    $structure_file = "./json/structure.json";
    $this->_structure = json_decode(file_get_contents($structure_file), true);
    $this->app_info = array(
      'styles' => array(),
      'scripts' => array(),
      'components' => array()
    );

    if ($this->appName) {
      $this->app_info = app_invoke($this->appName, 'info', $this);
    }
  }

  public static function generateToken()
  {
    $token = bin2hex(random_bytes(16)); //generates a crypto-secure 32 characters long

    return $token;
  }

  public static function validateCSRFToken($token)
  {
    // Both token and session token must be present and non-empty
    if (empty($token) || empty($_SESSION['csrf_token'])) {
      return false;
    }
    return ($token === $_SESSION['csrf_token']);
  }

  public function set($var, $value)
  {
    $this->context[$var] = $value;
  }

  public function get($var, $default = null)
  {
    return (isset($this->context[$var])) ? $this->context[$var] : $default;
  }

  public function setCookie($name, $value)
  {
    // Only set cookie if headers haven't been sent yet
    if (!headers_sent()) {
      $expiry = time() + (5 * 365 * 24 * 60 * 60);
      setcookie(
        $name,
        $value,
        [
          'expires' => $expiry,
          'path' => '/',
          'httponly' => true,
          'samesite' => 'Lax'
        ]
      );
    }
  }

  public function getCookie($name, $default = false)
  {
    return (isset($_COOKIE[$name])) ? $_COOKIE[$name] : $default;
  }

  public function requireAdmin()
  {
    $this->authManager->requireAdmin();
  }

  public function isAdmin()
  {
    return $this->authManager->isAdmin();
  }

  public function isCommander(): bool
  {
    // Hard-check the role from the hydrated user object
    return (isset($this->user) && $this->user->role === 'Admin');
  }

  public function isUserLoggedIn()
  {
    return $this->authManager->isUserLoggedIn();
  }

  public function getCurrentUser()
  {
    return $this->authManager->getCurrentUser();
  }


  public function registerError($error = array())
  {
    $this->errors[] = $error;
    // Normalize message and context
    $message = '';
    $context = [];
    if (is_string($error)) {
      $message = $error;
    } elseif (is_array($error)) {
      if (isset($error['message'])) $message = $error['message'];
      if (isset($error['backtrace'])) $context['backtrace'] = $error['backtrace'];
      if (isset($error['file'])) $context['file'] = $error['file'];
      if (isset($error['line'])) $context['line'] = $error['line'];
    }

    // Send to Monolog if available
    if ($this->logger) {
      try {
        $this->logger->error($message ?: 'Unknown error', $context);
      } catch (Exception $e) {
        // fallback to PHP error_log on logger failure
        error_log('Logger failure: ' . $e->getMessage());
      }
    } else {
      // Fallback: always write to PHP error log so errors are visible when Monolog not installed
      error_log((string)$message);
      if (!empty($context['backtrace'])) {
        error_log(print_r($context['backtrace'], true));
      }
    }
  }

  // Central handlers for uncaught exceptions and PHP errors
  public function _handleException($ex)
  {
    $bt = $ex->getTrace();
    $this->registerError(array(
      'message' => 'Uncaught Exception: ' . $ex->getMessage(),
      'file' => $ex->getFile(),
      'line' => $ex->getLine(),
      'backtrace' => $bt
    ));
    // Re-throw for PHP internal handler if desired (we choose to exit)
    http_response_code(500);
    exit;
  }

  public function _handleError($errno, $errstr, $errfile, $errline)
  {
    $this->registerError(array(
      'message' => "PHP Error [$errno]: $errstr",
      'file' => $errfile,
      'line' => $errline,
      'backtrace' => debug_backtrace()
    ));
    // Respect PHP internal error handling for fatal errors
    return false;
  }

  public function _handleShutdown()
  {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
      $this->registerError(array(
        'message' => 'Shutdown fatal error: ' . $err['message'],
        'file' => $err['file'],
        'line' => $err['line']
      ));
    }
  }

  private function _map(&$app)
  {
    if (empty($app)) {
      return;
    }

    $appMap = array(
      'biblebot' => 'bibleBot',
      'BibleBot' => 'bibleBot',
      'Biblebot' => 'bibleBot',
      'bibleBot' => 'bibleBot',
      'Weather' => 'weather',
    );

    if (!empty($appMap[$app])) {
      $app = $appMap[$app];
    }
  }

  function getDefaultMetaImageArray()
  {
    return array(
      'image' => $this->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png',
      'image_width' => '600',
      'image_height' => '600',
    );
  }

  private function _connect_mysql()
  {
    $db_type = (isset($this->app_info['db_type'])) ? $this->app_info['db_type'] : 'mysql';

    switch ($db_type) {
      case 'mysql':
        if (is_production()) {
          // 🌐 PRODUCTION VIA GOOGLE CLOUD RUN TO COMPUTE ENGINE VM (TCP HOST)
          // PRODUCTION: Cloud Run Environment Variables pointing to your VM Internal IP
          $dsn  = "mysql:host=" . getenv('DB_HOST') . ";port=3306;dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
          $user = getenv('DB_USER');
          $pass = getenv('DB_PASS');
        } else {
          // 💻 LOCAL DEVELOPMENT VIA DOCKER COMPOSE (TCP HOST)
          $dsn = "mysql:host=db_mediabrain;dbname=cloudeats-app;charset=utf8mb4";
          $user = "webuser";
          $pass = "H7_lsfodOEep043L";
        }

        $this->db = new PDO($dsn, $user, $pass);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db->exec("SET time_zone = '-04:00';"); // Indiana EDT is UTC-4
        break;

      case 'sqlite':
      default:
        // 🛰️ ANCHOR TO THE ROOT
        // This ensures that /oauth/google.php and /index.php see the SAME file
        // Construct an absolute path 
        $basePath = $_SERVER['DOCUMENT_ROOT'];
        $dbFile = (is_development()) ? 'db/mediabrain_dev.sqlite' : 'db/mediabrain.sqlite';

        $dbPath = (is_development()) ? $basePath . '/' . $dbFile : $dbFile;
        $this->debugInfo['DB_FILE_WRITABLE'] = 'YES';
        $this->debugInfo['DB_FILE_EXISTS'] = 'NO';
        if (file_exists($dbFile)) {
          $this->debugInfo['DB_FILE_EXISTS'] = 'YES';
        }

        if (!is_writable($dbPath)) {
          $this->debugInfo['DB_FILE_WRITABLE'] = 'NO';
          $dbPath = $basePath . '/db/mediabrain.sqlite';
        }
        $this->debugInfo['DB_PATH'] = $dbPath;
        $this->dbPath = $dbPath;

        // Determine the absolute path for diagnostics
        $absolutePath = $dbPath;

        if (!file_exists($dbPath)) {
          // ... (Keep your diagnostic block, it's great for debugging!) ...
          // But change this line to check the absolute path:
          $debugInfo['DIR_EXISTS'] = is_dir(dirname($dbPath)) ? 'YES' : 'NO';
        }

        try {
          // 🎯 PDO now gets the full /var/www/html/db/... path
          $this->db = new PDO("sqlite:" . $dbPath);
          $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
          $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // 👈 THIS DISABLES STRING EMULATION FOR NUMBERS GLOBALLY
        } catch (PDOException $e) {
          error_log("SQLITE_CONNECTION_FAILED: " . $e->getMessage());
        }
        break;
    }
  }
}
