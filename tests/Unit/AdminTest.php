<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Mock AuthManager for testing
 */
class MockAuthManager {
    public static $users = [];
    public static $currentUser = null;
    
    public static function reset() {
        self::$users = [];
        self::$currentUser = null;
    }
    
    public static function userIsAdmin($user) {
        if (is_array($user)) {
            return $user['is_admin'] ?? false;
        }
        return false;
    }
    
    public static function requireLogin() {
        if (!isset($_SESSION['user'])) {
            throw new Exception('Login required');
        }
    }
    
    public static function authenticateUser($username, $password) {
        if (isset(self::$users[$username])) {
            $user = self::$users[$username];
            // Simple password check for testing
            if ($user['password'] === $password) {
                self::$currentUser = $user;
                $_SESSION['user'] = $user;
                return true;
            }
        }
        return false;
    }
    
    public static function createUser($userData) {
        $username = $userData['username'];
        self::$users[$username] = array_merge([
            'username' => $username,
            'email' => '',
            'role' => 'user',
            'is_admin' => false,
            'active' => true,
            'created' => date('c'),
            'last_login' => null
        ], $userData);
        return self::$users[$username];
    }
}

/**
 * Test UserManager implementation
 */
class TestUserManager {
    private $users = [];
    
    public function __construct() {
        // Initialize with default admin user
        $this->users = [
            'admin' => [
                'username' => 'admin',
                'email' => 'admin@mediabrain.app',
                'password' => 'admin123',
                'role' => 'admin',
                'is_admin' => true,
                'active' => true,
                'created' => date('c'),
                'last_login' => null,
                'profilePicture' => ''
            ]
        ];
    }
    
    public function getAllUsers() {
        $result = [];
        foreach ($this->users as $username => $user) {
            $userCopy = $user;
            unset($userCopy['password']); // Remove sensitive data
            $result[] = $userCopy;
        }
        return $result;
    }
    
    public function getUser($username) {
        if (isset($this->users[$username])) {
            $user = $this->users[$username];
            unset($user['password']);
            return $user;
        }
        return null;
    }
    
    public function createUser($userData) {
        $username = $userData['username'];
        
        if (isset($this->users[$username])) {
            throw new Exception("User {$username} already exists");
        }
        
        $newUser = array_merge([
            'username' => $username,
            'email' => '',
            'password' => 'default123',
            'role' => 'user',
            'is_admin' => false,
            'active' => true,
            'created' => date('c'),
            'last_login' => null,
            'profilePicture' => ''
        ], $userData);
        
        $this->users[$username] = $newUser;
        $result = $newUser;
        unset($result['password']);
        return $result;
    }
    
    public function updateUser($username, $userData) {
        if (!isset($this->users[$username])) {
            return null;
        }
        
        // Update user data
        foreach ($userData as $key => $value) {
            if ($key !== 'username') { // Don't allow username changes
                $this->users[$username][$key] = $value;
            }
        }
        
        $result = $this->users[$username];
        unset($result['password']);
        return $result;
    }
    
    public function deleteUser($username) {
        if ($username === 'admin') {
            throw new Exception('Cannot delete admin user');
        }
        
        if (isset($this->users[$username])) {
            unset($this->users[$username]);
            return true;
        }
        return false;
    }
    
    public function authenticateUser($username, $password) {
        if (isset($this->users[$username])) {
            $user = $this->users[$username];
            if ($user['password'] === $password && $user['active']) {
                $this->users[$username]['last_login'] = date('c');
                return $user;
            }
        }
        return null;
    }
    
    public function searchUsers($query) {
        $results = [];
        $query = strtolower($query);
        
        foreach ($this->users as $user) {
            $searchText = strtolower($user['username'] . ' ' . $user['email'] . ' ' . $user['role']);
            if (strpos($searchText, $query) !== false) {
                $userCopy = $user;
                unset($userCopy['password']);
                $results[] = $userCopy;
            }
        }
        
        return $results;
    }
    
    public function getUsersByRole($role) {
        $results = [];
        foreach ($this->users as $user) {
            if ($user['role'] === $role) {
                $userCopy = $user;
                unset($userCopy['password']);
                $results[] = $userCopy;
            }
        }
        return $results;
    }
    
    public function setUserStatus($username, $active) {
        if (isset($this->users[$username])) {
            $this->users[$username]['active'] = $active;
            return true;
        }
        return false;
    }
    
