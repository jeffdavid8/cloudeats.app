<?php
// Test admin API through web request
echo "Testing admin API via web request...\n";

// Test authentication first
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api.php?app=admin&action=check_auth');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Auth check response (HTTP $httpCode): $response\n\n";

// Test OAuth config loading
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api.php?app=admin&action=get_oauth_config');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "OAuth config response (HTTP $httpCode): $response\n\n";

// Test OAuth config saving with Facebook data
$postData = json_encode([
    'facebook_oauth_enabled' => true,
    'facebook_client_id' => 'test_facebook_app_id',
    'facebook_client_secret' => 'test_facebook_secret'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/api.php?app=admin&action=save_oauth_config');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Save OAuth config response (HTTP $httpCode): $response\n";
?>