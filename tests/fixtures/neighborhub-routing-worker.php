<?php

define('MB_RUNNING', true);
session_start();
require __DIR__ . '/../../html/includes/util.php';
require __DIR__ . '/../../html/apps/neighborhub/neighborhub.app.php';

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value) { return $value; }
}

class RoutingMerchant
{
    public $business_name = 'Example';
    public $image_url;
    public function __construct() { $this->image_url = __DIR__ . '/../../html/images/favicon-32x32.png'; }
    public function address() { return 'Example address'; }
}
class Merchant
{
    public static function getMerchantWithGallery(...$args) { return new RoutingMerchant(); }
    public static function getMerchantById(...$args) { return new RoutingMerchant(); }
    public static function getAllMerchants(...$args) { return []; }
}
class Customer
{
    public static function getCustomerByUserId($id) { return (object)['id' => 1]; }
}
class Order
{
    public static function getOrdersByCustomerId($id) { return []; }
}
class Menu
{
    public static function getMenusWithProductsByMerchantId(...$args) { return []; }
}
class Product
{
    public static function getProductsByMerchant(...$args) { return []; }
    public static function getProductWithGallery(...$args) { return null; }
}
class MerchantStorefront
{
    public static function getByMerchantId(...$args) { return []; }
}
class RoutingStatement
{
    public function execute($values) {}
    public function fetch($mode) { return false; }
}
class RoutingDatabase
{
    public function prepare($sql) { return new RoutingStatement(); }
}
class App
{
    public $user;
    public $db;
    public $config = ['site_name' => 'CloudEats', 'site_description' => 'Food', 'base_url' => 'https://example.test'];
    public $app_info = ['scripts' => [], 'styles' => []];
    public $app_dir;
    private $context = [];

    public function __construct($signedIn)
    {
        $this->user = $signedIn ? (object)['id' => 7, 'is_admin' => false] : null;
        $this->db = new RoutingDatabase();
        $this->app_dir = __DIR__ . '/../../html/apps/neighborhub';
    }
    public static function getInstance($name = null) { return $GLOBALS['app']; }
    public function includeModel($name) {}
    public function includeClass($name) {}
    public function set($name, $value) { $this->context[$name] = $value; }
    public function get($name, $default = null) { return $this->context[$name] ?? $default; }
    public function isUserLoggedIn() { return !empty($this->user->id); }
}

$mode = $argv[1];
$signedIn = str_starts_with($mode, 'signed-in-');
$app = new App($signedIn);
$_SESSION = $signedIn ? ['user' => ['id' => 7]] : [];
$_GET = match ($mode) {
    'guest-dashboard', 'signed-in-dashboard' => ['view' => 'customer', 'p' => 'dashboard'],
    'guest-login' => ['p' => 'login'],
    'guest-home' => [],
    'guest-storefront', 'signed-in-storefront' => ['view' => 'customer', 'p' => 'merchant_products', 'merchant_id' => 8],
    'signed-in-merchant' => ['view' => 'merchant', 'p' => 'dashboard', 'merchant_id' => 8],
};
$completed = false;
register_shutdown_function(function () use (&$completed) {
    echo json_encode([
        'completed' => $completed,
        'status' => http_response_code() ?: 200,
        'notifications' => $_SESSION['notifications'] ?? [],
    ]);
});
neighborhub_init($app);
$completed = true;
