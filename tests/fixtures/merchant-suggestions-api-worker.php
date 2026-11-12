<?php

define('MB_RUNNING', true);
require __DIR__ . '/../../html/apps/neighborhub/includes/models/merchant.model.php';

class App
{
    public PDO $db;

    public static function getInstance($name = ''): self
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->db = new PDO('sqlite::memory:');
            $app->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $app->db->exec('CREATE TABLE neighborhub_merchants (id INTEGER, business_name TEXT, address TEXT, city TEXT, state TEXT, zip_code TEXT, status TEXT, phone TEXT)');
            $stmt = $app->db->prepare('INSERT INTO neighborhub_merchants VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ([
                [1, 'Pizza King', '123 Main Street', 'Marion', 'IN', '46952', 'online', 'private'],
                [2, 'Pizza Palace', '456 Main Street', 'Marion', 'IN', '46952', 'active', 'private'],
                [3, 'Pizza Hidden', '123 Main Street', 'Marion', 'IN', '46952', 'disabled', 'private'],
                [4, 'Pizza Offline', '123 Main Street', 'Marion', 'IN', '46952', 'offline', 'private'],
                [5, '100% Pizza', '99 Side Street', 'Marion', 'IN', '46952', 'online', 'private'],
                [6, 'Bakery A', '1 Main', 'Marion', 'IN', '46952', 'active', 'private'],
                [7, 'Bakery B', '2 Main', 'Marion', 'IN', '46952', 'active', 'private'],
                [8, 'Bakery C', '3 Main', 'Marion', 'IN', '46952', 'active', 'private'],
                [9, 'Bakery D', '4 Main', 'Marion', 'IN', '46952', 'active', 'private'],
                [10, 'Bakery E', '5 Main', 'Marion', 'IN', '46952', 'active', 'private'],
                [11, 'Bakery F', '6 Main', 'Marion', 'IN', '46952', 'active', 'private'],
            ] as $row) {
                $stmt->execute($row);
            }
        }
        return $app;
    }

    public function includeModel($name): void {}
    public function includeClass($name): void {}
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = ['action' => 'merchant_suggestions', 'q' => $argv[1] ?? ''];
$_REQUEST = $_GET;
session_start();
$_SESSION = [];
register_shutdown_function(static function (): void {
    fwrite(STDERR, (string)(http_response_code() ?: 200));
});
require __DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php';
