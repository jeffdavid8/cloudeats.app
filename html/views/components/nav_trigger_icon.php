<?php
if (!defined('MB_RUNNING')) exit;
/**
 * @var Object $icon
 * @var Object $size
 */
?>

<svg class="nav-trigger-favicon" style="width: <?= $size ?>px; height: <?= $size ?>px;" viewBox="0 0 100 100" xmlns="http://w3.org">
  <!-- Solid backing circle for high-contrast visibility against tab colors -->
  <circle cx="50" cy="50" r="46" class="fav-bg" />
  
  <!-- Unified 'C' and 'E' single-path construction -->
  <path d="M 72,26 
           H 44 
           A 24,24 0 0,0 44,74 
           H 72 
           M 44,50 
           H 66" 
        class="fav-letters" />
</svg>
