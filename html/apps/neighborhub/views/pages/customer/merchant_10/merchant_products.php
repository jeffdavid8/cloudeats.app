<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Generic Merchant Products Catalog & Spotlight Storefront
 * @var Object $customer
 * @var Object $merchant
 * @var array $products
 * @var int $spotlightProductId
 */
$customer = $this->get('customer');
$merchant = $this->get('merchant');
$menus = $this->get('menus');
$products = $this->get('products');
$activeMenuId = get_var('menu_id', array_keys($menus)[0]);
$menuProductsByCategory = Menu::getProductsGroupedByCategory($activeMenuId);

//error_log(print_r($menus, true));
// Isolate the Spotlight Product ID parameter from the URL query context if present
$spotlightProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$spotlightProduct = null;

if ($spotlightProductId) {
  foreach ($products as $prod) {
    if (intval($prod['id']) === $spotlightProductId) {
      $spotlightProduct = $prod;
      break;
    }
  }
}

?>
<style>

</style>

<div class="ivanhoes-storefront">
  <div class="container">
    <!-- Ivanhoe's welcome panel: merchant name and business details are data-driven. -->
    <section class="iv-hero" aria-label="Welcome to the diner">
      <div class="iv-kicker">A little hometown happiness</div>
      <h2><?= htmlspecialchars($merchant->business_name, ENT_QUOTES, 'UTF-8') ?></h2>
      <p>Good food, sweet treats, and the kind of hometown stop that makes a day better. Browse the menu and find a new favorite.</p>
      <div class="iv-starline" aria-hidden="true">★ ★ ★ ★ ★</div>
      <!-- Product Search & Filter Bar -->
      <!-- Product Search & Filter Bar -->
      <div class="row" style="margin-bottom: 20px;">
        <div class="col s12 m8 offset-m2">
          <div class="card-panel" style="padding: 12px 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
            <i class="fas fa-search" style="color: var(--brand-text-muted);"></i>
            <input type="search" id="iv-product-search-input" placeholder="Search menu items..." aria-label="Search products" style="border: none; box-shadow: none; margin: 0; height: 35px; font-size: 15px; width: 100%;">
          </div>

          <!-- Search Results Counter & Filter Chips Row -->
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 0 4px;">
            <? /*
            <div class="iv-product-filters" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;" aria-label="Filter products by tag">
              <button type="button" class="btn-small waves-effect waves-light iv-filter-chip is-active" data-filter="all" style="border-radius: 20px; background-color: var(--brand-caramel);">All Items</button>
              <button type="button" class="btn-small waves-effect waves-light iv-filter-chip grey lighten-3 grey-text text-darken-3" data-filter="customizable" style="border-radius: 20px;">Customizable</button>
            </div>
            */ ?>
            <!-- Result Count Indicator -->
            <span id="iv-result-count" class="sweets-text-muted" style="font-size: 13px; font-weight: 600;" aria-live="polite"></span>
          </div>
        </div>
      </div>
    </section>

    <div class="card horizontal sweets-card" style="border-top:5px solid var(--brand-caramel);">
      <div class="card-stacked">
        <div class="card-content" style="padding:24px 28px;">

          <p class="iv-menu-intro">Come on in · Browse the menu</p>
          <div class="row" style="margin-bottom:0;">
            <div class="col s12 m6">
              <?php if (!empty($merchant->address) || !empty($merchant->city)): ?>
                <?php $map_link = "https://www.google.com/maps/search/?api=1&query=" . urlencode(trim($merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code, ' ,')); ?>
                <a href="<?= htmlspecialchars($map_link, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-flat iv-contact-link"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars(trim($merchant->address . ', ' . $merchant->city . ', ' . $merchant->state . ' ' . $merchant->zip_code, ' ,'), ENT_QUOTES, 'UTF-8') ?></span></a>
              <?php endif; ?>
              <?php if (!empty($merchant->phone)): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $merchant->phone), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-flat iv-contact-link"><i class="fas fa-phone"></i><span><?= htmlspecialchars($merchant->phone, ENT_QUOTES, 'UTF-8') ?></span></a>
              <?php endif; ?>
              <?php if (!empty($merchant->website)): ?>
                <a href="<?= htmlspecialchars($merchant->website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-flat iv-contact-link"><i class="fas fa-globe"></i><span>Visit Website</span></a>
              <?php endif; ?>
            </div>
            <div class="col s12 m6">
              <?php if (!empty($merchant->store_hours)): ?>
                <div class="iv-contact-link"><i class="fas fa-clock"></i><span><?= nl2br(htmlspecialchars($merchant->store_hours, ENT_QUOTES, 'UTF-8')) ?></span></div>
              <?php endif; ?>
              <p class="sweets-text-muted" style="font-size:13px;line-height:1.7;margin-top:8px;">Pick something delicious below. Your selections will be added to your CloudEats order.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Spotlight Product Panel (Featured Menu Item) -->
    <?php if ($spotlightProduct):
      $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
      $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
    ?>
      <div id="nh-spotlight-callout-panel" class="card z-depth-2 animate-fade-in sweets-card" style="border: 2px solid var(--brand-gold); overflow: hidden; margin-top: 30px;">
        <div class="white-text" style="background: linear-gradient(135deg, var(--brand-caramel), var(--brand-gold)); padding: 12px 20px; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; justify-content: space-between;">
          <span><i class="fas fa-star" style="color: #FFF; margin-right: 6px;"></i> Diner Favorite Spotlight</span>
        </div>
        <div class="card-content row" style="margin-bottom: 0; padding: 24px;">
          <div class="col s12 m4 center-align">
            <?php if (!empty($spotlightProduct['image_url'])): ?>
              <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 8px; max-height: 220px; object-fit: cover; width:100%; border: 4px solid var(--brand-bg);">
            <?php else: ?>
              <div class="grey lighten-4 grey-text" style="height: 180px; display: flex; justify-content: center; align-items: center; border-radius: 8px;">
                <i class="fas fa-ice-cream fa-3x" style="color: var(--brand-text-muted);"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="col s12 m8">
            <h4 class="sweets-text-brown" style="margin: 0 0 8px 0; font-weight: 900;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
              <a class="page_link waves-effect waves-light" style="color: var(--brand-caramel);" href="<?= htmlspecialchars($this->config['base_url'], ENT_QUOTES, 'UTF-8') ?>/?app=neighborhub&amp;view=customer&amp;p=merchant_products&amp;merchant_id=<?= rawurlencode((string)$merchant->id) ?><?= (get_var('menu_id', false)) ? '&amp;menu_id=' . rawurlencode((string)get_var('menu_id')) : '' ?>&amp;product_id=<?= rawurlencode((string)$spotlightProduct['id']) ?>"><i class="material-icons">share</i></a>
            </h4>

            <h5 style="color: var(--brand-caramel); margin: 0 0 12px 0; font-weight: 800;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
            <p class="sweets-text-muted" style="font-size: 15px; line-height: 1.6; margin-bottom: 24px; font-weight: 500;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

            <? if ($merchant->status == 'online') : ?>
              <div class="card-action" style="display: flex; flex-direction: column; align-items: flex-start; justify-content: space-between; margin-bottom: 10px; padding-left: 0; padding-right: 0;">


                <div class="quantity-pill" style="margin-right: 10px;margin-bottom: 2rem;">
                  <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                  <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
                  <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
                </div>

                <div class="card-button-container" style="display: flex; gap: 10px;">
                  <?php if ($isSpotCustom): ?>
                    <button class="btn btn-large waves-effect waves-light full-width nh-customize-trigger btn-custom-customize"
                      style="width: 100%; background-color:#174a7d !important; margin-bottom: 1rem;"
                      data-id="<?php echo $spotlightProduct['id']; ?>"
                      data-merchant-id="<?php echo $merchant->id; ?>"
                      data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
                      data-merchant-address="<?php echo $merchant->address . ', ' . htmlspecialchars($merchant->city) . ', ' . htmlspecialchars($merchant->state) . ' ' . htmlspecialchars($merchant->zip_code); ?>"
                      data-merchant-lat="<?php echo $merchant->latitude; ?>"
                      data-merchant-lon="<?php echo $merchant->longitude; ?>"
                      data-type="<?php echo htmlspecialchars($spotlightProduct['type']); ?>"
                      data-customizable="<?= $isSpotCustom ? '1' : '0' ?>"
                      data-price="<?php echo $spotlightProduct['price']; ?>">
                      Customize <i class="fas fa-sliders-h right"></i>
                    </button>
                  <?php endif; ?>

                  <button class="btn btn-large waves-effect waves-light full-width nh-add-standard-btn btn-custom-add"
                    style="width: 100%; background-color:#3d7329 !important;"
                    data-id="<?php echo $spotlightProduct['id']; ?>"
                    data-merchant-id="<?php echo $merchant->id; ?>"
                    data-merchant-address="<?php echo $merchant->address . ', ' . htmlspecialchars($merchant->city) . ', ' . htmlspecialchars($merchant->state) . ' ' . htmlspecialchars($merchant->zip_code); ?>"
                    data-merchant-lat="<?php echo $merchant->latitude; ?>"
                    data-merchant-lon="<?php echo $merchant->longitude; ?>"
                    data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
                    data-customizable="<?= $isSpotCustom ? '1' : '0' ?>"
                    data-price="<?php echo $spotlightProduct['price']; ?>">
                    Add to Order <i class="fas fa-shopping-basket right"></i>
                  </button>
                </div>
              </div>
            <? endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col s12 m4 l3 sticky-column hide-on-small-only">
        <div class="card secondary-category-menu">
          <div class="menu-title"><i class="fas fa-list-ul"></i> Menu Categories</div>
          <ul>
            <?php foreach ($menus[$activeMenuId]['categories'] as $category):
              $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
            ?>
              <li><a class="category-menu-item" data-category-anchor="<?= $cleanId ?>" href="#!"><?= htmlspecialchars($category['name']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="col s12 m8 l9">
        <!-- Product Catalog Categories -->
        <?php foreach ($menuProductsByCategory as $categoryId => $catProducts) : ?>
          <?php
          render('pages/customer/merchant_' . $merchant->id . '/ivanhoes_product_category.php', array(
            'merchant' => $merchant,
            'products' => $catProducts,
            'spotlightProductId' => $spotlightProductId,
          )); ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Modal framework styling for the Ivanhoe diner theme -->
    <div id="nh-custom-builder-modal" class="modal mb-modal-fixed sweets-card" style="border: 3px solid var(--brand-gold); max-width: 800px; border-radius: 8px !important;">

      <div class="modal-header" style="max-width: 800px; padding: 24px 24px 10px; background-color: var(--brand-bg); border-bottom: 2px dashed var(--brand-border);">
        <h4 id="builder-modal-title" class="sweets-text-brown" style="font-weight: 900; margin-top: 0; text-transform: uppercase;">Customize Your Order</h4>
        <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row; margin-bottom: 10px;">
          <span class="sweets-text-brown" style="font-size: 14px; font-weight: 700; margin-right: 8px;">Qty: </span>
          <div class="quantity-pill">
            <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
            <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
            <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
          </div>
        </div>
      </div>
      <div class="modal-content" style="padding: 24px; background-color: var(--brand-card-bg);">
        <p class="sweets-text-muted" style="margin-bottom: 20px; font-weight: 500;">Choose your options below to make this item your own.</p>
        <div id="builder-widget-mount-viewport"></div>
      </div>

      <div class="row" style="width:100%; margin: 0; padding: 0 24px 15px; background-color: var(--brand-card-bg);">
        <div class="input-field col s12" style="margin-top: 10px; margin-bottom: 0;">
          <i class="fas fa-edit prefix pk-icon" style="top: 0.8rem; font-size: 1.2rem;"></i>
          <textarea id="builder-customer-notes"
            class="materialize-textarea nh-card-customer-notes-input pk-text-main"
            data-length="200"
            maxlength="200"
            placeholder="E.g., Dressing on the side, well done, extra crispy..."
            style="min-height: 45px; padding-bottom: 0; margin-bottom: 5px;"></textarea>
          <label for="builder-customer-notes" class="active" style="color: var(--brand-gold); font-weight: 600;">Special Instructions / Customer Notes</label>
          <span class="helper-text pk-text-muted" style="font-size: 11px;">Add any custom requests or prep preferences for the kitchen.</span>
        </div>
      </div>

      <div class="modal-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 24px; background-color: var(--brand-bg); border-top: 2px dashed var(--brand-border);">
        <div>
          <h5 style="margin:0; font-size: 13px; color: var(--brand-text-muted); font-weight: 700; text-transform: uppercase;">Order Item Total:</h5>
          <span class="live-builder-total" style="font-weight: 900; font-size: 26px; color: var(--brand-caramel);">$0.00</span>
        </div>
        <div>
          <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light btn-custom-customize" style="font-weight:700; height: 45px; padding: 0 24px; background-color:#3d7329 !important;">
            Add To Order <i class="fas fa-plus right"></i>
          </button>
          <a href="#!" class="modal-close waves-effect btn-flat sweets-text-brown" style="margin-right: 12px; font-weight: 700; text-transform: uppercase;">Cancel</a>
        </div>
      </div>
    </div>

    <!-- Category Floating Dropdown Anchor -->
    <div style="position: fixed; top: 4rem; height: 5rem; width: 100%; z-index: 899; pointer-events: none;">
      <div class="container" style="pointer-events: auto;">
        <div class="right">
          <div class="top-right-dropdown-wrapper">
            <a class="dropdown-trigger hover-grow btn-floating btn-large waves-effect waves-light" href="#" data-target="top-right-menu">
              <i class="fas fa-utensils"></i>
            </a>

            <!-- Dropdown Categories -->
            <ul id="top-right-menu" class="dropdown-content">
              <li class="category-menu-item-back-to-top"><a class="category-menu-item" data-category-anchor="top" href="#!">Back to Top</a></li>
              <?php foreach ($menus[$activeMenuId]['categories'] as $category):
                $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
              ?>
                <li><a class="category-menu-item" data-category-anchor="<?= $cleanId ?>" href="#!"><?= htmlspecialchars($category['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /.container -->
</div><!-- /.ivanhoes-storefront -->

<script>
  $(document).ready(function() {
    window.merchant = <?= json_encode($merchant) ?>;
    // Initialize standard merchant storefront interactions
    window.storefront = new MerchantStorefront({
      toastIcon: 'fa-check-circle', // Customize icon per theme if desired (e.g. 'fa-fire-alt')
      toastVerb: 'order' // Customize text per theme if desired (e.g. 'order')
    });

    const $searchInput = $('#iv-product-search-input');
    const $productCards = $('.iv-product-grid .col');
    const $resultCountSpan = $('#iv-result-count');
    const totalProducts = $productCards.length;

    function filterProducts() {
      const query = $searchInput.val().toLowerCase().trim();
      const activeFilter = $('.iv-filter-chip.is-active').data('filter') || 'all';
      let visibleCount = 0;

      $productCards.each(function() {
        const $card = $(this);
        const name = $card.find('.iv-product-name span').text().toLowerCase();
        const desc = $card.find('.iv-product-description').text().toLowerCase();
        const isCustomizable = $card.find('.nh-customize-trigger').length > 0;

        let matchesSearch = query === '' || name.includes(query) || desc.includes(query);
        let matchesFilter = true;

        if (activeFilter === 'customizable') {
          matchesFilter = isCustomizable;
        }

        if (matchesSearch && matchesFilter) {
          $card.show();
          visibleCount++;
        } else {
          $card.hide();
        }
      });

      // Update result count text indicator
      if (query !== '' || activeFilter !== 'all') {
        $resultCountSpan.text(`Showing ${visibleCount} of ${totalProducts} items`);
      } else {
        $resultCountSpan.text(`${totalProducts} items available`);
      }
    }

    // Initialize counter text on load
    $resultCountSpan.text(`${totalProducts} items available`);

    // Bind events
    $searchInput.on('input', filterProducts);

    $('.iv-filter-chip').on('click', function() {
      $('.iv-filter-chip').removeClass('is-active').addClass('grey lighten-3 grey-text text-darken-3').css('background-color', '');
      $(this).addClass('is-active').removeClass('grey lighten-3 grey-text text-darken-3').css('background-color', 'var(--brand-caramel)');
      filterProducts();
    });
  });
</script>