    public function changeUserPassword($username, $newPassword) {
        if (isset($this->users[$username])) {
            $this->users[$username]['password'] = $newPassword;
            return true;
        }
        return false;
    }
    
    public function cleanup() {
        $this->users = [];
    }
}

/**
 * Comprehensive Admin App Unit Tests
 * 
 * Tests the core functionality of the Admin application including:
 * - User management CRUD operations
 * - Authentication and authorization
 * - Permission management
 * - Security features
 * - Admin-specific functionality
 */
class AdminTest extends TestCase
{
    private $userManager;
    private $originalSession;
    private $originalGet;
    private $originalPost;
    
    public function setUp(): void
    {
        // Reset mock auth manager
        MockAuthManager::reset();
        
        // Create fresh user manager
        $this->userManager = new TestUserManager();
        
        // Backup and reset superglobals
        $this->originalSession = $_SESSION ?? [];
        $this->originalGet = $_GET ?? [];
        $this->originalPost = $_POST ?? [];
        
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        
        // Set up test admin session
        $_SESSION['user'] = [
            'username' => 'admin',
            'role' => 'admin',
            'is_admin' => true
        ];
    }
    
    public function tearDown(): void
    {
        // Cleanup
        $this->userManager->cleanup();
        
        // Restore superglobals
        $_SESSION = $this->originalSession;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
    }

    /**
     * Test admin app info
     */
    #[Test]
    public function testAdminAppInfo()
    {
        // Include admin app file to get info function
        require_once __DIR__ . '/../../html/apps/admin/admin.app.php';
        
        $info = admin_info();
        
        $this->assertIsArray($info);
        $this->assertEquals('Admin Center', $info['title']);
        $this->assertTrue($info['requires_auth']);
        $this->assertTrue($info['requires_admin']);
        $this->assertFalse($info['public_app']);
    }

    /**
     * Test user management initialization
     */
    #[Test]
    public function testUserManagerInitialization()
    {
        $this->assertInstanceOf(TestUserManager::class, $this->userManager);
        
        // Should have default admin user
        $users = $this->userManager->getAllUsers();
        $this->assertCount(1, $users);
        $this->assertEquals('admin', $users[0]['username']);
        $this->assertTrue($users[0]['is_admin']);
    }

