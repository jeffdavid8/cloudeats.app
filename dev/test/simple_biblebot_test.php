<?php
// Simple BibleBot diagnostic without full App dependency
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html><html><head><title>Simple BibleBot Test</title></head><body>";
echo "<h1>Simple BibleBot Test</h1>";

echo "<h3>1. Session State</h3>";
echo "<p><strong>User logged in:</strong> " . (isset($_SESSION['user']) ? 'YES' : 'NO') . "</p>";
if (isset($_SESSION['user'])) {
    $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>User data type:</strong> " . gettype($_SESSION['user']) . "</p>";
    if (is_array($_SESSION['user'])) {
        echo "<p><strong>User array keys:</strong> " . implode(', ', array_keys($_SESSION['user'])) . "</p>";
    }
}

echo "<h3>2. File Existence Checks</h3>";
$files_to_check = [
    'includes/app.php',
    'apps/bibleBot/bibleBot.app.php',
    'apps/bibleBot/views/pages/search_results/header.php',
    'apps/bibleBot/views/components/search_section.php',
    'apps/bibleBot/views/components/sidenav/main_left.php',
    'views/components/sidenav/applications_menu.php',
    'views/components/sidenav/user_sidenav_menu.php'
];

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    echo "<p><strong>$file:</strong> " . ($exists ? '✓ EXISTS' : '❌ NOT FOUND') . "</p>";
    if ($exists) {
        $size = filesize($file);
        echo "<p>&nbsp;&nbsp;Size: $size bytes</p>";
    }
}

echo "<h3>3. Direct Component Tests (without App class)</h3>";

// Test applications_menu.php directly
echo "<h4>3.1 Testing applications_menu.php</h4>";
if (file_exists('views/components/sidenav/applications_menu.php')) {
    ob_start();
    try {
        include 'views/components/sidenav/applications_menu.php';
        $output = ob_get_clean();
        echo "<p>✓ Applications menu loaded (" . strlen($output) . " chars)</p>";
        if (strlen($output) < 50) {
            echo "<p>Output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p>❌ Applications menu failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ applications_menu.php not found</p>";
}

// Test user_sidenav_menu.php directly
echo "<h4>3.2 Testing user_sidenav_menu.php</h4>";
if (file_exists('views/components/sidenav/user_sidenav_menu.php')) {
    ob_start();
    try {
        include 'views/components/sidenav/user_sidenav_menu.php';
        $output = ob_get_clean();
        echo "<p>✓ Admin auth loaded (" . strlen($output) . " chars)</p>";
        if (strlen($output) < 50) {
            echo "<p>Output: " . htmlspecialchars($output) . "</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p>❌ Admin auth failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ user_sidenav_menu.php not found</p>";
}

echo "<h3>4. Browser Tests</h3>";
echo "<p><a href='/?app=bibleBot' target='_blank'>Test BibleBot (Normal Route)</a></p>";
echo "<p><a href='/?app=bibleBot&s=John+3:16' target='_blank'>Test BibleBot with Search</a></p>";

echo "<h3>5. PHP Environment</h3>";
echo "<p><strong>Current working directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Document root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script path:</strong> " . __FILE__ . "</p>";

echo "</body></html>";
?>