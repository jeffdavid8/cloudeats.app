<?php
if (!defined('MB_RUNNING')) exit;
/** Ivanhoe's diner-themed product category. Uses the same cart/customizer hooks as the shared component. */
$merchant = $merchant ?? null;
$products = $products ?? [];
$spotlightProductId = $spotlightProductId ?? null;
$categoryName = !empty($products) ? ($products[0]['category_name'] ?? 'Menu') : ($category['name'] ?? 'Menu');
$cleanId = preg_replace('/-+/', '-', trim(preg_replace('/[^a-z0-9]+/', '-', str_replace('&', 'and', strtolower($categoryName))), '-'));
$e = static function ($value) {
  return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$merchantAddress = trim(($merchant->address ?? '') . ', ' . ($merchant->city ?? '') . ', ' . ($merchant->state ?? '') . ' ' . ($merchant->zip_code ?? ''), ' ,');
?>
<section class="iv-product-category" id="<?= $e($cleanId) ?>">
  <h4 class="iv-section-title"><?= $e($categoryName) ?></h4>
  <?php if (empty($products)): ?>
    <div class="card sweets-card">
      <div class="card-content">
        <p>This menu category doesn't have any items listed yet.</p>
      </div>
    </div>
  <?php else: ?>
    <div class="row iv-product-grid">
      <?php foreach ($products as $prod):
        $meta = is_string($prod['meta'] ?? null) ? json_decode($prod['meta'], true) : ($prod['meta'] ?? []);
        if (!is_array($meta)) $meta = [];
        $productType = $prod['type'] ?? 'default';
        $isCustomizable = !empty($meta['form_builder']['steps']);
        $isSpotlit = ((string)($prod['id'] ?? '') === (string)$spotlightProductId);
        $price = (float)($prod['price'] ?? 0);
        $name = $prod['name'] ?? 'Menu item';
        $image = $prod['image_url'] ?? '';
        $merchantImage = $merchant->image_url ?? '';
        $merchantId = $merchant->id ?? '';
        $merchantName = $merchant->business_name ?? '';
        $merchantLat = $merchant->latitude ?? '';
        $merchantLon = $merchant->longitude ?? '';
      ?>
        <div class="col s12 m6 l4" style="display:flex;">
          <article class="card product-item-card" style="width:100%;<?= $isSpotlit ? 'outline:3px solid var(--brand-gold);outline-offset:-3px;' : '' ?><?= (($merchant->status ?? '') === 'online') ? '' : 'padding-bottom:1rem;' ?>">
            <?php if (!empty($image)): ?>
              <div class="iv-product-image">
                <img class="materialboxed" src="<?= $e($image) ?>" alt="<?= $e($name) ?>" loading="lazy">
                <span class="iv-price-ribbon">$<?= number_format($price, 2) ?></span>
              </div>
            <?php else: ?>
              <div class="iv-product-image" style="display:flex;align-items:center;justify-content:center;"><i class="fas fa-ice-cream fa-3x" style="color:#177e83;opacity:.7;" aria-hidden="true"></i><span class="iv-price-ribbon">$<?= number_format($price, 2) ?></span></div>
            <?php endif; ?>
            <div class="card-content iv-product-body">
              <h5 class="iv-product-name"><span><?= $e($name) ?></span>
                <a class="page_link waves-effect waves-light" aria-label="Share <?= $e($name) ?>" href="<?= $e($this->config['base_url']) ?>/?app=neighborhub&amp;view=customer&amp;p=merchant_products&amp;merchant_id=<?= rawurlencode((string)$merchantId) ?><?= (get_var('menu_id', false)) ? '&amp;menu_id='. rawurlencode((string)get_var('menu_id')) : '' ?>&amp;product_id=<?= rawurlencode((string)($prod['id'] ?? '')) ?>"><i class="material-icons">share</i></a>
              </h5>
              <?php if (!empty($prod['description'])): ?>
                <p class="iv-product-description clamped" onclick="$(this).toggleClass('clamped')" title="Tap to expand description" style="cursor:pointer;"><?= nl2br($e($prod['description'])) ?></p>
              <?php endif; ?>
            </div>
            <?php if (($merchant->status ?? '') === 'online'): ?>
              <div class="iv-card-action">
                <div class="iv-quantity-row"><span class="iv-quantity-label">Quantity</span>
                  <div class="quantity-pill">
                    <button type="button" class="btn-flat nh-card-qty-minus" aria-label="Decrease quantity" style="padding:0 7px;height:28px;line-height:28px;"><i class="fas fa-minus fa-xs"></i></button>
                    <input type="number" class="nh-card-qty-input" value="1" min="1" step="1" aria-label="Quantity for <?= $e($name) ?>" style="width:36px;text-align:center;margin:0;border:0;height:28px;font-weight:800;-webkit-appearance:none;-moz-appearance:textfield;">
                    <button type="button" class="btn-flat nh-card-qty-plus" aria-label="Increase quantity" style="padding:0 7px;height:28px;line-height:28px;"><i class="fas fa-plus fa-xs"></i></button>
                  </div>
                </div>
                <?php if ($isCustomizable): ?>
                  <button type="button" class="btn waves-effect waves-light iv-action-btn nh-customize-trigger btn-custom-customize"
                    data-id="<?= $e($prod['id'] ?? '') ?>" data-name="<?= $e($name) ?>" data-type="<?= $e($productType) ?>" data-price="<?= $e($prod['price'] ?? 0) ?>"
                    data-merchant-image="<?= $e($merchantImage) ?>" data-merchant-id="<?= $e($merchantId) ?>" data-customizable="1"
                    data-merchant-name="<?= $e($merchantName) ?>" data-merchant-address="<?= $e($merchantAddress) ?>" data-merchant-lat="<?= $e($merchantLat) ?>" data-merchant-lon="<?= $e($merchantLon) ?>">
                    Customize <i class="fas fa-sliders-h right"></i>
                  </button>
                <?php endif; ?>
                <button type="button" class="btn waves-effect waves-light iv-action-btn nh-add-standard-btn btn-custom-add"
                  data-id="<?= $e($prod['id'] ?? '') ?>" data-merchant-id="<?= $e($merchantId) ?>" data-name="<?= $e($name) ?>" data-price="<?= $e($prod['price'] ?? 0) ?>"
                  data-customizable="<?= $isCustomizable ? '1' : '0' ?>" data-merchant-image="<?= $e($merchantImage) ?>" data-merchant-name="<?= $e($merchantName) ?>"
                  data-merchant-address="<?= $e($merchantAddress) ?>" data-merchant-lat="<?= $e($merchantLat) ?>" data-merchant-lon="<?= $e($merchantLon) ?>">
                  Add to Order <i class="fas fa-shopping-basket right"></i>
                </button>
              </div>
            <?php endif; ?>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>