<?php
define('MB_RUNNING', true);

/**
 * Facebook OAuth Callback Handler
 * Handles Facebook OAuth authentication flow
 */
// Enable error display and reporting for debugging
ini_set('display_errors', 'Off');
//error_reporting(E_ALL);
error_reporting(E_ALL && ~E_WARNING && ~E_NOTICE);

// Include required files - use app.php as single entry point
require_once __DIR__ . '/../includes/mb.bootstrap.php';
require_once __DIR__ . '/../includes/OAuthHandler.php';
require_once __DIR__ . '/../apps/admin/includes/UserManager.php';

try {
    session_start();
    $oauthHandler = new OAuthHandler();
    $userManager = new UserManager();

    // Read JSON payload from JavaScript frontend
    $input = json_decode(file_get_contents('php://input'), true);
    $accessToken = $input['token'] ?? null;

    if (!$accessToken) {
        echo json_encode(['success' => false, 'message' => 'Access token is missing.']);
        exit;
    }

    // 1. Verify token & Fetch User Data via Facebook Graph API
    $fields = 'id,name,email,picture';

    // 2. Build the complete Graph API URL structure
    $graphUrl = "https://graph.facebook.com/v21.0/me?" . http_build_query([
        'fields'       => $fields,
        'access_token' => $accessToken
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $userData = json_decode($response, true);
    // 2. Validate Response
    if (isset($userData['error'])) {
        echo json_encode(['success' => false, 'message' => $userData['error']['message']]);
        exit;
    }
    $userInfo = [
        'provider_id' => $userData['id'],
        'email' => $userData['email'] ?? '',
        'oauth_provider' => 'facebook',
        'oauth_profile_url' => $userData['link'] ?? "https://www.facebook.com/" . $userData['id'],
        'name' => $userData['name'] ?? '',
        'first_name' => explode(' ', $userData['name'])[0] ?? '',
        'last_name' => explode(' ', $userData['name'])[1] ?? '',
        'picture' => $userData['picture']['data']['url'] ?? '',
        'email_verified' => true // Facebook emails are generally verified

    ];
    $loginResult = $oauthHandler->processOAuthLogin($userInfo);

    //error_log('OAuth Login Result: ' . print_r($loginResult, true));
    if ($loginResult['success']) {
        // Successful login            
        // Store session data
        //error_log('OAuth Login Successful - Storing session data');
        $_SESSION['user'] = [
            'id' => $loginResult['user']['id'],
            'username' => $userInfo['name'],
            'oauth_provider' => $userInfo['provider'],
            'oauth_provider_id' => $userInfo['provider_id'],
            'email' => $userInfo['email'] ?? '',
            'role' => $loginResult['user']['role'],
            'is_admin' => ($loginResult['user']['role'] === 'admin'),
            'is_oauth' => true,
            'profilePicture' => $userInfo['picture'] ?? '',
        ];

        $_SESSION['oauth_provider'] = 'facebook';
        // legacy session variable for backward compatibility
        $_SESSION['mb_user'] = $loginResult['user']['username'];
        $_SESSION['mb_user_data'] = $loginResult['user'];

        $_SESSION['oauth_user'] = $userInfo;
        $_SESSION['oauth_success'] = true;
        //error_log('OAuth Session Data: ' . print_r($_SESSION, true));
        $redirectUrl = null;
        // Return success to the Javascript frontend
        echo json_encode([
            'success' => true,
            'user' => [
                'name' => $userData['name'],
                'email' => $userData['email'] ?? null
            ]
        ]);
        exit;
    } else {
        // Handle registration or errors as needed
        throw new Exception($loginResult['error'] ?? 'OAuth login failed');
    }
} catch (Exception $e) {
    $errorResponse = [
        'success' => false,
        'error' => $e->getMessage(),
        'provider' => 'facebook'
    ];

    // If this is a callback error, redirect with error message
    if (($_GET['action'] ?? '') === 'callback') {
        $_SESSION['oauth_error'] = $e->getMessage();
        header('Location: ../?p=login&oauth_error=1&error=' . urlencode($e->getMessage()));
        exit;
    }

    // Otherwise return JSON error
    setJsonHeader();
    http_response_code(400);
    echo json_encode($errorResponse);
}
