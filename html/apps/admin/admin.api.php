<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Admin API endpoints for AJAX requests
 */
// Enable error reporting for debugging

require_once __DIR__ . '/../../includes/SecurityHeaders.php';
require_once __DIR__ . '/../../includes/RateLimiter.php';

// Set API security headers early
SecurityHeaders::setAPIHeaders([
    'cors' => false // Disable CORS for admin API
]);
$app = App::getInstance();

// Rate limiting for admin API
if (!RateLimiter::checkAndRecord('api')) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Rate limit exceeded',
        'retry_after' => RateLimiter::getTimeUntilReset('api')
    ]);
    exit;
}

// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include admin helper functions
if (!function_exists('admin_user_logged_in')) {
    function admin_user_logged_in()
    {
        return isset($_SESSION['user']);
    }
}

if (!function_exists('admin_user_is_admin')) {
    function admin_user_is_admin()
    {
        if (!admin_user_logged_in()) {
            return false;
        }

        return $app->authManager->userIsAdmin($_SESSION['user']);
    }
}

if (!function_exists('admin_require_login')) {
    function admin_require_login()
    {
        if (!admin_user_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
}

if (!function_exists('admin_require_admin')) {
    function admin_require_admin()
    {
        admin_require_login();

        if (!admin_user_is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin privileges required']);
            exit;
        }
    }
}

// Set JSON content type for all responses
header('Content-Type: application/json');

// Get the action parameter
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
    exit;
}

// Define which actions require CSRF protection (all state-changing operations)
$csrf_protected_actions = [
    'add_user',
    'update_user',
    'update_profile',
    'change_password',
    'delete_user',
    'update_user_permissions',
    'initialize_permissions',
    'storage_switch',
    'storage_migrate',
    'save_oauth_config',
    'unlink_oauth_provider',
    'create_role',
    'update_role',
    'delete_role',
    'toggle_event_logging',
    'clear_event_log',
    'migrate_json_file'
];

// Validate CSRF token for protected actions
if (in_array($action, $csrf_protected_actions)) {
    // Handle JSON input
    $rawInput = file_get_contents('php://input');
    $jsonData = $rawInput ? json_decode($rawInput, true) : null;

    $csrf_token = $jsonData['_csrf'] ?? $_GET['_csrf'] ?? $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!AuthManager::validateCsrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// Initialize components
$app = App::getInstance();
$userManager = new UserManager();

// Import Analytics Service for analytics endpoints
use MediaBrain\Services\AnalyticsService;

switch ($action) {
    case 'login':
        // Admin login now handled by main authentication system
        // Return redirect instruction to client
        http_response_code(401);
        echo json_encode([
            'error' => 'Please use main login system',
            'redirect' => '/?app=admin&p=login&return_url=' . urlencode($_SERVER['REQUEST_URI'] ?? '/?app=admin')
        ]);
        break;

    case 'logout':
        session_destroy();
        echo json_encode(['success' => true]);
        break;

    case 'check_auth':
        echo json_encode([
            'logged_in' => admin_user_logged_in(),
            'is_admin' => admin_user_is_admin(),
            'user' => $_SESSION['user'] ?? null
        ]);
        break;

    case 'restore_db':
        $db = $app->db;
        $results = [];
        $uploadedFile = $_FILES['db_file']['tmp_name'];
        $targetTables = $_POST['target_tables'] ?? [];

        if (empty($targetTables) || !is_array($targetTables)) {
            http_response_code(400);
            echo "ERROR: Restructure matrix failure. Zero explicit table elements specified.";
            exit;
        }

        // 3. Extract and Verify Payload Contents
        $payloadContent = file_get_contents($uploadedFile);
        $data = json_decode($payloadContent, true);

        // 1. DEACTIVATE CONSTRAINTS TO ALLOW OUT-OF-ORDER SEEDING
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

        // 2. BEGIN ATOMIC ISOLATION TRANSACTION BLOCK
        $db->exec("START TRANSACTION;");

        // Drop and Re-Create Tables
        /*
        $results['install']['admin '] = app_invoke('admin', 'install_db');
        $results['install']['stitch '] = app_invoke('stitch', 'install_db');
        $results['install']['neighborhub'] = app_invoke('neighborhub', 'install_db');
        */

        foreach ($targetTables as $table) {
            try {
                // Clear existing table content to prevent UNIQUE key/PKey constraint collisons
                $db->exec("DELETE FROM {$table};");
            } catch (PDOException $e) {
                // Table doesn't exist, do nothing or log the error
                $results['restore'][$table][] = "Table [{$table}] does not exist";
            }

            if (!isset($data['tables'][$table]) || empty($data['tables'][$table])) {
                $results['restore'][$table][] = "Table [{$table}] cleared, no backup dataset rows to ingest.";
                continue;
            }

            $rows = $data['tables'][$table];

            // Grab array columns dynamically from the first payload chunk element
            $sampleRow = $rows[0];
            $columns = array_keys($sampleRow);

            // Construct statement strings mapping query variables safely
            $columnList = implode(', ', $columns);
            $placeholderList = ':' . implode(', :', $columns);

            $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholderList})";
            $stmt = $db->prepare($sql);

            $rowCount = 0;
            foreach ($rows as $row) {
                $bindArray = [];
                foreach ($row as $columnName => $value) {
                    $bindArray[':' . $columnName] = $value;
                }
                $stmt->execute($bindArray);
                $rowCount++;
            }

            $results['restore'][$table][] = "Successfully restored {$rowCount} record segments into [{$table}].";
        }

        // 3. SECURELY COMMIT TRANSFERS DOCKING CHUNKS TO PERMANENT DISK STORAGE
        $db->exec("COMMIT;");

        // 4. REACTIVATE INTEGRITY LAYER ENFORCEMENT RULES
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");


        error_log('-------------------FULL DEPLOYMENT RESULTS----------------------------------');
        error_log(print_r($results, true));
        error_log('------------------------------------------------------------------------------------');
        error_log('------------------------------------------------------------------------------------');
        error_log(' ');
        error_log(' ');
        error_log(' ');

        echo json_encode([
            'success' => true,
            'data' => array(
                'tables_restored' => $targetTables,
                'results' => $results,
            ),
        ]);
        break;

    case 'users':
        admin_require_admin();
        echo json_encode($userManager->getAllUsers());
        break;

    case 'add_user':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $result = $userManager->addUser($input);
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;

    case 'update_user':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = $_GET['user'] ?? $input['username'] ?? '';
        // Handle profile image upload if provided
        if (isset($_FILES['profileImageFile']) && $_FILES['profileImageFile']['error'] === UPLOAD_ERR_OK) {
            $imageManager = new ProfileImageManager();

            // Get current user to check for existing image
            $currentUser = $userManager->getUser($username);

            // Delete old image if exists
            if ($currentUser && !empty($currentUser['profileImageFilename'])) {
                $imageManager->deleteProfileImage($currentUser['profileImageFilename']);
            }

            // Upload new image using the universal storage system
            $uploadResult = $imageManager->uploadProfileImage($_FILES['profileImageFile'], $username);

            if ($uploadResult['success']) {
                $input['profilePicture'] = $uploadResult['url'];
                $input['profileImageFilename'] = $uploadResult['filename'];
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Image upload failed: ' . $uploadResult['error']]);
                break;
            }
        }

        // Handle profile image removal
        if (isset($input['removeProfilePicture']) && $input['removeProfilePicture']) {
            $imageManager = new ProfileImageManager();

            // Get current user to get filename
            $currentUser = $userManager->getUser($username);
            if ($currentUser && !empty($currentUser['profileImageFilename'])) {
                $imageManager->deleteProfileImage($currentUser['profileImageFilename']);
            }

            $input['profilePicture'] = '';
            $input['profileImageFilename'] = '';
        }

        // Update basic user info
        $result = $userManager->updateUser($username, $input);

        if ($result['success']) {
            // Handle granular permissions if provided
            if (isset($input['app_access']) || isset($input['feature_access']) || isset($input['action_access'])) {
                $permissionsMatrix = new PermissionsMatrix();

                $permissionsMatrix->clearUserPermissionsCache();

                // Set app-level permissions
                $appAccess = $input['app_access'] ?? [];
                foreach ($appAccess as $appName) {
                    $permissionsMatrix->setUserPermissionCache($username, "apps.{$appName}", ['access']);
                }

                // Set feature-level permissions
                $featureAccess = $input['feature_access'] ?? [];
                foreach ($featureAccess as $appName => $features) {
                    if (is_array($features)) {
                        foreach ($features as $featureName) {
                            $permissionsMatrix->setUserPermissionCache($username, "apps.{$appName}.features.{$featureName}", ['access']);
                        }
                    }
                }

                // Set action-level permissions
                $actionAccess = $input['action_access'] ?? [];
                foreach ($actionAccess as $appName => $features) {
                    if (is_array($features)) {
                        foreach ($features as $featureName => $actions) {
                            if (is_array($actions)) {
                                $permissionsMatrix->setUserPermissionCache($username, "apps.{$appName}.features.{$featureName}", $actions);
                            }
                        }
                    }
                }

                $permissionsMatrix->saveUserPermissionsCache();
            }

            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;

    case 'update_profile':
        admin_require_login();
        $input = $_POST;
        $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];

        // Update email if provided and current password is correct
        if (!empty($input['email']) && !empty($input['current_password'])) {
            if (!$app->authManager->checkCredentials($username, $input['current_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect']);
                break;
            }

            $result = $userManager->updateUser($username, ['email' => $input['email']]);
            echo json_encode($result);
            break;
        }

        // Update password if provided
        if (!empty($input['new_password'])) {
            $oldPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (empty($oldPassword) || empty($newPassword)) {
                http_response_code(400);
                echo json_encode(['error' => 'Current and new passwords are required']);
                break;
            }

            if ($app->authManager->checkCredentials($username, $oldPassword)) {
                $result = $userManager->updateUser($username, ['password' => $newPassword]);

                if ($result['success']) {
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode($result);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect']);
            }
            break;
        }

        http_response_code(400);
        echo json_encode(['error' => 'No valid data provided']);
        break;

    case 'change_password':
        admin_require_login();
        $input = $_POST;
        $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
        $oldPassword = $input['old_password'] ?? '';
        $newPassword = $input['new_password'] ?? '';

        if (empty($oldPassword) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['error' => 'Current and new passwords are required']);
            break;
        }

        if ($app->authManager->checkCredentials($username, $oldPassword)) {
            $result = $userManager->updateUser($username, ['password' => $newPassword]);

            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Current password is incorrect']);
        }
        break;

    case 'delete_user':
        admin_require_admin();
        $username = $_POST['username'] ?? '';
        $result = $userManager->deleteUser($username);
        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }
        break;

    case 'get_user_permissions':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();
        $username = $_GET['username'] ?? '';

        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username required']);
            break;
        }

        $userApps = $permissionsMatrix->getUserApps($username);
        $permissionsSummary = $permissionsMatrix->getPermissionsSummary();
        $userPermissions = $permissionsSummary['users'][$username] ?? ['role' => 'guest', 'custom_permissions' => []];

        echo json_encode([
            'success' => true,
            'permissions' => $userPermissions,
            'user_apps' => array_keys($userApps)
        ]);
        break;

    case 'update_user_permissions':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        $username = $_POST['username'] ?? '';
        $role = $_POST['role'] ?? 'user';
        $apps = $_POST['apps'] ?? [];

        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username required']);
            break;
        }

        try {
            // Set user role
            $permissionsMatrix->setUserRole($username, $role);

            // Get all available apps and role permissions
            $permissionsSummary = $permissionsMatrix->getPermissionsSummary();
            $allApps = array_keys($permissionsSummary['apps'] ?? []);
            $rolePermissions = $permissionsSummary['roles'][$role]['permissions'] ?? [];

            // Clear all existing custom app permissions and denials first
            foreach ($allApps as $appName) {
                $permissionsMatrix->removeUserPermission($username, "apps.{$appName}");
                $permissionsMatrix->removeDeniedPermission($username, "apps.{$appName}");
            }

            // For each app, determine what to do based on selection and role permissions
            foreach ($allApps as $appName) {
                $isSelected = in_array($appName, $apps);
                $grantedByRole = isset($rolePermissions["apps.{$appName}"]);

                if ($isSelected && !$grantedByRole) {
                    // App is selected but not granted by role → add custom permission
                    $permissionsMatrix->setUserPermission($username, "apps.{$appName}", ['access']);
                } elseif (!$isSelected && $grantedByRole) {
                    // App is not selected but granted by role → add denial
                    $permissionsMatrix->denyUserPermission($username, "apps.{$appName}", ['access']);
                }
                // If selected and granted by role → no action needed (role permission applies)
                // If not selected and not granted by role → no action needed (no access)
            }

            echo json_encode([
                'success' => true,
                'message' => 'User permissions updated successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update permissions: ' . $e->getMessage()]);
        }
        break;

    case 'initialize_permissions':
        admin_require_admin();

        try {
            // Force recreation of permissions files by deleting them first
            $permissionsFile = '/var/data/mediabrain/storage/system_data/permissions.json';
            $userPermissionsFile = '/var/data/mediabrain/storage/system_data/user_permissions.json';

            if (file_exists($permissionsFile)) {
                unlink($permissionsFile);
            }
            if (file_exists($userPermissionsFile)) {
                unlink($userPermissionsFile);
            }

            // Build new permissions array from current app structure
            $structure = App::getInstance()->structure();
            $apps = $structure['apps'] ?? [];
            $permissionsApps = [];
            foreach ($apps as $app) {
                // Extract app name from href (e.g., '?app=help' -> 'help')
                $appName = '';
                if (preg_match('/[?&]app=([^&]+)/', $app['href'], $matches)) {
                    $appName = $matches[1];
                }
                if (!$appName) continue;
                $permissionsApps[$appName] = [
                    'name' => $app['title'] ?? ucfirst($appName),
                    'description' => $app['description'] ?? '',
                    'features' => [] // Features can be added/edited later
                ];
            }

            // Minimal default roles for initialization
            $permissionsRoles = [
                'guest' => [
                    'name' => 'Guest User',
                    'description' => 'Anonymous users with access to public apps',
                    'permissions' => []
                ],
                'user' => [
                    'name' => 'Regular User',
                    'description' => 'Standard user with basic access',
                    'permissions' => []
                ],
                'admin' => [
                    'name' => 'Administrator',
                    'description' => 'Full system access and user management',
                    'permissions' => []
                ]
            ];

            $permissionsData = [
                'apps' => $permissionsApps,
                'roles' => $permissionsRoles
            ];

            file_put_contents($permissionsFile, json_encode($permissionsData, JSON_PRETTY_PRINT));

            // Default user permissions
            $defaultUserPermissions = [
                'admin' => [
                    'role' => 'admin',
                    'custom_permissions' => []
                ],
                'demo' => [
                    'role' => 'user',
                    'custom_permissions' => []
                ],
                'guest' => [
                    'role' => 'guest',
                    'custom_permissions' => []
                ]
            ];
            file_put_contents($userPermissionsFile, json_encode($defaultUserPermissions, JSON_PRETTY_PRINT));

            echo json_encode([
                'success' => true,
                'message' => 'Permissions system initialized from app structure.'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to initialize permissions: ' . $e->getMessage()]);
        }
        break;

    case 'get_app_user_counts':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        try {
            $summary = $permissionsMatrix->getPermissionsSummary();
            $counts = [];

            foreach ($summary['apps'] as $appName => $appConfig) {
                $count = 0;
                foreach ($summary['users'] as $username => $userPerms) {
                    $userApps = $permissionsMatrix->getUserApps($username);
                    if (isset($userApps[$appName])) {
                        $count++;
                    }
                }
                $counts[$appName] = $count;
            }

            echo json_encode(['success' => true, 'counts' => $counts]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get app user counts: ' . $e->getMessage()]);
        }
        break;

    case 'get_app_users':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();
        $appName = $_GET['app'] ?? '';

        if (empty($appName)) {
            http_response_code(400);
            echo json_encode(['error' => 'App name required']);
            break;
        }

        try {
            $summary = $permissionsMatrix->getPermissionsSummary();
            $appUsers = [];

            foreach ($summary['users'] as $username => $userPerms) {
                $userApps = $permissionsMatrix->getUserApps($username);
                if (isset($userApps[$appName])) {
                    $appUsers[] = [
                        'username' => $username,
                        'role' => $userPerms['role'] ?? 'user'
                    ];
                }
            }

            echo json_encode(['success' => true, 'users' => $appUsers]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get app users: ' . $e->getMessage()]);
        }
        break;

    // Storage Management Endpoints
    case 'storage_status':
        admin_require_admin();
        try {
            $storage = FileStorageManager::getInstance();
            $info = $storage->getProviderInfo();
            echo json_encode(['success' => true, 'storage' => $info]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get storage status: ' . $e->getMessage()]);
        }
        break;

    case 'storage_switch':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $provider = $input['provider'] ?? '';
        $config = $input['config'] ?? [];

        if (empty($provider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Provider required']);
            break;
        }

        try {
            $storage = FileStorageManager::getInstance();
            $result = $storage->switchProvider($provider, $config);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to switch provider: ' . $e->getMessage()]);
        }
        break;

    case 'storage_migrate':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sourceProvider = $input['source_provider'] ?? '';
        $targetProvider = $input['target_provider'] ?? '';
        $sourceConfig = $input['source_config'] ?? [];
        $targetConfig = $input['target_config'] ?? [];
        $options = $input['options'] ?? [];

        // If source provider is empty, try to detect current storage or default to local
        if (empty($sourceProvider)) {
            try {
                $storage = FileStorageManager::getInstance();
                $info = $storage->getProviderInfo();
                $sourceProvider = $info['type'] ?? 'local';
            } catch (Exception $e) {
                // Default to local if detection fails
                $sourceProvider = 'local';
            }
        }

        if (empty($sourceProvider) || empty($targetProvider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Source and target providers required']);
            break;
        }

        try {
            $migrationManager = new StorageMigrationManager($sourceProvider, $targetProvider, $sourceConfig, $targetConfig);

            // Set up progress tracking for real-time updates
            $progressFile = '/tmp/migration_progress_' . session_id() . '.json';
            $options['progress_callback'] = function ($progress) use ($progressFile) {
                file_put_contents($progressFile, json_encode($progress));
            };

            $result = $migrationManager->migrateAllFiles($options);

            // Clean up progress file
            if (file_exists($progressFile)) {
                unlink($progressFile);
            }

            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Migration failed: ' . $e->getMessage()]);
        }
        break;

    case 'storage_migration_progress':
        admin_require_admin();
        $progressFile = '/tmp/migration_progress_' . session_id() . '.json';

        if (file_exists($progressFile)) {
            $progress = json_decode(file_get_contents($progressFile), true);
            echo json_encode(['success' => true, 'progress' => $progress]);
        } else {
            echo json_encode(['success' => true, 'progress' => null]);
        }
        break;

    case 'storage_migration_estimate':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sourceProvider = $input['source_provider'] ?? '';
        $sourceConfig = $input['source_config'] ?? [];

        // If source provider is empty, try to detect current storage or default to local
        if (empty($sourceProvider)) {
            try {
                $storage = FileStorageManager::getInstance();
                $info = $storage->getProviderInfo();
                $sourceProvider = $info['type'] ?? 'local';
            } catch (Exception $e) {
                // Default to local if detection fails
                $sourceProvider = 'local';
            }
        }

        if (empty($sourceProvider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Source provider required']);
            break;
        }

        try {
            $migrationManager = new StorageMigrationManager($sourceProvider, 'temp', $sourceConfig, []);
            $estimate = $migrationManager->estimateMigration();
            echo json_encode(['success' => true, 'estimate' => $estimate]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to estimate migration: ' . $e->getMessage()]);
        }
        break;

    case 'storage_migration_history':
        admin_require_admin();
        try {
            // Use any migration manager instance to get history
            $migrationManager = new StorageMigrationManager('local', 'local');
            $history = $migrationManager->getMigrationHistory();
            echo json_encode($history);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get migration history: ' . $e->getMessage()]);
        }
        break;

    // OAuth Management Endpoints
    case 'check_oauth_config':
        try {
            $oauthHandler = new OAuthHandler();

            $config = $oauthHandler->getConfig();
            $providers = [
                'google' => [
                    'enabled' => $config['google']['enabled'] && $oauthHandler->isProviderConfigured('google')
                ],
                'apple' => [
                    'enabled' => $config['apple']['enabled'] && $oauthHandler->isProviderConfigured('apple')
                ],
                'facebook' => [
                    'enabled' => $config['facebook']['enabled'] && $oauthHandler->isProviderConfigured('facebook')
                ],
                'linkedin' => [
                    'enabled' => $config['linkedin']['enabled'] && $oauthHandler->isProviderConfigured('linkedin')
                ],
            ];

            echo json_encode(['success' => true, 'providers' => $providers]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_oauth_config':
        admin_require_admin();
        try {
            $oauthHandler = new OAuthHandler();

            $config = $oauthHandler->getConfig();

            // Remove sensitive data for frontend
            if (isset($config['google']['client_secret'])) {
                $config['google']['client_secret'] = !empty($config['google']['client_secret']);
            }
            if (isset($config['apple']['private_key_path'])) {
                $config['apple']['has_private_key'] = file_exists($config['apple']['private_key_path']);
            }
            if (isset($config['facebook']['client_secret'])) {
                $config['facebook']['client_secret'] = !empty($config['facebook']['client_secret']);
            }
            if (isset($config['linkedin']['client_secret'])) {
                $config['linkedin']['client_secret'] = !empty($config['linkedin']['client_secret']);
            }

            echo json_encode(['success' => true, 'config' => $config]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load OAuth configuration: ' . $e->getMessage()]);
        }
        break;

    case 'save_oauth_config':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        try {
            $oauthHandler = new OAuthHandler();

            $config = [];

            // Process Google configuration
            if (isset($input['google_oauth_enabled'])) {
                $config['google'] = [
                    'enabled' => $input['google_oauth_enabled']
                ];

                if (!empty($input['google_client_id'])) {
                    $config['google']['client_id'] = $input['google_client_id'];
                }

                if (!empty($input['google_client_secret'])) {
                    $config['google']['client_secret'] = $input['google_client_secret'];
                }
            }

            // Process Apple configuration
            if (isset($input['apple_oauth_enabled'])) {
                $config['apple'] = [
                    'enabled' => $input['apple_oauth_enabled']
                ];

                if (!empty($input['apple_client_id'])) {
                    $config['apple']['client_id'] = $input['apple_client_id'];
                }

                if (!empty($input['apple_team_id'])) {
                    $config['apple']['team_id'] = $input['apple_team_id'];
                }

                if (!empty($input['apple_key_id'])) {
                    $config['apple']['key_id'] = $input['apple_key_id'];
                }

                // Handle Apple private key
                if (!empty($input['apple_private_key_content'])) {
                    $keyPath = '/var/data/mediabrain/apple_private_key.p8';
                    $result = file_put_contents($keyPath, $input['apple_private_key_content']);
                    if ($result === false) {
                        throw new Exception('Failed to save Apple private key');
                    }
                    chmod($keyPath, 0600); // Secure permissions
                }
            }

            // Process Facebook configuration
            if (isset($input['facebook_oauth_enabled'])) {
                $config['facebook'] = [
                    'enabled' => $input['facebook_oauth_enabled']
                ];

                if (!empty($input['facebook_client_id'])) {
                    $config['facebook']['client_id'] = $input['facebook_client_id'];
                }

                if (!empty($input['facebook_client_secret']) && $input['facebook_client_secret'] !== '••••••••') {
                    // Only update client_secret if it's not the masked placeholder
                    $config['facebook']['client_secret'] = $input['facebook_client_secret'];
                }
            }

            $oauthHandler->saveConfig($config);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save OAuth configuration: ' . $e->getMessage()]);
        }
        break;

    case 'test_oauth_config':
        admin_require_admin();
        try {
            $oauthHandler = new OAuthHandler();

            $results = [
                'google' => $oauthHandler->testProviderConfig('google'),
                'apple' => $oauthHandler->testProviderConfig('apple'),
                'facebook' => $oauthHandler->testProviderConfig('facebook')
            ];

            echo json_encode(['success' => true, 'results' => $results]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'OAuth test failed: ' . $e->getMessage()]);
        }
        break;

    case 'oauth_user_info':
        admin_require_admin();
        $username = $_GET['username'] ?? '';

        if (empty($username)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username required']);
            break;
        }

        try {
            $user = $userManager->getUser($username);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }

            $oauthProviders = $user['oauth_providers'] ?? [];
            echo json_encode(['success' => true, 'oauth_providers' => $oauthProviders]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get OAuth info: ' . $e->getMessage()]);
        }
        break;

    case 'unlink_oauth_provider':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = $input['username'] ?? '';
        $provider = $input['provider'] ?? '';

        if (empty($username) || empty($provider)) {
            http_response_code(400);
            echo json_encode(['error' => 'Username and provider required']);
            break;
        }

        try {
            $user = $userManager->getUser($username);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }

            if (isset($user['oauth_providers'][$provider])) {
                unset($user['oauth_providers'][$provider]);
                $userManager->updateUser($username, $user);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to unlink OAuth provider: ' . $e->getMessage()]);
        }
        break;

    // Role Management Endpoints
    case 'get_role_user_counts':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        try {
            $summary = $permissionsMatrix->getPermissionsSummary();
            $counts = [];

            foreach ($summary['roles'] as $roleName => $roleConfig) {
                $count = 0;
                foreach ($summary['users'] as $username => $userPerms) {
                    if (($userPerms['role'] ?? 'guest') === $roleName) {
                        $count++;
                    }
                }
                $counts[$roleName] = $count;
            }

            echo json_encode(['success' => true, 'counts' => $counts]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get role user counts: ' . $e->getMessage()]);
        }
        break;

    case 'get_role_details':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();
        $roleName = $_GET['role'] ?? '';

        if (empty($roleName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Role name required']);
            break;
        }

        try {
            $summary = $permissionsMatrix->getPermissionsSummary();
            if (!isset($summary['roles'][$roleName])) {
                http_response_code(404);
                echo json_encode(['error' => 'Role not found']);
                break;
            }

            echo json_encode(['success' => true, 'role' => $summary['roles'][$roleName]]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get role details: ' . $e->getMessage()]);
        }
        break;

    case 'create_role':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $roleKey = $input['role_key'] ?? '';
        $roleName = $input['role_name'] ?? '';
        $roleDescription = $input['role_description'] ?? '';
        $inheritRoles = $input['inherit_roles'] ?? [];
        $appAccess = $input['app_access'] ?? [];
        $featurePermissions = $input['feature_permissions'] ?? [];
        $systemPermissions = $input['system_permissions'] ?? [];

        if (empty($roleKey) || empty($roleName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Role key and name are required']);
            break;
        }

        try {
            $result = $permissionsMatrix->createRole($roleKey, $roleName, $roleDescription, $inheritRoles, $appAccess, $featurePermissions, $systemPermissions);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Role created successfully']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create role: ' . $e->getMessage()]);
        }
        break;

    case 'update_role':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $originalRoleKey = $input['original_role_key'] ?? '';
        $roleKey = $input['role_key'] ?? '';
        $roleName = $input['role_name'] ?? '';
        $roleDescription = $input['role_description'] ?? '';
        $inheritRoles = $input['inherit_roles'] ?? [];
        $appAccess = $input['app_access'] ?? [];
        $featurePermissions = $input['feature_permissions'] ?? [];
        $systemPermissions = $input['system_permissions'] ?? [];

        // Debug logging
        log_error("Role update API called - Role: $roleKey, Apps: " . json_encode($appAccess) . ", Features: " . json_encode($featurePermissions));

        if (empty($originalRoleKey) || empty($roleKey) || empty($roleName)) {
            log_error("Role update validation failed - missing required fields");
            http_response_code(400);
            echo json_encode(['error' => 'Role key and name are required']);
            break;
        }

        try {
            $result = $permissionsMatrix->updateRole($originalRoleKey, $roleKey, $roleName, $roleDescription, $inheritRoles, $appAccess, $featurePermissions, $systemPermissions);

            log_error("Role update result: " . json_encode($result));

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
            } else {
                log_error("Role update failed with error: " . ($result['error'] ?? 'Unknown error'));
                http_response_code(400);
                // Return more detailed error info for debugging
                echo json_encode([
                    'error' => $result['error'] ?? 'Failed to save role',
                    'debug' => [
                        'roleKey' => $roleKey,
                        'hasAppAccess' => !empty($appAccess),
                        'hasFeaturePerms' => !empty($featurePermissions),
                        'featureApps' => array_keys($featurePermissions),
                        'result' => $result
                    ]
                ]);
            }
        } catch (Exception $e) {
            log_error("Role update exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to update role: ' . $e->getMessage(),
                'debug' => [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
        }
        break;

    case 'delete_role':
        admin_require_admin();
        $permissionsMatrix = new PermissionsMatrix();

        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $roleKey = $input['role'] ?? '';

        if (empty($roleKey)) {
            http_response_code(400);
            echo json_encode(['error' => 'Role key required']);
            break;
        }

        // Prevent deletion of critical system roles
        if (in_array($roleKey, ['admin', 'guest', 'user', 'editor'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete system roles']);
            break;
        }

        try {
            $result = $permissionsMatrix->deleteRole($roleKey);

            if ($result['success']) {
                echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete role: ' . $e->getMessage()]);
        }
        break;

    // JSON Data Migration Endpoints
    case 'scan_json_files':
        admin_require_admin();
        try {
            $files = [];

            // Define potential JSON file locations
            $searchPaths = [
                '/var/data/mediabrain' => [
                    'oauth_config.json',
                    'users.json',
                    'permissions.json',
                    'user_permissions.json',
                    'storage_config.json'
                ],
                '/tmp' => [
                    'oauth_config.json',
                    'users.json'
                ],
                getcwd() . '/json' => [
                    'structure.json'
                ],
                getcwd() . '/apps/bibleBot/json' => [
                    'share_images.json'
                ]
            ];

            foreach ($searchPaths as $basePath => $filenames) {
                foreach ($filenames as $filename) {
                    $fullPath = $basePath . '/' . $filename;

                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        $content = file_get_contents($fullPath);

                        // Validate JSON
                        $jsonData = json_decode($content, true);
                        if ($jsonData !== null) {
                            $files[] = [
                                'filename' => $filename,
                                'path' => $fullPath,
                                'size' => filesize($fullPath),
                                'valid_json' => true,
                                'cloud_filename' => $filename
                            ];
                        }
                    }
                }
            }

            echo json_encode(['success' => true, 'files' => $files]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'File scan failed: ' . $e->getMessage()]);
        }
        break;

    case 'migrate_json_file':
        admin_require_admin();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $file = $input['file'] ?? null;

        if (!$file || !isset($file['path']) || !isset($file['cloud_filename'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file data']);
            break;
        }

        try {
            if (!file_exists($file['path'])) {
                echo json_encode(['success' => false, 'error' => 'File not found']);
                break;
            }

            $content = file_get_contents($file['path']);
            $jsonData = json_decode($content, true);

            if ($jsonData === null) {
                echo json_encode(['success' => false, 'error' => 'Invalid JSON content']);
                break;
            }

            // Use FileStorageManager to store in cloud
            $storage = FileStorageManager::getInstance();
            $result = $storage->storeJsonData(
                FileStorageManager::CATEGORY_SYSTEM_DATA,
                $file['cloud_filename'],
                $jsonData
            );

            if ($result['success']) {
                // Optionally backup the original file
                $backupPath = $file['path'] . '.migrated.' . date('Y-m-d-H-i-s');
                copy($file['path'], $backupPath);

                echo json_encode([
                    'success' => true,
                    'message' => 'File migrated successfully',
                    'backup_path' => $backupPath,
                    'cloud_url' => $result['url'] ?? null
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => $result['error'] ?? 'Migration failed'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Migration failed: ' . $e->getMessage()]);
        }
        break;

    case 'toggle_event_logging':
        admin_require_admin();
        $eventLogger = EventLogger::resetInstance();
        if ($eventLogger->isEnabled()) {
            $eventLogger->disable();
            echo json_encode(['status' => 'disabled', 'message' => 'Event logging disabled']);
        } else {
            $eventLogger->enable();
            echo json_encode(['status' => 'enabled', 'message' => 'Event logging enabled']);
        }
        break;

    case 'get_event_logging_status':
        admin_require_admin();
        $eventLogger = EventLogger::getInstance();
        echo json_encode([
            'enabled' => $eventLogger->isEnabled(),
            'log_file' => $eventLogger->getLogFile()
        ]);
        break;

    case 'clear_event_log':
        admin_require_admin();
        $eventLogger = EventLogger::resetInstance();
        $eventLogger->clearLog();
        echo json_encode(['status' => 'success', 'message' => 'Event log cleared']);
        break;

    case 'get_event_logs':
        admin_require_admin();

        // Increase time limit for processing large log files
        set_time_limit(10);
        ini_set('memory_limit', '64M');

        $lines = intval($_POST['lines'] ?? $_GET['lines'] ?? 100);
        $lines = min($lines, 500); // Cap at 500 lines for performance

        // Simple approach: always start with empty logs for immediate response
        $entries = [];

        // Optional clearing for manual refresh (not automatic)
        $clearFirst = $_POST['clear_first'] ?? $_GET['clear_first'] ?? 'false';
        if ($clearFirst === 'true') {
            // Add a manual clear entry
            $entries[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => 'INFO',
                'event' => 'LOGS_CLEARED',
                'message' => 'Event log manually cleared from admin interface',
                'user' => is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'context' => []
            ];
        } else {
            // Add a session info entry
            $sessionId = session_id();
            $entries[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level' => 'INFO',
                'event' => 'LOGS_VIEW_LOADED',
                'message' => "Admin logs page loaded for session: $sessionId",
                'user' => is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'context' => ['session_id' => $sessionId]
            ];
        }

        echo json_encode(['entries' => $entries]);
        break;

    case 'get_error_logs':
        admin_require_admin();
        $lines = intval($_POST['lines'] ?? $_GET['lines'] ?? 100);
        $logFile = dirname(__DIR__, 2) . '/logs/app.log';
        $errorEntries = [];

        if (file_exists($logFile)) {
            $logLines = [];
            $handle = fopen($logFile, 'r');

            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $logLines[] = trim($line);
                }
                fclose($handle);

                // Get last N lines
                $logLines = array_slice($logLines, -$lines);

                foreach ($logLines as $line) {
                    if (!empty($line)) {
                        $errorEntries[] = [
                            'timestamp' => 'raw',
                            'level' => 'ERROR',
                            'message' => $line
                        ];
                    }
                }

                $errorEntries = array_reverse($errorEntries);
            }
        }

        echo json_encode(['entries' => $errorEntries]);
        break;

    // PHPUnit Testing API Actions
    case 'phpunit_run_tests':
        admin_require_admin();

        // Paths (resolving from document root)
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $projectRoot = dirname($docRoot);
        $testDirectory = $projectRoot . '/tests';
        $vendorPath = $projectRoot . '/vendor';
        $phpunitPath = $vendorPath . '/bin/phpunit';
        $phpunitConfig = $projectRoot . '/phpunit.xml';

        if (!file_exists($phpunitPath)) {
            http_response_code(500);
            echo json_encode(['error' => 'PHPUnit not found. Please run composer install.']);
            exit;
        }

        $suite = $_POST['suite'] ?? 'all';
        $testFilter = $_POST['test_filter'] ?? '';
        $coverage = $_POST['coverage'] ?? false;

        $command = escapeshellcmd($phpunitPath);

        // Add test suite filter
        if ($suite !== 'all' && !empty($suite)) {
            $command .= ' --testsuite ' . escapeshellarg($suite);
        }

        // Add test filter
        if (!empty($testFilter)) {
            $command .= ' --filter ' . escapeshellarg($testFilter);
        }

        // Add coverage
        if ($coverage) {
            $command .= ' --coverage-text';
        }

        // Add configuration
        $command .= ' --configuration ' . escapeshellarg($phpunitConfig);

        // Change to project directory
        $originalDir = getcwd();
        chdir($projectRoot);

        $startTime = microtime(true);
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        $executionTime = microtime(true) - $startTime;
        chdir($originalDir);

        echo json_encode([
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
            'execution_time' => round($executionTime, 3),
            'command' => $command,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    case 'phpunit_run_single_test':
        admin_require_admin();

        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $projectRoot = dirname($docRoot);
        $testDirectory = $projectRoot . '/tests';
        $vendorPath = $projectRoot . '/vendor';
        $phpunitPath = $vendorPath . '/bin/phpunit';

        $testFile = $_POST['test_file'] ?? '';

        if (empty($testFile)) {
            echo json_encode(['error' => 'No test file specified']);
            exit;
        }

        $fullPath = $testDirectory . '/' . $testFile;
        if (!file_exists($fullPath)) {
            echo json_encode(['error' => 'Test file not found']);
            exit;
        }

        $command = escapeshellcmd($phpunitPath) . ' ' . escapeshellarg($fullPath);

        $originalDir = getcwd();
        chdir($projectRoot);

        $startTime = microtime(true);
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        $executionTime = microtime(true) - $startTime;
        chdir($originalDir);

        echo json_encode([
            'success' => $returnCode === 0,
            'output' => implode("\n", $output),
            'return_code' => $returnCode,
            'execution_time' => round($executionTime, 3),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    case 'phpunit_get_test_content':
        admin_require_admin();

        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $projectRoot = dirname($docRoot);
        $testDirectory = $projectRoot . '/tests';

        $testFile = $_GET['test_file'] ?? $_POST['test_file'] ?? '';

        if (empty($testFile)) {
            echo json_encode(['error' => 'No test file specified']);
            exit;
        }

        $fullPath = $testDirectory . '/' . $testFile;
        if (!file_exists($fullPath)) {
            echo json_encode(['error' => 'Test file not found']);
            exit;
        }

        echo json_encode([
            'content' => file_get_contents($fullPath),
            'file' => $testFile,
            'size' => filesize($fullPath),
            'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
        ]);
        break;

    case 'phpunit_get_test_files':
        admin_require_admin();

        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        $projectRoot = dirname($docRoot);
        $testDirectory = $projectRoot . '/tests';

        $files = [];

        if (is_dir($testDirectory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($testDirectory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($testDirectory . '/', '', $file->getPathname());
                    $files[] = [
                        'name' => $file->getBasename(),
                        'path' => $file->getPathname(),
                        'relative_path' => $relativePath,
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime()
                    ];
                }
            }
        }

        echo json_encode([
            'files' => $files,
            'count' => count($files)
        ]);
        break;

    // Analytics API Endpoints
    case 'analytics_overview':
        admin_require_admin();

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(90, $days));

        $analytics = AnalyticsService::getInstance();
        $overview = $analytics->getOverviewStats($days);

        echo json_encode([
            'success' => true,
            'data' => $overview
        ]);
        break;

    case 'analytics_chart_data':
        admin_require_admin();

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(90, $days));

        $analytics = AnalyticsService::getInstance();
        $chartData = $analytics->getChartData($days);

        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        break;

    case 'analytics_recent_visits':
        admin_require_admin();

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = max(1, min(100, $limit));

        $analytics = AnalyticsService::getInstance();
        $visits = $analytics->getRecentVisits($limit);

        echo json_encode([
            'success' => true,
            'data' => $visits
        ]);
        break;

    case 'analytics_top_pages':
        admin_require_admin();

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $limit = max(1, min(50, $limit));
        $days = max(1, min(90, $days));

        $analytics = AnalyticsService::getInstance();
        $topPages = $analytics->getTopPages($limit, $days);

        echo json_encode([
            'success' => true,
            'data' => $topPages
        ]);
        break;

    case 'analytics_active_users':
        admin_require_admin();

        $timeWindow = isset($_GET['time_window']) ? (int)$_GET['time_window'] : 5;
        $timeWindow = max(1, min(60, $timeWindow));

        $analytics = AnalyticsService::getInstance();
        $activeUsers = $analytics->getActiveUsers($timeWindow);

        echo json_encode([
            'success' => true,
            'data' => $activeUsers
        ]);
        break;

    case 'analytics_top_searches':
        admin_require_admin();

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $limit = max(1, min(100, $limit));
        $days = max(1, min(90, $days));

        $analytics = AnalyticsService::getInstance();
        $topSearches = $analytics->getTopSearches($limit, $days);

        echo json_encode([
            'success' => true,
            'data' => $topSearches
        ]);
        break;

    case 'analytics_error_stats':
        admin_require_admin();

        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        $days = max(1, min(90, $days));

        $analytics = AnalyticsService::getInstance();
        $errorStats = $analytics->getErrorStats($days);

        echo json_encode([
            'success' => true,
            'data' => $errorStats
        ]);
        break;

    case 'analytics_error_log_tail':
        admin_require_admin();

        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
        $lines = max(1, min(500, $lines));

        $analytics = AnalyticsService::getInstance();
        $logTail = $analytics->getErrorLogTail($lines);

        echo json_encode([
            'success' => true,
            'data' => $logTail
        ]);
        break;

    // TTS Preferences Management
    case 'get_tts_preferences':
        admin_require_login();

        // Get current user
        $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];

        try {
            require_once __DIR__ . '/../../includes/UserPreferencesManager.php';
            $preferencesManager = new UserPreferencesManager();

            $preferences = $preferencesManager->getTTSPreferences($username);

            echo json_encode([
                'success' => true,
                'preferences' => $preferences
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load TTS preferences: ' . $e->getMessage()]);
        }
        break;

    case 'save_tts_preferences':
        admin_require_login();

        // Get current user
        $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'No preferences data provided']);
            break;
        }

        try {
            require_once __DIR__ . '/../../includes/UserPreferencesManager.php';
            $preferencesManager = new UserPreferencesManager();

            $success = $preferencesManager->saveTTSPreferences($username, $input);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'TTS preferences saved successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save TTS preferences']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save TTS preferences: ' . $e->getMessage()]);
        }
        break;

    case 'preview_tts':
        admin_require_login();

        // Get current user
        $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['text'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No text provided for preview']);
            break;
        }

        try {
            require_once __DIR__ . '/../../includes/Services/TextToSpeechService.php';

            // Create TTS service with user preferences
            $ttsConfig = [
                'default_voice' => $input['voice'] ?? 'en-US-Neural2-A',
                'default_language' => $input['language'] ?? 'en-US',
                'default_gender' => $input['gender'] ?? 'NEUTRAL',
                'audio_format' => $input['audio_format'] ?? 'MP3',
                'enable_ssml' => $input['enable_ssml'] ?? true
            ];

            $ttsService = new MediaBrain\Services\TextToSpeechService($ttsConfig);

            // Generate audio with user settings
            $audioOptions = [
                'voice' => $input['voice'] ?? 'en-US-Neural2-A',
                'language' => $input['language'] ?? 'en-US',
                'gender' => $input['gender'] ?? 'NEUTRAL',
                'audio_format' => $input['audio_format'] ?? 'MP3',
                'speech_rate' => floatval($input['speech_rate'] ?? 1.0),
                'enable_ssml' => $input['enable_ssml'] ?? true
            ];

            $result = $ttsService->synthesize($input['text'], $audioOptions);

            if ($result && $result->getAudioContent()) {
                // Save the audio to a temporary file for preview
                $tempDir = sys_get_temp_dir();
                $tempFilename = 'tts_preview_' . $username . '_' . uniqid() . '.mp3';
                $tempPath = $tempDir . '/' . $tempFilename;

                file_put_contents($tempPath, $result->getAudioContent());

                // Create a web-accessible URL for the temporary file
                $audioUrl = '/api.php?app=admin&action=serve_temp_audio&file=' . urlencode($tempFilename);

                echo json_encode([
                    'success' => true,
                    'audio_url' => $audioUrl,
                    'temp_file' => $tempFilename,
                    'metadata' => $result->getMetadata()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'TTS generation failed - no audio content returned']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'TTS preview failed: ' . $e->getMessage()]);
        }
        break;

    case 'serve_temp_audio':
        admin_require_login();

        $filename = $_GET['file'] ?? '';

        if (empty($filename) || !preg_match('/^tts_preview_[a-zA-Z0-9_]+\.mp3$/', $filename)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid filename']);
            break;
        }

        $tempDir = sys_get_temp_dir();
        $filePath = $tempDir . '/' . $filename;

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['error' => 'File not found']);
            break;
        }

        // Security check - ensure the file was created within the last hour
        if (time() - filemtime($filePath) > 3600) {
            unlink($filePath);
            http_response_code(404);
            echo json_encode(['error' => 'File expired']);
            break;
        }

        // Serve the audio file
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($filePath));
        header('Accept-Ranges: bytes');
        header('Cache-Control: no-cache');

        readfile($filePath);

        // Clean up the temporary file after serving
        register_shutdown_function(function () use ($filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });
        break;

    // Admin TTS Site-wide Configuration
    case 'get_admin_tts_config':
        admin_require_admin();

        try {
            require_once __DIR__ . '/../../includes/Services/TextToSpeechService.php';

            $adminConfig = MediaBrain\Services\TextToSpeechService::getAdminConfig();

            echo json_encode([
                'success' => true,
                'config' => $adminConfig
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to load admin TTS config: ' . $e->getMessage()]);
        }
        break;

    case 'save_admin_tts_config':
        admin_require_admin();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'No configuration data provided']);
            break;
        }

        try {
            require_once __DIR__ . '/../../includes/Services/TextToSpeechService.php';

            $success = MediaBrain\Services\TextToSpeechService::saveAdminConfig($input);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin TTS configuration saved successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save admin TTS configuration']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save admin TTS config: ' . $e->getMessage()]);
        }
        break;

    // Theme Management API
    case 'themes':
        // Include the theme API handler
        require_once __DIR__ . '/api/theme-api.php';

        // Handle theme API requests through our ThemeAPI class
        $themeAPI = new ThemeAPI();
        $themeAPI->handleRequest();
        break;

    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}