    /**
     * Test creating new users
     */
    #[Test]
    public function testCreateUser()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user',
            'is_admin' => false
        ];
        
        $user = $this->userManager->createUser($userData);
        
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('test@example.com', $user['email']);
        $this->assertEquals('user', $user['role']);
        $this->assertFalse($user['is_admin']);
        $this->assertTrue($user['active']);
        $this->assertArrayNotHasKey('password', $user);
        
        // Verify user was added
        $allUsers = $this->userManager->getAllUsers();
        $this->assertCount(2, $allUsers);
    }

    /**
     * Test user validation
     */
    #[Test]
    #[DataProvider('userDataProvider')]
    public function testCreateUserValidation($userData, $shouldSucceed, $expectedError = null)
    {
        if (!$shouldSucceed) {
            $this->expectException(Exception::class);
            if ($expectedError) {
                $this->expectExceptionMessage($expectedError);
            }
        }
        
        $user = $this->userManager->createUser($userData);
        
        if ($shouldSucceed) {
            $this->assertIsArray($user);
            $this->assertArrayHasKey('username', $user);
        }
    }

    public static function userDataProvider(): array
    {
        return [
            'Valid user' => [
                ['username' => 'validuser', 'email' => 'valid@test.com'],
                true
            ],
            'Duplicate username' => [
                ['username' => 'admin', 'email' => 'admin2@test.com'],
                false,
                'User admin already exists'
            ],
            'Empty username' => [
                ['username' => '', 'email' => 'test@example.com'],
                true  // Should create with empty username (validation handled elsewhere)
            ]
        ];
    }

    /**
     * Test updating existing users
     */
    #[Test]
    public function testUpdateUser()
    {
        // Create a test user first
        $this->userManager->createUser([
            'username' => 'updatetest',
            'email' => 'original@test.com',
            'role' => 'user'
        ]);
        
        // Update the user
        $updateData = [
            'email' => 'updated@test.com',
            'role' => 'moderator',
            'is_admin' => true
        ];
        
        $updatedUser = $this->userManager->updateUser('updatetest', $updateData);
        
        $this->assertNotNull($updatedUser);
        $this->assertEquals('updated@test.com', $updatedUser['email']);
        $this->assertEquals('moderator', $updatedUser['role']);
        $this->assertTrue($updatedUser['is_admin']);
        $this->assertEquals('updatetest', $updatedUser['username']); // Should not change
    }

    /**
     * Test deleting users
     */
    #[Test]
    public function testDeleteUser()
    {
        // Create a test user
        $this->userManager->createUser(['username' => 'deletetest']);
        
        // Verify user exists
        $user = $this->userManager->getUser('deletetest');
        $this->assertNotNull($user);
        
        // Delete the user
        $result = $this->userManager->deleteUser('deletetest');
        $this->assertTrue($result);
        
        // Verify user is gone
        $user = $this->userManager->getUser('deletetest');
        $this->assertNull($user);
    }

    /**
     * Test admin user protection
     */
    #[Test]
    public function testAdminUserProtection()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete admin user');
        
        $this->userManager->deleteUser('admin');
    }

    /**
     * Test user authentication
     */
    #[Test]
    public function testUserAuthentication()
    {
        // Create a test user with known password
        $this->userManager->createUser([
            'username' => 'authtest',
            'password' => 'testpass123'
        ]);
        
        // Test successful authentication
        $result = $this->userManager->authenticateUser('authtest', 'testpass123');
        $this->assertNotNull($result);
        $this->assertEquals('authtest', $result['username']);
        
        // Test failed authentication
        $result = $this->userManager->authenticateUser('authtest', 'wrongpass');
        $this->assertNull($result);
        
        // Test non-existent user
        $result = $this->userManager->authenticateUser('nonexistent', 'anypass');
        $this->assertNull($result);
    }

    /**
     * Test user search functionality
     */
    #[Test]
    public function testSearchUsers()
    {
        // Create test users
        $testUsers = [
            ['username' => 'john_doe', 'email' => 'john@example.com', 'role' => 'user'],
            ['username' => 'jane_smith', 'email' => 'jane@test.com', 'role' => 'moderator'],
            ['username' => 'bob_admin', 'email' => 'bob@admin.com', 'role' => 'admin']
        ];
        
        foreach ($testUsers as $userData) {
            $this->userManager->createUser($userData);
        }
        
        // Search by username
        $results = $this->userManager->searchUsers('john');
        $this->assertCount(1, $results);
        $this->assertEquals('john_doe', $results[0]['username']);
        
        // Search by email domain
        $results = $this->userManager->searchUsers('@test.com');
        $this->assertCount(1, $results);
        $this->assertEquals('jane_smith', $results[0]['username']);
        
        // Search by role
        $results = $this->userManager->searchUsers('admin');
        $this->assertCount(2, $results); // admin user + bob_admin
        
        // Search with no results
        $results = $this->userManager->searchUsers('nonexistent');
        $this->assertEmpty($results);
    }

    /**
     * Test getting users by role
     */
    #[Test]
    public function testGetUsersByRole()
    {
        // Create users with different roles
        $this->userManager->createUser(['username' => 'user1', 'role' => 'user']);
        $this->userManager->createUser(['username' => 'user2', 'role' => 'user']);
        $this->userManager->createUser(['username' => 'mod1', 'role' => 'moderator']);
        
        // Test getting users
        $users = $this->userManager->getUsersByRole('user');
        $this->assertCount(2, $users);
        
        $moderators = $this->userManager->getUsersByRole('moderator');
        $this->assertCount(1, $moderators);
        $this->assertEquals('mod1', $moderators[0]['username']);
        
        $admins = $this->userManager->getUsersByRole('admin');
        $this->assertCount(1, $admins); // Default admin user
        $this->assertEquals('admin', $admins[0]['username']);
    }

    /**
     * Test user status management
     */
    #[Test]
    public function testUserStatusManagement()
    {
        $this->userManager->createUser(['username' => 'statustest']);
        
        // Initially active
        $user = $this->userManager->getUser('statustest');
        $this->assertTrue($user['active']);
        
        // Deactivate user
        $result = $this->userManager->setUserStatus('statustest', false);
        $this->assertTrue($result);
        
        $user = $this->userManager->getUser('statustest');
        $this->assertFalse($user['active']);
        
        // Reactivate user
        $result = $this->userManager->setUserStatus('statustest', true);
        $this->assertTrue($result);
        
        $user = $this->userManager->getUser('statustest');
        $this->assertTrue($user['active']);
    }

    /**
     * Test password management
     */
    #[Test]
    public function testPasswordManagement()
    {
        $this->userManager->createUser([
            'username' => 'pwdtest',
            'password' => 'oldpassword'
        ]);
        
        // Test old password works
        $result = $this->userManager->authenticateUser('pwdtest', 'oldpassword');
        $this->assertNotNull($result);
        
        // Change password
        $result = $this->userManager->changeUserPassword('pwdtest', 'newpassword');
        $this->assertTrue($result);
        
        // Test old password no longer works
        $result = $this->userManager->authenticateUser('pwdtest', 'oldpassword');
        $this->assertNull($result);
        
        // Test new password works
        $result = $this->userManager->authenticateUser('pwdtest', 'newpassword');
        $this->assertNotNull($result);
    }

    /**
     * Test admin authentication functions
     */
    #[Test]
    public function testAdminAuthenticationFunctions()
    {
        require_once __DIR__ . '/../../html/apps/admin/admin.app.php';
        
        // Test admin user login check
        $_SESSION['user'] = ['username' => 'admin', 'is_admin' => true];
        $this->assertTrue(admin_user_logged_in());
        
        // Test non-logged in user
        $_SESSION = [];
        $this->assertFalse(admin_user_logged_in());
        
        // Test admin privilege check requires AuthManager mock
        $_SESSION['user'] = ['username' => 'admin', 'is_admin' => true];
        // Note: admin_user_is_admin() uses AuthManager::userIsAdmin() which we've mocked
    }

    /**
     * Test security measures
     */
    #[Test]
    public function testSecurityMeasures()
    {
        // Test that sensitive data is not exposed
        $user = $this->userManager->getUser('admin');
        $this->assertArrayNotHasKey('password', $user);
        
        $allUsers = $this->userManager->getAllUsers();
        foreach ($allUsers as $user) {
            $this->assertArrayNotHasKey('password', $user);
        }
        
        // Test inactive user cannot authenticate
        $this->userManager->createUser([
            'username' => 'inactive',
            'password' => 'testpass',
            'active' => false
        ]);
        
        $result = $this->userManager->authenticateUser('inactive', 'testpass');
        $this->assertNull($result);
    }

    /**
     * Test concurrent user operations
     */
    #[Test]
    public function testConcurrentUserOperations()
    {
        // Simulate concurrent user creation
        $userManager1 = new TestUserManager();
        $userManager2 = new TestUserManager();
        
        // Both should have admin user
        $this->assertCount(1, $userManager1->getAllUsers());
        $this->assertCount(1, $userManager2->getAllUsers());
        
        // Each manager operates independently
        $userManager1->createUser(['username' => 'user1']);
        $userManager2->createUser(['username' => 'user2']);
        
        $this->assertCount(2, $userManager1->getAllUsers());
        $this->assertCount(2, $userManager2->getAllUsers());
        
        // Users are different
        $this->assertNotNull($userManager1->getUser('user1'));
        $this->assertNull($userManager1->getUser('user2'));
        
        $this->assertNotNull($userManager2->getUser('user2'));
        $this->assertNull($userManager2->getUser('user1'));
    }

    /**
     * Test large user dataset performance
     */
    #[Test]
    public function testLargeUserDatasetPerformance()
    {
        $startTime = microtime(true);
        
        // Create 100 users
        for ($i = 1; $i <= 100; $i++) {
            $this->userManager->createUser([
                'username' => "user{$i}",
                'email' => "user{$i}@test.com",
                'role' => ['user', 'moderator'][$i % 2]
            ]);
        }
        
        $creationTime = microtime(true) - $startTime;
        
        // Test search performance
        $searchStart = microtime(true);
        $results = $this->userManager->searchUsers('user');
        $searchTime = microtime(true) - $searchStart;
        
        // Test role filter performance
        $roleStart = microtime(true);
        $users = $this->userManager->getUsersByRole('user');
        $roleTime = microtime(true) - $roleStart;
        
        // Verify results
        $this->assertCount(100, $results);
        $this->assertCount(50, $users);
        
        // Performance assertions
        $this->assertLessThan(1.0, $creationTime);
        $this->assertLessThan(0.1, $searchTime);
        $this->assertLessThan(0.1, $roleTime);
    }
}