<?php
if (!defined('MB_RUNNING')) exit;

$menus = $this->get('menus');
$activeMenuId = $activeMenuId ?? get_var('menu_id', !empty($menus) ? array_keys($menus)[0] : 0);
?>
<div class="merchant-menu-dropdown" style="position: fixed; top: 4rem; height: 5rem; width: 100%; z-index: 899;">
  <div class="container">
    <div class="right">
      <div class="top-right-dropdown-wrapper">
        <a class="dropdown-trigger hover-grow btn-floating btn-large waves-effect waves-light" href="#" data-target="top-right-menu">
          <i class="fas fa-utensils"></i>
        </a>
        <ul id="top-right-menu" class="dropdown-content">
          <li class="category-menu-item-back-to-top"><a class="category-menu-item" data-category-anchor="top" href="#!">Back to Top</a></li>
          <?php foreach (($menus[$activeMenuId]['categories'] ?? []) as $category):
            $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
          ?>
            <li><a class="category-menu-item" data-category-anchor="<?= htmlspecialchars($cleanId, ENT_QUOTES, 'UTF-8') ?>" href="#!"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>
