<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Utility Functions Unit Tests
 * Tests the global utility functions in util.php
 */
class UtilityFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean session and reset state
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            'HTTP_HOST' => 'mediabrain.app.local',
            'HTTPS' => 'on',
            'SERVER_SOFTWARE' => 'nginx/1.29.2'
        ];
        
        // Reset App singleton
        $reflectedClass = new \ReflectionClass('\App');
        $instance = $reflectedClass->getProperty('_instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
        $instance->setAccessible(false);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testProtocolFunction()
    {
        // Test HTTPS detection
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https', protocol());
        
        // Test HTTP fallback
        unset($_SERVER['HTTPS']);
        $this->assertEquals('http', protocol());
        
        // Test X-Forwarded-Proto header
        unset($_SERVER['HTTPS']);
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $this->assertEquals('https', protocol());
        
        // Test HTTP with X-Forwarded-Proto
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $this->assertEquals('http', protocol());
    }

    public function testIsDevelopment()
    {
        // Test localhost
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertTrue(is_development());
        
        // Test localhost with port
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        $this->assertTrue(is_development());
        
        // Test 127.0.0.1
        $_SERVER['HTTP_HOST'] = '127.0.0.1';
        $this->assertTrue(is_development());
        
        // Test .local domain
        $_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
        $this->assertTrue(is_development());
        
        // Test development ports
        $_SERVER['HTTP_HOST'] = 'example.com:8080';
        $this->assertTrue(is_development());
        
        $_SERVER['HTTP_HOST'] = 'example.com:3000';
        $this->assertTrue(is_development());
        
        $_SERVER['HTTP_HOST'] = 'example.com:8000';
        $this->assertTrue(is_development());
        
        // Test production domain
        $_SERVER['HTTP_HOST'] = 'mediabrain.app';
        $this->assertFalse(is_development());
        
        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertFalse(is_development());
    }

    public function testIsProduction()
    {
        // Production should be inverse of development
        $_SERVER['HTTP_HOST'] = 'mediabrain.app';
        $this->assertTrue(is_production());
        $this->assertFalse(is_development());
        
        $_SERVER['HTTP_HOST'] = 'localhost';
        $this->assertFalse(is_production());
        $this->assertTrue(is_development());
    }

    public function testIsCloudRun()
    {
        // Test without environment variables
        $this->assertFalse(isCloudRun());
        
        // Test with K_SERVICE
        putenv('K_SERVICE=test-service');
        $this->assertTrue(isCloudRun());
        putenv('K_SERVICE=');
        
        // Test with GOOGLE_CLOUD_PROJECT
        putenv('GOOGLE_CLOUD_PROJECT=test-project');
        $this->assertTrue(isCloudRun());
        putenv('GOOGLE_CLOUD_PROJECT=');
    }

    public function testGetBaseUrl()
    {
        // Test development URL
        $_SERVER['HTTP_HOST'] = 'mediabrain.app.local';
        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals('https://mediabrain.app.local', get_base_url());
        
        // Test production URL (should always use mediabrain.app)
        $_SERVER['HTTP_HOST'] = 'example.com';
        unset($_SERVER['HTTPS']);
        $this->assertEquals('https://mediabrain.app', get_base_url());
        
        // Test localhost
        $_SERVER['HTTP_HOST'] = 'localhost:8080';
        unset($_SERVER['HTTPS']);
        $this->assertEquals('http://localhost:8080', get_base_url());
    }

    public function testGetVar()
    {
        // Test with value present
        $_GET['test_param'] = 'test_value';
        $this->assertEquals('test_value', get_var('test_param'));
        
        // Test with default value
        $this->assertEquals('default', get_var('nonexistent', 'default'));
        
        // Test with null default
        $this->assertNull(get_var('nonexistent'));
    }

    public function testConfigFunction()
    {
        // Initialize app to set up config
        $app = \App::getInstance();
        
        // Test getting full config
        $fullConfig = config();
        $this->assertIsArray($fullConfig);
        
        // Test getting specific key
        $siteName = config('site_name');
        $this->assertIsString($siteName);
        
        // Test getting key with default
        $nonexistent = config('nonexistent_key', 'default_value');
        $this->assertEquals('default_value', $nonexistent);
        
        // Test getting nested key
        $mysqlHost = config('mysql');
        $this->assertIsArray($mysqlHost);
    }

    public function testRenderFunction()
    {
        // Initialize app
        $app = \App::getInstance();
        
        // Create a test view file
        $testViewDir = dirname(__DIR__) . '/data/views';
        if (!is_dir($testViewDir)) {
            mkdir($testViewDir, 0755, true);
        }
        
        $testViewFile = $testViewDir . '/test_render.php';
        file_put_contents($testViewFile, '<?php echo "Render test: " . ($test_var ?? "no var"); ?>');
        
        // Mock the app directory
        $app->dir = dirname(__DIR__) . '/data';
        
        // Test render without return
        ob_start();
        render('views/test_render.php', ['test_var' => 'success']);
        $output = ob_get_clean();
        $this->assertEquals('Render test: success', $output);
        
        // Test render with return
        $returned = render('views/test_render.php', ['test_var' => 'returned'], true);
        $this->assertEquals('Render test: returned', $returned);
        
        // Clean up
        unlink($testViewFile);
        if (is_dir($testViewDir)) {
            rmdir($testViewDir);
        }
    }

    public function testAppDir()
    {
        $app = \App::getInstance();
        $appDir = app_dir();
        
        $this->assertIsString($appDir);
        $this->assertEquals($app->app_dir, $appDir);
    }

    public function testAppPath()
    {
        $app = \App::getInstance();
        $appPath = app_path();
        
        $this->assertIsString($appPath);
        $this->assertEquals($app->app_path, $appPath);
    }

    public function testAppRootUrl()
    {
        $app = \App::getInstance();
        $rootUrl = app_root_url();
        
        $this->assertIsString($rootUrl);
        $this->assertEquals($app->root_url, $rootUrl);
    }

    public function testAppRequire()
    {
        $app = \App::getInstance('admin');
        
        // Test requiring a non-existent file
        $result = app_require('nonexistent/file.php');
        $this->assertFalse($result);
        
        // We can't easily test successful require without creating actual files
        // in the app directory structure, which is complex for unit tests
    }

    public function testDebugFunction()
    {
        // Test that debug function exists and doesn't throw errors
        ob_start();
        debug(['test' => 'data'], 'Test debug message');
        $output = ob_get_clean();
        
        // Debug function writes to error log, not output
        $this->assertEquals('', $output);
    }

    public function testErrorFunction()
    {
        $app = \App::getInstance();
        
        // Clear any existing errors
        $app->errors = [];
        
        // Test error function
        error('Test error message');
        
        $this->assertCount(1, $app->errors);
    }

    public function testJsonReadFile()
    {
        // Create a test JSON file
        $testFile = dirname(__DIR__) . '/data/test.json';
        $testData = ['test' => 'data', 'number' => 123];
        
        // Ensure data directory exists
        $dataDir = dirname($testFile);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        file_put_contents($testFile, json_encode($testData));
        
        // Test reading
        $result = json_read_file($testFile);
        $this->assertEquals($testData, $result);
        
        // Clean up
        unlink($testFile);
        
        // Test reading non-existent file
        $result = json_read_file('/path/to/nonexistent.json');
        $this->assertNull($result);
    }

    public function testGetSynonyms()
    {
        // Test that function exists and returns array
        $result = getSynonyms('test');
        $this->assertIsArray($result);
        $this->assertEmpty($result); // Current implementation returns empty array
    }
}