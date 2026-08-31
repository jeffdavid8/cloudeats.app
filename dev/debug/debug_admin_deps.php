<?php
// Debug admin dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Admin Dependencies Debug</h1>";

// Start session
session_start();
$_SESSION['admin_user'] = 'admin';
$_SESSION['mb_user'] = 'admin';

echo "<h2>1. Testing AdminAuth</h2>";
try {
    require_once 'html/apps/admin/includes/AdminAuth.php';
    $adminAuth = new AdminAuth();
    echo "✅ AdminAuth loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ AdminAuth failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>2. Testing UserManager</h2>";
try {
    require_once 'html/apps/admin/includes/UserManager.php';
    $userManager = new UserManager();
    echo "✅ UserManager loaded successfully<br>";
    
    // Test getUserStats method
    echo "<h3>2a. Testing getUserStats</h3>";
    $stats = $userManager->getUserStats();
    echo "✅ getUserStats returned: <pre>" . print_r($stats, true) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ UserManager failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "❌ UserManager fatal error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>3. Testing FileStorageManager</h2>";
try {
    require_once 'html/includes/storage/FileStorageManager.php';
    $storage = FileStorageManager::getInstance();
    echo "✅ FileStorageManager loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ FileStorageManager failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>4. Testing Admin Dashboard View</h2>";
try {
    // Set up environment like admin app would
    require_once 'html/includes/app.php';
    $app = App::getInstance();
    
    echo "Testing dashboard view directly...<br>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    include 'html/apps/admin/views/dashboard.php';
    echo "</div>";
    echo "✅ Dashboard view completed<br>";
    
} catch (Exception $e) {
    echo "❌ Dashboard view failed: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "❌ Dashboard view fatal error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>5. Complete Test</h2>";
echo "Debug completed.";
?>