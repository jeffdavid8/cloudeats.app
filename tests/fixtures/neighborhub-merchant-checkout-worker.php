<?php

define('MB_RUNNING', true);

class App
{
    public $db;
    public $user;

    public function __construct()
    {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('CREATE TABLE neighborhub_products (id INTEGER PRIMARY KEY, name TEXT, price REAL, description TEXT, type TEXT, meta TEXT)');
        $this->db->exec("INSERT INTO neighborhub_products VALUES (1, 'Test Sandwich', 10.00, '', 'default', '{}')");
        $this->user = (object)['id' => 7, 'is_admin' => false];
    }

    public static function getInstance($name = null)
    {
        return $GLOBALS['neighborhub_test_app'];
    }

    public function includeModel($model): void {}
    public function includeClass($class): void {}
}

class Merchant
{
    public static $mode = '';

    public static function getMerchantById($id)
    {
        return (object)[
            'sandbox_mode' => 1,
            'delivery_assignment_mode' => 'disabled',
            'platform_flat_fee' => 0,
            'platform_fee_rate' => 0.04,
        ];
    }

    public static function getOperationalStatus($merchant)
    {
        return self::$mode === 'closed'
            ? ['status' => 'closed', 'message' => 'Temporarily paused']
            : ['status' => 'open', 'message' => 'Open'];
    }
}

class Menu
{
    public static $mode = '';

    public static function productHasActiveMenuAssignment($productId, $merchantId): bool
    {
        return self::$mode !== 'unavailable';
    }
}

class Customer
{
    public static function getCustomerByUserId($userId)
    {
        return (object)['id' => 42, 'delivery_locations' => []];
    }

    public static function update($customerId, array $update): bool
    {
        return true;
    }
}

function config($key)
{
    return 'https://example.test';
}

$mode = $argv[1] ?? 'success';
Merchant::$mode = $mode;
Menu::$mode = $mode;
$GLOBALS['neighborhub_test_app'] = new App();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = [];
$_POST = [
    'action' => 'create_checkout_session',
    'merchant_id' => 10,
    'basket' => [
        'items' => [[
            'product_id' => 1,
            'quantity' => 2,
            'unit_price' => 0.01,
            'base_price' => 0.01,
            'merchant_lat' => 30,
            'merchant_lon' => -97,
        ]],
    ],
    'totals' => ['tips' => 0],
    'delivery' => false,
    'delivery_address' => '',
    'delivery_lat' => '',
    'delivery_lon' => '',
    'delivery_notes' => '',
    'customer_phone' => '',
    'return_url' => 'https://example.test/?app=neighborhub&view=merchant&p=pos&merchant_id=10',
    'cancel_url' => 'https://example.test/?app=neighborhub&view=merchant&p=pos&merchant_id=10',
];
$_REQUEST = ['action' => 'create_checkout_session'];
$_SESSION = [];

register_shutdown_function(static function (): void {
    fwrite(STDERR, json_encode([
        'status' => http_response_code() ?: 200,
        'session' => $_SESSION,
    ]));
});

require __DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php';
