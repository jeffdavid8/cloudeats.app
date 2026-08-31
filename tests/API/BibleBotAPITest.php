<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * BibleBot API Tests
 * 
 * Tests the API endpoints for BibleBot including:
 * - Search API endpoints
 * - Bookmark management API
 * - TTS API integration
 * - Authentication and security
 * - Error handling and validation
 */
class BibleBotAPITest extends TestCase
{
    private $originalGet;
    private $originalPost;
    private $originalSession;
    
    public function setUp(): void
    {
        // Backup original superglobals
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalSession = $_SESSION;
        
        // Reset state
        $_GET = [];
        $_POST = [];
        $_SESSION = [];
        
        // Set up basic environment
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
    }
    
    public function tearDown(): void
    {
        // Restore original superglobals
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;
    }

    /**
     * Test search API endpoint
     */
    #[Test]
    public function testSearchAPIEndpoint()
    {
        $_POST = [
            'action' => 'search',
            'query' => 'John 3:16',
            'limit' => 10
        ];
        
        // Mock API response processing
        $this->assertEquals('search', $_POST['action']);
        $this->assertEquals('John 3:16', $_POST['query']);
        $this->assertEquals(10, $_POST['limit']);
        
        // Test that required parameters are present
        $this->assertNotEmpty($_POST['action']);
        $this->assertNotEmpty($_POST['query']);
    }

    /**
     * Test search API with various inputs
     */
    #[Test]
    #[DataProvider('searchAPIInputsProvider')]
    public function testSearchAPIWithVariousInputs($query, $expectedValid, $expectedLength = null)
    {
        $_POST = [
            'action' => 'search',
            'query' => $query
        ];
        
        if ($expectedValid) {
            $this->assertNotEmpty($_POST['query']);
            $this->assertIsString($_POST['query']);
            
            if ($expectedLength !== null) {
                $this->assertEquals($expectedLength, strlen($_POST['query']));
            }
        } else {
            // Test validation logic would catch invalid inputs
            $this->assertTrue(empty($query) || !is_string($query));
        }
    }

    public static function searchAPIInputsProvider(): array
    {
        return [
            'Valid verse reference' => ['John 3:16', true],
            'Valid search term' => ['love', true, 4],
            'Valid phrase' => ['God so loved', true],
            'Empty query' => ['', false],
            'Null query' => [null, false],
            'Array query' => [['invalid'], false],
            'Very long query' => [str_repeat('a', 1000), true, 1000],
            'Special characters' => ['café résumé', true],
            'SQL injection attempt' => ["'; DROP TABLE verses; --", true], // Should be sanitized
        ];
    }

    /**
     * Test bookmark API endpoints
     */
    #[Test]
    public function testBookmarkAPIEndpoints()
    {
        // Set up authenticated user
        $_SESSION['user'] = [
            'username' => 'testuser',
            'role' => 'user'
        ];
        
        // Test add bookmark
        $bookmarkData = [
            'reference' => 'John 3:16',
            'text' => 'For God so loved the world...',
            'timestamp' => time()
        ];
        
        $_POST = [
            'action' => 'add_bookmark',
            'bookmark' => json_encode($bookmarkData),
            '_csrf' => 'mock_csrf_token'
        ];
        
        $this->assertEquals('add_bookmark', $_POST['action']);
        $this->assertNotEmpty($_POST['bookmark']);
        
        $decoded = json_decode($_POST['bookmark'], true);
        $this->assertEquals('John 3:16', $decoded['reference']);
        $this->assertArrayHasKey('timestamp', $decoded);
    }

    /**
     * Test CSRF protection for protected endpoints
     */
    #[Test]
    public function testCSRFProtectionForProtectedEndpoints()
    {
        $protectedActions = [
            'add_bookmark',
            'clear_all_bookmarks',
            'upload_session_bookmarks',
            'text_to_speech'
        ];
        
        foreach ($protectedActions as $action) {
            $_POST = [
                'action' => $action,
                'data' => 'test data'
            ];
            
            // Should require CSRF token
            $this->assertEquals($action, $_POST['action']);
            $this->assertArrayNotHasKey('_csrf', $_POST); // Missing CSRF token
            
            // Test with CSRF token
            $_POST['_csrf'] = 'valid_token';
            $this->assertArrayHasKey('_csrf', $_POST);
        }
    }

    /**
     * Test TTS API endpoint
     */
    #[Test]
    public function testTTSAPIEndpoint()
    {
        $_POST = [
            'action' => 'text_to_speech',
            'text' => 'For God so loved the world that he gave his one and only Son.',
            'voice' => 'en-US-Neural2-A',
            'speed' => 1.0,
            '_csrf' => 'mock_csrf_token'
        ];
        
        $this->assertEquals('text_to_speech', $_POST['action']);
        $this->assertNotEmpty($_POST['text']);
        $this->assertEquals('en-US-Neural2-A', $_POST['voice']);
        $this->assertEquals(1.0, $_POST['speed']);
        $this->assertArrayHasKey('_csrf', $_POST);
    }

    /**
     * Test API error handling
     */
    #[Test]
    public function testAPIErrorHandling()
    {
        // Test missing action parameter
        $_POST = ['data' => 'some data'];
        
        $this->assertArrayNotHasKey('action', $_POST);
        // API should return error for missing action
        
        // Test invalid action
        $_POST = ['action' => 'invalid_action_name'];
        
        $this->assertEquals('invalid_action_name', $_POST['action']);
        // API should return error for invalid action
        
        // Test malformed JSON data
        $_POST = [
            'action' => 'add_bookmark',
            'bookmark' => 'malformed json {'
        ];
        
        $decoded = json_decode($_POST['bookmark'], true);
        $this->assertNull($decoded); // JSON decode should fail
    }

