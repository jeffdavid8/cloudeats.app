<?php
define('MB_RUNNING', true);
define('ROOT_PATH', __DIR__);
ini_set('display_errors', 'On');
//error_reporting(E_ALL);
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);
date_default_timezone_set('America/Indiana/Indianapolis');
echo 'here'; die();
// Set the absolute path to your log file
require_once __DIR__ . '/includes/mb.bootstrap.php';

use MediaBrain\Services\AnalyticsService;

// Track page view for analytics (after session started, before API routing)
try {
    $analytics = AnalyticsService::getInstance();
    $analytics->trackPageView();
} catch (Exception $e) {
    // Silently fail if analytics tracking encounters errors
    error_log("Analytics tracking error: " . $e->getMessage());
}

// Handle API requests first (before any app initialization)
$api_app = get_var('api');
if (!empty($api_app)) {
    // Route to app-specific API file
    $api_file = __DIR__ . "/apps/{$api_app}/{$api_app}.api.php";

    if (file_exists($api_file)) {
        // Set basic headers for API response
        //setJsonHeader();
        //$csrf_token = $params['data']['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';

        // Use AuthManager for CSRF validation
        $auth = App::getInstance($api_app)->getAuthManager();

        if (validate_csrf_request()) {
            // CSRF token is valid
            // Include and execute the API file
            include $api_file;
            exit;
        } else {
            //error_log("TTS CSRF Warning - Token validation failed: received='" . $csrf_token . "', session='" . ($_SESSION['csrf_token'] ?? 'NULL') . "'");
            if (empty($csrf_token)) {
                http_response_code(403);
                echo json_encode(array('success' => false, 'error' => 'CSRF token required'));
                return;
            }
        }
    } else if ($api_app === 'mediabrain') {
        include 'api.php';
        exit;
    } else {
        // API file not found
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => "API not found for app: {$api_app}"]);
        exit;
    }
}


// Handle authentication and routing BEFORE any rendering
if ((get_var('p', false)) && (!get_var('app', false))) {
    // Use PageController for page requests

    mb_require('includes/PageController.php');
    $pageController = new PageController(get_var('p'));

    if ($pageController->pageExists()) {
        $pageController->handleRequest();
    } else {
        // Only render 404 if page doesn't exist
        render('components/head.php');
?>

        <body>
            <?php
            render('components/header/header.php');
            render('pages/error/404.php', array('message' => "Sorry, could not load the $page page<br/> Page not found"));
            render('components/footer.php');
            ?>
        </body>

        </html>
    <?php
    }
    exit();
} else {
    // Handle app requests - authentication check happens in AppController
    mb_require('includes/AppController.php');

    try {
        $appController = new AppController(get_var('app'));
        $appController->handleRequest();
    } catch (Exception $e) {
        // Create minimal app instance for error display
        render('components/head.php');
    ?>

        <body>
            <?php
            echo '<div class="container" style="margin-top: 20px;">';
            echo '<div class="card red lighten-4">';
            echo '<div class="card-content">';
            echo '<span class="card-title red-text">App Loading Error</span>';
            echo '<p><strong>App:</strong> ' . htmlspecialchars($app_name) . '</p>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>';
            echo '<details><summary>Stack Trace</summary><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></details>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            render('components/footer.php');
            ?>
        </body>

        </html>
<?php
    }
    exit();
}
?>