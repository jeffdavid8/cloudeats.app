<?php

define('MB_RUNNING', true);
session_start();
require __DIR__ . '/../../html/includes/util.php';

class Merchant
{
    public static function getMerchantById($id): ?object
    {
        if ($GLOBALS['mode'] === 'merchant-missing') {
            return null;
        }
        return (object)['id' => $id, 'platform_fee_rate' => 0.04, 'platform_flat_fee' => 0, 'address' => 'Merchant address'];
    }
}

class Order
{
    public static function create(...$args)
    {
        if ($GLOBALS['mode'] === 'checkout-exception') {
            throw new Exception('Database error');
        }
        return in_array($GLOBALS['mode'], ['checkout-success', 'sandbox-success'], true) ? 42 : false;
    }
}

class NeighborhubReporting
{
    public function __construct($db) {}
    public function recordEvent(...$args): void {}
}

class App
{
    public $user = null;
    public $data = [];
    public $db = null;
    public static function getInstance($name = null): self { return $GLOBALS['app']; }
    public function get($key, $default = null) { return $this->data[$key] ?? $default; }
    public function set($key, $value): void { $this->data[$key] = $value; }
    public function includeModel($name): void {}
    public function includeClass($name): void {}
    public function processCheckout(): void
    {
        include __DIR__ . '/../../html/apps/neighborhub/actions/order/create.order.action.php';
    }
    public function render($filename, $vars = [], $return = false, $cascading = true)
    {
        include __DIR__ . '/../../html/views/' . $filename;
    }
    public function renderPage($path): string
    {
        ob_start();
        include $path;
        return ob_get_clean();
    }
}

$app = new App();
$mode = $argv[1];
$_SESSION = ['user' => ['username' => 'Example']];
session_notify('Queued notice', 'success');
if ($mode === 'home') {
    $path = __DIR__ . '/../../html/apps/neighborhub/views/pages/customer/onboarding.php';
} else {
    $path = __DIR__ . '/../../html/apps/neighborhub/views/pages/customer/customer_dashboard.php';
    $_GET = ['action' => 'checkout_success', 'session_key' => 'pending_order'];
    if ($mode !== 'pending-missing') {
        $_SESSION['pending_order'] = [
            'merchant_id' => 8,
            'customer_id' => 1,
            'delivery_location' => ['address' => 'Customer address', 'notes' => ''],
            'delivery_fee' => 0,
            'merchant_package' => [],
            'subtotal_amount' => 10,
            'processing_fee' => 0,
            'platform_fee' => 0,
            'delivery_requested' => false,
            'tips' => 0,
            'sales_tax' => 0,
            'total_amount' => 10,
            'sandbox_mode' => str_starts_with($mode, 'sandbox-'),
        ];
    }
    $app->processCheckout();
}
$notifications = $_SESSION['notifications'];
ob_start();
render('components/session_notifications.php');
$firstNotices = ob_get_clean();
$first = $app->renderPage($path);
ob_start();
render('components/session_notifications.php');
$secondNotices = ob_get_clean();
$second = $app->renderPage($path);
echo json_encode([
    'queuedFirst' => str_contains($firstNotices, 'Queued notice'),
    'queuedSecond' => str_contains($secondNotices, 'Queued notice'),
    'notifications' => $notifications,
    'checkoutNotice' => isset($notifications[1]) && str_contains($firstNotices, $notifications[1]['message']),
    'clearCart' => str_contains($first, 'NHCart.clear()'),
    'pendingRemoved' => !isset($_SESSION['pending_order']),
]);
