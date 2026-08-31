<?php
if (!defined('MB_RUNNING')) exit;


// Set error log location is handled by PHP-FPM container configuration
// ini_set('error_log', 'php://stderr');
// Enable error reporting for debugging
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// Block requests from known bad bots
if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'GPTBot') !== false) {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}
// Configure session to last for 30 days
ini_set('session.cookie_lifetime', 2592000);
ini_set('session.gc_maxlifetime', 2592000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load composer autoloader
$autoloadPath = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

require_once __DIR__ . '/util.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/SecurityHeaders.php';
require_once __DIR__ . '/EventLogger.php';
require_once __DIR__ . '/storage/FileStorageManager.php';
require_once __DIR__ . '/../includes/OAuthHandler.php';
require_once __DIR__ . '/../apps/admin/includes/UserManager.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/models/storage.model.php';


// Check/Run Post-Deployment Init DB Script
$host = $_SERVER['HTTP_HOST'] ?? '';
$is_development = (bool)preg_match('/localhost|127\\.0\\.0\\.1|\\.local|:8080|:3000|:8000/', $host);

if (file_exists('./json/default_db.json') && !$is_development) {
    $_SESSION['bypass_admin_key'] = true;

    // Start the shield
    ob_start(); 
    App::getInstance()->includeClass('BackupManager');

    //include './apps/admin/actions/db/init_users.action.php';
    //include './apps/admin/actions/db/init_db.action.php';
    $result = [];
    $result['install']['admin '] = app_invoke('admin', 'install_db');
    $result['install']['stitch '] = app_invoke('stitch', 'install_db');
    $result['install']['neighborhub'] = app_invoke('neighborhub', 'install_db');

    $result['restore']['admin '] = app_invoke('admin', 'restore_db');
    $result['restore']['stitch '] = app_invoke('stitch', 'restore_db');
    $result['restore']['neighborhub'] = app_invoke('neighborhub', 'restore_db');

    error_log('-------------------FULL DEPLOYMENT RESULTS----------------------------------');
    error_log(print_r($result, true));
    error_log('------------------------------------------------------------------------------------');
    error_log('------------------------------------------------------------------------------------');
    error_log(' ');
    error_log(' ');
    error_log(' ');

    unlink('./json/default_db.json');
    unset($_SESSION['bypass_admin_key']);
    
    // Redirect so the user never sees the "init" output residue
    header('Location: ' . $_SERVER['REQUEST_URI']);
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: text/html');
    exit();
}
