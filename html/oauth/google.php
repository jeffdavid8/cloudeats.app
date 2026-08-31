<?php
define('MB_RUNNING', true);
/**
 * Google OAuth Callback Handler
 */

// Include required files - use mb.bootstrap.php as single entry point
require_once __DIR__ . '/../includes/mb.bootstrap.php';

// Enable error display and reporting for debugging
/*
*/
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL && ~E_WARNING);

try {
    $oauthHandler = new OAuthHandler();
    $userManager = new UserManager();

    // Handle login initiation
    if (isset($_GET['action']) && $_GET['action'] === 'login') {
        if (!$oauthHandler->isProviderEnabled('google')) {
            throw new Exception('Google OAuth is not enabled');
        }

        $state = $oauthHandler->generateState();
        $oauthHandler->storeState($state);

        $authUrl = $oauthHandler->getGoogleAuthUrl($state);

        if (isset($_GET['return_url'])) {
            $_SESSION['oauth_return_url'] = $_GET['return_url'];
        }
        $_SESSION['oauth_provider'] = 'google';

        header('Location: ' . $authUrl);
        exit;
    }

    // Handle callback from Google
    if (isset($_GET['code'])) {
        $code = $_GET['code'];
        $state = $_GET['state'] ?? '';

        // Verify state parameter
        if (!$oauthHandler->verifyState($state)) {
            throw new Exception('Invalid state parameter - possible CSRF attack');
        }

        // Exchange code for tokens
        $tokenResponse = $oauthHandler->exchangeGoogleCode($code);

        // Get user information
        $userInfo = $oauthHandler->getGoogleUserInfo($tokenResponse['access_token']);
        //error_log('Google OAuth User Info: ' . print_r($userInfo, true));
        // Process user login/registration
        //$loginResult = processOAuthLogin($userManager, $userInfo);
        $loginResult = $oauthHandler->processOAuthLogin($userInfo);
        
        //error_log('OAuth Login Result: ' . print_r($loginResult, true));
        if ($loginResult['success']) {
            // Set session
            $_SESSION['user'] = [
                'id' => $loginResult['user']['id'],
                'username' => $loginResult['user']['username'],
                'role' => $loginResult['user']['role'],
                'is_admin' => ($loginResult['user']['role'] === 'admin'),
                'is_oauth' => true,
                'profilePicture' => $userInfo['picture'] ?? '',
            ];

            $_SESSION['oauth_provider'] = 'google';
            // legacy session variable for backward compatibility
            $_SESSION['mb_user'] = $loginResult['user']['username'];
            $_SESSION['mb_user_data'] = $loginResult['user'];

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
            // Generic error handling based on app parameter
            $targetApp = $_GET['app'] ?? null;
            if ($targetApp) {
                header("Location: /index.php?app={$targetApp}&p=login&oauth_error=" . urlencode($loginResult['error']));
            } else {
                throw new Exception($loginResult['error']);
            }
            exit;
        }
    }

    // Handle errors
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $errorDescription = $_GET['error_description'] ?? '';
        throw new Exception("Google OAuth error: $error - $errorDescription");
    }

    throw new Exception('Invalid OAuth request');
} catch (Exception $e) {
    // Log error
    error_log('Google OAuth Error: ' . $e->getMessage());

    // Redirect to login with error
    $errorMsg = urlencode($e->getMessage());
    header("Location: /index.php?p=login&oauth_error=$errorMsg");
    exit;
}
