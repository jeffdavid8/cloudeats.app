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
    public static function getOperationalStatus($merchant): array
    {
        return ['status' => 'open', 'urgency' => '', 'label' => 'Open', 'message' => 'Open'];
    }
}

class FixtureApp
{
    public $appName = 'neighborhub';
    public $user;
    public $merchant;

    public function get($key, $default = null)
    {
        return match ($key) {
            'merchant' => $this->merchant,
            'customer' => (object)['terms_accepted_at' => 'yes'],
            'view' => $GLOBALS['mode'] === 'merchant-view' ? 'merchant' : 'customer',
            default => $default,
        };
    }

    public function renderFile($path, array $vars = []): void
    {
        extract($vars);
        include $path;
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
        'components/sidenav/merchant_menu.php' => __DIR__ . '/../../html/apps/neighborhub/views/components/sidenav/merchant_menu.php',
    ];
    if (isset($paths[$filename])) {
        $app->renderFile($paths[$filename], $vars);
    }
}

$mode = $argv[1];
$app = new FixtureApp();
$staffModes = ['staff', 'no-image', 'other-app', 'merchant-view'];
$app->user = new FixtureUser($mode === 'guest' ? 0 : 1, $mode === 'admin', in_array($mode, $staffModes, true) ? 8 : 9);
$app->merchant = (object)['id' => 8, 'business_name' => 'Example', 'image_url' => $mode === 'no-image' ? '' : 'image.png', 'status' => 'online'];
if ($mode === 'other-app') $app->appName = 'other';
$_SERVER['REQUEST_URI'] = '/?view=customer&merchant_id=8';
$_GET = ['view' => 'customer', 'merchant_id' => 8];
$app->renderFile(__DIR__ . '/../../html/views/components/header/header.php', ['search_string' => '']);
