<?php
echo "<h1>Session Cleanup Utility</h1>";

// Start session to get current session info
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h3>Current Session Info:</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Name:</strong> " . session_name() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";
echo "<p><strong>Session Save Path:</strong> " . session_save_path() . "</p>";

echo "<h3>Session Data:</h3>";
if (!empty($_SESSION)) {
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
} else {
    echo "<p>No session data</p>";
}

echo "<h3>Session Cookie Info:</h3>";
$cookieParams = session_get_cookie_params();
echo "<pre>" . print_r($cookieParams, true) . "</pre>";

// Check for problematic session data
echo "<h3>Session Analysis:</h3>";
$issues = [];

if (isset($_SESSION['mb_user']) && isset($_SESSION['user'])) {
    $issues[] = "Duplicate user session variables detected (mb_user and user)";
}

if (isset($_SESSION['user'])) {
    $userType = gettype($_SESSION['user']);
    echo "<p><strong>User session type:</strong> $userType</p>";
    if ($userType === 'array') {
        echo "<p><strong>User keys:</strong> " . implode(', ', array_keys($_SESSION['user'])) . "</p>";
    }
}

// Check for old session format
if (isset($_SESSION['mb_user']) && !isset($_SESSION['user'])) {
    $issues[] = "Old session format detected (mb_user without unified user)";
}

// Check session file if accessible
$sessionPath = session_save_path();
$sessionFile = $sessionPath . '/sess_' . session_id();
if (file_exists($sessionFile)) {
    $fileSize = filesize($sessionFile);
    $lastModified = date('Y-m-d H:i:s', filemtime($sessionFile));
    echo "<p><strong>Session file size:</strong> $fileSize bytes</p>";
    echo "<p><strong>Last modified:</strong> $lastModified</p>";
    
    if ($fileSize > 10000) {
        $issues[] = "Large session file ($fileSize bytes) - may cause performance issues";
    }
} else {
    echo "<p><strong>Session file:</strong> Not found at $sessionFile</p>";
}

if (count($issues) > 0) {
    echo "<h3 style='color: red;'>Issues Found:</h3>";
    foreach ($issues as $issue) {
        echo "<p style='color: red;'>• $issue</p>";
    }
    
    echo "<h3>Cleanup Options:</h3>";
    echo "<form method='post'>";
    echo "<button type='submit' name='clear_session' style='background: red; color: white; padding: 10px; border: none; cursor: pointer;'>Clear Current Session</button>";
    echo "</form>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='fix_session' style='background: orange; color: white; padding: 10px; border: none; cursor: pointer; margin-left: 10px;'>Fix Session Format</button>";
    echo "</form>";
} else {
    echo "<p style='color: green;'>No issues detected with current session</p>";
}

// Handle cleanup actions
if (isset($_POST['clear_session'])) {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    echo "<p style='color: green;'>Session cleared! Please refresh the page.</p>";
    echo "<script>setTimeout(function() { window.location.reload(); }, 2000);</script>";
}

if (isset($_POST['fix_session'])) {
    // Convert old session format to new
    if (isset($_SESSION['mb_user']) && !isset($_SESSION['user'])) {
        $_SESSION['user'] = $_SESSION['mb_user'];
        unset($_SESSION['mb_user']);
        echo "<p style='color: green;'>Session format fixed! Old mb_user converted to user.</p>";
    }
    
    // Ensure user is in correct format
    if (isset($_SESSION['user']) && is_string($_SESSION['user'])) {
        $username = $_SESSION['user'];
        $_SESSION['user'] = [
            'username' => $username,
            'role' => 'user', // Default role
            'is_admin' => false
        ];
        echo "<p style='color: green;'>User session converted to array format.</p>";
    }
    
    echo "<script>setTimeout(function() { window.location.reload(); }, 2000);</script>";
}

echo "<hr>";
echo "<p><a href='/'>← Back to Home</a></p>";
?>