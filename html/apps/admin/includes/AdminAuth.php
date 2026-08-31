<?php
/**
 * AdminAuth - DEPRECATED - Use AuthManager instead
 * 
 * @deprecated This class is deprecated as of Nov 2025. Use AuthManager instead.
 * This file is kept for compatibility with old test files but should not be used in new code.
 */

trigger_error('AdminAuth class is deprecated. Use AuthManager instead.', E_USER_DEPRECATED);

class AdminAuth {
    private $storageManager;
    private $sessionTimeout = 3600; // 1 hour
    private $isCloudRun = false;
    
    public function __construct() {
        // Detect Cloud Run environment
        $this->isCloudRun = (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false);
        
        if (!$this->isCloudRun) {
            // Only initialize storage manager for local development
            //require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';
            //$this->storageManager = FileStorageManager::getInstance();
            //$this->ensureDataFile();
        }
        
        $this->startSession();
    }
    
    private function ensureDataFile() {
        // Only run in local development when storageManager is available
        if (!$this->storageManager) {
            return;
        }
        
        // Check if users data exists in storage
        if (!$this->storageManager->jsonDataExists('', 'users.json')) {
            // Get admin password from environment variable or use default
            $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: 'admin';
            
            // Create default admin user
            $defaultUsers = [
                'admin' => [
                    'username' => 'admin',
                    'password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                    'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@mediabrain.app',
                    'role' => 'admin',
                    'is_admin' => true,
                    'created' => date('c'),
                    'last_login' => null,
                    'active' => true
                ]
            ];
            
            $this->storageManager->storeJsonData('', 'users.json', $defaultUsers);
        }
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session timeout
        if (isset($_SESSION['admin_last_activity']) && 
            (time() - $_SESSION['admin_last_activity']) > $this->sessionTimeout) {
            $this->logout();
        }
        
        $_SESSION['admin_last_activity'] = time();
    }
    
    public function authenticate($username, $password) {
        // Cloud Run environment - use environment variables
        
        $user = User::getByUsername($username);
        
        if (!$user->active) {
            return false;
        }
        
        if (password_verify($password, $user->password)) {
            // Update last login
            $user->update_last_login();
            
            // Set session data
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['admin_user_data'] = [
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'is_admin' => $user['is_admin']
            ];
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        unset($_SESSION['admin_user']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
        unset($_SESSION['admin_user_data']);
        unset($_SESSION['admin_last_activity']);
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['admin_user']);
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['admin_user_data'] ?? null;
    }
    
    public function isAdmin($username = null) {
        if ($username === null) {
            $userData = $this->getCurrentUser();
            return $userData && ($userData['role'] === 'admin' || $userData['is_admin'] === true);
        }
        
        $users = $this->getUsers();
        if (!isset($users[$username])) {
            return false;
        }
        
        $user = $users[$username];
        return $user['role'] === 'admin' || $user['is_admin'] === true;
    }
    
    public function changePassword($username, $newPassword) {
        // In Cloud Run, password changes are not persistent
        if ($this->isCloudRun) {
            return [
                'success' => false, 
                'error' => 'Password changes are not supported in Cloud Run environment. Use environment variables to update credentials.'
            ];
        }
        
        $users = $this->getUsers();
        
        if (!isset($users[$username])) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters long'];
        }
        
        $users[$username]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $users[$username]['modified'] = date('c');
        
        if ($this->saveUsers($users)) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to save password changes'];
        }
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['admin_csrf_token'])) {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf_token'];
    }
    
    public function validateCSRFToken($token) {
        return isset($_SESSION['admin_csrf_token']) && 
               hash_equals($_SESSION['admin_csrf_token'], $token);
    }
    
    private function authenticateCloudRun($username, $password) {
        // In Cloud Run, use environment variables for admin credentials
        $adminUser = getenv('ADMIN_USERNAME') ?: 'admin';
        $adminPass = getenv('ADMIN_PASSWORD') ?: 'admin';
        
        if ($username === $adminUser && $password === $adminPass) {
            // Set session data for successful login
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_username'] = $username;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['admin_user_data'] = [
                'username' => $username,
                'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@mediabrain.app',
                'role' => 'admin',
                'is_admin' => true
            ];
            return true;
        }
        
        return false;
    }
    
    private function getUsers() {
        if ($this->isCloudRun) {
            // In Cloud Run, return environment-based user data
            $adminUser = getenv('ADMIN_USERNAME') ?: 'admin';
            return [
                $adminUser => [
                    'username' => $adminUser,
                    'password' => '', // Not stored in Cloud Run
                    'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@mediabrain.app',
                    'role' => 'admin',
                    'is_admin' => true,
                    'created' => date('c'),
                    'last_login' => null,
                    'active' => true
                ]
            ];
        }
        
        // Local development - use FileStorageManager
        if (!$this->storageManager) {
            return [];
        }
        
        $result = $this->storageManager->getJsonData('', 'users.json');
        
        if ($result['success']) {
            return $result['data'];
        }
        
        return [];
    }
    
    private function saveUsers($users) {
        if ($this->isCloudRun) {
            // In Cloud Run, user changes are not persistent through file storage
            // Use environment variables for admin auth
            return false; // Keep this for now to maintain environment-based auth
        }
        
        // Local development - use FileStorageManager if available
        if (!$this->storageManager) {
            return false;
        }
        
        $result = $this->storageManager->storeJsonData('', 'users.json', $users);
        
        return $result['success'];
    }
}