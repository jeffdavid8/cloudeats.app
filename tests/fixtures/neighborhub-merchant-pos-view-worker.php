<?php

define('MB_RUNNING', true);
$_SESSION['user'] = [
    'username' => 'Merchant Staff',
    'merchant_staff_role' => 'owner',
];

class App
{
    public function get($key, $default = null)
    {
        return [
            'merchant' => (object)[
                'id' => 10,
                'business_name' => 'Test Merchant',
                'address' => '10 Test Street',
                'latitude' => 30,
                'longitude' => -97,
                'image_url' => '/merchant.png',
            ],
            'product_catalog' => [[
                'id' => 1,
                'name' => 'Test Sandwich',
                'price' => 10,
                'tags' => 'Lunch',
                'meta' => '{}',
            ]],
        ][$key] ?? $default;
    }

    public static function getInstance($name = null)
    {
        return new self();
    }
}

function render($component, array $variables = []): void
{
    echo '<div id="shopping-cart"></div>';
}

function config($key)
{
    return 'https://example.test';
}

function get_var($name, $default = null)
{
    return $default;
}

$app = App::getInstance('neighborhub');
ob_start();
include __DIR__ . '/../../html/apps/neighborhub/views/pages/merchant/pos.php';
echo ob_get_clean();
