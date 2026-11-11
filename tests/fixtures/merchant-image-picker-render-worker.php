<?php

define('MB_RUNNING', true);
$gallery = [
    ['id' => 51, 'image_url' => '/one.png'],
    ['id' => 52, 'image_url' => '/two.png'],
];
foreach (['logo', 'hero'] as $slot) {
    $imagePicker = [
        'id' => $slot,
        'label' => ucfirst($slot),
        'fallback_label' => 'Keep current image',
        'fallback_url' => '/profile.png',
        'gallery' => $gallery,
    ];
    include __DIR__ . '/../../html/apps/neighborhub/views/components/merchant_image_picker.php';
}
