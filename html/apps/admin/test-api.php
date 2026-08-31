<?php
/**
 * Test Management API
 * Handles test creation, editing, and management operations
 */

// Only allow admin access
if (!isset($_SESSION['user']) || !AuthManager::userIsAdmin($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$action = $_POST['api_action'] ?? $_GET['api_action'] ?? '';
$testDirectory = dirname(dirname(dirname(__DIR__))) . '/dev/test/';

switch ($action) {
    case 'create_test':
        handleCreateTest();
        break;
    case 'delete_test':
        handleDeleteTest();
        break;
    case 'edit_test':
        handleEditTest();
        break;
    case 'get_test_templates':
        handleGetTestTemplates();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleCreateTest() {
    global $testDirectory;
    
    $testName = $_POST['test_name'] ?? '';
    $testCategory = $_POST['test_category'] ?? '';
    $testDescription = $_POST['test_description'] ?? '';
    $useBasicTemplate = isset($_POST['use_basic_template']) && $_POST['use_basic_template'] === 'true';
    $useWebTemplate = isset($_POST['use_web_template']) && $_POST['use_web_template'] === 'true';
    
    // Validate input
    if (empty($testName)) {
        echo json_encode(['error' => 'Test name is required']);
        return;
    }
    
    // Sanitize filename
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $testName);
    if (!str_starts_with($filename, 'test_')) {
        $filename = 'test_' . $filename;
    }
    $filename .= '.php';
    
    $filePath = $testDirectory . $filename;
    
    // Check if file already exists
    if (file_exists($filePath)) {
        echo json_encode(['error' => 'Test file already exists']);
        return;
    }
    
    // Generate test content based on templates
    $content = generateTestContent($testName, $testCategory, $testDescription, $useBasicTemplate, $useWebTemplate);
    
    // Write file
    if (file_put_contents($filePath, $content) !== false) {
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'path' => $filePath
        ]);
    } else {
        echo json_encode(['error' => 'Failed to create test file']);
    }
}

function handleDeleteTest() {
    global $testDirectory;
    
    $testFile = $_POST['test_file'] ?? '';
    
    if (empty($testFile)) {
        echo json_encode(['error' => 'Test file is required']);
        return;
    }
    
    $filePath = $testDirectory . $testFile;
    
    if (!file_exists($filePath)) {
        echo json_encode(['error' => 'Test file not found']);
        return;
    }
    
    if (unlink($filePath)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete test file']);
    }
}

function handleEditTest() {
    global $testDirectory;
    
    $testFile = $_POST['test_file'] ?? '';
    $content = $_POST['content'] ?? '';
    
    if (empty($testFile)) {
        echo json_encode(['error' => 'Test file is required']);
        return;
    }
    
    $filePath = $testDirectory . $testFile;
    
    if (!file_exists($filePath)) {
        echo json_encode(['error' => 'Test file not found']);
        return;
    }
    
    if (file_put_contents($filePath, $content) !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to save test file']);
    }
}

function handleGetTestTemplates() {
    $templates = [
        'basic' => getBasicTestTemplate(),
        'web' => getWebTestTemplate(),
        'api' => getApiTestTemplate(),
        'auth' => getAuthTestTemplate()
    ];
    
    echo json_encode(['templates' => $templates]);
}

function generateTestContent($testName, $category, $description, $useBasic, $useWeb) {
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * $testName Test\n";
    if (!empty($category)) {
        $content .= " * Category: " . ucfirst($category) . "\n";
    }
    if (!empty($description)) {
        $content .= " * Description: $description\n";
    }
    $content .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
    $content .= " */\n\n";
    
    if ($useBasic) {
        $content .= getBasicTestTemplate();
    }
    
    if ($useWeb) {
        $content .= "\n\n" . getWebTestTemplate();
    }
    
    if (!$useBasic && !$useWeb) {
        $content .= "// Add your test code here\n";
        $content .= "echo \"Test: $testName\";\n";
        $content .= "echo \"Status: PASS\";\n";
    }
    
    return $content;
}

function getBasicTestTemplate() {
    return <<<'PHP'
// Basic test setup
require_once __DIR__ . '/../../html/includes/util.php';

echo "=== $testName Test ===\n\n";

$testsPassed = 0;
$testsFailed = 0;

function testAssert($condition, $message) {
    global $testsPassed, $testsFailed;
    
    if ($condition) {
        echo "✓ PASS: $message\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: $message\n";
        $testsFailed++;
    }
}

// Example test
testAssert(true, "Basic assertion test");
testAssert(1 === 1, "Equality test");

// Add your tests here
// testAssert(your_function() === expected_value, "Your test description");

echo "\n=== Test Results ===\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";
echo "Total: " . ($testsPassed + $testsFailed) . "\n";

if ($testsFailed === 0) {
    echo "Status: ALL TESTS PASSED\n";
    exit(0);
} else {
    echo "Status: SOME TESTS FAILED\n";
    exit(1);
}
PHP;
}

function getWebTestTemplate() {
    return <<<'PHP'
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($testName) ?> Test</title>
    <link rel="stylesheet" href="/css/materialize.min.css">
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($testName) ?> Test</h1>
        
        <div class="card">
            <div class="card-content">
                <span class="card-title">Test Information</span>
                <p>Test executed at: <?= date('Y-m-d H:i:s') ?></p>
                <p>Session status: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active' ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-content">
                <span class="card-title">Test Results</span>
                <div id="test-results">
                    <!-- Test results will be displayed here -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="/js/jquery-2.1.1.min.js"></script>
    <script src="/js/materialize.min.js"></script>
    <script>
        // Add your test JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Your test logic
        });
    </script>
</body>
</html>
<?php
PHP;
}

function getApiTestTemplate() {
    return <<<'PHP'
// API Test Template
require_once __DIR__ . '/../../html/includes/util.php';

echo "=== API Test ===\n\n";

function testApiEndpoint($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// Example API test
$result = testApiEndpoint('http://localhost:8080/api.php?action=test');
echo "API Response Code: " . $result['http_code'] . "\n";
echo "API Response: " . $result['response'] . "\n";
PHP;
}

function getAuthTestTemplate() {
    return <<<'PHP'
// Authentication Test Template
require_once __DIR__ . '/../../html/includes/util.php';
require_once __DIR__ . '/../../html/includes/AuthManager.php';

echo "=== Authentication Test ===\n\n";

// Test authentication functions
$testUser = [
    'username' => 'testuser',
    'email' => 'test@example.com'
];

// Test user login status
echo "Testing authentication...\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n";
echo "User logged in: " . (isset($_SESSION['user']) ? 'Yes' : 'No') . "\n";

if (isset($_SESSION['user'])) {
    $isAdmin = AuthManager::userIsAdmin($_SESSION['user']);
    echo "User is admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";
}
PHP;
}
?>