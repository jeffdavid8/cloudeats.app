<?php
// Secure the entry point
if (!defined('MB_RUNNING')) exit;
/**
 * Centralized Admin App for User Management and Authentication
 */

function admin_info()
{
    return [
        'title' => 'Admin Center',
        'description' => 'Centralized user management and authentication for all applications',
        'icon' => '<i class="material-icons">admin_panel_settings</i>',
        'version' => '1.0.0',
        'requires_auth' => true,
        'requires_admin' => true,
        'no_header' => false,
        'public_app' => false
    ];
}

/**
 * Check if current user is logged in
 */
function admin_user_logged_in()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']);
}

/**
 * Check if current user has admin privileges
 */
function admin_user_is_admin()
{
    if (!admin_user_logged_in()) {
        return false;
    }

    return App::getInstance()->authManager->userIsAdmin($_SESSION['user']);
}

/**
 * Require user to be logged in
 */
function admin_require_login()
{
    if (!admin_user_logged_in()) {
        App::getInstance()->authManager::requireLogin();
    }
}

/**
 * Require user to have admin privileges
 */
function admin_require_admin()
{
    admin_require_login();

    if (!admin_user_is_admin()) {
        App::getInstance()->authManager::requireAdmin();
    }
}

function admin_init(&$app)
{
    // Initialize components
    $userManager = new UserManager();

    $app->set('userManager', $userManager);

    // Handle page routing
    $page = $_GET['p'] ?? 'dashboard';
    $app->set('page', $page);

    // Always require authentication
    admin_require_login();

    switch ($page) {

        case 'download_db':
            admin_require_admin();
            $app->processAction('db/download');
            break;
    }

    // Require admin for user management pages
    if (in_array($page, ['users', 'settings'])) {
        admin_require_admin();
    }
}


function admin_db_tables()
{
    return array(
        'users',
        'permissions_registry',
        'user_permissions_map',
    );
}

