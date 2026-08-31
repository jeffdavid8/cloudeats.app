<?

class User
{
    public $id;
    public $username;
    public $password;
    public $email;
    public $role;
    public $is_admin;
    public $active;
    public $created_at;
    public $modified_at;
    public $last_login;
    public $picture;           // Added
    public $oauth_provider;    // Added
    public $oauth_providers;   // Added (JSON string or array)
    public $stripe_connect_id; // Added for Stripe Connect integration

    public function __construct($u)
    {
        $this->id = $u['id'] ?? null;
        $this->username = $u['username'] ?? null;
        $this->password = $u['password'] ?? null;
        $this->email = $u['email'] ?? null;
        $this->role = $u['role'] ?? 'user';
        $this->is_admin = (bool)($u['is_admin'] ?? false);
        $this->active = (bool)($u['active'] ?? true);
        
        // Map database column names to object properties
        $this->created_at = $u['created_at'] ?? ($u['created'] ?? null);
        $this->modified_at = $u['modified_at'] ?? ($u['modified'] ?? null);
        $this->last_login = $u['last_login'] ?? null;
        
        // New OAuth and Profile fields
        $this->picture = $u['picture'] ?? ($u['profilePicture'] ?? null);
        $this->oauth_provider = $u['oauth_provider'] ?? null;
        
        // Handle the providers JSON
        if (isset($u['oauth_providers']) && is_string($u['oauth_providers'])) {
            $this->oauth_providers = json_decode($u['oauth_providers'], true);
        } else {
            $this->oauth_providers = $u['oauth_providers'] ?? [];
        }
    }

    public function data($data=[])
    {
        if (empty($data)) {
            return [
                'id' => $this->id,
                'username' => $this->username,
                'password' => $this->password,
                'email' => $this->email,
                'stripe_connect_id' => $this->stripe_connect_id,
                'role' => $this->role,
                'is_admin' => $this->is_admin,
                'active' => $this->active,
                'picture' => $this->picture,
                'created_at' => $this->created_at,
                'last_login' => $this->last_login,
                'oauth_provider' => $this->oauth_provider
            ];
        } else {
            $this->id = $data['id'] ?? null;
            $this->username = $data['username'] ?? null;
            $this->password = $data['password'] ?? null;
            $this->email = $data['email'] ?? null;
            $this->stripe_connect_id = $data['stripe_connect_id'] ?? null;
            $this->role = $data['role'] ?? 'user';
            $this->is_admin = (bool)($data['is_admin'] ?? false);
            $this->active = (bool)($data['active'] ?? true);
            $this->created_at = $data['created_at'] ?? ($data['created'] ?? null);
            $this->modified_at = $data['modified_at'] ?? ($data['modified'] ?? null);
            $this->last_login = $data['last_login'] ?? null;
            $this->picture = $data['picture'] ?? ($data['profilePicture'] ?? null);
            $this->oauth_provider = $data['oauth_provider'] ?? null;
        }

    }

    public function update() 
    {
        $app = App::getInstance();
        $db = $app->db;
        $stmt = $db->prepare("
            UPDATE users SET 
                username = ?, 
                password = ?, 
                email = ?, 
                stripe_connect_id = ?, 
                role = ?, 
                is_admin = ?, 
                active = ?, 
                modified_at = ?, 
                last_login  = ?,
                picture = ?,
                oauth_provider = ?,
                oauth_providers = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $this->username,
            $this->password,
            $this->email,
            $this->stripe_connect_id,
            $this->role,
            $this->is_admin,
            $this->active,
            $this->modified_at,
            $this->last_login,
            $this->picture,
            $this->oauth_provider,
            json_encode($this->oauth_providers),
            $this->id
        ]);
    }

    public function save()
    {
        $app = App::getInstance();
        $db = $app->db;

        $stmt = $db->prepare("
            INSERT INTO users (username, password, email, role, is_admin, active, created_at, modified_at, last_login, picture, oauth_provider, oauth_providers)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                username = ?,
                password = ?,
                email = ?,
                stripe_connect_id = ?, 
                role = ?,
                is_admin = ?,
                active = ?,
                modified_at = ?,
                last_login = ?,
                picture = ?,
                oauth_provider = ?,
                oauth_providers = ?
        ");

        $success = $stmt->execute([
            $this->username,
            $this->password,
            $this->email,
            $this->stripe_connect_id,
            $this->role,
            $this->is_admin,
            $this->active,
            $this->created_at,
            $this->modified_at,
            $this->last_login,
            $this->picture,
            $this->oauth_provider,
            json_encode($this->oauth_providers),
        ]);

        return $success;
    }

    public function update_last_login()
    {
        $app = App::getInstance();
        $db = $app->db;
        $stmt = $db->prepare("UPDATE users SET last_login = ? WHERE id = ?");
        return $stmt->execute([time(), $this->id]);
    }


    /* ... require_admin and is_admin methods ... */

    public static function getById($id)
    {
        $app = App::getInstance();
        try {
            $stmt = $app->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return null;
            return new User($user); // The constructor now handles the mapping
        } catch (Exception $e) {
            error_log("User::getById Error: " . $e->getMessage());
            return null;
        }
    }

    public static function getByUsername($username)
    {
        $app = App::getInstance();
        try {
            $stmt = $app->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return null;
            return new User($user);
        } catch (Exception $e) {
            error_log("User::getByUsername Error: " . $e->getMessage());
            return null;
        }
    }

    public static function getByEmail($email)
    {
        $app = App::getInstance();
        try {
            $stmt = $app->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) return null;
            return new User($user);
        } catch (Exception $e) {
            error_log("User::getByEmail Error: " . $e->getMessage());
            return null;
        }
    }


    public static function getUserStripeConnectId($userId)
    {
        $app = App::getInstance();
        try {
            $stmt = $app->db->prepare("SELECT stripe_connect_id FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("User::getUserStripeConnectId Error: " . $e->getMessage());
            return null;
        }
    }

    
}