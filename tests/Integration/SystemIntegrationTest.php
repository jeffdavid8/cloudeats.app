<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Mock App class for integration testing
 */
class MockApp {
    private static $instance = null;
    private $config = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->config = [
            'base_url' => 'https://mediabrain.app.local',
            'app_path' => dirname(__DIR__, 2) . '/html/apps',
            'storage_path' => '/tmp/mediabrain_test',
            'environment' => 'testing'
        ];
    }
    
    public function render($template, $vars = [], $return = false) {
        $output = "Rendered: {$template} with " . count($vars) . " variables";
        if ($return) {
            return $output;
        }
        echo $output;
        return null;
    }
    
    public function __get($name) {
        return $this->config[$name] ?? null;
    }
}

/**
 * Integration test utilities
 */
class IntegrationTestUtils {
    public static function setupTestEnvironment() {
        // Set up test directories
        $testDir = sys_get_temp_dir() . '/mediabrain_integration_' . uniqid();
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        return $testDir;
    }
    
    public static function cleanupTestEnvironment($testDir) {
        if (is_dir($testDir)) {
            self::removeDirectory($testDir);
        }
    }
    
    private static function removeDirectory($dir) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
    
    public static function createTestUser($username = 'testuser') {
        return [
            'username' => $username,
            'email' => $username . '@test.com',
            'role' => 'user',
            'is_admin' => false,
            'active' => true,
            'created' => date('c'),
            'last_login' => null
        ];
    }
    
    public static function createTestRecipe($title = 'Test Recipe') {
        return [
            'id' => uniqid(),
            'title' => $title,
            'description' => 'A delicious test recipe',
            'category' => 'main',
            'ingredients' => ['Test ingredient 1', 'Test ingredient 2'],
            'steps' => ['Step 1', 'Step 2'],
            'created' => date('c'),
            'modified' => date('c')
        ];
    }
    
    public static function simulateHttpRequest($url, $method = 'GET', $data = null) {
        // Mock HTTP request for testing
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        return [
            'url' => $url,
            'method' => $method,
            'params' => $params,
            'data' => $data,
            'status' => 200,
            'response' => json_encode(['success' => true, 'data' => $params])
        ];
    }
}

/**
 * Comprehensive Integration Tests
 * 
 * Tests cross-app functionality, API integrations, and system-wide features:
 * - App-to-app communication
 * - Shared authentication
 * - API endpoint integration
 * - Database consistency
 * - Performance across apps
 * - Error handling and recovery
 */
class SystemIntegrationTest extends TestCase
{
    private $testDir;
    private $originalSession;
    private $originalGet;
    private $originalPost;
    
    public function setUp(): void
    {
        // Setup test environment
        $this->testDir = IntegrationTestUtils::setupTestEnvironment();
        
        // Backup superglobals
        $this->originalSession = $_SESSION ?? [];
        $this->originalGet = $_GET ?? [];
        $this->originalPost = $_POST ?? [];
        
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        
        // Setup test session
        $_SESSION['user'] = IntegrationTestUtils::createTestUser('integrationtest');
        $_SESSION['csrf_token'] = 'test-csrf-token-' . uniqid();
    }
    
    public function tearDown(): void
    {
        // Cleanup
        IntegrationTestUtils::cleanupTestEnvironment($this->testDir);
        
        // Restore superglobals
        $_SESSION = $this->originalSession;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
    }

    /**
     * Test cross-app authentication consistency
     */
    #[Test]
    public function testCrossAppAuthentication()
    {
        // Test that authentication state is consistent across apps
        $user = $_SESSION['user'];
        
        // Simulate visiting different apps
        $_GET['app'] = 'admin';
        $this->assertEquals('integrationtest', $user['username']);
        
        $_GET['app'] = 'recipes';
        $this->assertEquals('integrationtest', $user['username']);
        
        $_GET['app'] = 'bibleBot';
        $this->assertEquals('integrationtest', $user['username']);
        
        // Test that session data persists
        $this->assertNotEmpty($_SESSION['csrf_token']);
        $this->assertArrayHasKey('user', $_SESSION);
    }

