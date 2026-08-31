<?php

namespace MediaBrain\Tests\Unit;

use PHPUnit\Framework\TestCase;
use MediaBrain\Tests\TestCase as MediaBrainTestCase;

require_once __DIR__ . '/../../html/includes/AuthManager.php';

class AuthManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean session before each test
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up after each test
        $_SESSION = [];
        parent::tearDown();
    }

    public function testCsrfTokenGeneration()
    {
        $token1 = \AuthManager::csrfToken();
        $token2 = \AuthManager::csrfToken();
        
        // Should return same token on subsequent calls
        $this->assertEquals($token1, $token2);
        
        // Token should be non-empty
        $this->assertNotEmpty($token1);
        
        // Token should be in session
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        $this->assertEquals($token1, $_SESSION['_csrf_token']);
    }

    public function testValidCsrfValidation()
    {
        $token = \AuthManager::csrfToken();
        
        // Valid token should validate
        $this->assertTrue(\AuthManager::validateCsrf($token));
    }

    public function testInvalidCsrfValidation()
    {
        // Invalid token should fail
        $this->assertFalse(\AuthManager::validateCsrf('invalid-token'));
        
        // Empty token should fail
        $this->assertFalse(\AuthManager::validateCsrf(''));
        
        // Null token should fail
        $this->assertFalse(\AuthManager::validateCsrf(null));
    }

    public function testUserIsAdminWithStringUser()
    {
        // Test with string username (legacy format)
        $this->assertFalse(\AuthManager::userIsAdmin('normaluser'));
        $this->assertTrue(\AuthManager::userIsAdmin('admin'));
    }

    public function testUserIsAdminWithArrayUser()
    {
        // Test with array format (current format)
        $normalUser = ['username' => 'user', 'role' => 'user', 'is_admin' => false];
        $adminUser = ['username' => 'admin', 'role' => 'admin', 'is_admin' => true];
        $adminUserByRole = ['username' => 'admin2', 'role' => 'admin'];
        
        $this->assertFalse(\AuthManager::userIsAdmin($normalUser));
        $this->assertTrue(\AuthManager::userIsAdmin($adminUser));
        $this->assertTrue(\AuthManager::userIsAdmin($adminUserByRole));
    }

    public function testCheckCredentials()
    {
        // This would require mocking the user storage
        // For now, test that it returns boolean
        $result = \AuthManager::checkCredentials('testuser', 'testpass');
        $this->assertIsBool($result);
    }

    public function testSessionManagement()
    {
        // Test session starting
        \AuthManager::manageSession();
        
        // Session should be active
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
    }

    public function testRequireLoginRedirection()
    {
        // This test needs to capture headers, which is complex in unit tests
        // We'll test this in integration tests instead
        $this->markTestSkipped('Header testing requires integration test setup');
    }

    public function testRequireAdminWithNoSession()
    {
        // Clear any user session
        unset($_SESSION['user']);
        
        // Should not have admin access
        $this->expectOutputRegex('/.*authentication.*|.*login.*/i');
        
        try {
            \AuthManager::requireAdmin();
        } catch (\Exception $e) {
            // Expected to exit or redirect
            $this->assertTrue(true);
        }
    }

    public function testRequireAdminWithNormalUser()
    {
        // Set up normal user session
        $_SESSION['user'] = ['username' => 'normaluser', 'role' => 'user', 'is_admin' => false];
        
        // Should not have admin access
        $this->expectOutputRegex('/.*admin.*required.*/i');
        
        try {
            \AuthManager::requireAdmin();
        } catch (\Exception $e) {
            // Expected to exit or redirect
            $this->assertTrue(true);
        }
    }

    public function testTokenGeneration()
    {
        $token1 = \AuthManager::csrfToken();
        
        // Clear session and generate new token
        unset($_SESSION['_csrf_token']);
        $token2 = \AuthManager::csrfToken();
        
        // Should be different tokens
        $this->assertNotEquals($token1, $token2);
        
        // Both should be valid lengths
        $this->assertGreaterThan(10, strlen($token1));
        $this->assertGreaterThan(10, strlen($token2));
    }
}
?>