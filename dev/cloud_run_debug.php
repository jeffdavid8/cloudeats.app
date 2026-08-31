<?php
/**
 * Cloud Run Environment Diagnostic
 * Access this at: https://mediabrain.app/cloud_run_debug.php
 */

header('Content-Type: application/json');

// Security check - only allow in development or with special parameter
if (!isset($_GET['debug_token']) || $_GET['debug_token'] !== 'mediabrain_debug_2025') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$diagnostics = [
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'environment_detection' => [
        'K_SERVICE' => getenv('K_SERVICE'),
        'GOOGLE_CLOUD_PROJECT' => getenv('GOOGLE_CLOUD_PROJECT'),
        'is_cloud_run_detected' => (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false)
    ],
    'admin_env_vars' => [
        'ADMIN_USERNAME_set' => getenv('ADMIN_USERNAME') !== false,
        'ADMIN_USERNAME_value' => getenv('ADMIN_USERNAME') ?: 'not_set',
        'ADMIN_PASSWORD_set' => getenv('ADMIN_PASSWORD') !== false,
        'ADMIN_PASSWORD_length' => getenv('ADMIN_PASSWORD') ? strlen(getenv('ADMIN_PASSWORD')) : 0
    ],
    'storage_env_vars' => [
        'STORAGE_PROVIDER' => getenv('STORAGE_PROVIDER') ?: 'not_set',
        'STORAGE_BUCKET_PREFIX' => getenv('STORAGE_BUCKET_PREFIX') ?: 'not_set',
        'STORAGE_LOCATION' => getenv('STORAGE_LOCATION') ?: 'not_set'
    ],
    'file_system' => [
        'var_data_exists' => is_dir('/var/data'),
        'var_data_writable' => is_writable('/var/data'),
        'tmp_exists' => is_dir('/tmp'),
        'tmp_writable' => is_writable('/tmp')
    ]
];

// Test AdminAuth initialization
try {
    require_once 'apps/admin/includes/AdminAuth.php';
    $adminAuth = new AdminAuth();
    $diagnostics['admin_auth'] = [
        'initialization' => 'success',
        'error' => null
    ];
    
    // Test authentication with environment variables
    $username = getenv('ADMIN_USERNAME') ?: 'admin';
    $password = getenv('ADMIN_PASSWORD') ?: 'admin';
    
    $authResult = $adminAuth->authenticate($username, $password);
    $diagnostics['authentication_test'] = [
        'username_used' => $username,
        'auth_result' => $authResult,
        'method' => 'environment_variables'
    ];
    
} catch (Exception $e) {
    $diagnostics['admin_auth'] = [
        'initialization' => 'failed',
        'error' => $e->getMessage()
    ];
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>