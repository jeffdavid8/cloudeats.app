<?php

define('MB_RUNNING', true);

$merchant_id = 8;
$merchant = (object)['gallery' => []];
$storefront_config = [];
require __DIR__ . '/../../html/apps/neighborhub/views/pages/merchant/storefront_editor.php';
