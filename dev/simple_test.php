<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Session Test</title>
</head>
<body>
    <h1>Simple Session Test Page</h1>
    <p>Current time: <?= date('Y-m-d H:i:s') ?></p>
    <p>Session status: <?= session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Not Active' ?></p>
    <p>Session ID: <?= session_id() ?></p>
    <?php if (isset($_SESSION['user'])): ?>
        <p>User: <?= is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'] ?></p>
    <?php else: ?>
        <p>No user in session</p>
    <?php endif; ?>
    
    <h3>All Session Data:</h3>
    <pre><?= var_export($_SESSION, true) ?></pre>
</body>
</html>