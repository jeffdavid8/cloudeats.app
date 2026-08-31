<?php
define('MB_RUNNING', true);

/**
 * LinkedIn OAuth Callback Handler
 * Handles LinkedIn OAuth authentication flow
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
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $oauthHandler = new OAuthHandler();
            $userManager = new UserManager();

            $config = $oauthHandler->getConfig('linkedin');
            $provider = new League\OAuth2\Client\Provider\LinkedIn([
                'clientId'          => $config['client_id'],
                'clientSecret'      => $config['client_secret'],
                'redirectUri'       => $config['redirect_uri'],
            ]);

            if (!isset($_GET['code'])) {
                // If we don't have an authorization code then get one
                $options = [
                    'scope' => ['openid', 'profile', 'email'] // Request OIDC scopes
                ];
                $authorizationUrl = $provider->getAuthorizationUrl($options);
                $_SESSION['oauth2state'] = $provider->getState();
                if (isset($_GET['return_url'])) {
                    $_SESSION['oauth_return_url'] = $_GET['return_url'];
                }
                $_SESSION['oauth_provider'] = 'linkedIn';

                header('Location: ' . $authorizationUrl);
                exit();
            }
            // If a code is present, it should be handled by the 'callback' action.
            $queryParams = http_build_query($_GET);
            header('Location: ?action=callback&' . $queryParams);
            exit();

        case 'callback':
            $oauthHandler = new OAuthHandler();
            $userManager = new UserManager();

            $config = $oauthHandler->getConfig('linkedin');
            $provider = new League\OAuth2\Client\Provider\LinkedIn([
                'clientId'          => $config['client_id'],
                'clientSecret'      => $config['client_secret'],
                'redirectUri'       => $config['redirect_uri'],
            ]);
            // Handle OAuth callback from LinkedIn
            if (isset($_GET['error'])) {
                throw new Exception('LinkedIn returned an error: ' . htmlspecialchars($_GET['error_description']));
            }

            if (!isset($_GET['code'])) {
                throw new Exception('Invalid request, authorization code missing.');
            }

            if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
                throw new Exception('Invalid state parameter. Possible CSRF attack.');
            }

            try {
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                // Manually fetch user details from the OIDC userinfo endpoint
                $userInfoUrl = 'https://api.linkedin.com/v2/userinfo';
                $context = stream_context_create([
                    'http' => [
                        'header' => "Authorization: Bearer " . $accessToken->getToken() . "\r\n"
                    ]
                ]);
                $response = file_get_contents($userInfoUrl, false, $context);
                $userOIDC = json_decode($response, true);

                if (!$userOIDC) {
                    throw new Exception('Failed to get user details from LinkedIn.');
                }

                //error_log('LinkedIn OIDC User Details: ' . print_r($userOIDC, true));

                // Map OIDC user details to the application's user info format
                $userInfo = [
                    'provider'       => 'linkedin',
                    'provider_id'    => $userOIDC['sub'],
                    'email'          => $userOIDC['email'],
                    'name'           => $userOIDC['name'],
                    'first_name'     => $userOIDC['given_name'],
                    'last_name'      => $userOIDC['family_name'],
                    'picture'        => $userOIDC['picture'] ?? '',
                    'email_verified' => $userOIDC['email_verified'] ?? false
                ];

                $loginResult = $oauthHandler->processOAuthLogin($userInfo);
                //error_log('OAuth Login Result: ' . print_r($loginResult, true));

                if ($loginResult['success']) {
                    // Set session variables
                    $_SESSION['user'] = [
                        'id' => $loginResult['user']['id'],
                        'username' => $loginResult['user']['username'],
                        'role' => $loginResult['user']['role'],
                        'is_admin' => ($loginResult['user']['role'] === 'admin'),
                        'is_oauth' => true,
                        'profilePicture' => $userInfo['picture'] ?? '',
                    ];
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
                    throw new Exception($loginResult['error'] ?? 'An unknown error occurred during login.');
                }
            } catch (\Exception $e) {
                // Catch both library exceptions and our own
                throw new Exception('Failed to process LinkedIn login: ' . $e->getMessage());
            }
            break;

        default:
            throw new Exception('Invalid action for LinkedIn OAuth');
    }
} catch (Exception $e) {
    error_log('LinkedIn OAuth Error: ' . $e->getMessage());
    // Redirect to login page with error message
    header('Location: /?p=login&oauth_error=' . urlencode($e->getMessage()));
    exit;
}