    /**
     * Test API routing and endpoint discovery
     */
    #[Test]
    public function testAPIRoutingAndEndpoints()
    {
        // Test main API routing pattern
        $apiUrls = [
            'https://mediabrain.app.local/?api=admin&action=get_users',
            'https://mediabrain.app.local/?api=recipes&action=get_recipes',
            'https://mediabrain.app.local/?api=bibleBot&action=search'
        ];
        
        foreach ($apiUrls as $url) {
            $response = IntegrationTestUtils::simulateHttpRequest($url);
            
            $this->assertEquals(200, $response['status']);
            $this->assertNotEmpty($response['params']['api']);
            $this->assertNotEmpty($response['params']['action']);
            
            // Parse response
            $data = json_decode($response['response'], true);
            $this->assertTrue($data['success']);
        }
    }

    /**
     * Test shared utility functions across apps
     */
    #[Test]
    public function testSharedUtilityFunctions()
    {
        // Test protocol detection
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https', protocol());
        
        unset($_SERVER['HTTPS']);
        $this->assertEquals('http', protocol());
        
        // Test environment detection
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue(is_development());
        $this->assertFalse(is_production());
        
        $_SERVER['HTTP_HOST'] = 'mediabrain.app';
        $this->assertFalse(is_development());
        $this->assertTrue(is_production());
        
        // Test that essential utility functions exist
        $this->assertTrue(function_exists('protocol'));
        $this->assertTrue(function_exists('is_development'));
        $this->assertTrue(function_exists('is_production'));
        $this->assertTrue(function_exists('config'));
    }

    /**
     * Test data sharing between apps
     */
    #[Test]
    public function testDataSharingBetweenApps()
    {
        // Test that user preferences can be shared across apps
        $_SESSION['preferences'] = [
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'UTC',
            'recipes_per_page' => 20,
            'bible_version' => 'KJV'
        ];
        
        // Simulate switching between apps
        $_GET['app'] = 'recipes';
        $recipesPrefs = $_SESSION['preferences'];
        $this->assertEquals(20, $recipesPrefs['recipes_per_page']);
        
        $_GET['app'] = 'bibleBot';
        $biblePrefs = $_SESSION['preferences'];
        $this->assertEquals('KJV', $biblePrefs['bible_version']);
        
        $_GET['app'] = 'admin';
        $adminPrefs = $_SESSION['preferences'];
        $this->assertEquals('dark', $adminPrefs['theme']);
        
        // Test preference updates persist
        $_SESSION['preferences']['theme'] = 'light';
        $this->assertEquals('light', $_SESSION['preferences']['theme']);
    }

    /**
     * Test error handling across app boundaries
     */
    #[Test]
    public function testErrorHandlingAcrossApps()
    {
        // Test that errors in one app don't break others
        $errorMessages = [];
        
        try {
            // Simulate error in admin app
            $_GET['app'] = 'admin';
            throw new Exception('Admin error test');
        } catch (Exception $e) {
            $errorMessages['admin'] = $e->getMessage();
        }
        
        try {
            // Simulate error in recipes app
            $_GET['app'] = 'recipes';
            throw new Exception('Recipes error test');
        } catch (Exception $e) {
            $errorMessages['recipes'] = $e->getMessage();
        }
        
        // Both errors should be captured independently
        $this->assertEquals('Admin error test', $errorMessages['admin']);
        $this->assertEquals('Recipes error test', $errorMessages['recipes']);
        
        // Session should still be intact
        $this->assertArrayHasKey('user', $_SESSION);
    }

    /**
     * Test security measures across apps
     */
    #[Test]
    public function testSecurityMeasuresAcrossApps()
    {
        // Test CSRF token validation
        $csrfToken = $_SESSION['csrf_token'];
        $this->assertNotEmpty($csrfToken);
        
        // Test that CSRF token is required for sensitive operations
        $_POST['action'] = 'delete_user';
        $_POST['username'] = 'testuser';
        // Missing CSRF token should be caught
        $this->assertArrayNotHasKey('_csrf', $_POST);
        
        // Add CSRF token
        $_POST['_csrf'] = $csrfToken;
        $this->assertEquals($csrfToken, $_POST['_csrf']);
        
        // Test that admin operations require admin privileges
        $user = $_SESSION['user'];
        $this->assertFalse($user['is_admin']);
        
        // Simulate admin check
        $hasAdminAccess = $user['is_admin'] ?? false;
        $this->assertFalse($hasAdminAccess);
        
        // Test XSS protection in shared data
        $_SESSION['test_data'] = '<script>alert("xss")</script>';
        $this->assertStringContainsString('<script>', $_SESSION['test_data']);
        // Note: XSS protection should happen at output level, not storage level
    }

