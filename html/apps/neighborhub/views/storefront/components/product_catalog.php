<?php
if (!defined('MB_RUNNING')) exit;

$menuProductsByCategory = $menuProductsByCategory ?? [];
$spotlightProductId = $spotlightProductId ?? null;
$merchant = $this->get('merchant');
?>
<div class="col s12 m8 l9">
  <?php foreach ($menuProductsByCategory as $catProducts): ?>
    <?php render('components/product_category.php', [
      'merchant' => $merchant,
      'products' => $catProducts,
      'spotlightProductId' => $spotlightProductId,
    ]); ?>
  <?php endforeach; ?>
</div>
