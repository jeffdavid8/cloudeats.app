<?php

define('MB_RUNNING', true);

class FixtureUser
{
    public $id;
    public $is_admin;
    private $staffMerchantId;

    public function __construct(int $id, bool $isAdmin, int $staffMerchantId)
    {
        $this->id = $id;
        $this->is_admin = $isAdmin;
        $this->staffMerchantId = $staffMerchantId;
    }

    public function isMerchantUser($merchantId): bool
    {
        return $this->staffMerchantId === $merchantId;
    }
}

class Merchant
{
    public static function getAllMerchants(): array
    {
        return [];
    }

    public static function getMerchantById($id): object
    {
        return (object)['id' => $id, 'status' => 'online'];
    }

    public static function getOperationalStatus($merchant): array
    {
        $status = in_array($GLOBALS['mode'], ['closed', 'merchant-closed', 'staff-closed'], true) ? 'closed' : 'open';
        return ['status' => $status, 'urgency' => '', 'label' => ucfirst($status), 'message' => ucfirst($status)];
    }
}

class FixtureApp
{
    public $appName = 'neighborhub';
    public $user;
    public $merchant;
    public $db = null;
    public $config = ['base_url' => '/'];

    public function get($key, $default = null)
    {
        return match ($key) {
            'merchant' => $this->merchant,
            'customer' => (object)['terms_accepted_at' => 'yes'],
            'view' => in_array($GLOBALS['mode'], ['merchant-view', 'merchant-closed'], true) ? 'merchant' : 'customer',
            'show_header_shopping_basket' => !in_array($GLOBALS['mode'], ['merchant-view', 'merchant-closed', 'no-merchant'], true),
            default => $default,
        };
    }

    public function renderFile($path, array $vars = []): void
    {
        extract($vars);
        include $path;
    }
}

class App
{
    public static function getInstance($name = null)
    {
        return $GLOBALS['app'];
    }
}

function get_var($key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function render($filename, $vars = []): void
{
    global $app;
    $paths = [
        'components/header/header_left.php' => __DIR__ . '/../../html/views/components/header/header_left.php',
        'components/header/header_right.php' => $GLOBALS['headerRight'],
        'components/header/merchant_trigger.php' => __DIR__ . '/../../html/views/components/header/merchant_trigger.php',
        'components/sidenav/merchant_menu.php' => __DIR__ . '/../../html/apps/neighborhub/views/components/sidenav/merchant_menu.php',
    ];
    if (isset($paths[$filename])) {
        $app->renderFile($paths[$filename], $vars);
    }
}

$mode = $argv[1];
$headerRight = ($argv[2] ?? 'neighborhub') === 'global'
    ? __DIR__ . '/../../html/views/components/header/header_right.php'
    : __DIR__ . '/../../html/apps/neighborhub/views/components/header/header_right.php';
$app = new FixtureApp();
$staffModes = ['staff', 'staff-closed', 'no-image', 'other-app', 'merchant-view'];
$app->user = new FixtureUser($mode === 'guest' ? 0 : 1, $mode === 'admin', in_array($mode, $staffModes, true) ? 8 : 9);
$app->merchant = $mode === 'no-merchant' ? null : (object)['id' => 8, 'business_name' => 'Example', 'image_url' => $mode === 'no-image' ? '' : 'image.png', 'status' => 'online'];
if ($mode === 'other-app') $app->appName = 'other';
$_SERVER['REQUEST_URI'] = '/?view=customer&merchant_id=8';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['QUERY_STRING'] = 'view=customer&merchant_id=8';
$_GET = ['view' => 'customer', 'merchant_id' => 8];
$_SESSION = [];
$app->renderFile(__DIR__ . '/../../html/views/components/header/header.php', ['search_string' => '']);
