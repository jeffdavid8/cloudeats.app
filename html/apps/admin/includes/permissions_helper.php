<?php
/**
 * Permissions Helper Functions
 * 
 * These functions provide easy access to the permissions system
 * throughout the MediaBrain application.
 */

require_once __DIR__ . '/PermissionsMatrix.php';

/**
 * Get the global permissions matrix instance
 */
function getPermissionsMatrix() {
    static $instance = null;
    if ($instance === null) {
        $instance = new PermissionsMatrix();
    }
    return $instance;
}

/**
 * Check if current user has permission
 */
function userCan($resource, $action = null) {
    $user = $_SESSION['user'] ?? $_SESSION['username'] ?? 'guest';
    $username = is_array($user) ? ($user['username'] ?? 'guest') : $user;
    return getPermissionsMatrix()->hasPermission($username, $resource, $action);
}

/**
 * Check if current user can access an app
 */
function userCanAccessApp($appName) {
    $user = $_SESSION['user'] ?? $_SESSION['username'] ?? 'guest';
    $username = is_array($user) ? ($user['username'] ?? 'guest') : $user;
    return getPermissionsMatrix()->canAccessApp($username, $appName);
}

/**
 * Check if current user can use a feature
 */
function userCanUseFeature($appName, $featureName, $action) {
    $user = $_SESSION['user'] ?? $_SESSION['username'] ?? 'guest';
    $username = is_array($user) ? ($user['username'] ?? 'guest') : $user;
    return getPermissionsMatrix()->canUseFeature($username, $appName, $featureName, $action);
}

/**
 * Get apps available to current user
 */
function getUserApps() {
    $user = $_SESSION['user'] ?? $_SESSION['username'] ?? 'guest';
    $username = is_array($user) ? ($user['username'] ?? 'guest') : $user;
    return getPermissionsMatrix()->getUserApps($username);
}

/**
 * Require permission (throw error if not authorized)
 */
function requirePermission($resource, $action = null) {
    if (!userCan($resource, $action)) {
        http_response_code(403);
        if (isset($_GET['app'])) {
            // In app context, show error page
            echo '<div class="card-panel red lighten-4">';
            echo '<span class="red-text">Access Denied: You do not have permission to access this resource.</span>';
            echo '</div>';
            exit;
        } else {
            // API context, return JSON
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied: insufficient permissions']);
            exit;
        }
    }
}

/**
 * Require app access
 */
function requireAppAccess($appName) {
    if (!userCanAccessApp($appName)) {
        http_response_code(403);
        if (isset($_GET['app'])) {
            echo '<div class="card-panel red lighten-4">';
            echo '<span class="red-text">Access Denied: You do not have access to the ' . htmlspecialchars($appName) . ' app.</span>';
            echo '</div>';
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied: app access not permitted']);
            exit;
        }
    }
}

/**
 * Get permission-filtered navigation menu
 */
function getPermissionFilteredApps() {
    $allApps = [
        'admin' => ['name' => 'Admin Panel', 'icon' => 'settings'],
        'recipes' => ['name' => 'Recipe Manager', 'icon' => 'restaurant'],
        'weather' => ['name' => 'Weather', 'icon' => 'wb_sunny'],
        'bibleBot' => ['name' => 'Bible Bot', 'icon' => 'book'],
        'ancestry' => ['name' => 'Ancestry', 'icon' => 'account_tree']
    ];
    
    $allowedApps = [];
    foreach ($allApps as $appName => $appInfo) {
        if (userCanAccessApp($appName)) {
            $allowedApps[$appName] = $appInfo;
        }
    }
    
    return $allowedApps;
}
?>