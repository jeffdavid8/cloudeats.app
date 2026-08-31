<?php
if (!defined('MB_RUNNING')) exit;

class AuthManager
{
    private $db = null;
    private $eventLogger = null;

    function __construct($db = null, $eventLogger = null)
    {
        $this->db = $db;
        $this->eventLogger = $eventLogger;
    }
    // Defensive: start output buffering immediately to avoid any accidental output (BOM/newlines)
    public static function startBuffering()
    {
        if (function_exists('ob_start')) {
            ob_start();
        }
    }

    // Start session as early as possible with safer cookie params
    public static function startSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (
                isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443
            );
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $host = preg_replace('/:\\d+$/', '', $host);
            $cookieParams = [
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            if ($host && preg_match('/[a-zA-Z]/', $host)) {
                $cookieParams['domain'] = $host;
            }
            session_set_cookie_params($cookieParams);
            session_start();
        }
    }

    // Session inactivity timeout and periodic regeneration
    public static function manageSession()
    {
        $inactive_limit = 60 * 30; // 30 minutes
        $regenerate_interval = 60 * 5; // 5 minutes
        if (isset($_SESSION)) {
            if (!empty($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $inactive_limit) {
                session_unset();
                session_destroy();
                session_start();
            }
            if (empty($_SESSION['last_regenerate'])) $_SESSION['last_regenerate'] = time();
            if ((time() - $_SESSION['last_regenerate']) > $regenerate_interval) {
                if (function_exists('session_regenerate_id')) {
                    session_regenerate_id(true);
                    $_SESSION['last_regenerate'] = time();
                }
            }
            $_SESSION['last_activity'] = time();
        }
    }

    // simple file-based auth: users stored in users.json with password_hash
    public function loadUsers()
    {

        //echo 'app.php6.1 '; die();

        // 🛰️ LOG: Performance check
        if ($this->eventLogger) {
            $this->eventLogger->log('DEBUG', 'auth', 'loadUsers: Fetching all users from SQLite');
        }
        //echo 'app.php6.2 '; die();

        try {
            // 🔍 Fetch all users. We grab everything to match the old JSON structure.
            $stmt = $this->db->query("SELECT * FROM users WHERE active = 1");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $users = [];
            foreach ($rows as $row) {
                // 🔄 Transform: We use the username as the key to match the legacy JSON format
                $username = $row['username'];

                // Map the SQL columns back to the keys your app expects
                $users[$username] = [
                    'id'         => $row['id'],
                    'username'   => $row['username'],
                    'password'   => $row['password'],
                    'email'      => $row['email'],
                    'role'       => $row['role'],
                    'is_admin'   => (bool)$row['is_admin'],
                    'active'     => (bool)$row['active'],
                    'created'    => $row['created_at'],
                    'modified'   => $row['modified_at'],
                    'last_login' => $row['last_login']
                ];
            }

            return $users;
        } catch (Exception $e) {
            error_log('CRITICAL: AuthManager failed to load users from DB: ' . $e->getMessage());
            return []; // Return empty array to prevent app crashes
        }
    }

    public static function requireLogin()
    {
        if (!isset($_SESSION['user'])) {
            if (class_exists('App')) {
                try {
                    $app = App::getInstance();
                    $app_name = !empty($app->app) ? $app->app : (isset($_GET['app']) ? $_GET['app'] : 'ancestry');
                    $return = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ('/?app=' . $app_name);
                    $url = '/?app=' . urlencode($app_name) . '&p=login&return_url=' . rawurlencode($return);
                    if (!headers_sent()) {
                        header('Location: ' . $url);
                        exit();
                    } else {
                        $escAttr = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                        $jsUrl = json_encode($url);
                        echo "<!doctype html><html><head>\n";
                        echo "<meta http-equiv=\"refresh\" content=\"0;url=$escAttr\">\n";
                        echo "<script>location.replace($jsUrl);</script>\n";
                        echo "</head><body>If you are not redirected automatically, <a href=\"$escAttr\">click here</a>.</body></html>";
                        exit();
                    }
                } catch (Exception $e) {
                }
            }
            if (!headers_sent()) {
                $return = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
                header('Location: /?p=login&return_url=' . rawurlencode($return));
                exit();
            } else {
                $fallbackUrl = '/?p=login&return_url=' . rawurlencode(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
                $escAttr = htmlspecialchars($fallbackUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $jsUrl = json_encode($fallbackUrl);
                echo "<!doctype html><html><head>\n";
                echo "<meta http-equiv=\"refresh\" content=\"0;url=$escAttr\">\n";
                echo "<script>location.replace($jsUrl);</script>\n";
                echo "</head><body>If you are not redirected automatically, <a href=\"$escAttr\">click here</a>.</body></html>";
                exit();
            }
        }
    }

    public function checkCredentials($user, $pass)
    {
        if (($user === 'admin') && (!is_development())) {
            return false;
        }
        // 🛰️ LOG: Initiate attempt
        if ($this->eventLogger) {
            $this->eventLogger->log('INFO', 'checkCredentials started', [
                'username' => $user
            ]);
        }

        // 🔍 Step 1: SQL Lookup (The "Surgical Strike")
        // We only fetch what we need: ID for session, Hash for verify, Active for gatekeeping
        $stmt = $this->db->prepare("SELECT id, username, password, active FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$user]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        // 🛡️ Step 2: Validation Gate
        if (!$userData) {
            if ($this->eventLogger) {
                $this->eventLogger->log('WARNING', 'User not found', ['username' => $user]);
            }
            return false;
        }

        // Check if account is active (Safety Valve)
        if (!(int)$userData['active']) {
            if ($this->eventLogger) {
                $this->eventLogger->log('WARNING', 'Attempted login to inactive account', ['username' => $user]);
            }
            return false;
        }

        // 🔑 Step 3: Hash Verification
        // Works with the hashes migrated from users.json
        $passwordVerifyResult = password_verify($pass, $userData['password']);

        if ($passwordVerifyResult) {
            // 🎯 SUCCESS: Establish Session Identity
            $_SESSION['user']    = $userData; // Backward compatibility

            // ⏱️ Update Heartbeat
            $this->db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$userData['id']]);

            if ($this->eventLogger) {
                $this->eventLogger->log('INFO', 'Login successful', ['user_id' => $userData['id']]);
            }
        } else {
            if ($this->eventLogger) {
                $this->eventLogger->log('INFO', 'Password mismatch', ['username' => $user]);
            }
        }

        return $passwordVerifyResult;
    }

    // CSRF helpers
    public static function csrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf($token)
    {
        if (!isset($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    // Is user logged in
    public static function isUserLoggedIn()
    {
        return isset($_SESSION['user']);
    }

    public static function getCurrentUser()
    {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    /* get user by id */
    public static function getUserById($id)
    {
        $app = App::getInstance();

        try {
            // We use a prepared statement to prevent SQL injection
            $stmt = $app->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return null;

            return [
                'id'         => $user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'is_admin'   => (bool)$user['is_admin'],
                'active'     => (bool)$user['active'],
                'created'    => $user['created_at'],
                'modified'   => $user['modified_at'],
                'last_login' => $user['last_login']
            ];

        } catch (Exception $e) {
            // Log the error
            error_log("AuthManager::getUserById Error: " . $e->getMessage());
            return null;
        };
    }


    public static function getUserByUsername($username)
    {
        $app = App::getInstance();

        try {
            // We use a prepared statement to prevent SQL injection
            $stmt = $app->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return null;

            return [
                'id'         => $user['id'],
                'username'   => $user['username'],
                'email'      => $user['email'],
                'role'       => $user['role'],
                'is_admin'   => (bool)$user['is_admin'],
                'active'     => (bool)$user['active'],
                'created'    => $user['created_at'],
                'modified'   => $user['modified_at'],
                'last_login' => $user['last_login']
            ];

        } catch (Exception $e) {
            // Log the error
            error_log("AuthManager::getUserById Error: " . $e->getMessage());
            return null;
        };
    }



    /**
     * 🔍 USER ID LOOKUP
     * Translates a username into a database ID. 
     * Essential for stitching memory_anchors to specific architects.
     */
    public static function getUserIdByUsername($username)
    {
        $app = App::getInstance();

        try {
            // We use a prepared statement to prevent SQL injection
            $stmt = $app->db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            
            // fetchColumn() is perfect here since we only asked for 'id'
            $id = $stmt->fetchColumn();

            return $id ? (int)$id : null;

        } catch (Exception $e) {
            // Log the error through your cloud logger if possible
            error_log("AuthManager::getUserIdByUsername Error: " . $e->getMessage());
            return null;
        }
    }
    

    // Admin helpers
    public function userIsAdmin($username = null)
    {
        if (!$username) return false;

        // 3. Database Check (The Source of Truth)
        // We do this if a specific username was passed, or if the session was inconclusive
        try {
            $stmt = $this->db->prepare("SELECT is_admin, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return false;

            // Check both the boolean flag and the 'admin' role string
            $isAdmin = (bool)$user['is_admin'] || $user['role'] === 'admin';

            // 🛰️ SELF-HEALING SESSION: If we just found out they ARE admin, update the session
            if ($username === ($_SESSION['user'] ?? null) && $isAdmin) {
                $_SESSION['is_admin'] = true;
            }

            return $isAdmin;
        } catch (Exception $e) {
            error_log("AuthManager::userIsAdmin error: " . $e->getMessage());
            return false;
        }
    }

    public static function requireAdmin()
    {

        if ((!isset($_SESSION['user']) && is_array($_SESSION['user'])) 
            && ($_SESSION['user']['is_admin'] == false)) 
        {
            $url = '/?p=dashboard&msg=Access+required';
            header("Location: $url");
            echo "<script>location.replace(\"$url\");</script>";
            exit();
        }
    }

    public static function isAdmin()
    {
        return (isset($_SESSION['user'])) &&
            (is_array($_SESSION['user']) ? $_SESSION['user']['is_admin'] : false);
    }
}
