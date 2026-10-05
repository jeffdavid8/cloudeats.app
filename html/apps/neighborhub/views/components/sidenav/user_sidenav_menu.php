<?php
if (!defined('MB_RUNNING')) exit;
?>
<?
$isLoggedIn = false;
$username = '';
$isAdmin = false;

try {
    $isLoggedIn = isset($_SESSION['user']) &&
        (is_array($_SESSION['user']) ? !empty($_SESSION['user']['username']) : !empty($_SESSION['user']));

    if ($isLoggedIn) {
        if (is_array($_SESSION['user'])) {
            $username = $_SESSION['user']['username'] ?? 'User';
        } else {
            $username = $_SESSION['user'];
        }

        // Check admin status using unified system
        $isAdmin = AuthManager::isAdmin();
    }
} catch (Exception $e) {
    // Silently handle any errors
    $isLoggedIn = false;
    $username = '';
    $isAdmin = false;
}
?>
<?php if ($isLoggedIn): ?>
        <? // if user is admin, show link to admin /?app=admin 
        if (App::getInstance()->user->is_admin): ?>
            <li>
                <a href="?app=admin">
                    <i class="material-icons">settings</i>
                    Admin
                </a>
            </li>
        <?
        endif;
        ?>
        <li>
            <a href="#" class="logout-btn" onclick="mb.userLogout(); return false;">
                <i class="material-icons">exit_to_app</i>
                Logout (<?php echo htmlspecialchars($username); ?>)
            </a>
        </li>
<?php else: ?>
    <li>
        <a href="?p=login&return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
            <i class="material-icons">account_circle</i>
            Login
        </a>

    </li>
<?php endif; ?>