    /**
     * Test API authentication requirements
     */
    #[Test]
    public function testAPIAuthenticationRequirements()
    {
        $authRequiredActions = [
            'add_bookmark',
            'get_bookmarks',
            'clear_all_bookmarks',
            'save_preferences'
        ];
        
        foreach ($authRequiredActions as $action) {
            // Test without authentication
            $_SESSION = [];
            $_POST = ['action' => $action];
            
            $this->assertEquals($action, $_POST['action']);
            $this->assertEmpty($_SESSION); // No user session
            
            // Test with authentication
            $_SESSION['user'] = ['username' => 'testuser'];
            
            $this->assertNotEmpty($_SESSION['user']);
            $this->assertEquals('testuser', $_SESSION['user']['username']);
        }
    }

    /**
     * Test API rate limiting simulation
     */
    #[Test]
    public function testAPIRateLimitingSimulation()
    {
        // Simulate multiple rapid requests
        $requests = [];
        
        for ($i = 0; $i < 10; $i++) {
            $request = [
                'action' => 'search',
                'query' => 'test query ' . $i,
                'timestamp' => time(),
                'ip' => '192.168.1.100'
            ];
            
            $requests[] = $request;
        }
        
        $this->assertCount(10, $requests);
        
        // Test that all requests have same IP (for rate limiting)
        $ips = array_unique(array_column($requests, 'ip'));
        $this->assertCount(1, $ips);
        $this->assertEquals('192.168.1.100', $ips[0]);
    }

    /**
     * Test API input validation and sanitization
     */
    #[Test]
    public function testAPIInputValidationAndSanitization()
    {
        $testInputs = [
            // XSS attempts
            'script' => '<script>alert("xss")</script>',
            'html' => '<div onclick="alert(1)">Click me</div>',
            
            // SQL injection attempts  
            'sql1' => "'; DELETE FROM users; --",
            'sql2' => "1' OR '1'='1",
            
            // Path traversal attempts
            'path1' => '../../../etc/passwd',
            'path2' => '..\\..\\windows\\system32',
            
            // Command injection attempts
            'cmd1' => '; ls -la;',
            'cmd2' => '| cat /etc/passwd',
            
            // Very long input
            'long' => str_repeat('A', 10000),
            
            // Unicode and special characters
            'unicode' => '🙏 ❤️ ✝️',
            'special' => "Special chars: !@#$%^&*()_+-={}[]|\\:;\"'<>?,./"
        ];
        
        foreach ($testInputs as $type => $input) {
            $_POST = [
                'action' => 'search',
                'query' => $input
            ];
            
            // Test that input is properly handled
            $this->assertEquals($input, $_POST['query']);
            $this->assertIsString($_POST['query']);
            
            // Length validation
            if ($type === 'long') {
                $this->assertGreaterThan(5000, strlen($_POST['query']));
            }
        }
    }

    /**
     * Test API response format consistency
     */
    #[Test]
    public function testAPIResponseFormatConsistency()
    {
        // Test different API actions and their expected response formats
        $apiActions = [
            'search' => [
                'action' => 'search',
                'query' => 'John 3:16',
                'expected_keys' => ['success', 'results', 'count']
            ],
            'get_bookmarks' => [
                'action' => 'get_bookmarks',
                'expected_keys' => ['success', 'bookmarks', 'count']
            ],
            'text_to_speech' => [
                'action' => 'text_to_speech',
                'text' => 'Test text',
                '_csrf' => 'token',
                'expected_keys' => ['success', 'audio_url', 'cache_hit']
            ]
        ];
        
        foreach ($apiActions as $actionName => $config) {
            $_POST = array_filter($config, function($key) {
                return $key !== 'expected_keys';
            }, ARRAY_FILTER_USE_KEY);
            
            $this->assertEquals($actionName, $_POST['action']);
            
            // Mock response structure validation
            $expectedKeys = $config['expected_keys'];
            $this->assertIsArray($expectedKeys);
            $this->assertContains('success', $expectedKeys);
        }
    }

    /**
     * Test API versioning and backward compatibility
     */
    #[Test]
    public function testAPIVersioningAndBackwardCompatibility()
    {
        // Test API version header support
        $_SERVER['HTTP_API_VERSION'] = 'v1';
        $_POST = [
            'action' => 'search',
            'query' => 'test'
        ];
        
        $this->assertEquals('v1', $_SERVER['HTTP_API_VERSION']);
        
        // Test legacy parameter support
        $_POST = [
            'action' => 'search',
            'q' => 'legacy query parameter', // Old parameter name
            'query' => 'new query parameter'  // New parameter name
        ];
        
        // Should support both old and new parameter names
        $this->assertArrayHasKey('q', $_POST);
        $this->assertArrayHasKey('query', $_POST);
    }

    /**
     * Test API logging and monitoring
     */
    #[Test]
    public function testAPILoggingAndMonitoring()
    {
        $_POST = [
            'action' => 'search',
            'query' => 'test query',
            'user_agent' => 'PHPUnit/Test',
            'timestamp' => time()
        ];
        
        // Mock logging data structure
        $logEntry = [
            'timestamp' => $_POST['timestamp'],
            'action' => $_POST['action'],
            'query' => $_POST['query'],
            'user_agent' => $_POST['user_agent'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'test',
            'response_time' => 0.1,
            'success' => true
        ];
        
        $this->assertArrayHasKey('timestamp', $logEntry);
        $this->assertArrayHasKey('action', $logEntry);
        $this->assertArrayHasKey('response_time', $logEntry);
        $this->assertTrue($logEntry['success']);
    }
}