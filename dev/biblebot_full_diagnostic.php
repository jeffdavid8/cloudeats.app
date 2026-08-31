<?php
// Comprehensive BibleBot diagnostic
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<!DOCTYPE html><html><head><title>BibleBot Full Diagnostic</title></head><body>";
echo "<h1>BibleBot Full Diagnostic</h1>";

echo "<h3>1. Session State</h3>";
echo "<p><strong>User logged in:</strong> " . (isset($_SESSION['user']) ? 'YES' : 'NO') . "</p>";
if (isset($_SESSION['user'])) {
    $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
    echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
    echo "<p><strong>User data:</strong> " . var_export($_SESSION['user'], true) . "</p>";
}

echo "<h3>2. Testing Each Component</h3>";

try {
    require_once 'includes/app.php';
    require_once 'apps/bibleBot/bibleBot.app.php';
    
    $app = App::getInstance('bibleBot');
    bibleBot_init();
    
    echo "<h4>2.1 bibleBot_render_body function test</h4>";
    echo "<p>Function exists: " . (function_exists('bibleBot_render_body') ? 'YES' : 'NO') . "</p>";
    
    echo "<h4>2.2 Testing individual components</h4>";
    
    // Test search_results/header.php
    echo "<p><strong>Testing search_results/header.php:</strong></p>";
    ob_start();
    $search_string = $app->get('search_string');
    try {
        include 'apps/bibleBot/views/pages/search_results/header.php';
        $headerOutput = ob_get_clean();
        echo "✓ Header rendered (" . strlen($headerOutput) . " chars)<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Header failed: " . $e->getMessage() . "<br>";
    }
    
    // Test search_section.php
    echo "<p><strong>Testing search_section.php:</strong></p>";
    ob_start();
    try {
        include 'apps/bibleBot/views/components/search_section.php';
        $searchOutput = ob_get_clean();
        echo "✓ Search section rendered (" . strlen($searchOutput) . " chars)<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Search section failed: " . $e->getMessage() . "<br>";
    }
    
    // Test main_left.php sidenav
    echo "<p><strong>Testing sidenav/main_left.php:</strong></p>";
    ob_start();
    try {
        include 'apps/bibleBot/views/components/sidenav/main_left.php';
        $sidenavOutput = ob_get_clean();
        echo "✓ Sidenav rendered (" . strlen($sidenavOutput) . " chars)<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Sidenav failed: " . $e->getMessage() . "<br>";
    }
    
    // Test applications_menu.php
    echo "<p><strong>Testing applications_menu.php:</strong></p>";
    ob_start();
    try {
        include 'views/components/sidenav/applications_menu.php';
        $appsOutput = ob_get_clean();
        echo "✓ Applications menu rendered (" . strlen($appsOutput) . " chars)<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Applications menu failed: " . $e->getMessage() . "<br>";
    }
    
    // Test user_sidenav_menu.php
    echo "<p><strong>Testing user_sidenav_menu.php:</strong></p>";
    ob_start();
    try {
        include 'views/components/sidenav/user_sidenav_menu.php';
        $adminOutput = ob_get_clean();
        echo "✓ Admin auth rendered (" . strlen($adminOutput) . " chars)<br>";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Admin auth failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<h4>2.3 Full render test</h4>";
    ob_start();
    try {
        bibleBot_render_body();
        $fullOutput = ob_get_clean();
        echo "<p>✓ Full render completed (" . strlen($fullOutput) . " chars)</p>";
        
        if (strlen($fullOutput) < 100) {
            echo "<p style='color: red;'>⚠️ Output seems too short, possible render failure</p>";
            echo "<p><strong>Output:</strong> " . htmlspecialchars($fullOutput) . "</p>";
        } else {
            echo "<p style='color: green;'>✓ Output length looks normal</p>";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color: red;'>❌ Full render failed: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Initial setup failed: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>3. Browser Tests</h3>";
echo "<p><a href='?app=bibleBot' target='_blank'>Test BibleBot (Normal Route)</a></p>";
echo "<p><a href='?app=bibleBot&s=John+3:16' target='_blank'>Test BibleBot with Search</a></p>";

// Check if there are any errors in the PHP error log
echo "<h3>4. Recent PHP Errors</h3>";
$errorLogPath = '/var/log/php_errors.log';
if (file_exists($errorLogPath)) {
    $errors = file_get_contents($errorLogPath);
    $recentErrors = substr($errors, -1000); // Last 1000 characters
    if (!empty(trim($recentErrors))) {
        echo "<pre style='background: #ffe6e6; padding: 10px; max-height: 200px; overflow: auto;'>";
        echo htmlspecialchars($recentErrors);
        echo "</pre>";
    } else {
        echo "<p>No recent errors in PHP error log</p>";
    }
} else {
    echo "<p>PHP error log not found at $errorLogPath</p>";
}

echo "</body></html>";
?>