<?php
// DEPRECATED: This file uses the old AdminAuth system
echo "⚠️ DEPRECATED: This test file is deprecated. Use the unified authentication system instead.";
echo "<br><a href='/?p=login'>Go to main login</a>";
exit();

// Test admin authentication - DISABLED
require_once __DIR__ . '/apps/admin/includes/AdminAuth.php';

session_start();

echo "<h2>Admin Authentication Test</h2>";

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<p>Attempting login with username: '$username' and password: '$password'</p>";
    
    $auth = new AdminAuth();
    $result = $auth->authenticate($username, $password);
    
    echo "<p>Authentication result: " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";
    
    if ($result) {
        echo "<p>Session admin_user: " . ($_SESSION['admin_user'] ?? 'NOT SET') . "</p>";
        echo "<p>Session admin_username: " . ($_SESSION['admin_username'] ?? 'NOT SET') . "</p>";
    }
    
    echo "<hr>";
}

echo "<p>Current session admin_user: " . ($_SESSION['admin_user'] ?? 'NOT SET') . "</p>";
echo "<p>Is logged in: " . (isset($_SESSION['admin_user']) ? 'YES' : 'NO') . "</p>";

?>

<form method="post">
    <p>
        <label>Username: <input type="text" name="username" value="admin"></label>
    </p>
    <p>
        <label>Password: <input type="password" name="password" value="<?php echo $_ENV['ADMIN_PASSWORD'] ?? 'admin'; ?>"></label>
    </p>
    <p>
        <button type="submit">Test Login</button>
    </p>
</form>

<p><a href="?app=admin">Go to Admin App</a></p>