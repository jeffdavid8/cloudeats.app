<?php
require_once 'html/includes/storage/FileStorageManager.php';

$storage = FileStorageManager::getInstance();
$data = $storage->getJsonData('', 'user_permissions.json');

if ($data['success']) {
    echo "Current user permissions:\n";
    echo json_encode($data['data'], JSON_PRETTY_PRINT);
} else {
    echo 'Error loading user permissions: ' . $data['error'];
}
?>