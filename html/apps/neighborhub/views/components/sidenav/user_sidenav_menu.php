<?php
if (!defined('MB_RUNNING')) exit;
?>

<?php if ($isLoggedIn): ?>
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