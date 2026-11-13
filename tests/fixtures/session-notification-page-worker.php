<?php

define('MB_RUNNING', true);
session_start();
require __DIR__ . '/../../html/includes/util.php';

class Merchant
{
    public static function getMerchantById($id): object { return (object)['id' => $id]; }
}

class App
{
    public $user = null;
    public static function getInstance(): self { return $GLOBALS['app']; }
    public function get($key, $default = null) { return $default; }
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
session_notify('Queued notice', 'info');
if ($mode === 'home') {
    $path = __DIR__ . '/../../html/apps/neighborhub/views/pages/customer/onboarding.php';
} else {
    $path = __DIR__ . '/../../html/apps/neighborhub/views/pages/customer/customer_dashboard.php';
    $_GET = ['action' => 'checkout_success', 'session_key' => 'pending_order'];
    $_SESSION['pending_order'] = ['merchant_id' => 8];
    $_SESSION['notification'] = ['type' => $mode === 'checkout-success' ? 'success' : 'error', 'message' => 'Checkout notice'];
}
$first = $app->renderPage($path);
$second = $app->renderPage($path);
echo json_encode([
    'queuedFirst' => str_contains($first, 'Queued notice'),
    'queuedSecond' => str_contains($second, 'Queued notice'),
    'checkoutNotice' => str_contains($first, 'Checkout notice'),
    'clearCart' => str_contains($first, 'NHCart.clear()'),
    'pendingRemoved' => !isset($_SESSION['pending_order']),
]);
