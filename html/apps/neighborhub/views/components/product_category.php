  <?php
  if (!defined('MB_RUNNING')) exit;
  /**
   * Generic Merchant Products Catalog & Spotlight Storefront
   * @var String $category
   * @var Object $merchant
   * @var array $products
   * @var int $spotlightProductId
   */
  //error_log(print_r($category, true));
  $categoryName = $products[0]['category_name'];

  $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($categoryName))));
  // Output: burgers-and-sandwiches
  ?>
  <a id="<?= strtolower($cleanId) ?>"></a>
  <h4 class="custom-section-title" style="margin-top: 40px; font-weight: 600;"><?= $categoryName ?></h4>
  <div class="row">
    <?php if (empty($products)): ?>
      <div class="col s12">
        <p class="">This merchant hasn't cataloged any items yet.</p>
      </div>
    <?php else: ?>
      <?
      foreach ($products as $prod):
        $meta = is_string($prod['meta']) ? json_decode($prod['meta'], true) : $prod['meta'];
        $productType = $prod['type'] ?? 'default';
        $isCustomizable = (isset($meta['form_builder']['steps']));
        // If spotlighting this item, visually dim or style slightly differently if desired
        $isSpotlit = ($prod['id'] == $spotlightProductId);
      ?>
        <div class="col s12 m6 l4">
          <div class="card product-item-card" style="border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; <?= ($merchant->status == 'online') ? 'height: auto;' : 'padding-bottom: 2rem;' ?>border: <?php echo $isSpotlit ? '2px solid #ff9800;' : 'none;'; ?>">
            <div class="card-image" style="">
              <div style="<?= (empty($prod['image_url'])) ? ' display: none;' : '' ?>">
                <?php if (!empty($prod['image_url'])): ?>
                  <img class="materialboxed" src="<?php echo htmlspecialchars($prod['image_url']); ?>" style="height: 100%; object-fit: cover;">
                <?php else: ?>
                  <div style="display:flex; justify-content:center; align-items:center; height:100%; color:#b0bec5;">
                    <i class="fas fa-box-open fa-3x"></i>
                  </div>
                <?php endif; ?>
              </div>
              <? if (!empty($prod['image_url'])): ?>
                <span class="card-title font-weight-bold" style="background: rgba(0,0,0,0.6); width: 100%; padding: 8px 15px; font-size:17px; bottom:0; display: block; position: initial;">
                  $<?php echo number_format($prod['price'], 2); ?>
                </span>
              <? endif; ?>
            </div>

            <div class="card-content" style="flex-grow: 1; padding: 15px;<?= (empty($prod['image_url'])) ? 'position: relative; padding-bottom: 4rem;' : '' ?>">
              <span class="card-title text-darken-4 font-weight-bold" style="font-size: 18px; line-height: 22px; margin-bottom: 5px; color:#232323;">

                <?php echo htmlspecialchars($prod['name']); ?>

                <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&menu_id=<?= get_var('menu_id', '1') ?>&product_id=<?= $prod['id'] ?>"><i class="material-icons">share</i></a>
              </span>
              <?php if (!empty($prod['description'])): ?>
                <p class="clamped" onclick="$(this).toggleClass('clamped')" style="cursor: pointer; font-size: 13px; line-height: 17px; overflow: hidden;">
                  <?php
                  echo nl2br($prod['description']); ?>
                </p>
              <?php endif; ?>
              <? if (empty($prod['image_url'])): ?>
                <span class="card-body-price font-weight-bold" style="width: 100%; padding: 8px 15px; font-size:17px; bottom:0; position: absolute; bottom: 0;left: 0;">
                  $<?php echo number_format($prod['price'], 2); ?>
                </span>
              <? endif; ?>

            </div>

            <? if ($merchant->status == 'online') : ?>
              <!-- Interactive Quantity + Action Control Center Pad -->
              <div class="card-action" style="border-top: 1px solid #7c7c7c; padding: 12px 15px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                  <span class="grey-text text-darken-2" style="font-size: 13px; font-weight: 600;">Quantity:</span>
                  <div class="quantity-pill">
                    <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                    <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
                    <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
                  </div>

                </div>

                <?php if ($isCustomizable): ?>
                  <button class="btn waves-effect waves-light full-width nh-customize-trigger"
                    style="width: 100%; background-color:#174a7d !important; margin-bottom: 1rem;"
                    data-id="<?php echo $prod['id']; ?>"
                    data-name="<?php echo htmlspecialchars($prod['name']); ?>"
                    data-type="<?php echo htmlspecialchars($productType); ?>"
                    data-price="<?php echo $prod['price']; ?>"
                    data-merchant-image="<?php echo $merchant->image_url; ?>"
                    data-merchant-id="<?php echo $merchant->id; ?>"
                    data-merchant-name="<?php echo $merchant->business_name; ?>"
                    data-merchant-address="<?php echo $merchant->address; ?>"
                    data-merchant-lat="<?php echo $merchant->latitude; ?>"
                    data-merchant-lon="<?php echo $merchant->longitude; ?>">
                    Customize <i class="fas fa-sliders-h right"></i>
                  </button>
                <?php endif; ?>
                <button class="btn waves-effect waves-light full-width nh-add-standard-btn"
                  style="width: 100%; background-color:#3d7329 !important;"
                  data-id="<?php echo $prod['id']; ?>"
                  data-merchant-id="<?php echo $merchant->id; ?>"
                  data-name="<?php echo htmlspecialchars($prod['name']); ?>"
                  data-price="<?php echo $prod['price']; ?>"
                  data-merchant-image="<?php echo $merchant->image_url; ?>"
                  data-merchant-name="<?php echo $merchant->business_name; ?>"
                  data-merchant-address="<?php echo $merchant->address; ?>"
                  data-merchant-lat="<?php echo $merchant->latitude; ?>"
                  data-merchant-lon="<?php echo $merchant->longitude; ?>">
                  Add to Order <i class="fas fa-shopping-basket right"></i>
                </button>
              </div>
            <? endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>