<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Generic Merchant Products Catalog & Spotlight Storefront
 * Custom Demo Theme: Kammy's Kafe (Jonesboro, IN)
 * @var Object $customer
 * @var Object $merchant
 * @var array $products
 * @var int $spotlightProductId
 */
$customer = $this->get('customer');
$merchant = $this->get('merchant');
$menus = $this->get('menus');
$products = $this->get('products');

$current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$params = $_GET;

// Default Active Menu Context (Kammy's Kafe standard single catalog menu)
$activeMenuId = get_var('menu_id', !empty($menus) ? array_keys($menus)[0] : 0);
$menuProductsByCategory = Menu::getProductsGroupedByCategory($activeMenuId);
$menuCategories = Menu::getCategoriesByMenuId($activeMenuId);
//error_log(print_r($menuCategories, true));
$menu_meta = array(
  1 => array(
    'logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/kammys/kammys-cafe-logo.png',
    'text-logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/kammys/kammys-cafe-logo.png',
  ),
);

// Isolate the Spotlight Product ID parameter from URL query context if present
$spotlightProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$spotlightProduct = null;

if ($spotlightProductId && isset($menus[$activeMenuId]['categories'])) {
  foreach ($menus[$activeMenuId]['categories'] as $category) {
    foreach ($category['products'] as $product) {
      if (intval($product['id']) === $spotlightProductId) {
        $spotlightProduct = $product;
        break;
      }
    }
  }
}
?>

<? render('pages/customer/merchant_1/merchant_css.php'); ?>

<!-- Hero Banner Container -->
<div class="kc-hero-banner">
  <div style="text-align: center; width: 100%;">
    <? /*
    <!-- Merchant Logo -->
    <img class="merchant-logo-image" src="<?= isset($menu_meta[$activeMenuId]['text-logo-image']) ? $menu_meta[$activeMenuId]['text-logo-image'] : '' ?>" alt="Kammy's Kafe" />
    */ ?>

    <!-- Text Overlay Pill -->
    <div class="hero-text-overlay-pill white-text" style="border-radius: 25px; background-color: rgba(0, 0, 0, 0.65); margin: 1.5rem auto 0 auto; padding: 1rem 0;">
      <h3 class="lily-script-one-regular" style="margin: 0 0 6px 0; font-size: 2rem; font-weight: 900; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
        <?php echo htmlspecialchars($merchant->business_name); ?>
      </h3>
      <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--brand-gold); font-weight: 800;">
        ★ Home-Cooked Goodness & Fresh Coffee ★
      </div>
    </div>
  </div>
</div>

