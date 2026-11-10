<?php

define('MB_RUNNING', true);

$merchant_id = 8;
$merchant = (object)['image_url' => '/profile.png', 'gallery' => [
    ['id' => 51, 'image_url' => '/one.png'],
    ['id' => 52, 'image_url' => '/two.png'],
]];
$storefront_config = [];
require __DIR__ . '/../../html/apps/neighborhub/views/pages/merchant/storefront_editor.php';
