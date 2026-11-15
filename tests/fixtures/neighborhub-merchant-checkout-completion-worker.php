<?php

define('MB_RUNNING', true);

class App
{
    public $db;
    private array $context = [];

    public static function getInstance($name = null)
    {
        return $GLOBALS['neighborhub_test_app'];
    }

    public function includeModel($model): void {}
    public function set(string $key, $value): void
    {
        $this->context[$key] = $value;
    }
    public function getContext(): array
    {
        return $this->context;
    }
    public function processCheckoutAction(): void
    {
        include __DIR__ . '/../../html/apps/neighborhub/actions/order/create.order.action.php';
    }
}

class Merchant
{
    public static function getMerchantById($merchantId)
    {
        return (object)[
            'platform_fee_rate' => 0.04,
            'platform_flat_fee' => 0,
            'address' => '10 Test Street',
        ];
    }
}

class Order
{
    public static array $arguments = [];
    public static ?int $createdId = null;

    public static function create(...$arguments)
    {
        self::$arguments = $arguments;
        self::$createdId = 55;
        return self::$createdId;
    }
}

function get_var($name, $default = null)
{
    return $_GET[$name] ?? $default;
}

function session_notify(string $message, string $type = 'info'): void
{
    $_SESSION['notifications'][] = ['message' => $message, 'type' => $type];
}

$GLOBALS['neighborhub_test_app'] = new App();
$GLOBALS['neighborhub_test_app']->db = new stdClass();
$sessionKey = 'test-checkout-session';
$_GET = ['session_key' => $sessionKey];
$_SESSION = [
    $sessionKey => [
        'merchant_id' => 10,
        'customer_id' => 42,
        'delivery_location' => ['address' => '10 Customer Street', 'notes' => 'Ring bell'],
        'delivery_fee' => 0,
        'merchant_package' => [['product_id' => 1, 'quantity' => 2, 'price_at_order' => 10]],
        'subtotal_amount' => 20,
        'processing_fee' => 0.96,
        'platform_fee' => 0.8,
        'delivery_requested' => 0,
        'tips' => 0,
        'sales_tax' => 1.65,
        'total_amount' => 22.61,
        'sandbox_mode' => true,
    ],
];

$app = $GLOBALS['neighborhub_test_app'];
$app->processCheckoutAction();

echo json_encode([
    'context' => $app->getContext(),
    'notifications' => $_SESSION['notifications'] ?? [],
    'order_id' => Order::$createdId,
    'create_args' => Order::$arguments,
]);
