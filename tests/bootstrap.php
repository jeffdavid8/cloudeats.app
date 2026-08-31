<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment for MediaBrain application
 */

// Set test environment
define('TESTING', true);
define('MB_RUNNING', true);

// Start output buffering to prevent headers already sent issues
if (!ob_get_level()) {
    ob_start();
}

// Set up basic session for testing
if (session_status() === PHP_SESSION_NONE) {
    // Use memory-based sessions for testing
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', sys_get_temp_dir());
    session_start();
}

// Load the autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load MediaBrain core files directly
require_once __DIR__ . '/../html/includes/AuthManager.php';
require_once __DIR__ . '/../html/includes/util.php';
require_once __DIR__ . '/mocks/App.php';

// For integration tests, create a global function alias
if (!function_exists('mb_require')) {
    function mb_require($path) {
        // Mock implementation for testing
        return true;
    }
}

// Set up test database/storage paths
define('TEST_DATA_DIR', __DIR__ . '/data');
if (!file_exists(TEST_DATA_DIR)) {
    mkdir(TEST_DATA_DIR, 0755, true);
}

// Helper function to clean up test data
function cleanupTestData() {
    $files = glob(TEST_DATA_DIR . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// Helper function to create test user session
function createTestUserSession($username = 'testuser', $isAdmin = false) {
    $_SESSION['user'] = [
        'username' => $username,
        'role' => $isAdmin ? 'admin' : 'user',
        'is_admin' => $isAdmin
    ];
    return $_SESSION['user'];
}

// Helper function to clear session
function clearTestSession() {
    $_SESSION = [];
}

// Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHPUnit bootstrap loaded successfully\n";
?>