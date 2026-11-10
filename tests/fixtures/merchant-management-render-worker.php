<?php

define('MB_RUNNING', true);
$_SESSION = ['user' => ['is_admin' => true]];
$merchant_id = 8;
$merchant = [
    'id' => 8,
    'business_name' => 'Example',
    'image_url' => '/profile.png',
    'state' => 'CA',
    'zip_code' => '12345',
    'delivery_max_distance' => '7',
];
$staff_roster = [];
class Merchant
{
    public static function getMerchantWithGallery($id, $format)
    {
        return (object)['gallery' => [
            ['id' => 51, 'image_url' => '/one.png'],
            ['id' => 52, 'image_url' => '/two.png'],
        ]];
    }
}
require __DIR__ . '/../../html/apps/neighborhub/views/pages/merchant/merchant_management.php';
