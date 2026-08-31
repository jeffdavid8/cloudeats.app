<?php

/**
 * OAuth Handler Class
 * Manages OAuth authentication for Google and Apple providers
 */

class OAuthHandler
{
    private $configPath;
    private $config;
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new OAuthHandler();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        // Decide whether to use Secret Manager
        $forceSM = false;
        $forceEnv = getenv('FORCE_SECRET_MANAGER');
        if ($forceEnv !== false) {
            $forceSM = in_array(strtolower($forceEnv), ['1', 'true', 'yes'], true);
        }

        $useSecretManager = (function_exists('isCloudRun') && isCloudRun()) || $forceSM;

        if ($useSecretManager) {
            // Try to load from Secret Manager. On local dev this can be forced with FORCE_SECRET_MANAGER=1
            try {

                $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: getenv('GCLOUD_PROJECT');
                if (!$projectId) throw new Exception('GOOGLE_CLOUD_PROJECT not set for Secret Manager access');

                $client = new \Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient();
                $name = "projects/$projectId/secrets/kammys_kafe_oauth_config/versions/latest";
                $request = new \Google\Cloud\SecretManager\V1\AccessSecretVersionRequest();
                $request->setName($name);
                $response = $client->accessSecretVersion($request);
                $secretJson = $response->getPayload()->getData();
                //error_log(json_decode($secretJson, true));
                if ($secretJson) {
                    $this->config = json_decode($secretJson, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('Invalid OAuth configuration format from Secret Manager');
                    }
                    return;
                }
            } catch (Exception $e) {
                // Don't hard-fail for dev; log and fall back to file-based config
                error_log('OAuthHandler Secret Manager fetch failed: ' . $e->getMessage());
            }
        }

