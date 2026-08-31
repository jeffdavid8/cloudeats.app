<?php
$structure = App::getInstance()->structure();
// Initialize permission checking
$permissionsMatrix = null;
$currentUser = null;

// Session should already be started by index.php


// Get current user using unified session format
if (isset($_SESSION['user'])) {
  if (is_array($_SESSION['user'])) {
    $currentUser = $_SESSION['user']['username'];
  } else {
    $currentUser = $_SESSION['user'];
  }
} else {
  // Use anonymous user for permission checking
  $currentUser = 'anonymous';
}

// Always initialize permissions matrix for consistent permission checking
try {

  $permissionsMatrix = new PermissionsMatrix();
} catch (Exception $e) {
  // Silently handle if permissions system not available
  $permissionsMatrix = null;
}

$userApps = $permissionsMatrix->getUserApps($currentUser);

?>
  <?php
  $row_limit = 3;
  foreach ($userApps as $key => $app) {
  ?>
    <a href="<?= $app['href'] ?>" class="application-card">
      <div class="icon"><?= $app['app_icon'] ?></div>
      <div class="title"><?= $app['title'] ?></div>
    </a>
  <?
  }
  ?>
