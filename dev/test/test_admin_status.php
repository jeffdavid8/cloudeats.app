<?php
/**
 * Check current admin user status
 */

session_start();

echo "Session data:\n";
print_r($_SESSION);

if (isset($_SESSION['admin_user'])) {
    echo "\nAdmin user: " . $_SESSION['admin_user'] . "\n";
    
    require_once '/var/www/html/html/apps/admin/includes/UserManager.php';
    $userManager = new UserManager();
    $user = $userManager->getUser($_SESSION['admin_user']);
    
    echo "User data:\n";
    print_r($user);
    
    echo "\nIs admin: " . (($user && ($user['role'] === 'admin' || $user['is_admin'] === true)) ? 'YES' : 'NO') . "\n";
} else {
    echo "No admin user in session\n";
}
?>