function admin_install_db()
{
    $app = App::getInstance();

    //echo "🔨 Building Identity Foundations...<br>";

    // Updated SQLite data types to appropriate MySQL equivalents (INT, VARCHAR, TEXT, JSON, TINYINT)
    $tableSql = "
            SET FOREIGN_KEY_CHECKS = 0;
            DROP TABLE IF EXISTS users;
            DROP TABLE IF EXISTS permissions_registry;
            DROP TABLE IF EXISTS user_permissions_map;
            SET FOREIGN_KEY_CHECKS = 1;

            -- 👤 USER TABLE
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(180) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                picture VARCHAR(2048),
                oauth_provider VARCHAR(50),
                oauth_profile_url VARCHAR(2048),
                oauth_providers JSON NOT NULL, -- Upgraded to Native MySQL JSON Engine Workspace
                role VARCHAR(50) DEFAULT 'user',
                is_admin TINYINT DEFAULT 0,
                stripe_connect_id VARCHAR(255),
                active TINYINT DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL,
                modified_at TIMESTAMP NULL DEFAULT NULL,
                last_login TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS permissions_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                perm_key VARCHAR(180) UNIQUE NOT NULL, 
                title VARCHAR(255),
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS user_permissions_map (
                user_id INT NOT NULL,
                perm_id INT NOT NULL,
                action VARCHAR(50) NOT NULL, 
                PRIMARY KEY (user_id, perm_id, action),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (perm_id) REFERENCES permissions_registry(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";

    // Split statements safely assuming individual executions
    $log = [];
    foreach (explode(';', $tableSql) as $q) {
        $q = trim($q);
        $cleaned = str_replace("\r\n", "\n", $q);
        $cleanSQL = preg_replace('/\s+/', ' ', $cleaned);
        if ($q) {
            try {
                error_log('-----------------------------------------------------');
                error_log('Running Admin Query - ');
                error_log($cleanSQL);
                error_log('-----------------------------------------------------');
                $app->db->exec($q);

                $log[] = '-----------------------------------------------------';
                $log[] = "Running Admin Query - ";
                $log[] = $cleanSQL;
                $log[] = '-----------------------------------------------------';
            } catch (PDOException $e) {
                error_log('-----------------------------------------------------');
                error_log($e->getMessage());
                error_log('-----------------------------------------------------');
                if (strpos($e->getMessage(), 'duplicate column name') === false) {
                    error_log("Admin Installer Warning: " . $e->getMessage());
                    $log[] = "Admin Installer Warning: " . $e->getMessage();
                }
            }
        }
    }
    return [
        'success' => true,
        'log'     => $log
    ];

    //echo "✅ User Tables Synced.<br>";
}


function admin_restore_db()
{
    $targetMap = array('admin' => admin_db_tables());

    $result = BackupManager::importFromJsonFile('./json/default_db.json', $targetMap);

    return $result;
}


/**
 * Handle admin API requests
 */
function admin_handle_api()
{
    admin_require_admin();

    $api = $_GET['api'] ?? '';

    switch ($api) {
        case 'themes':
        case 'switch-theme':
            require_once __DIR__ . '/api/theme-api.php';
            $themeAPI = new ThemeAPI();
            $themeAPI->handleRequest();
            break;

        case 'dashboard-stats':
            admin_api_dashboard_stats();
            break;

        default:
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
            exit;
    }
}

/**
 * Dashboard statistics API
 */
function admin_api_dashboard_stats()
{
    header('Content-Type: application/json');

    try {
        $userManager = new UserManager();
        $allUsers = $userManager->getAllUsers();

        $stats = [
            'total_users' => count($allUsers),
            'recent_logins' => 0,
            'storage_used' => '0 MB',
            'system_uptime' => 'Unknown'
        ];

        // Calculate recent logins (last 24 hours)
        $recentTime = time() - (24 * 60 * 60);
        foreach ($allUsers as $user) {
            if (isset($user['last_login']) && $user['last_login'] > $recentTime) {
                $stats['recent_logins']++;
            }
        }

        echo json_encode(['success' => true, 'stats' => $stats]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

function admin_render_body(&$app)
{
    // Handle API requests
    if (isset($_GET['api'])) {
        admin_handle_api();
        return;
    }

    // Include CSS
    echo '<link rel="stylesheet" href="apps/admin/css/admin.css">';

    try {
        // Authentication now handled by AppController - just render content
        // Render the admin app content
        $page = $_GET['p'] ?? 'dashboard';

        echo '<div class="admin-app">';
        //echo '<div class="container">';

        switch ($page) {

            case 'init_db':
                admin_require_admin();
                $app->processAction('db/init_db');
                break;
            case 'init_users':
                admin_require_admin();
                $app->processAction('db/init_users');
                break;
            case 'populate_intelligence':
                admin_require_admin();
                $app->processAction('db/populate_intelligence');
                break;

            case 'install_db':
                admin_require_admin();
                $app->processAction('db/install_db');
                break;

            case 'restore_db':
                admin_require_admin();
                $tables = [];
                $tables[] = app_invoke('admin', 'db_tables');
                $tables[] = app_invoke('stitch', 'db_tables');
                $tables[] = app_invoke('neighborhub', 'db_tables');
                $tables = array_reduce($tables, 'array_merge', []);

                render('pages/restore_db.php', array('tables' => $tables));
                break;

            case 'backup_db':
                admin_require_admin();
                $app->processAction('db/backup_db');
                break;


            case 'users':
                admin_require_admin();
                if (isset($_GET['action']) && $_GET['action'] === 'add') {
                    include __DIR__ . '/views/user_form.php';
                } elseif (isset($_GET['action']) && $_GET['action'] === 'edit') {
                    include __DIR__ . '/views/user_form.php';
                } else {
                    include __DIR__ . '/views/users.php';
                }
                break;
            case 'permissions':
                admin_require_admin();
                include __DIR__ . '/views/permissions.php';
                break;
            case 'roles':
                admin_require_admin();
                if (isset($_GET['action']) && $_GET['action'] === 'add') {
                    include __DIR__ . '/views/role_form.php';
                } elseif (isset($_GET['action']) && $_GET['action'] === 'edit') {
                    include __DIR__ . '/views/role_form.php';
                } else {
                    include __DIR__ . '/views/roles.php';
                }
                break;
            case 'logs':
                admin_require_admin();
                include __DIR__ . '/views/logs.php';
                break;
            case 'analytics':
                admin_require_admin();
                include __DIR__ . '/views/analytics.php';
                break;
            case 'profile':
                admin_require_login();
                include __DIR__ . '/views/profile.php';
                break;
            case 'tests':
                admin_require_admin();
                include __DIR__ . '/views/tests.php';
                break;
            case 'phpunit-tests':
                admin_require_admin();
                include __DIR__ . '/views/phpunit-tests.php';
                break;
            case 'settings':
                admin_require_admin();
                include __DIR__ . '/views/settings.php';
                break;
            case 'ancestry_family':
                admin_require_admin();

                // Try to handle this route via app hooks
                $handled = false;
                $appDashboards = app_invoke_all('hook_admin_dashboard');
                foreach ($appDashboards as $appName => $dashboardData) {
                    if (isset($dashboardData['admin_routes']['ancestry_family'])) {
                        $routeData = $dashboardData['admin_routes']['ancestry_family'];
                        $routeFile = __DIR__ . '/../' . $appName . '/' . $routeData['file'];

                        if (file_exists($routeFile)) {
                            include $routeFile;
                            $handled = true;
                            break;
                        }
                    }
                }

                if (!$handled) {
                    echo '<div class="card red lighten-4"><div class="card-content">';
                    echo '<span class="card-title">Route Not Found</span>';
                    echo '<p>The ancestry_family route is no longer available. It may have been moved or removed.</p>';
                    echo '</div></div>';
                }
                break;
            case 'dashboard':
            default:
                admin_require_login();
                $app->render('dashboard.php');
                break;
        }

        //echo '</div>'; // Close container
        echo '</div>'; // Close admin-app

    } catch (Exception $e) {
        echo '<div class="card red lighten-4">';
        echo '<div class="card-content">';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<details><summary>Debug Info</summary><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></details>';
        echo '</div>';
        echo '</div>';
        echo '<div class="card red lighten-4">';
        echo '<div class="card-content">';
        $credsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
        if ($credsPath && file_exists($credsPath)) {
            $creds = json_decode(file_get_contents($credsPath), true);
            echo '<p>Service Account: ' . htmlspecialchars($creds['client_email'] ?? 'N/A') . '</p>';
            echo '<p>Project ID: ' . htmlspecialchars($creds['project_id'] ?? 'N/A') . '</p>';
            echo '<p>Private Key ID: ' . htmlspecialchars($creds['private_key_id'] ?? 'N/A') . '</p>';
        } else {
            echo '<p>GOOGLE_APPLICATION_CREDENTIALS not set or file does not exist.</p>';
            $metadataUrl = 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/email';
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Metadata-Flavor: Google'
                ]
            ];
            $context = stream_context_create($opts);
            $email = @file_get_contents($metadataUrl, false, $context);
            echo '<p>Service Account: ' . htmlspecialchars($email) . '</p>';
        }
        echo '<details><summary>Debug Info</summary><pre>' . htmlspecialchars(print_r($caller, true)) . '</pre></details>';
        echo '</div>';
        echo '</div>';
    } catch (Error $e) {
        echo '<div class="container">';
        echo '<div class="card red lighten-4">';
        echo '<div class="card-content">';
        echo '<span class="card-title red-text">Admin App Fatal Error</span>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<details><summary>Debug Info</summary><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></details>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}

/**
 * Admin app dashboard hook - contribute TTS management widget
 */
function admin_hook_admin_dashboard()
{

    return [
        'dashboard_widgets' => [
            [
                'title' => 'Text-to-Speech Management',
                'icon' => '<i class="material-icons left">record_voice_over</i>',
                'priority' => 90, // High priority to show near top
                'content_callback' => 'admin_render_tts_dashboard_widget'
            ]
        ]
    ];
}

/**
 * Render TTS management dashboard widget
 */
function admin_render_tts_dashboard_widget()
{
    // Collect TTS statistics
    $stats = [
        'users_with_preferences' => 0,
        'admin_config_status' => 'Not Configured',
        'total_voices_available' => 17,
        'default_voice' => 'Not Set'
    ];

    try {
        // Simple count - just check if any user preference files exist
        $prefsPath = __DIR__ . '/../../storage/user_preferences/';
        if (is_dir($prefsPath)) {
            $userDirs = glob($prefsPath . '*', GLOB_ONLYDIR);
            foreach ($userDirs as $userDir) {
                $ttsFile = $userDir . '/tts_preferences.json';
                if (file_exists($ttsFile)) {
                    $stats['users_with_preferences']++;
                }
            }
        }

        // Check for admin TTS config file
        $adminConfigPath = __DIR__ . '/../../storage/admin_tts_config.json';
        if (file_exists($adminConfigPath)) {
            $adminConfig = json_decode(file_get_contents($adminConfigPath), true);
            if (!empty($adminConfig)) {
                $stats['admin_config_status'] = 'Configured';
                $stats['default_voice'] = $adminConfig['voice'] ?? 'Default';
            }
        }
    } catch (Exception $e) {
        error_log("Error loading TTS stats: " . $e->getMessage());
    }

    ob_start();
?>
    <div class="card">
        <div class="card-content">

            <div class="row">
                <div class="col s6">
                    <div class="statistic">
                        <h4 class="blue-text"><?= $stats['users_with_preferences'] ?></h4>
                        <p class="grey-text">Users with TTS Preferences</p>
                    </div>
                </div>
                <div class="col s6">
                    <div class="statistic">
                        <h4 class="<?= $stats['admin_config_status'] === 'Configured' ? 'green-text' : 'orange-text' ?>">
                            <?= $stats['total_voices_available'] ?>
                        </h4>
                        <p class="grey-text">Available Voices</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col s12">
                    <div class="chip <?= $stats['admin_config_status'] === 'Configured' ? 'green lighten-4' : 'orange lighten-4' ?>">
                        <i class="material-icons tiny">settings</i>
                        Admin Config: <?= $stats['admin_config_status'] ?>
                    </div>
                    <?php if ($stats['admin_config_status'] === 'Configured'): ?>
                        <div class="chip blue lighten-4">
                            <i class="material-icons tiny">voice_over_off</i>
                            Default Voice: <?= $stats['default_voice'] ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-action">
                <a href="?app=admin&p=settings#tts-config" class="blue-text">
                    <i class="material-icons tiny left">settings</i>
                    Admin TTS Settings
                </a>
                <a href="?app=admin&p=profile#tts-preferences" class="blue-text">
                    <i class="material-icons tiny left">account_circle</i>
                    My TTS Preferences
                </a>
                <a href="?app=admin&p=users" class="blue-text">
                    <i class="material-icons tiny left">people</i>
                    Manage Users
                </a>
            </div>
        </div>
    </div>

    <style>
        .statistic {
            text-align: center;
            padding: 10px 0;
        }

        .statistic h4 {
            margin: 0 0 5px 0;
            font-size: 2rem;
            font-weight: 300;
        }

        .statistic p {
            margin: 0;
            font-size: 0.9rem;
        }

        .chip {
            margin: 2px;
            display: inline-flex;
            align-items: center;
        }

        .chip i {
            margin-right: 5px;
        }
    </style>
<?php
    return ob_get_clean();
}
