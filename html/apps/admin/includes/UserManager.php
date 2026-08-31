<?php

/**
 * UserManager - User Management System
 */


class UserManager
{
    private $storageManager;
    private $isCloudRun = false;
    private static $_instance;
    private $app = null;

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new UserManager();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        // Detect Cloud Run environment
        $this->isCloudRun = (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false);
        $this->app = App::getInstance();

        // Always try to initialize storage manager, regardless of environment
        try {
            require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';
            $this->storageManager = FileStorageManager::getInstance();
        } catch (Exception $e) {
            error_log('UserManager: Failed to initialize storage manager: ' . $e->getMessage());
            $this->storageManager = null;
        }
    }

    public function getAllUsers()
    {
        try {
            $stmt = $this->app->db->query("SELECT * FROM users");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = [];
            foreach ($rows as $row) {
                $users[$row['username']] = $row; // Keep the username-as-key format
            }
            return $users;
        } catch (Exception $e) {
            error_log('UserManager DB Error: ' . $e->getMessage());
            return [];
        }
    }

    public function getUser($username)
    {
        // In Cloud Run, return environment-based user data for admin
        if ($this->isCloudRun && $username === 'admin') {
            return [
                'username' => 'admin',
                'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@mediabrain.app',
                'role' => 'admin',
                'is_admin' => true,
                'profilePicture' => '',
                'profileImageFilename' => '',
                'created' => date('c'),
                'last_login' => date('c'),
                'active' => true
            ];
        }

        $users = $this->getUsers();

        if (!isset($users[$username])) {
            return null;
        }

        $user = $users[$username];

        // Remove sensitive data and ensure all fields exist
        return [
            'username' => $user['username'] ?? $username,
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'user',
            'is_admin' => $user['is_admin'] ?? false,
            'profilePicture' => $user['profilePicture'] ?? '',
            'profileImageFilename' => $user['profileImageFilename'] ?? '',
            'created' => $user['created'] ?? date('c'),
            'last_login' => $user['last_login'] ?? null,
            'active' => $user['active'] ?? true
        ];
    }
    /**
     * Find user by email address
     */
    function findUserByEmail($email)
    {
        /*
        $users = $this->getAllUsers();

        foreach ($users as $username => $user) {
            if (isset($user['email']) && (strtolower($user['email']) === strtolower($email))) {
                //$user['username'] = $username;
                return $user;
            }
        }
        */
        try {
            $stmt = $this->app->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (empty($user)) {
                return null;
            }

            return $user;
        } catch (Exception $e) {
            error_log('UserManager DB Error: ' . $e->getMessage());
            return [];
        }

        return null;
    }


    public function addUser($userData)
    {
        // 1. Prepare values and ensure defaults
        $username = $userData['username'] ?? '';
        $email = $userData['email'] ?? '';

        // Use the provided password, or generate a random one for OAuth users if empty
        $password = !empty($userData['password']) ?
            $userData['password'] :
            password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        // Ensure oauth_providers is a string (JSON) for the database
        $oauthProviders = is_array($userData['oauth_providers'] ?? null) ?
            json_encode($userData['oauth_providers']) : ($userData['oauth_providers'] ?? '{}');

        // 2. SQL Statement - Exactly 12 columns
        $sql = "INSERT OR REPLACE INTO users (
                username, 
                password, 
                email, 
                role, 
                is_admin, 
                active, 
                oauth_provider, 
                oauth_profile_url, 
                oauth_providers, 
                created_at, 
                modified_at, 
                last_login
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->app->db->prepare($sql);

        // 3. Execute with values in the EXACT same order as the columns above
        $stmt->execute([
            $username,                                      // username
            $password,                                      // password
            $email,                                         // email
            $userData['role'] ?? 'user',                    // role
            ($userData['is_admin'] ?? false) ? 1 : 0,       // is_admin
            ($userData['active'] ?? true) ? 1 : 0,          // active
            $userData['oauth_provider'] ?? null,            // oauth_provider
            $userData['oauth_profile_url'] ?? null,         // oauth_profile_url
            $oauthProviders,                                // oauth_providers (JSON string)
            $userData['created_at'] ?? date('Y-m-d H:i:s'), // created_at
            $userData['modified_at'] ?? date('Y-m-d H:i:s'), // modified_at
            $userData['last_login'] ?? null                 // last_login
        ]);

        // 4. Return the new ID so OAuthHandler can use it
        return $this->app->db->lastInsertId();
    }

    public function updateUser($username, $userData)
    {
        $user = User::getByUsername($username);

        // Update fields
        if (isset($userData['email'])) {
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'error' => 'Invalid email address'];
            }

            $user->email = $userData['email'];
        }

        if (isset($userData['role'])) {
            $user->role = $userData['role'];
        }

        if (isset($userData['is_admin'])) {
            $user->is_admin = $userData['is_admin'];
        }

        if (isset($userData['active'])) {
            $user->active = $userData['active'];
        }

        if (isset($userData['profilePicture'])) {
            $user->picture = $userData['profilePicture'];
        }

        if (isset($userData['profileImageFilename'])) {
            $user->picture = $userData['profileImageFilename'];
        }

        if (isset($userData['password']) && !empty($userData['password'])) {
            if (strlen($userData['password']) < 6) {
                return ['success' => false, 'error' => 'Password must be at least 6 characters'];
            }
            $user['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }

        $user->modified = date('c');
        
        if ($user->update()) {
            error_log('User updated: ' . print_r($user, true));
            return ['success' => true, 'message' => 'User updated successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to save user'];
        }
    }

    public function deleteUser($username)
    {
        if ($username === 'admin') {
            return ['success' => false, 'error' => 'Cannot delete admin user'];
        }

        $users = $this->getUsers();

        if (!isset($users[$username])) {
            return ['success' => false, 'error' => 'User not found'];
        }

        unset($users[$username]);

        if ($this->saveUsers($users)) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to delete user'];
        }
    }

    public function authenticateUser($username, $password)
    {
        $user = User::getByUsername($username);
        
        if (!$user->active) {
            return false;
        }
        
        return password_verify($password, $user->password);
    }

    public function getUserStats()
    {
        $users = $this->getUsers();

        $stats = [
            'total' => count($users),
            'active' => 0,
            'admins' => 0,
            'recent_logins' => 0
        ];

        $oneWeekAgo = strtotime('-1 week');

        foreach ($users as $user) {
            if ($user['active']) {
                $stats['active']++;
            }

            if ($user['is_admin'] || $user['role'] === 'admin') {
                $stats['admins']++;
            }

            if ($user['last_login'] && strtotime($user['last_login']) > $oneWeekAgo) {
                $stats['recent_logins']++;
            }
        }

        return $stats;
    }

    public function getUsers()
    {
        // Try to use storage manager first
        if ($this->storageManager) {
            $result = $this->storageManager->getJsonData('', 'users.json');

            if ($result['success']) {
                return $result['data'];
            }
        }

        // Fallback: In Cloud Run without storage data, provide environment admin
        if ($this->isCloudRun) {
            $adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
            return [
                $adminUsername => [
                    'username' => $adminUsername,
                    'email' => $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'admin@mediabrain.app',
                    'role' => 'admin',
                    'is_admin' => true,
                    'active' => true,
                    'created' => date('Y-m-d H:i:s'),
                    'last_login' => null
                ]
            ];
        }

        return [];
    }

    private function saveUsers($users)
    {
        // Try to use storage manager if available
        if ($this->storageManager) {
            $result = $this->storageManager->storeJsonData('', 'users.json', $users);
            // error_log getUsers()
            //error_log('UserManager: users data post save in UserManager line 300: ' . print_r($this->getUsers(), true));
            return $result['success'];
        }

        // In Cloud Run without storage manager, log the attempt but don't fail
        if ($this->isCloudRun) {
            error_log('UserManager: Cannot save users in Cloud Run without storage manager');
            return false;
        }

        return false;
    }
}