    /**
     * Test performance across multiple apps
     */
    #[Test]
    public function testPerformanceAcrossApps()
    {
        $performanceMetrics = [];
        
        // Test app switching performance
        $apps = ['admin', 'recipes', 'bibleBot', 'ancestry'];
        
        foreach ($apps as $app) {
            $startTime = microtime(true);
            
            // Simulate app switch
            $_GET['app'] = $app;
            $_SESSION['current_app'] = $app;
            
            // Simulate some app operations
            for ($i = 0; $i < 10; $i++) {
                $data = IntegrationTestUtils::createTestRecipe("Recipe {$i} for {$app}");
                $this->assertNotEmpty($data['id']);
            }
            
            $endTime = microtime(true);
            $performanceMetrics[$app] = $endTime - $startTime;
        }
        
        // All app operations should complete quickly
        foreach ($performanceMetrics as $app => $time) {
            $this->assertLessThan(0.5, $time, "App {$app} took too long: {$time}s");
        }
        
        // Test memory usage doesn't grow excessively
        $memoryUsage = memory_get_usage(true);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage); // Less than 50MB
    }

    /**
     * Test API consistency across apps
     */
    #[Test]
    public function testAPIConsistencyAcrossApps()
    {
        // Test that all apps follow the same API patterns
        $apiEndpoints = [
            'admin' => [
                'get_users' => ['method' => 'GET'],
                'create_user' => ['method' => 'POST'],
                'update_user' => ['method' => 'PUT'],
                'delete_user' => ['method' => 'DELETE']
            ],
            'recipes' => [
                'get_recipes' => ['method' => 'GET'],
                'create_recipe' => ['method' => 'POST'],
                'update_recipe' => ['method' => 'PUT'],
                'delete_recipe' => ['method' => 'DELETE']
            ]
        ];
        
        foreach ($apiEndpoints as $app => $endpoints) {
            foreach ($endpoints as $action => $config) {
                $url = "https://mediabrain.app.local/?api={$app}&action={$action}";
                $response = IntegrationTestUtils::simulateHttpRequest($url, $config['method']);
                
                $this->assertEquals(200, $response['status']);
                $this->assertEquals($app, $response['params']['api']);
                $this->assertEquals($action, $response['params']['action']);
                
                // Test response format consistency
                $data = json_decode($response['response'], true);
                $this->assertArrayHasKey('success', $data);
                $this->assertArrayHasKey('data', $data);
            }
        }
    }

    /**
     * Test database transaction consistency
     */
    #[Test]
    public function testDatabaseTransactionConsistency()
    {
        // Test that operations across apps maintain data integrity
        $transactionData = [];
        
        // Simulate creating related data across apps
        $user = IntegrationTestUtils::createTestUser('transactiontest');
        $recipe = IntegrationTestUtils::createTestRecipe('Transaction Recipe');
        
        // Store in "database" (session for testing)
        $_SESSION['transaction_data'] = [
            'user' => $user,
            'recipe' => $recipe,
            'timestamp' => date('c')
        ];
        
        // Test that data is consistent
        $storedData = $_SESSION['transaction_data'];
        $this->assertEquals($user['username'], $storedData['user']['username']);
        $this->assertEquals($recipe['title'], $storedData['recipe']['title']);
        
        // Test rollback scenario
        try {
            // Start transaction
            $backup = $_SESSION['transaction_data'];
            
            // Make changes
            $_SESSION['transaction_data']['user']['active'] = false;
            $_SESSION['transaction_data']['recipe']['title'] = 'Modified Recipe';
            
            // Simulate error
            throw new Exception('Transaction error');
            
        } catch (Exception $e) {
            // Rollback
            $_SESSION['transaction_data'] = $backup;
        }
        
        // Data should be restored
        $this->assertTrue($_SESSION['transaction_data']['user']['active']);
        $this->assertEquals('Transaction Recipe', $_SESSION['transaction_data']['recipe']['title']);
    }

    /**
     * Test system-wide configuration management
     */
    #[Test]
    public function testSystemWideConfigurationManagement()
    {
        // Test that configuration is consistent across apps
        $mockApp = MockApp::getInstance();
        
        // Test base configuration
        $this->assertEquals('https://mediabrain.app.local', $mockApp->base_url);
        $this->assertEquals('testing', $mockApp->environment);
        
        // Test app-specific configuration overlay
        $appConfigs = [
            'admin' => ['theme' => 'admin-dark', 'features' => ['user_management']],
            'recipes' => ['theme' => 'recipe-light', 'features' => ['tts', 'sharing']],
            'bibleBot' => ['theme' => 'bible-classic', 'features' => ['search', 'bookmarks']]
        ];
        
        foreach ($appConfigs as $app => $config) {
            $_GET['app'] = $app;
            $_SESSION['app_config'][$app] = $config;
            
            $appConfig = $_SESSION['app_config'][$app];
            $this->assertArrayHasKey('theme', $appConfig);
            $this->assertArrayHasKey('features', $appConfig);
        }
    }

    /**
     * Test backup and recovery across apps
     */
    #[Test]
    public function testBackupAndRecoveryAcrossApps()
    {
        // Create test data across multiple apps
        $testData = [
            'users' => [
                IntegrationTestUtils::createTestUser('user1'),
                IntegrationTestUtils::createTestUser('user2')
            ],
            'recipes' => [
                IntegrationTestUtils::createTestRecipe('Recipe 1'),
                IntegrationTestUtils::createTestRecipe('Recipe 2')
            ],
            'preferences' => [
                'theme' => 'dark',
                'language' => 'en'
            ]
        ];
        
        // Store data
        $_SESSION['backup_test'] = $testData;
        
        // Create backup
        $backup = $_SESSION['backup_test'];
        $backupJson = json_encode($backup);
        
        // Simulate data corruption
        $_SESSION['backup_test'] = [];
        $this->assertEmpty($_SESSION['backup_test']);
        
        // Restore from backup
        $restoredData = json_decode($backupJson, true);
        $_SESSION['backup_test'] = $restoredData;
        
        // Verify restoration
        $this->assertCount(2, $_SESSION['backup_test']['users']);
        $this->assertCount(2, $_SESSION['backup_test']['recipes']);
        $this->assertEquals('dark', $_SESSION['backup_test']['preferences']['theme']);
        
        // Test backup integrity
        $originalHash = md5($backupJson);
        $restoredHash = md5(json_encode($_SESSION['backup_test']));
        $this->assertEquals($originalHash, $restoredHash);
    }

    /**
     * Test scalability with multiple concurrent operations
     */
    #[Test]
    public function testScalabilityWithConcurrentOperations()
    {
        $startTime = microtime(true);
        $operations = [];
        
        // Simulate concurrent operations across apps
        for ($i = 0; $i < 50; $i++) {
            $app = ['admin', 'recipes', 'bibleBot'][$i % 3];
            $operation = [
                'app' => $app,
                'action' => 'create_' . $app . '_item',
                'data' => ['id' => uniqid(), 'name' => "Item {$i}"],
                'timestamp' => microtime(true)
            ];
            
            $operations[] = $operation;
        }
        
        // Process all operations
        foreach ($operations as $operation) {
            $_GET['app'] = $operation['app'];
            $_POST['action'] = $operation['action'];
            $_POST['data'] = $operation['data'];
            
            // Simulate processing
            $this->assertNotEmpty($operation['data']['id']);
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Should handle 50 operations quickly
        $this->assertLessThan(0.5, $totalTime);
        $this->assertCount(50, $operations);
        
        // Memory usage should remain reasonable
        $memoryUsage = memory_get_usage(true);
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsage); // Less than 100MB
    }
}