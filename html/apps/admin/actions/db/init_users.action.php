<?php
// Secure the entry point
if (!defined('MB_RUNNING')) exit;

function init_users($title)
{
  echo "<h1>$title</h1>";
  $key = get_var('key', false);
  if (((!$key) || ($key != $_SESSION['admin_key'])) && (!isset($_SESSION['bypass_admin_key']))) {
    $key = rand(100000, 999999);
    $_SESSION['admin_key'] = $key;
    echo '<a class="btn" href="?app=admin&p=init_users&key=' . $key . '">Initialize Users DB & Permissions</a>';
    die();
  }
  $_SESSION['admin_key'] = NULL;

  echo " <br><br><br><br>                                 ( . Y . ) <br><br>";
  echo 'Here we gooooo!  ------------~~~~~~~';
}

init_users('INIT USERS');


$app = App::getInstance();

echo "PURGING_EXISTING_USER_TABLE... <br>";
// Disable FK checks temporarily to drop safely in case child tables exist
$app->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
$app->db->exec("DROP TABLE IF EXISTS user_permissions_map");
$app->db->exec("DROP TABLE IF EXISTS permissions_registry");
$app->db->exec("DROP TABLE IF EXISTS users");
$app->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

$user_migration = new UserMigration();
$user_migration->createTables();
$user_migration->migrateUsers('./json/users.json');
$user_migration->migratePermissions('./json/permissions.json', './json/user_permissions.json');

$user_migration->listTables();
$user_migration->listUsers();


class UserMigration
{
  private $db;
  private $app;

  public function __construct()
  {
    $this->app = App::getInstance();
    $this->db = $this->app->db;
  }

