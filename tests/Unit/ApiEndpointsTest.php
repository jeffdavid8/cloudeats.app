<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApiEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        // Clean output buffer from any previous tests
        if (ob_get_length()) {
            ob_clean();
        }
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        if (ob_get_length()) {
            ob_clean();
        }
        parent::tearDown();
    }

    public function testApiRouting()
    {
        // Test that main API file exists
        $this->assertTrue(file_exists(__DIR__ . '/../../html/api.php'));
    }

    public function testAdminApiExists()
    {
        // Test that admin API file exists
        $this->assertTrue(file_exists(__DIR__ . '/../../html/apps/admin/admin.api.php'));
    }

    public function testBibleBotApiExists()
    {
        // Test that bibleBot API file exists
        $this->assertTrue(file_exists(__DIR__ . '/../../html/apps/bibleBot/bibleBot.api.php'));
    }

    public function testAncestryApiExists()
    {
        // Test that ancestry API file exists
        $this->assertTrue(file_exists(__DIR__ . '/../../html/apps/ancestry/ancestry.api.php'));
    }

    public function testCsrfTokenAvailable()
    {
        // Test that we can generate CSRF tokens for API protection
        $token = \AuthManager::csrfToken();
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(16, strlen($token)); // Should be a reasonable length
    }

    public function testCsrfTokenValidation()
    {
        // Generate a token
        $token = \AuthManager::csrfToken();
        
        // Valid token should pass
        $this->assertTrue(\AuthManager::validateCsrf($token));
        
        // Invalid token should fail
        $this->assertFalse(\AuthManager::validateCsrf('invalid-token'));
        $this->assertFalse(\AuthManager::validateCsrf(''));
        $this->assertFalse(\AuthManager::validateCsrf(null));
    }

    public function testSessionManagement()
    {
        // Test that sessions are properly managed
        \AuthManager::manageSession();
        
        // Should have session activity tracking
        $this->assertArrayHasKey('last_activity', $_SESSION);
        $this->assertArrayHasKey('last_regenerate', $_SESSION);
    }

    public function testUserSessionFormat()
    {
        // Test the unified user session format
        createTestUserSession('testuser', false);
        
        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertIsArray($_SESSION['user']);
        $this->assertArrayHasKey('username', $_SESSION['user']);
        $this->assertArrayHasKey('role', $_SESSION['user']);
        $this->assertArrayHasKey('is_admin', $_SESSION['user']);
        
        $this->assertEquals('testuser', $_SESSION['user']['username']);
        $this->assertEquals('user', $_SESSION['user']['role']);
        $this->assertFalse($_SESSION['user']['is_admin']);
    }

    public function testAdminUserSessionFormat()
    {
        // Test admin user session format
        createTestUserSession('admin', true);
        
        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertTrue($_SESSION['user']['is_admin']);
        $this->assertEquals('admin', $_SESSION['user']['role']);
        $this->assertTrue(\AuthManager::userIsAdmin($_SESSION['user']));
    }
}
?>