<div class="container">
  <!-- Merchant Info Card -->
  <div class="merchant-info-card card horizontal kk-card" style="margin-top: -1.5rem; border-top: 4px solid var(--brand-gold);">
    <div class="card-stacked">
      <div class="card-content" style="padding: 20px 24px;">
        <div class="row" style="margin-bottom: 0;">
          <div class="col s12 m6">
            <!-- Address -->
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="kk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                <i class="fas fa-map-marker-alt"></i>
              </span>
              <span class="kk-text-main" style="word-break: break-word; font-weight: 600;"><?php echo htmlspecialchars($merchant->address); ?></span>
            </p>

            <!-- Phone -->
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="kk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                <i class="fas fa-phone"></i>
              </span>
              <span class="kk-text-main" style="font-weight: 600;"><?php echo htmlspecialchars($merchant->phone); ?></span>
            </p>

            <!-- Facebook -->
            <?php if (!empty($merchant->facebook)): ?>
              <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                <span class="kk-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
                  <i class="fab fa-facebook text-teal"></i>
                </span>
                <a href="<?php echo htmlspecialchars($merchant->facebook); ?>" target="_blank" rel="noopener noreferrer" style="word-break: break-all;">
                  <?php echo htmlspecialchars($merchant->facebook); ?>
                </a>
              </p>
            <?php endif; ?>

            <!-- Website -->
            <?php if (!empty($merchant->website)): ?>
              <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                <span class="kk-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
                  <i class="fas fa-globe text-teal"></i>
                </span>
                <a href="<?php echo htmlspecialchars($merchant->website); ?>" target="_blank" rel="noopener noreferrer">
                  <?php echo htmlspecialchars($merchant->website); ?>
                </a>
              </p>
            <?php endif; ?>


          </div>

          <div class="col s12 m6">
            <!-- Store Hours -->
            <?php if (!empty($merchant->store_hours)): ?>
              <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                <span class="kk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; margin-top: 2px; font-size: 16px;">
                  <i class="fas fa-clock"></i>
                </span>
                <span class="kk-text-main" style="font-weight: 600; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($merchant->store_hours)); ?></span>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Notice - Consuming raw or uncooked meats, poultry seafood, shelf, or eggs, may increase your risk of food-borne diseases, especially if you have certain medical conditions. -->
  <div class="card kk-card" style="border: 2px solid var(--brand-gold); margin-top: 1.5rem; background-color: var(--brand-bg);">
    <div class="card-content" style="padding: 20px 24px;">
      <p class="kk-text-muted" style="margin-bottom: 0; font-size: 13px; line-height: 1.5; font-weight: 600;">
        <i class="fas fa-exclamation-triangle" style="color: var(--brand-gold); margin-right: 6px;"></i>
        Consuming raw or uncooked meats, poultry, seafood, shellfish, or eggs may increase your risk of food-borne illness, especially if you have certain medical conditions.
      </p>
    </div>
  </div>

  <!-- Spotlight Product Panel -->
  <?php if ($spotlightProduct):
    $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
    $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
  ?>
    <div id="nh-spotlight-callout-panel" class="card z-depth-2 animate-fade-in kk-card" style="border: 2px solid var(--brand-gold); overflow: hidden; margin-top: 25px;">
      <div class="white-text" style="background-color: var(--brand-primary); padding: 10px 20px; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-mug-hot" style="color: var(--brand-gold); margin-right: 6px;"></i> Today's Daily Special</span>
      </div>
      <div class="card-content row" style="margin-bottom: 0; padding: 24px;">
        <?php if (!empty($spotlightProduct['image_url'])): ?>
          <div class="col s12 m4 center-align">
            <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 8px; max-height: 200px; object-fit: cover; width:100%;">
          </div>
        <?php endif; ?>
        <div class="col s12 m8">
          <h4 class="kk-text-main" style="margin: 0 0 8px 0; font-weight: 800;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
            <a class="page_link waves-effect waves-light" style="" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&product_id=<?= $spotlightProduct['id'] ?>"><i class="material-icons">share</i></a>
          </h4>

          <h5 style="margin: 0 0 12px 0; font-weight: 800;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
          <p class="" style="font-size: 15px; line-height: 1.6; margin-bottom: 20px; font-weight: 500;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

          <?php if ($merchant->status == 'online') : ?>
            <div class="card-action" style="padding: 0; border: none; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <div class="quantity-pill" style="margin-right: 10px;">
                <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700; -webkit-appearance: none; -moz-appearance: textfield;">
                <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
              </div>

              <?php if ($isSpotCustom): ?>
                <button class="btn btn-large waves-effect waves-light nh-customize-trigger btn-kk-customize"
                  style="background-color: var(--brand-primary) !important;"
                  data-id="<?php echo $spotlightProduct['id']; ?>"
                  data-merchant-id="<?php echo $merchant->id; ?>"
                  data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
                  data-merchant-address="<?php echo $merchant->address; ?>"
                  data-merchant-lat="<?php echo $merchant->latitude; ?>"
                  data-merchant-lon="<?php echo $merchant->longitude; ?>"
                  data-type="<?php echo htmlspecialchars($spotlightProduct['type']); ?>"
                  data-price="<?php echo $spotlightProduct['price']; ?>">
                  Customize <i class="fas fa-sliders-h right"></i>
                </button>
              <?php endif; ?>

              <button class="btn btn-large waves-effect waves-light nh-add-standard-btn btn-kk-add"
                style="color: #fff; background-color: #3d7329 !important;"
                data-id="<?php echo $spotlightProduct['id']; ?>"
                data-merchant-id="<?php echo $merchant->id; ?>"
                data-merchant-address="<?php echo $merchant->address; ?>"
                data-merchant-lat="<?php echo $merchant->latitude; ?>"
                data-merchant-lon="<?php echo $merchant->longitude; ?>"
                data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
                data-price="<?php echo $spotlightProduct['price']; ?>">
                Add To Order <i class="fas fa-shopping-basket right"></i>
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Menu Divider -->
  <hr class="kc-gold-divider">

  <div class="row" style="margin-bottom: 20rem">
    <div class="col s12 m4 l3 sticky-column hide-on-small-only">
      <div class="card secondary-category-menu">
        <div class="menu-title"><i class="fas fa-utensils"></i> Kafe Menu</div>
        <ul>
          <?php if (count($menuCategories) >= 1) { ?>
            <?php foreach ($menuCategories as $category):
              $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
            ?>
              <li><a class="category-menu-item" data-category-anchor="<?= $cleanId ?>" href="#!"><?= htmlspecialchars($category['name']) ?></a></li>
            <?php endforeach; ?>
          <?php } else { ?>
            <li>Online Ordering Coming Soon!</li>
          <?php } ?>
        </ul>
      </div>
    </div>

    <div class="col s12 m8 l9">
      <!-- Product Catalog Categories -->
      <?php if (!empty($menuProductsByCategory)) : ?>
        <?php foreach ($menuProductsByCategory as $categoryId => $catProducts) : ?>
          <?php
          render('components/product_category.php', array(
            'merchant' => $merchant,
            'products' => $catProducts,
            'spotlightProductId' => $spotlightProductId,
          )); ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Builder Modal Framework -->
