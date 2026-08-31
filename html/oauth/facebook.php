<?php
define('MB_RUNNING', true);

/**
 * Facebook OAuth Callback Handler
 * Handles Facebook OAuth authentication flow
 */
// Enable error display and reporting for debugging
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

// Include required files - use app.php as single entry point
require_once __DIR__ . '/../includes/mb.bootstrap.php';
require_once __DIR__ . '/../includes/OAuthHandler.php';
require_once __DIR__ . '/../apps/admin/includes/UserManager.php';

try {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'login':
            $oauthHandler = new OAuthHandler();
            $userManager = new UserManager();
            // Start OAuth flow - redirect to Facebook
            $state = $_GET['state'] ?? '';
            if (!$state) {
                throw new Exception('State parameter is required for security');
            }

            $authUrl = $oauthHandler->getFacebookAuthUrl($state);
            if (isset($_GET['return_url'])) {
                $_SESSION['oauth_return_url'] = $_GET['return_url'];
            }
            $_SESSION['oauth_provider'] = 'facebook';

            header('Location: ' . $authUrl);
            exit;

        case 'callback':
            $oauthHandler = new OAuthHandler();
            $userManager = new UserManager();
            // Handle OAuth callback from Facebook
            $code = $_GET['code'] ?? '';
            $state = $_GET['state'] ?? '';

            if (!$code) {
                throw new Exception('Authorization code not received from Facebook');
            }

            // Exchange code for access token
            $tokenResponse = $oauthHandler->exchangeFacebookCode($code);
            $accessToken = $tokenResponse['access_token'];

            // Get user information
            $userInfo = $oauthHandler->getFacebookUserInfo($accessToken);

            error_log('Facebook OAuth User Info: ' . print_r($userInfo, true));
            $userInfo['provider'] = 'facebook';


            //$loginResult = processOAuthLogin($userManager, $userInfo);
            $loginResult = $oauthHandler->processOAuthLogin($userInfo);

            //error_log('OAuth Login Result: ' . print_r($loginResult, true));
            if ($loginResult['success']) {
                // Successful login            
                // Store session data
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

                $_SESSION['oauth_user'] = $userInfo;
                $_SESSION['oauth_success'] = true;
                $_SESSION['access_token'] = $accessToken;

                $redirectUrl = null;
                if (isset($_SESSION['oauth_return_url'])) {
                    $redirectUrl = $_SESSION['oauth_return_url'];
                    unset($_SESSION['oauth_return_url']);
                }

                if (!$redirectUrl || $redirectUrl === "null" || $redirectUrl === "undefined") {
                    $redirectUrl = '/index.php?p=dashboard&oauth_success=1';
                }

                header("Location: {$redirectUrl}");

                exit;
            } else {
                // Handle registration or errors as needed
                throw new Exception($loginResult['error'] ?? 'OAuth login failed');
            }

        case 'test':
            // Test Facebook OAuth configuration
            $result = $oauthHandler->testProviderConfig('facebook');
            setJsonHeader();
            echo json_encode([
                'success' => true,
                'provider' => 'facebook',
                'result' => $result
            ]);
            break;

        default:
            throw new Exception('Invalid action parameter');
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

