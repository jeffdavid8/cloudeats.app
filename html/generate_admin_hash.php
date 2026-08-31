<?php
// Generate correct password hash for admin password from environment
require_once __DIR__ . '/../vendor/autoload.php';

$password = 'G@LAg@2~edBjj>Hmgq3F';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password\n";
echo "Hash: $hash\n";

// Test the hash works
$verify = password_verify($password, $hash);
echo "Verification test: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
?>