<div id="nh-custom-builder-modal" class="modal mb-modal-fixed kk-card" style="border: 2px solid var(--brand-gold); max-width: 800px; border-radius: 10px !important;">

  <div class="modal-header" style="padding: 20px 24px 10px; background-color: var(--brand-bg); border-bottom: 1px solid var(--brand-border);">
    <h4 id="builder-modal-title" class="" style="font-weight: 800; margin-top: 0; text-transform: uppercase;">Customize Your Selection</h4>
    <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row; margin-bottom: 10px;">
      <span class="" style="font-size: 14px; font-weight: 700; margin-right: 8px;">Qty: </span>
      <div class="quantity-pill">
        <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
        <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; padding: 0; border: none; height: 28px; font-weight: 700; -webkit-appearance: none; -moz-appearance: textfield;">
        <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
      </div>
    </div>
  </div>
  <div class="modal-content" style="padding: 24px; background-color: var(--brand-card-bg);">
    <p class="kk-text-muted" style="margin-bottom: 20px; font-weight: 600;">Select your options and modifiers below.</p>
    <div id="builder-widget-mount-viewport"></div>
  </div>

  <div class="row" style="width:100%; margin: 0; padding: 0 24px 15px; background-color: var(--brand-card-bg);">
    <div class="input-field col s12" style="margin-top: 10px; margin-bottom: 0;">
      <i class="fas fa-edit prefix kk-icon" style="top: 0.8rem; font-size: 1.2rem;"></i>
      <textarea id="builder-customer-notes"
        class="materialize-textarea nh-card-customer-notes-input kk-text-main"
        data-length="200"
        maxlength="200"
        placeholder="E.g., Extra crispy, sauce on the side..."
        style="min-height: 45px; padding-bottom: 0; margin-bottom: 5px;"></textarea>
      <label for="builder-customer-notes" class="active" style="color: var(--brand-gold); font-weight: 600;">Special Instructions / Kitchen Notes</label>
      <span class="helper-text kk-text-muted" style="font-size: 11px;">Add any custom requests or prep preferences for the kitchen.</span>
    </div>
  </div>

  <div class="modal-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 24px; background-color: var(--brand-bg); border-top: 1px solid var(--brand-border);">
    <div>
      <h5 style="margin:0; font-size: 13px; font-weight: 700; text-transform: uppercase;">Item Price:</h5>
      <span class="live-builder-total" style="font-weight: 900; font-size: 24px;">$0.00</span>
    </div>
    <div>
      <a href="#!" class="modal-close waves-effect btn-flat" style="margin-right: 12px; font-weight: 700;">Cancel</a>
      <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light btn-kk-customize green" style="font-weight:700; height: 42px; padding: 0 20px;">
        Add To Order <i class="fas fa-plus right"></i>
      </button>
    </div>
  </div>
</div>

<!-- Floating Dropdown Category Menu -->
<div style="position: fixed; top: 4rem; height: 5rem; width: 100%; z-index: 899; pointer-events: none;">
  <div class="container" style="pointer-events: auto;">
    <div class="right">
      <div class="top-right-dropdown-wrapper">
        <a class="dropdown-trigger btn-floating btn-large waves-effect waves-light" href="#" data-target="top-right-menu">
          <i class="fas fa-utensils"></i>
        </a>

        <!-- Dropdown Categories -->
        <ul id="top-right-menu" class="dropdown-content">
          <li class="category-menu-item-back-to-top"><a class="category-menu-item" data-category-anchor="top" href="#!">Back to Top</a></li>
          <?php if (count($menuCategories) >= 1) { ?>
            <?php foreach ($menuCategories as $category):
              $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))))
            ?>
              <li><a class="category-menu-item" data-category-anchor="<?= $cleanId ?>" href="#!"> <?= htmlspecialchars($category['name']) ?> </a></li>
            <?php endforeach; ?>
          <?php } else { ?>
            <li><a href="javascript: void(0);">Online Ordering Coming Soon!</a></li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Initialize standard merchant storefront interactions
    window.storefront = new MerchantStorefront({
      toastIcon: 'fa-check-circle', // Customize icon per theme if desired (e.g. 'fa-fire-alt')
      toastVerb: 'basket' // Customize text per theme if desired (e.g. 'order')
    });
  });
</script>