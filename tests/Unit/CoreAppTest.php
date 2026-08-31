<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Core App Class Unit Tests
 * Tests the main App singleton class functionality
 */
class AppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean session and reset App instance before each test
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            'HTTP_HOST' => 'mediabrain.app.local',
            'HTTPS' => 'on',
            'SERVER_SOFTWARE' => 'nginx/1.29.2'
        ];
        
        // Reset the singleton instance using reflection
        $reflectedClass = new \ReflectionClass('\App');
        $instance = $reflectedClass->getProperty('_instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        $instance->setAccessible(false);
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testSingletonPattern()
    {
        $app1 = \App::getInstance();
        $app2 = \App::getInstance();
        
        $this->assertSame($app1, $app2, 'App should return the same instance (singleton pattern)');
        $this->assertInstanceOf('\App', $app1);
    }

    public function testInstanceWithApp()
    {
        $app = \App::getInstance('admin');
        
        $this->assertInstanceOf('\App', $app);
        $this->assertEquals('admin', $app->app);
    }

    public function testConfigurationLoading()
    {
        $app = \App::getInstance();
        
        // Config should be loaded
        $this->assertIsArray($app->config);
        
        // Basic config keys should exist
        $this->assertArrayHasKey('version', $app->config);
        $this->assertArrayHasKey('site_name', $app->config);
        $this->assertArrayHasKey('mysql', $app->config);
        
        // MySQL config should be array
        $this->assertIsArray($app->config['mysql']);
        $this->assertArrayHasKey('host', $app->config['mysql']);
        $this->assertArrayHasKey('database', $app->config['mysql']);
    }

    public function testAuthManagerInitialization()
    {
        $app = \App::getInstance();
        $authManager = $app->getAuthManager();
        
        $this->assertInstanceOf('\AuthManager', $authManager);
    }

    public function testCSRFTokenGeneration()
    {
        $app = \App::getInstance();
        
        // CSRF token should be set in session
        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertNotEmpty($_SESSION['csrf_token']);
        
        // Token should be consistent
        $token1 = \App::generateToken();
        $token2 = \App::generateToken();
        $this->assertNotEquals($token1, $token2, 'Generated tokens should be unique');
        
        // Tokens should be proper length
        $this->assertGreaterThan(10, strlen($token1));
        $this->assertGreaterThan(10, strlen($token2));
    }

    public function testCSRFTokenValidation()
    {
        $app = \App::getInstance();
        
        $validToken = $_SESSION['csrf_token'];
        
        // Valid token should validate
        $this->assertTrue(\App::validateCSRFToken($validToken));
        
        // Invalid tokens should fail
        $this->assertFalse(\App::validateCSRFToken('invalid-token'));
        $this->assertFalse(\App::validateCSRFToken(''));
        $this->assertFalse(\App::validateCSRFToken(null));
    }

    public function testContextManagement()
    {
        $app = \App::getInstance();
        
        // Test setting and getting context
        $app->set('test_key', 'test_value');
        $this->assertEquals('test_value', $app->get('test_key'));
        
        // Test default value
        $this->assertEquals('default', $app->get('nonexistent_key', 'default'));
        $this->assertNull($app->get('nonexistent_key'));
    }

    public function testCookieManagement()
    {
        $app = \App::getInstance();
        
        // Test setting cookie (note: headers aren't actually sent in tests)
        $app->setCookie('test_cookie', 'test_value');
        
        // Mock cookie in $_COOKIE for testing retrieval
        $_COOKIE['test_cookie'] = 'test_value';
        
        $this->assertEquals('test_value', $app->getCookie('test_cookie'));
        $this->assertEquals('default', $app->getCookie('nonexistent_cookie', 'default'));
        $this->assertFalse($app->getCookie('nonexistent_cookie'));
    }

    public function testErrorRegistration()
    {
        $app = \App::getInstance();
        
        // Test string error
        $app->registerError('Test error message');
        $this->assertCount(1, $app->errors);
        
        // Test array error
        $app->registerError([
            'message' => 'Test array error',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        $this->assertCount(2, $app->errors);
    }

    public function testRenderMethod()
    {
        // Create a temporary view file for testing
        $testViewDir = dirname(__DIR__) . '/data/views';
        if (!is_dir($testViewDir)) {
            mkdir($testViewDir, 0755, true);
        }
        
        $testViewFile = $testViewDir . '/test_view.php';
        file_put_contents($testViewFile, '<?php echo "Test view: " . $test_var; ?>');
        
        $app = \App::getInstance();
        
        // Mock the directory structure
        $app->dir = dirname(__DIR__) . '/data';
        
        $output = $app->render('views/test_view.php', ['test_var' => 'Hello World'], true);
        $this->assertEquals('Test view: Hello World', $output);
        
        // Clean up
        unlink($testViewFile);
        rmdir($testViewDir);
    }

    public function testDefaultMetaImageArray()
    {
        $app = \App::getInstance();
        $metaImage = $app->getDefaultMetaImageArray();
        
        $this->assertIsArray($metaImage);
        $this->assertArrayHasKey('image', $metaImage);
        $this->assertArrayHasKey('image_width', $metaImage);
        $this->assertArrayHasKey('image_height', $metaImage);
        
        $this->assertStringContainsString('mb-logo', $metaImage['image']);
    }

    public function testPageTitle()
    {
        $app = \App::getInstance();
        
        // Should return site title by default
        $title = $app->getPageTitle();
        $this->assertIsString($title);
    }

    public function testStructureLoading()
    {
        $app = \App::getInstance();
        
        // Structure should be loaded (if structure.json exists)
        $structure = $app->structure();
        $this->assertIsArray($structure);
    }

    public function testEventLoggerInitialization()
    {
        $app = \App::getInstance();
        $eventLogger = $app->getEventLogger();
        
        // Event logger might be null if not available, but method should exist
        $this->assertTrue(method_exists($app, 'getEventLogger'));
    }

    public function testLogEventMethod()
    {
        $app = \App::getInstance();
        
        // Should not throw error even if event logger is null
        $app->logEvent('info', 'test', 'Test log message', ['key' => 'value']);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testAppMapping()
    {
        // Test internal app name mapping
        $app1 = \App::getInstance('biblebot');
        $this->assertEquals('bibleBot', $app1->app);
        
        // Reset instance for next test
        $reflectedClass = new \ReflectionClass('\App');
        $instance = $reflectedClass->getProperty('_instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        
        $app2 = \App::getInstance('Weather');
        $this->assertEquals('weather', $app2->app);
    }

    public function testConfigWithoutEnvFile()
    {
        // Test that app loads with fallback config when .env file doesn't exist
        $app = \App::getInstance();
        
        // Should have fallback config values
        $this->assertIsArray($app->config);
        $this->assertArrayHasKey('version', $app->config);
        $this->assertArrayHasKey('domain', $app->config);
        
        // Should have default values
        $this->assertEquals('mediabrain.app', $app->config['domain']);
        $this->assertEquals('Mediabrain', $app->config['site_name']);
    }

    public function testSecurityHeaders()
    {
        // Test that security headers are set during initialization
        $app = \App::getInstance();
        
        // Headers should be set (though we can't test actual header output in PHPUnit)
        // We can verify the app initializes without error
        $this->assertInstanceOf('\App', $app);
        $this->assertTrue(class_exists('SecurityHeaders'));
    }

    public function testMysqlConfigStructure()
    {
        $app = \App::getInstance();
        
        $mysqlConfig = $app->config['mysql'];
        
        // Required MySQL config keys
        $requiredKeys = ['host', 'port', 'database', 'username', 'password'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $mysqlConfig, "MySQL config should have '$key'");
        }
        
        // Port should be numeric
        $this->assertIsNumeric($mysqlConfig['port']);
    }
}