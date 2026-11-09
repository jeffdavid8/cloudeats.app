<?php
if (!defined('MB_RUNNING')) exit;

$menus = $this->get('menus');
$activeMenuId = $activeMenuId ?? get_var('menu_id', !empty($menus) ? array_keys($menus)[0] : 0);
?>
<div class="col s12 m4 l3 sticky-column hide-on-small-only">
  <div class="card secondary-category-menu">
    <div class="menu-title"><i class="fas fa-list-ul"></i> Menu Categories</div>
    <ul>
      <?php foreach (($menus[$activeMenuId]['categories'] ?? []) as $category):
        $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
      ?>
        <li><a class="category-menu-item" data-category-anchor="<?= htmlspecialchars($cleanId, ENT_QUOTES, 'UTF-8') ?>" href="#!"><?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