  /**
   * 🏛️ CREATE THE IDENTITY TABLES
   * True Relational Identity System for MySQL/MariaDB.
   */
  public function createTables()
  {
    echo "🔨 Building Identity Foundations...<br>";

    // Updated SQLite data types to appropriate MySQL equivalents (INT, VARCHAR, TEXT, JSON, TINYINT)
    $sql = "-- 👤 USER TABLE
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(180) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                picture VARCHAR(2048),
                oauth_provider VARCHAR(50),
                oauth_profile_url VARCHAR(2048),
                oauth_providers JSON NOT NULL, -- Upgraded to Native MySQL JSON Engine Workspace
                role VARCHAR(50) DEFAULT 'user',
                is_admin TINYINT DEFAULT 0,
                active TINYINT DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL,
                modified_at TIMESTAMP NULL DEFAULT NULL,
                last_login TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS permissions_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                perm_key VARCHAR(180) UNIQUE NOT NULL, 
                title VARCHAR(255),
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS user_permissions_map (
                user_id INT NOT NULL,
                perm_id INT NOT NULL,
                action VARCHAR(50) NOT NULL, 
                PRIMARY KEY (user_id, perm_id, action),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (perm_id) REFERENCES permissions_registry(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";

    // Split statements safely assuming individual executions
    foreach (explode(';', $sql) as $q) {
      if (trim($q)) $this->db->exec($q);
    }

    echo "✅ Tables Synced.<br>";
  }

  /**
   * 🚀 INGEST USERS
   * Maps your users.json into the DB using standard MySQL UPSERT syntax.
   */
  public function migrateUsers($usersJson)
  {
    $users = [];
    $storageManager = null;

    if (is_production()) {
      try {
        $storageManager = FileStorageManager::getInstance();
      } catch (Exception $e) {
        error_log('UserManager: Failed to initialize storage manager: ' . $e->getMessage());
        $storageManager = null;
      }
      if ($storageManager) {
        $result = $storageManager->getJsonData('', 'users.json');
        $users = $result['data'];
      }
    } else {
      $users = json_decode(file_get_contents($usersJson), true);
    }

    if (!empty($users)) {

      // Changed SQLite "INSERT OR REPLACE" to standard MySQL "INSERT ... ON DUPLICATE KEY UPDATE"
      $stmt = $this->db->prepare("
            INSERT INTO users 
            (username, password, email, oauth_provider, oauth_profile_url, oauth_providers, role, is_admin, active, created_at, modified_at, last_login) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
              password = VALUES(password),
              email = VALUES(email),
              oauth_provider = VALUES(oauth_provider),
              oauth_profile_url = VALUES(oauth_profile_url),
              oauth_providers = VALUES(oauth_providers),
              role = VALUES(role),
              is_admin = VALUES(is_admin),
              active = VALUES(active),
              modified_at = VALUES(modified_at),
              last_login = VALUES(last_login)
        ");

      foreach ($users as $u) {
        $password = $u['password'] ?? '';
        if (in_array($u['username'], ['gemini', 'Sentinel_Agent_01'])) {
          $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        }

        $stmt->execute([
          $u['username'],
          $password,
          $u['email'] ?? '',
          $u['oauth_provider'] ?? null,
          $u['oauth_profile_url'] ?? null,
          isset($u['oauth_providers']) ? json_encode($u['oauth_providers']) : '{}',
          $u['role'] ?? 'user',
          ($u['is_admin'] ?? false) ? 1 : 0,
          ($u['active'] ?? true) ? 1 : 0,
          $u['created_at'] ?? $u['created'] ?? date('Y-m-d H:i:s'), 
          $u['modified_at'] ?? $u['modified'] ?? date('Y-m-d H:i:s'),
          $u['last_login'] ?? null
        ]);
      }

      echo "✅ User Migration Complete.<br>";
    } else {
      echo "There was a problem migrating users.<br>";
    }
  }

  /**
   * 🔑 Step 3: Migrate Permissions & Mappings
   */
  public function migratePermissions($masterJson, $userMapJson)
  {
    $registry = json_decode(file_get_contents($masterJson), true);
    $userMappings = json_decode(file_get_contents($userMapJson), true);

    // Converted "INSERT OR IGNORE" to standard MySQL "INSERT IGNORE"
    $regStmt = $this->db->prepare("INSERT IGNORE INTO permissions_registry (perm_key, title) VALUES (?, ?)");
    foreach ($registry['apps'] as $appKey => $app) {
      $regStmt->execute(["apps.$appKey", $app['name']]);
      foreach ($app['features'] as $featureKey => $actions) {
        $regStmt->execute(["apps.$appKey.features.$featureKey", "$appKey: $featureKey"]);
      }
    }

    echo "🔗 Linking User Permissions...<br>";
    // Converted "INSERT OR REPLACE" to standard MySQL "INSERT ... ON DUPLICATE KEY UPDATE"
    $mapStmt = $this->db->prepare("
        INSERT INTO user_permissions_map (user_id, perm_id, action) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE action = action
    ");

    foreach ($userMappings as $username => $data) {
      $userId = $this->getUserIdByUsername($username);
      if (!$userId || empty($data['custom_permissions'])) continue;

      foreach ($data['custom_permissions'] as $permKey => $actions) {
        $permId = $this->getPermIdByKey($permKey);
        if (!$permId) continue;

        foreach ($actions as $action) {
          $mapStmt->execute([$userId, $permId, $action]);
        }
      }
    }
    echo "✅ Permission Mapping Complete.<br>";
  }

  private function getUserIdByUsername($username)
  {
    $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn();
  }

  private function getPermIdByKey($key)
  {
    $stmt = $this->db->prepare("SELECT id FROM permissions_registry WHERE perm_key = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
  }

  public function listTables()
  {
    // Replaced SQLite system lookup schema query with standard MySQL SHOW TABLES command
    $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
      echo "📦 Table Found: $table <br/>";
    }
  }

  public function listUsers()
  {
    $users = $this->db->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
      echo "👤 User Found: <pre>" . print_r($user, true) . "</pre><br/>";
    }
  }
}