        // Local/dev: prefer canonical data directory but try several candidate paths
        $this->configPath = '/var/data/mediabrain/oauth_config.json';
        if (file_exists('C:\\var\\data\\mediabrain\\')) {
            $this->configPath = 'C:\\var\\data\\mediabrain\\oauth_config.json';
        }
        $this->loadConfig();
    }

    /**
     * Load OAuth configuration from file
     */
    private function loadConfig()
    {
        // Support multiple fallback locations and environment overrides for development
        // 1) If OAUTH_CONFIG_JSON environment variable is set, use that
        $envJson = getenv('OAUTH_CONFIG_JSON');
        if ($envJson !== false && !empty($envJson)) {
            $configData = file_get_contents($envJson);
            $this->config = json_decode($configData, true);
            //error_log(print_r($this->config, true));
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid OAuth configuration format from OAUTH_CONFIG_JSON env var');
            }
            return;
        }

        // Candidate file paths to try (order matters)
        $candidates = [
            $this->configPath,
            '/var/data/mediabrain/oauth_config.json',
            __DIR__ . '/../../../oauth_config.json',
            __DIR__ . '/../../oauth_config.json',
            __DIR__ . '/../../config/oauth_config.json',
            getcwd() . '/oauth_config.json',
            dirname(__DIR__, 3) . '/oauth_config.json'
        ];

        $found = false;
        foreach ($candidates as $path) {
            if (!$path) continue;
            // normalize Windows backslashes for file_exists
            $norm = str_replace('\\', DIRECTORY_SEPARATOR, $path);
            if (file_exists($norm) && is_readable($norm)) {
                $this->configPath = $norm;
                $configData = file_get_contents($norm);
                $this->config = json_decode($configData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Invalid OAuth configuration format in ' . $norm);
                }
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new Exception('OAuth configuration file not found');
        }
    }

    /**
     * Save OAuth configuration to file
     */
    public function saveConfig($newConfig)
    {
        // Deep merge with existing config to preserve all fields
        foreach ($newConfig as $provider => $providerConfig) {
            if (isset($this->config[$provider]) && is_array($providerConfig)) {
                // Merge provider configuration while preserving existing fields
                $this->config[$provider] = array_merge($this->config[$provider], $providerConfig);
            } else {
                // New provider or non-array value, set directly
                $this->config[$provider] = $providerConfig;
            }
        }

        $this->config['updated_at'] = date('Y-m-d H:i:s');

        $result = file_put_contents($this->configPath, json_encode($this->config, JSON_PRETTY_PRINT));
        if (!$result) {
            throw new Exception('Failed to save OAuth configuration');
        }

        return true;
    }

    /**
     * Get OAuth configuration with dynamic redirect URIs
     */
    public function getConfig($provider = null)
    {
        // Environment detection functions are available globally after app.php inclusion
        // No need to include util.php directly here

        $config = $this->config;
        $provider = (!empty($provider)) ? $provider : $_SESSION['oauth_provider'];

        // Update redirect URIs to match current environment
        $baseUrl = get_base_url();

        if (isset($config['google'])) {
            $config['google']['redirect_uri'] = $baseUrl . '/oauth/google.php?action=callback';
        }
        if (isset($config['apple'])) {
            $config['apple']['redirect_uri'] = $baseUrl . '/oauth/apple.php?action=callback';
        }
        if (isset($config['facebook'])) {
            $config['facebook']['redirect_uri'] = $baseUrl . '/oauth/facebook.php?action=callback';
        }
        if (isset($config['linkedin'])) {
            $config['linkedin']['redirect_uri'] = $baseUrl . '/oauth/linkedin.php?action=callback';
        }

        if ($provider) {
            return $config[$provider] ?? null;
        }
        return $config;
    }

    /**
     * Check if a provider is enabled and configured
     */
    public function isProviderEnabled($provider)
    {
        $providerConfig = $this->getConfig($provider);
        if (!$providerConfig) {
            return false;
        }

        return $providerConfig['enabled'] && $this->isProviderConfigured($provider);
    }

    /**
     * Check if a provider is properly configured
     */
    public function isProviderConfigured($provider)
    {
        $providerConfig = $this->getConfig($provider);
        if (!$providerConfig) {
            return false;
        }

        switch ($provider) {
            case 'google':
                return !empty($providerConfig['client_id']) && !empty($providerConfig['client_secret']);

            case 'apple':
                return !empty($providerConfig['client_id']) &&
                    !empty($providerConfig['team_id']) &&
                    !empty($providerConfig['key_id']) &&
                    file_exists($providerConfig['private_key_path']);

            case 'facebook':
                return !empty($providerConfig['client_id']) && !empty($providerConfig['client_secret']);

            case 'linkedin':
                return !empty($providerConfig['client_id']) && !empty($providerConfig['client_secret']);

            default:
                return false;
        }
    }

    /**
     * Generate OAuth authorization URL for Google
     */
    public function getGoogleAuthUrl($state = null)
    {
        $config = $this->getConfig('google');
        if (!$config || !$config['enabled']) {
            throw new Exception('Google OAuth is not enabled');
        }

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => implode(' ', $config['scopes']),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Generate OAuth authorization URL for Apple
     */
    public function getAppleAuthUrl($state = null)
    {
        $config = $this->getConfig('apple');
        if (!$config || !$config['enabled']) {
            throw new Exception('Apple OAuth is not enabled');
        }

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => implode(' ', $config['scopes']),
            'response_type' => 'code',
            'response_mode' => 'form_post'
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return 'https://appleid.apple.com/auth/authorize?' . http_build_query($params);
    }

    /**
     * Generate OAuth authorization URL for Facebook
     */
    public function getFacebookAuthUrl($state = null)
    {
        $config = $this->getConfig('facebook');
        if (!$config || !$config['enabled']) {
            throw new Exception('Facebook OAuth is not enabled');
        }

        $params = [
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => implode(',', $config['scopes']),
            'response_type' => 'code'
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
    }

    /**
     * Generate OAuth authorization URL for LinkedIn
     */
    public function getLinkedInAuthUrl($state = null)
    {
        $config = $this->getConfig('linkedin');
        if (!$config || !$config['enabled']) {
            throw new Exception('LinkedIn OAuth is not enabled');
        }

        $params = [
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'scope' => $config['scopes'],
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token (Google)
     */
    public function exchangeGoogleCode($code)
    {
        $config = $this->getConfig('google');

        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri']
        ];

        $response = $this->makeHttpRequest($tokenUrl, $postData);

        if (isset($response['error'])) {
            throw new Exception('Google token exchange failed: ' . $response['error_description']);
        }

        return $response;
    }

    /**
     * Exchange authorization code for access token (Facebook)
     */
    public function exchangeFacebookCode($code)
    {
        $config = $this->getConfig('facebook');

        $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'code' => $code,
            'redirect_uri' => $config['redirect_uri']
        ];

        $response = $this->makeHttpRequest($tokenUrl, $postData);

        if (isset($response['error'])) {
            throw new Exception('Facebook token exchange failed: ' . $response['error']['message']);
        }

        return $response;
    }

    /**
     * Exchange authorization code for access token (Apple)
     */
    public function exchangeAppleCode($code)
    {
        $config = $this->getConfig('apple');

        $tokenUrl = 'https://appleid.apple.com/auth/token';
        $clientSecret = $this->generateAppleClientSecret();

        $postData = [
            'client_id' => $config['client_id'],
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $config['redirect_uri']
        ];

        $response = $this->makeHttpRequest($tokenUrl, $postData);

        if (isset($response['error'])) {
            throw new Exception('Apple token exchange failed: ' . $response['error_description']);
        }

        return $response;
    }

    /**
     * Exchange authorization code for access token (LinkedIn)
     */
    public function exchangeLinkedInCode($code)
    {
        $config = $this->getConfig('linkedin');

        $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';
        $postData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ];
        error_log('LinkedIn Token Request Data: ' . print_r($postData, true));

        $response = $this->makeHttpRequest($tokenUrl, $postData);
        // log tokenUrl and response for debugging
        error_log('LinkedIn Token URL: ' . $tokenUrl);
        error_log('LinkedIn Token Response: ' . print_r($response, true));

        if (isset($response['error'])) {
            throw new Exception('LinkedIn token exchange failed: ' . $response['error_description']);
        }

        return $response;
    }

    /**
     * Get user information from Google
     */
    public function getGoogleUserInfo($accessToken)
    {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $accessToken;

        $response = $this->makeHttpRequest($userInfoUrl, null, 'GET');

        if (isset($response['error'])) {
            throw new Exception('Failed to get Google user info: ' . $response['error']['message']);
        }

        return [
            'provider_id' => $response['id'],
            'email' => $response['email'],
            'oauth_provider' => 'google',
            'oauth_profile_url' => $response['link'] ?? "https://profiles.google.com/" . $response['id'],
            'name' => $response['name'],
            'first_name' => $response['given_name'] ?? '',
            'last_name' => $response['family_name'] ?? '',
            'picture' => $response['picture'] ?? '',
            'email_verified' => $response['verified_email'] ?? false
        ];
    }

    /**
     * Get user information from Apple
     */
    public function getAppleUserInfo($idToken, $userInfo = null)
    {
        // Decode the JWT ID token
        $payload = $this->decodeAppleIdToken($idToken);

        $userInfo = [
            'provider' => 'apple',
            'provider_id' => $payload['sub'],
            'email' => $payload['email'] ?? '',
            'email_verified' => $payload['email_verified'] ?? false,
            'name' => '',
            'first_name' => '',
            'last_name' => '',
            'picture' => ''
        ];

        // If name info was provided in the first authorization
        if ($userInfo && isset($userInfo['name'])) {
            $userInfo['name'] = trim(($userInfo['name']['firstName'] ?? '') . ' ' . ($userInfo['name']['lastName'] ?? ''));
            $userInfo['first_name'] = $userInfo['name']['firstName'] ?? '';
            $userInfo['last_name'] = $userInfo['name']['lastName'] ?? '';
        }

        return $userInfo;
    }

    /**
     * Get user information from Facebook
     */
    public function getFacebookUserInfo($accessToken)
    {
        $fields = 'id,name,email,first_name,last_name,picture.type(large)';
        $userInfoUrl = 'https://graph.facebook.com/me?fields=' . $fields . '&access_token=' . $accessToken;

        $response = $this->makeHttpRequest($userInfoUrl, null, 'GET');

        if (isset($response['error'])) {
            throw new Exception('Failed to get Facebook user info: ' . $response['error']['message']);
        }

        return [
            'provider_id' => $response['id'],
            'email' => $response['email'] ?? '',
            'oauth_provider' => 'facebook',
            'oauth_profile_url' => $response['link'] ?? "https://www.facebook.com/" . $response['id'],
            'name' => $response['name'] ?? '',
            'first_name' => $response['first_name'] ?? '',
            'last_name' => $response['last_name'] ?? '',
            'picture' => $response['picture']['data']['url'] ?? '',
            'email_verified' => true // Facebook emails are generally verified
        ];
    }

    /**
     * Generate Apple client secret JWT
     */
    private function generateAppleClientSecret()
    {
        $config = $this->getConfig('apple');

        if (!file_exists($config['private_key_path'])) {
            throw new Exception('Apple private key file not found');
        }

        $privateKey = file_get_contents($config['private_key_path']);

        $header = [
            'alg' => 'ES256',
            'kid' => $config['key_id']
        ];

        $payload = [
            'iss' => $config['team_id'],
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour
            'aud' => 'https://appleid.apple.com',
            'sub' => $config['client_id']
        ];

        return $this->createJWT($header, $payload, $privateKey);
    }


    /**
     * Get user information from LinkedIn
     */
    public function getLinkedInUserInfo($accessToken)
    {
        // Correct endpoints
        $userInfoUrl = 'https://api.linkedin.com/v2/me?projection=(id,localizedFirstName,localizedLastName)';
        $emailUrl    = 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))';

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'X-Restli-Protocol-Version: 2.0.0',
            'Accept: application/json'
        ];

        // --- MUST BE GET ---
        $userResponse = $this->makeHttpRequest($userInfoUrl, null, 'GET', $headers);
        error_log('LinkedIn User Response: ' . print_r($userResponse, true));

        if (isset($userResponse['serviceErrorCode'])) {
            throw new Exception('Failed to get LinkedIn user info: ' . $userResponse['message']);
        }

        // --- MUST BE GET ---
        $emailResponse = $this->makeHttpRequest($emailUrl, null, 'GET', $headers);
        error_log('LinkedIn Email Response: ' . print_r($emailResponse, true));

        if (isset($emailResponse['serviceErrorCode'])) {
            throw new Exception('Failed to get LinkedIn email: ' . $emailResponse['message']);
        }

        $email = $emailResponse['elements'][0]['handle~']['emailAddress'] ?? '';

        return [
            'provider'       => 'linkedin',
            'provider_id'    => $userResponse['id'] ?? '',
            'email'          => $email,
            'oauth_provider' => 'linkedin',
            'oauth_profile_url' => "https://www.linkedin.com/common/profile?id=" . ($userResponse['id'] ?? ''),
            'name'           => trim(($userResponse['localizedFirstName'] ?? '') . ' ' . ($userResponse['localizedLastName'] ?? '')),
            'first_name'     => $userResponse['localizedFirstName'] ?? '',
            'last_name'      => $userResponse['localizedLastName'] ?? '',
            'picture'        => '',
            'email_verified' => !empty($email)
        ];
    }



    /**
     * Decode Apple ID token
     */
    private function decodeAppleIdToken($idToken)
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3) {
            throw new Exception('Invalid Apple ID token format');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

        if (!$payload) {
            throw new Exception('Failed to decode Apple ID token');
        }

        // TODO: Add signature verification for production use

        return $payload;
    }

    /**
     * Create JWT token
     */
    private function createJWT($header, $payload, $privateKey)
    {
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $data = $headerEncoded . '.' . $payloadEncoded;

        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $data . '.' . $signatureEncoded;
    }

    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Process OAuth login or registration
     *
     * @param array $userInfo
     * @return array
     */
    function processOAuthLogin($userInfo)
    {
        //$userManager = UserManager::getInstance();
        try {
            // Check if user already exists based on provider ID or email
            $existingUser = User::getByEmail($userInfo['email']);

            if ($existingUser) {
                // Existing user found, update OAuth info and return

                $existingUser = $this->updateUserOAuthInfo($existingUser->username, $userInfo);

                return [
                    'success' => true,
                    'user' => $existingUser->data(),
                ];
            } else {
                // No existing user, create new user
                $newUser = $this->createUserFromOAuth($userInfo);

                // Optionally set profile picture
                if (!empty($userInfo['picture'])) {
                    $this->updateUserProfilePicture($newUser['username'], $userInfo['picture'], $newUser);
                }

                return [
                    'success' => true,
                    'user' => $newUser->data()
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'OAuth login failed: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Update user profile picture from OAuth provider
     */
    function updateUserProfilePicture($username, $pictureUrl, &$userData = null)
    {
        try {
            $userManager = UserManager::getInstance();

            // Download and store the profile picture
            require_once __DIR__ . '/../includes/storage/FileStorageManager.php';

            $storageManager = FileStorageManager::getInstance();

            // Download image
            $imageData = file_get_contents($pictureUrl);
            if ($imageData === false) {
                return false;
            }
            $tempDir = sys_get_temp_dir();
            // Create a unique temporary filename
            $originalFileName = basename($pictureUrl);
            $tempFilePath = tempnam($tempDir, 'img_') . '_' . $originalFileName;

            // 3. Save the string data to the temporary file
            if (file_put_contents($tempFilePath, $imageData) === false) {
                die("Could not save image data to temporary file.");
            }

            // 4. (Optional) Create a mock $_FILES structure for compatibility
            // This is not a real $_FILES, but an associative array that mimics its structure.
            $file = [
                'name' => $originalFileName,
                'type' => mime_content_type($tempFilePath), // Determine the MIME type
                'size' => filesize($tempFilePath),
                'tmp_name' => $tempFilePath,
                'error' => 0 // 0 means no error
            ];

            // Generate filename
            $extension = 'jpg'; // Default to jpg for OAuth images
            $filename = $username . '_oauth_profile.' . $extension;

            // Store the file
            $result = $storageManager->uploadFile(
                $file,
                FileStorageManager::CATEGORY_PROFILE_IMAGES,
                $filename,
            );

            if ($result['success']) {
                // Update user data
                if ($userData !== null) {
                    // Updating array reference for new user
                    $userData['picture'] = $result['url'];
                    //$userData['profileImageFilename'] = $filename;
                } else {
                    // Update existing user
                    $user = User::getByUsername($username);
                    if ($user) {
                        $user->picture = $result['url'];
                        //$user['profileImageFilename'] = $filename;
                        $user->update();
                    }
                }

                return $result['url'];
            }
        } catch (Exception $e) {
            error_log('Failed to update profile picture: ' . $e->getMessage());
        }

        return false;
    }


    /**
     * Update user OAuth information
     */
    function updateUserOAuthInfo($username, $oauthInfo)
    {
        $user = User::getByUsername($username);

        if (!$user) {
            throw new Exception('User not found');
        }

        // Update OAuth provider info
        if (!isset($user->oauth_providers)) {
            $user->oauth_providers = [];
        }

        $user->oauth_providers[$oauthInfo['provider']] = [
            'provider_id' => $oauthInfo['provider_id'],
            'email' => $oauthInfo['email'],
            'name' => $oauthInfo['name'],
            'linked_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s')
        ];

        // Update last login
        $user->last_login = date('Y-m-d H:i:s');

        $user->update();
        return $user;
    }


    /**
     * Create new user from OAuth info
     */
    function createUserFromOAuth($oauthInfo)
    {
        //$userManager = UserManager::getInstance();
        $email = $oauthInfo['email'];
        $provider = $oauthInfo['provider'];
        $providerId = $oauthInfo['provider_id'];

        // 1. Check if user already exists by email
        $existingUser = User::getByEmail($email);

        if ($existingUser) {
            // User exists! Let's check if this provider is already linked
            $oauthProviders = isset($existingUser->oauth_providers) ?
                json_decode($existingUser->oauth_providers, true) : [];

            // Update or Add this provider's specific data
            $oauthProviders[$provider] = [
                'provider_id' => $providerId,
                'email' => $email,
                'name' => $oauthInfo['name'],
                'linked_at' => $existingUser->oauth_providers[$provider]['linked_at'] ?? date('Y-m-d H:i:s'),
                'last_login' => date('Y-m-d H:i:s')
            ];

            // Update the existing user record with the new provider info and profile pic
            $updateData = [
                'oauth_providers' => json_encode($oauthProviders),
                'last_login' => date('Y-m-d H:i:s')
            ];

            // Optionally update profile picture if the existing one is empty
            if (empty($existingUser->picture) && !empty($oauthInfo['picture'])) {
                $updateData['picture'] = $oauthInfo['picture'];
            }

            $existingUser->data($updateData);
            $existingUser->update();

            //$userManager->updateUser($existingUser->id, $updateData);

            // Merge updates into the object we return to the session
            return $existingUser;
        }

        // 2. No user found? Proceed with NEW registration

        // Generate unique username from email
        $username = strstr($email, '@', true);
        $originalUsername = $username;
        $counter = 1;
        while (User::getByUsername($username)) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        $userData = [
            'username' => $username,
            'email' => $email,
            'oauth_provider' => $provider, // Primary provider
            'oauth_profile_url' => $oauthInfo['oauth_profile_url'] ?? '',
            'password' => bin2hex(random_bytes(16)), // Better to store a random hash than empty string
            'role' => 'user',
            'is_admin' => false,
            'active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'modified_at' => date('Y-m-d H:i:s'),
            'picture' => $oauthInfo['picture'] ?? '',
            'oauth_providers' => json_encode([
                $provider => [
                    'provider_id' => $providerId,
                    'email' => $email,
                    'name' => $oauthInfo['name'],
                    'linked_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                ]
            ])
        ];

        $newUser = new User($userData);
        $newUser->save();

        $userData['id'] = $newUser->id;

        return $userData;
    }

    /**
     * Generic function to check if existing user has access to target app
     */
    function checkUserAppAccess($username, $email, $appName)
    {
        try {
            require_once __DIR__ . '/../apps/admin/includes/PermissionsMatrix.php';
            $permissionsMatrix = new PermissionsMatrix();

            // Check if user already has any roles for this app
            if ($permissionsMatrix->canAccessApp($username, $appName)) {
                return ['hasAccess' => true];
            }

            // Check if email is on invitation list for this app
            $invitationCheck = $this->checkUserAppInvitation($email, $appName);
            if ($invitationCheck['invited']) {
                // Auto-assign roles to existing user
                $this->assignUserAppRoles($username, $email, $appName, $invitationCheck['roles']);
                return ['hasAccess' => true];
            }

            return [
                'hasAccess' => false,
                'message' => "Your account does not have access to the {$appName} application. Please contact the administrator."
            ];
        } catch (Exception $e) {
            error_log("Error checking app access for {$appName}: " . $e->getMessage());
            return [
                'hasAccess' => false,
                'message' => 'Unable to verify application access permissions.'
            ];
        }
    }

    /**
     * Generic function to check if email address has been invited to an app
     */
    function checkUserAppInvitation($email, $appName)
    {
        try {
            require_once __DIR__ . '/../apps/admin/includes/PermissionsMatrix.php';
            $permissionsMatrix = new PermissionsMatrix();

            // Load user permissions to check for email-based invitations
            $userPermissions = $permissionsMatrix->getAllUserPermissions();

            // Check if email exists as a username with app roles
            if (
                isset($userPermissions[$email]) &&
                isset($userPermissions[$email]['app_roles'][$appName]) &&
                !empty($userPermissions[$email]['app_roles'][$appName])
            ) {

                $appRoles = $userPermissions[$email]['app_roles'][$appName];
                return [
                    'invited' => true,
                    'roles' => $appRoles // Return all assigned roles
                ];
            }

            return ['invited' => false, 'roles' => []];
        } catch (Exception $e) {
            error_log("Error checking app invitation for {$appName}: " . $e->getMessage());
            return ['invited' => false, 'roles' => []];
        }
    }

    /**
     * Generic function to assign app roles to user
     */
    function assignUserAppRoles($username, $email, $appName, $roles)
    {
        try {
            require_once __DIR__ . '/../apps/admin/includes/PermissionsMatrix.php';
            $permissionsMatrix = new PermissionsMatrix();

            $success = true;

            foreach ($roles as $role) {
                // If username differs from email (OAuth created username), 
                // we need to transfer the role from email-based invitation
                if ($username !== $email) {
                    // Remove role from email-based entry
                    $permissionsMatrix->removeUserAppRole($email, $appName, $role);
                }

                // Assign role to actual username
                $result = $permissionsMatrix->assignUserAppRole($username, $appName, $role);

                if ($result) {
                    error_log("Successfully assigned {$appName} role {$role} to user {$username}");
                } else {
                    error_log("Failed to assign {$appName} role {$role} to user {$username}");
                    $success = false;
                }
            }

            return $success;
        } catch (Exception $e) {
            error_log("Error assigning {$appName} roles: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Make HTTP request
     */
    private function makeHttpRequest($url, $postData = null, $method = 'POST', $headers = [])
    {
        $ch = curl_init();

        // Base headers
        $defaultHeaders = [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ];

        // Merge default + custom
        $allHeaders = array_merge($defaultHeaders, (empty($headers) ? $defaultHeaders : $headers));

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'MediaBrain OAuth Client/1.0',
            CURLOPT_HTTPHEADER => $allHeaders
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('HTTP request failed: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new Exception("HTTP request failed with status: $httpCode. Response: $response");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . $response);
        }

        return $decoded;
    }

    /**
     * Generate secure random state parameter
     */
    public function generateState()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Store state parameter for verification
     */
    public function storeState($state)
    {
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_state_time'] = time();
    }

    /**
     * Verify state parameter
     */
    public function verifyState($state)
    {
        if (!isset($_SESSION['oauth_state']) || !isset($_SESSION['oauth_state_time'])) {
            return false;
        }

        $storedState = $_SESSION['oauth_state'];
        $stateTime = $_SESSION['oauth_state_time'];

        // Clean up
        unset($_SESSION['oauth_state'], $_SESSION['oauth_state_time']);

        // Check if state matches and hasn't expired (5 minutes)
        return hash_equals($storedState, $state) && (time() - $stateTime) < 300;
    }

    /**
     * Test provider configuration
     */
    public function testProviderConfig($provider)
    {
        try {
            switch ($provider) {
                case 'google':
                    if (!$this->isProviderConfigured('google')) {
                        return [
                            'configured' => false,
                            'valid' => false,
                            'status' => 'Not configured',
                            'details' => 'Missing client ID or client secret'
                        ];
                    }

                    // Test by making a request to OAuth discovery endpoint
                    $response = $this->makeHttpRequest('https://accounts.google.com/.well-known/openid_configuration', null, 'GET');

                    return [
                        'configured' => true,
                        'valid' => true,
                        'status' => 'Ready',
                        'details' => 'Configuration appears valid'
                    ];

                case 'apple':
                    if (!$this->isProviderConfigured('apple')) {
                        return [
                            'configured' => false,
                            'valid' => false,
                            'status' => 'Not configured',
                            'details' => 'Missing required Apple credentials or private key'
                        ];
                    }

                    // Test by generating client secret (validates private key)
                    $this->generateAppleClientSecret();

                    return [
                        'configured' => true,
                        'valid' => true,
                        'status' => 'Ready',
                        'details' => 'Configuration appears valid'
                    ];

                case 'facebook':
                    if (!$this->isProviderConfigured('facebook')) {
                        return [
                            'configured' => false,
                            'valid' => false,
                            'status' => 'Not configured',
                            'details' => 'Missing Facebook App ID or App Secret'
                        ];
                    }

                    // Test by making a request to Facebook Graph API
                    $response = $this->makeHttpRequest('https://graph.facebook.com/oauth/access_token_info', null, 'GET');

                    return [
                        'configured' => true,
                        'valid' => true,
                        'status' => 'Ready',
                        'details' => 'Configuration appears valid'
                    ];

                default:
                    throw new Exception('Unknown provider: ' . $provider);
            }
        } catch (Exception $e) {
            return [
                'configured' => true,
                'valid' => false,
                'status' => 'Configuration Error',
                'details' => $e->getMessage()
            ];
        }
    }
}
