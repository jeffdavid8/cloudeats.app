<?php
if (!defined('MB_RUNNING')) exit;
/**
 * @var Object $icon
 * @var Object $size
 */
?>

<svg class="nav-trigger-icon" style="width: <?= $size ?>px; height: <?= $size ?>px;" viewBox="0 0 100 100" xmlns="http://w3.org">
  <!-- Outer circular background track -->
  <circle cx="50" cy="50" r="46" class="icon-bg" />

  <!-- "C" Outer ring envelope -->
  <path d="M 76,34 A 32,32 0 1,0 76,66" class="letter-c" />

  <!-- "E" Inner core element -->
  <path d="M 44,38 H 64 M 44,50 H 60 M 44,62 H 64 M 44,38 V 62" class="letter-e" />
</svg>