<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Auth API
 * Handles authentication requests (login, logout)
 * Route: /?api=auth&action=login|logout
 */
// Read the raw JSON from the request body
$json = file_get_contents('php://input');
// Decode it into an associative array
$data = json_decode($json, true);

// Now use $data['action'] or $data['username'] instead of $_POST['action']
$action = $data['action'] ?? $_POST['action'] ?? null;
$response = ['success' => false, 'error' => 'Unknown action'];

// Set proper headers
header('Content-Type: application/json');

switch ($action) {
    case 'login':
        $response = handleLogin($data);
        break;

    case 'logout':
        $response = handleLogout($data);
        break;

    default:
        $response = ['success' => false, 'error' => 'Invalid action'];
}

echo json_encode($response);
exit;

/**
 * Handle login request
 */
function handleLogin($data)
{
    require_once __DIR__ . '/../../includes/RateLimiter.php';

    $username = trim($data['username'] ?? $_POST['username']);
    $password = $data['password'] ?? $_POST['password'];

    // Check rate limiting
    if (!RateLimiter::isAllowed('login')) {
        $timeUntilReset = RateLimiter::getTimeUntilReset('login');
        $minutes = ceil($timeUntilReset / 60);
        return [
            'success' => false,
            'error' => "Too many login attempts. Please try again in {$minutes} minute(s)."
        ];
    }

    // Validate input
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'error' => 'Please enter both username and password.'
        ];
    }

    // Authenticate
    $app = App::getInstance();
    $auth = $app->getAuthManager();
    if ($auth->checkCredentials($username, $password)) {
        $isAdmin = $auth->userIsAdmin($username);

        // Clear rate limiting on success
        RateLimiter::clearAttempts('login');
        
        $stmt = $app->db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['mb_user'] = $username;
        $_SESSION['user'] = $user;
        

        session_write_close(); // Ensure session is saved

        // Log successful login
        if ($app->getEventLogger()) {
            $app->getEventLogger()->log('INFO', 'login', 'User logged in successfully', [
                'username' => $username,
                'is_admin' => $isAdmin
            ]);
        }

        return [
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $data['return'] ?? '?p=dashboard',
            'is_admin' => $isAdmin
        ];
    } else {
        // Record failed attempt
        RateLimiter::recordAttempt('login');

        // Log failed login
        if ($app->getEventLogger()) {
            $app->getEventLogger()->log('ERROR', 'login', 'Login failed - invalid credentials', [
                'username' => $username
            ]);
        }

        return [
            'success' => false,
            'error' => 'Invalid username or password.'
        ];
    }
}

/**
 * Handle logout request
 */
function handleLogout($data)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $username = $_SESSION['user']['username'] ?? 'unknown';

    // Log logout
    $app = App::getInstance();
    if ($app->getEventLogger()) {
        $app->getEventLogger()->log('INFO', 'login', 'User logged out', [
            'username' => $username
        ]);
    }

    // Destroy session
    session_destroy();

    return [
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => '?p=login'
    ];
}
