<?php

define('MB_RUNNING', true);

class App
{
    public $db;

    public static function getInstance($name = '')
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->db = new PDO('sqlite::memory:');
            $app->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $app->db->exec('CREATE TABLE neighborhub_merchants (id INTEGER PRIMARY KEY, status TEXT, store_hours TEXT)');
            $app->db->exec("INSERT INTO neighborhub_merchants VALUES (10, 'paused', '{}')");
        }
        return $app;
    }

    public function includeModel($model) {}
    public function includeClass($class) {}
}

class Merchant
{
    public static function getMerchantById($id)
    {
        return (object)['status' => 'paused', 'sandbox_mode' => 1];
    }

    public static function getOperationalStatus($merchant)
    {
        return ['status' => 'closed', 'label' => 'Temporarily paused', 'urgency' => '', 'message' => 'Temporarily paused by vendor'];
    }
}

function config($key)
{
    return 'https://example.invalid';
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['action' => $argv[1], 'merchant_id' => $argv[2] ?? '10'];
$_REQUEST = $_GET;
$_POST = $argv[1] === 'create_checkout_session'
    ? ['basket' => ['items' => [['product_id' => 1]]]]
    : [];
session_start();
$_SESSION = [];
register_shutdown_function(static function (): void {
    fwrite(STDERR, http_response_code() . ':' . session_status());
});
require __DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php';
