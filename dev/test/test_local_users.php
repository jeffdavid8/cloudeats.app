<?php
// Quick test script to verify local users.json loading
$path = 'E:/var/data/mediabrain/storage/system_data/users.json';
if (file_exists($path)) {
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Loaded users.json successfully.\n";
        echo "User count: " . count($data) . "\n";
        echo "Usernames: " . implode(', ', array_keys($data)) . "\n";
    } else {
        echo "Error decoding JSON: " . json_last_error_msg() . "\n";
    }
} else {
    echo "users.json not found at $path\n";
}
