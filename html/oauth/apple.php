<?php
define('MB_RUNNING', true);

/**
 * Apple OAuth Callback Handler
 */
// Enable error display and reporting for debugging
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/
session_start();


require_once __DIR__ . '/../includes/util.php';
require_once __DIR__ . '/../includes/OAuthHandler.php';
require_once __DIR__ . '/../apps/admin/includes/UserManager.php';

try {
    $oauthHandler = new OAuthHandler();
    $userManager = new UserManager();

    // Handle login initiation
    if (isset($_GET['action']) && $_GET['action'] === 'login') {
        if (!$oauthHandler->isProviderEnabled('apple')) {
            throw new Exception('Apple OAuth is not enabled');
        }

        $state = $oauthHandler->generateState();
        $oauthHandler->storeState($state);

        $authUrl = $oauthHandler->getAppleAuthUrl($state);
        if (isset($_GET['return_url'])) {   
            $_SESSION['oauth_return_url'] = $_GET['return_url'];
        }
        header('Location: ' . $authUrl);
        exit;
    }

    // Handle callback from Apple (POST request)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
        $code = $_POST['code'];
        $state = $_POST['state'] ?? '';
        $idToken = $_POST['id_token'] ?? '';
        $user = isset($_POST['user']) ? json_decode($_POST['user'], true) : null;

        // Verify state parameter
        if (!$oauthHandler->verifyState($state)) {
            throw new Exception('Invalid state parameter - possible CSRF attack');
        }

        // Exchange code for tokens
        $tokenResponse = $oauthHandler->exchangeAppleCode($code);

        // Get user information from ID token
        $userInfo = $oauthHandler->getAppleUserInfo($idToken, $user);

        // Process user login/registration
        $loginResult = processOAuthLogin($userManager, $userInfo);

        if ($loginResult['success']) {
            $_SESSION['user'] = [
                'id' => $loginResult['user']['id'],
                'username' => $loginResult['user']['username'],
                'role' => $loginResult['user']['is_admin'] ? 'admin' : 'user',
                'is_admin' => $loginResult['user']['is_admin'],
                'is_oauth' => true
            ];
            // Set session
            $_SESSION['mb_user'] = $loginResult['user']['username'];
            $_SESSION['mb_user_data'] = $loginResult['user'];

            // Redirect to appropriate page
            if ($loginResult['user']['is_admin']) {
                header('Location: /index.php?app=admin&page=dashboard');
            } else {
                header('Location: /index.php?p=dashboard');
            }
            exit;
        } else {
            throw new Exception($loginResult['error']);
        }
    }

    // Handle errors
    if (isset($_POST['error'])) {
        $error = $_POST['error'];
        $errorDescription = $_POST['error_description'] ?? '';
        throw new Exception("Apple OAuth error: $error - $errorDescription");
    }

    throw new Exception('Invalid OAuth request');
} catch (Exception $e) {
    // Log error
    error_log('Apple OAuth Error: ' . $e->getMessage());

    // Redirect to login with error
    $errorMsg = urlencode($e->getMessage());
    header("Location: /index.php?p=login&oauth_error=$errorMsg");
    exit;
}

/**
 * Process OAuth login/registration
 */
function processOAuthLogin($userManager, $userInfo) {
    try {
        error_log('Processing OAuth login for user info: ' . print_r($userInfo, true));
        $email = $userInfo['email'];
        
        if (empty($email)) {
            return ['success' => false, 'error' => 'No email provided by OAuth provider'];
        }

        $redirectUrl = null;
        if (isset($_SESSION['oauth_return_url'])) {
            $redirectUrl = $_SESSION['oauth_return_url'];
            unset($_SESSION['oauth_return_url']);
        }


        
        // Try to find existing user by email
        $existingUser = $userManager->findUserByEmail($userManager, $email);
        $username = strstr($userInfo['email'], '@', true);

        // Download and update profile picture if needed
        if (!empty($userInfo['picture'])) {
            updateUserProfilePicture($userManager, $username, $userInfo['picture']);
        }

        if ($existingUser) {
            // Update OAuth info for existing user
            updateUserOAuthInfo($userManager, $existingUser['username'], $userInfo);
            /*
            */
            // Generic app access check (no app-specific logic)
            if ($targetApp) {
                $appAccessResult = checkUserAppAccess($existingUser['username'], $email, $targetApp);
                if (!$appAccessResult['hasAccess']) {
                    return ['success' => false, 'error' => $appAccessResult['message']];
                }
            }
            
            return ['success' => true, 'user' => $existingUser];
        } else {
            // Generic new user creation with app-aware role assignment
            if ($targetApp) {
                $invitationCheck = checkUserAppInvitation($email, $targetApp);
                if (!$invitationCheck['invited']) {
                    return ['success' => false, 'error' => 'This email address has not been invited to access this application. Please contact the administrator.'];
                }
                
                // Create new user and assign roles
                $newUser = createUserFromOAuth($userManager, $userInfo);
                assignUserAppRoles($newUser['username'], $email, $targetApp, $invitationCheck['roles']);
                
                return ['success' => true, 'user' => $newUser];
            } else {
                // Regular user creation for non-app-specific login
                $newUser = createUserFromOAuth($userManager, $userInfo);
                return ['success' => true, 'user' => $newUser];
            }
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
