<?php

namespace MediaBrain\Tests\Integration;

use PHPUnit\Framework\TestCase;

class AuthenticationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        clearTestSession();
        // Clean any output buffers
        if (ob_get_length()) {
            ob_clean();
        }
    }

    protected function tearDown(): void
    {
        clearTestSession();
        if (ob_get_length()) {
            ob_clean();
        }
        parent::tearDown();
    }

    public function testAnonymousUserAccess()
    {
        // Test that non-logged-in users have no session data
        $this->assertEmpty($_SESSION);
        
        // Test that admin check fails for anonymous users
        $this->assertFalse(\AuthManager::userIsAdmin(null));
        $this->assertFalse(\AuthManager::userIsAdmin(''));
        
        // Anonymous should not be admin via array format either
        $this->assertFalse(\AuthManager::userIsAdmin([]));
    }

    public function testNormalUserSession()
    {
        // Create a normal user session
        $user = createTestUserSession('normaluser', false);
        
        // Verify session format
        $this->assertIsArray($user);
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('role', $user);
        $this->assertArrayHasKey('is_admin', $user);
        
        // Verify normal user is not admin
        $this->assertFalse($user['is_admin']);
        $this->assertEquals('user', $user['role']);
        $this->assertFalse(\AuthManager::userIsAdmin($user));
    }

    public function testAdminUserSession()
    {
        // Create an admin user session using the special 'admin' username
        $user = createTestUserSession('admin', true);
        
        // Verify admin privileges
        $this->assertTrue($user['is_admin']);
        $this->assertEquals('admin', $user['role']);
        $this->assertTrue(\AuthManager::userIsAdmin($user));
    }

    public function testLegacyStringUserCheck()
    {
        // Test legacy string-based user checking (for backward compatibility)
        $this->assertTrue(\AuthManager::userIsAdmin('admin'));
        $this->assertFalse(\AuthManager::userIsAdmin('user'));
        $this->assertFalse(\AuthManager::userIsAdmin('normaluser'));
    }

    public function testSessionTimeout()
    {
        // Set up an old session activity
        $_SESSION['last_activity'] = time() - 3600; // 1 hour ago
        
        // This should trigger session cleanup
        \AuthManager::manageSession();
        
        // Session should have been refreshed with new activity time
        $this->assertGreaterThan(time() - 10, $_SESSION['last_activity']);
    }

    public function testCsrfTokenPersistence()
    {
        // Generate a token
        $token1 = \AuthManager::csrfToken();
        
        // Same session should return same token
        $token2 = \AuthManager::csrfToken();
        $this->assertEquals($token1, $token2);
        
        // Token should persist in session
        $this->assertEquals($token1, $_SESSION['_csrf_token']);
    }

    public function testCsrfTokenValidation()
    {
        $token = \AuthManager::csrfToken();
        
        // Valid tokens should validate
        $this->assertTrue(\AuthManager::validateCsrf($token));
        
        // Tampered tokens should fail
        $this->assertFalse(\AuthManager::validateCsrf($token . 'x'));
        $this->assertFalse(\AuthManager::validateCsrf(substr($token, 1)));
        
        // Empty/null should fail
        $this->assertFalse(\AuthManager::validateCsrf(''));
        $this->assertFalse(\AuthManager::validateCsrf(null));
    }

    public function testUserSessionTransition()
    {
        // Start as anonymous
        $this->assertEmpty($_SESSION);
        
        // Login as normal user
        createTestUserSession('testuser', false);
        $this->assertFalse(\AuthManager::userIsAdmin($_SESSION['user']));
        
        // Clear session (logout)
        clearTestSession();
        $this->assertEmpty($_SESSION);
        
        // Login as admin
        createTestUserSession('admin', true);
        $this->assertTrue(\AuthManager::userIsAdmin($_SESSION['user']));
    }
}
?>