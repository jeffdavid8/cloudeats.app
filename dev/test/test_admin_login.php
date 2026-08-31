<?php
// DEPRECATED: This file uses the old AdminAuth system
// Use the unified authentication system instead: /?p=login
echo "⚠️ DEPRECATED: This test file is deprecated. Use <a href='/?p=login'>main login</a> instead.";
exit();

// Simple login test - DISABLED
session_start();

echo "<!DOCTYPE html><html><head><title>Admin Login Test</title></head><body>";
echo "<h2>Admin Login Test</h2>";

if ($_POST['username'] ?? false) {
    echo "<h3>Testing Login...</h3>";
    
    require_once 'apps/admin/includes/AdminAuth.php';
    $adminAuth = new AdminAuth();
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    echo "<p>Attempting to authenticate: <strong>$username</strong> / <strong>$password</strong></p>";
    
    if ($adminAuth->authenticate($username, $password)) {
        echo "<div style='color: green; padding: 10px; border: 1px solid green; background: #e8f5e8;'>";
        echo "✅ <strong>LOGIN SUCCESSFUL!</strong><br>";
        echo "User: $username<br>";
        echo "Session data: " . print_r($_SESSION, true);
        echo "</div>";
        
        echo "<p><a href='/apps/admin/'>Go to Admin Panel</a></p>";
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; background: #ffe8e8;'>";
        echo "❌ <strong>LOGIN FAILED</strong><br>";
        echo "Username: $username<br>";
        echo "Password: $password<br>";
        echo "</div>";
    }
} else {
    echo "<h3>Enter Admin Credentials</h3>";
    echo "<form method='post'>";
    echo "<p><label>Username: <input type='text' name='username' value='admin'></label></p>";
    echo "<p><label>Password: <input type='password' name='password' value='admin'></label></p>";
    echo "<p><input type='submit' value='Test Login'></p>";
    echo "</form>";
    
    echo "<h4>Try these credentials:</h4>";
    echo "<ul>";
    
    // Get admin password from environment
    $envAdminPassword = $_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD') ?: 'admin';
    echo "<li><strong>admin / $envAdminPassword</strong> (from ADMIN_PASSWORD env var)</li>";
    echo "<li>admin / admin (fallback default)</li>";
    echo "<li>admin / password123</li>";
    echo "</ul>";
}

echo "</body></html>";
?>