<?php

define('MB_RUNNING', true);
define('STORAGE_PATH', $argv[1]);

class AuthManager
{
    public static function validateCsrf($token) { return $token === 'valid-token'; }
    public function userIsAdmin($user) { return !empty($user['is_admin']); }
}

class UserManager {}

class RestoreStatement
{
    private array $rows = [];
    public function __construct(private string $sql, private PDO $db) {}
    public function execute($values): void
    {
        if (str_contains($this->sql, 'information_schema.tables')) {
            $stmt = $this->db->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?");
            $stmt->execute($values);
            $this->rows = $stmt->fetchAll(PDO::FETCH_NUM);
        } elseif (str_contains($this->sql, 'information_schema.COLUMNS')) {
            $this->rows = $values[0] === 'selected_table'
                ? [['COLUMN_NAME' => 'id', 'data_type' => 'integer'], ['COLUMN_NAME' => 'name', 'data_type' => 'text']]
                : [];
        }
    }
    public function fetchColumn() { return $this->rows[0][0] ?? false; }
    public function fetch($mode = null) { return array_shift($this->rows) ?: false; }
}

class RestoreDatabase
{
    public PDO $db;
    public function __construct()
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE selected_table (id INTEGER, name TEXT); INSERT INTO selected_table VALUES (1, 'old')");
        $this->db->exec("CREATE TABLE other_table (id INTEGER); INSERT INTO other_table VALUES (99)");
    }
    public function exec($sql)
    {
        if (str_starts_with($sql, 'SET FOREIGN_KEY_CHECKS')) return 0;
        return $this->db->exec($sql);
    }
    public function prepare($sql)
    {
        if (str_contains($sql, 'information_schema.')) return new RestoreStatement($sql, $this->db);
        return $this->db->prepare($sql);
    }
    public function beginTransaction() { return $this->db->beginTransaction(); }
    public function commit() { return $this->db->commit(); }
    public function rollBack() { return $this->db->rollBack(); }
    public function inTransaction() { return $this->db->inTransaction(); }
}

class App
{
    public $db;
    public $authManager;
    public static function getInstance()
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->db = new RestoreDatabase();
            $app->authManager = new AuthManager();
        }
        return $app;
    }
    public function getEventLogger() { return null; }
}

session_start();
$_SESSION['user'] = ['is_admin' => ($argv[3] ?? 'admin') === 'admin'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_X_CSRF_TOKEN'] = $argv[4] ?? 'valid-token';
$_GET['action'] = 'restore_db';
$_POST = ['backup_file' => $argv[2], 'target_tables' => ['selected_table']];
register_shutdown_function(static function (): void {
    $db = App::getInstance()->db->db;
    file_put_contents('php://stderr', json_encode([
        'status' => http_response_code() ?: 200,
        'selected' => $db->query('SELECT * FROM selected_table')->fetchAll(PDO::FETCH_ASSOC),
        'other' => $db->query('SELECT * FROM other_table')->fetchAll(PDO::FETCH_ASSOC),
    ]));
});
require __DIR__ . '/../../html/apps/admin/admin.api.php';
