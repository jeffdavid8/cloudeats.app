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
   require_once(__DIR__ . '/../../../apps/admin/includes/PermissionsMatrix.php');
   $permissionsMatrix = new PermissionsMatrix();
} catch (Exception $e) {
   // Silently handle if permissions system not available
   $permissionsMatrix = null;
}

$userApps = $permissionsMatrix->getUserApps($currentUser);

?>
<ul class="collapsible collapsible-accordion">
   <li>
      <a class="collapsible-header waves-effect waves-light">Public Apps <i class="fas fa-rocket"></i></a>
      <div class="collapsible-body">
         <ul>
            <?php
            $row_limit = 3;
            foreach ($userApps as $key => $app) {
               if (strtolower($key) == 'help' || strtolower($key) == 'admin') {
                  continue; // Skip admin and help apps
               }

            ?>
               <li>
                  <?= '<a target="_blank" href="' . $app['href'] . '">' . $app['icon'] . $app['title'] . '</a>' ?>
               </li>
            <?
            }
            ?>
         </ul>
      </div>
   </li>
</ul>