<?php
/**
 * PHPUnit Testing API endpoints for admin interface
 */

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean output buffer early
ob_start();

// Include only necessary files without side effects
require_once __DIR__ . '/../../includes/SecurityHeaders.php';
require_once __DIR__ . '/../../includes/RateLimiter.php';
require_once __DIR__ . '/../../includes/AuthManager.php';

// Set API security headers
SecurityHeaders::setAPIHeaders([
    'cors' => false // Disable CORS for admin API
]);

// Rate limiting
if (!RateLimiter::checkAndRecord('api')) {
    ob_clean();
    http_response_code(429);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Rate limit exceeded',
        'retry_after' => RateLimiter::getTimeUntilReset('api')
    ]);
    exit;
}

// Authentication helper functions
function admin_user_logged_in() {
    return isset($_SESSION['user']);
}

function admin_user_is_admin() {
    if (!admin_user_logged_in()) {
        return false;
    }
    return AuthManager::userIsAdmin($_SESSION['user']);
}

function admin_require_login() {
    if (!admin_user_logged_in()) {
        ob_clean();
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

function admin_require_admin() {
    admin_require_login();
    if (!admin_user_is_admin()) {
        ob_clean();
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Admin privileges required']);
        exit;
    }
}

// Require admin authentication
admin_require_admin();

// Set content type
header('Content-Type: application/json');

// Paths (resolving from document root)
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$projectRoot = dirname($docRoot);
$testDirectory = $projectRoot . '/tests';
$vendorPath = $projectRoot . '/vendor';
$phpunitPath = $vendorPath . '/bin/phpunit';
$phpunitConfig = $projectRoot . '/phpunit.xml';

// Validate PHPUnit availability
function validatePHPUnit() {
    global $phpunitPath, $vendorPath;
    
    if (!file_exists($phpunitPath)) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'PHPUnit not found. Please run composer install.']);
        exit;
    }
    
    if (!file_exists($vendorPath . '/autoload.php')) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'Composer autoloader not found.']);
        exit;
    }
}

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
} elseif ($method === 'GET') {
    $action = $_GET['action'] ?? '';
}

// Route API requests
switch ($action) {
    case 'run_phpunit':
        if ($method !== 'POST') {
            http_response_code(405);
            ob_clean();
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        validatePHPUnit();
        runPHPUnit();
        break;
        
    case 'run_single_test':
        if ($method !== 'POST') {
            http_response_code(405);
            ob_clean();
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        validatePHPUnit();
        runSingleTest();
        break;
        
    case 'get_test_content':
        if ($method !== 'GET') {
            http_response_code(405);
            ob_clean();
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        getTestContent();
        break;
        
    case 'get_test_files':
        if ($method !== 'GET') {
            http_response_code(405);
            ob_clean();
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }
        
        getTestFiles();
        break;
        
    default:
        http_response_code(400);
        ob_clean();
        echo json_encode(['error' => 'Invalid action']);
        exit;
}

/**
 * Run PHPUnit test suite
 */
function runPHPUnit() {
    global $phpunitPath, $phpunitConfig, $projectRoot;
    
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
    
    // Execute PHPUnit
    $originalDir = getcwd();
    chdir($projectRoot);
    
    $startTime = microtime(true);
    $output = [];
    $returnCode = 0;
    exec($command . ' 2>&1', $output, $returnCode);
    
    $executionTime = microtime(true) - $startTime;
    chdir($originalDir);
    
    ob_clean();
    echo json_encode([
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'return_code' => $returnCode,
        'execution_time' => round($executionTime, 3),
        'command' => $command,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Run a single test file
 */
function runSingleTest() {
    global $phpunitPath, $testDirectory, $projectRoot;
    
    $testFile = $_POST['test_file'] ?? '';
    
    if (empty($testFile)) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['error' => 'No test file specified']);
        exit;
    }
    
    // Validate test file exists
    $fullPath = $testDirectory . '/' . $testFile;
    if (!file_exists($fullPath)) {
        http_response_code(404);
        ob_clean();
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
    
    ob_clean();
    echo json_encode([
        'success' => $returnCode === 0,
        'output' => implode("\n", $output),
        'return_code' => $returnCode,
        'execution_time' => round($executionTime, 3),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get test file content
 */
function getTestContent() {
    global $testDirectory;
    
    $testFile = $_GET['test_file'] ?? '';
    
    if (empty($testFile)) {
        http_response_code(400);
        ob_clean();
        echo json_encode(['error' => 'No test file specified']);
        exit;
    }
    
    $fullPath = $testDirectory . '/' . $testFile;
    if (!file_exists($fullPath)) {
        http_response_code(404);
        ob_clean();
        echo json_encode(['error' => 'Test file not found']);
        exit;
    }
    
    ob_clean();
    echo json_encode([
        'content' => file_get_contents($fullPath),
        'file' => $testFile,
        'size' => filesize($fullPath),
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath))
    ]);
}

/**
 * Get list of test files
 */
function getTestFiles() {
    global $testDirectory;
    
    $files = [];
    if (is_dir($testDirectory)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testDirectory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getBasename(), 'Test') !== false) {
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
    
    ob_clean();
    echo json_encode([
        'files' => $files,
        'count' => count($files)
    ]);
}