<?
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
<div class="spec-section">
  <h3>📈 PROJECT PORTFOLIO</h3>
  <div class="portfolio-links">

    <?
    foreach ($userApps as $key => $app) {
    ?>
      <a href="<?= $app['href'] ?>" data-announcement-title="<?= $app['announcement-title'] ? $app['announcement-title'] : $app['title'] ?>" target="_blank" class="portfolio-link">
        <?= $app['app_icon'] ?> <?= $app['title'] ?> <?= (!empty($app['description']) ? '<small> - ' . $app['description'] . '</small>' : '') ?>
      </a>
    <?
    }

    ?>

  </div>
</div>