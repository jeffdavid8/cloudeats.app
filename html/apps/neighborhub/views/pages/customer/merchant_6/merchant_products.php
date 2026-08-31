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

// Isolate the Spotlight Product ID parameter from URL query context if present
$spotlightProductId = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;
$spotlightProduct = null;

if ($spotlightProductId) {
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
<style>
  /* --- Brown's Sweets & Eats Semantic Color Palette --- */

  /* Day Mode (Vanilla-Cream & Cocoa) */
  body.dayMode,
  body {
    --brand-bg: #FAF3EA;
    /* Cozy Bakery Paper background */
    --brand-card-bg: #FFFBF7;
    /* Vanilla Cream card */
    --brand-text-main: #2E1C16;
    /* Deep Espresso */
    --brand-text-muted: #5D4037;
    /* Warm Cocoa */
    --brand-border: rgba(93, 64, 55, 0.1);
    --brand-nav-bg: #3E2723;
    /* Dark Espresso Nav */
    --brand-nav-border: #FFB300;
    --brand-primary: #E65100;
    /* Warm Gold */
    --brand-caramel: #E65100;
    /* Sweet Caramel */
    --brand-gold: #FFB300;
    /* Pastry Gold */
    --brand-btn-add: #5D4037;
    /* Rich Cocoa Button */
    --brand-btn-add-hover: #3E2723;
    --brand-pill-bg: #FFFFFF;
  }

  /* Night Mode (Dark Chocolate & Honey Caramel) */
  body.nightMode {
    --brand-bg: #1C110E;
    /* Dark Cocoa Roast background */
    --brand-card-bg: #2E1C16;
    /* Espresso Card */
    --brand-text-main: #F5EBE6;
    /* Soft Meringue text */
    --brand-text-muted: #D7CCC8;
    /* Whipped Cream text */
    --brand-border: rgba(255, 179, 0, 0.15);
    --brand-nav-bg: #120A08;
    /* Near-Black Cocoa Nav */
    --brand-nav-border: #FFB300;
    --brand-primary: #FF8F00;
    /* Kept Gold for consistency */
    --brand-caramel: #FF8F00;
    /* Vibrant Honey Caramel (high contrast) */
    --brand-gold: #FFC107;
    /* Bright Pastry Gold */
    --brand-btn-add: #4E342E;
    /* Medium Cocoa Button */
    --brand-btn-add-hover: #5D4037;
    --brand-pill-bg: #3E2723;
  }

  /* Global Body Theme Override */
  body {
    padding-top: 10rem;
    background-color: var(--brand-bg) !important;
    color: var(--brand-text-main);
    font-family: 'Montserrat', 'Playfair Display', 'Helvetica Neue', sans-serif;
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  /* Navigation Customization */
  header.header {
    z-index: revert-rule;
  }

  header.header nav {
    transition: all 0.3s;
    box-shadow: none;
    background-color: var(--brand-nav-bg) !important;
    border-bottom: 4px solid var(--brand-nav-border);
  }

  body.scrolled header.header nav {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  }

  header.header ul.left a.page_link {
    margin-left: 0;
  }

  /* Floating Logo Adjustments */
  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    top: 35px;
    display: flex;
    align-items: center;
    width: 160px;
    right: calc(50% - 80px);
    flex: 1;
    z-index: 999;
  }

  body.scrolled .merchant-header-image {
    right: calc(50% - 50px);
    width: 100px;
  }

  .merchant-header-image img {
    max-height: 160px;
    max-width: 160px;
    margin: 0 auto;
    position: relative;
    border: 8px solid var(--brand-card-bg);
    border-radius: 50%;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    transition: all 0.5s;
  }

  .header nav ul li a {
    padding: 0 12px;
    text-align: center;
    display: block;
    margin-top: 6px;
    color: #FFFBF7 !important;
    /* Keep link text crisp on header */
  }

  body.scrolled header.header {
    border-bottom: 2px solid rgb(189, 87, 27);
  }

  body.scrolled .merchant-header-image {
    top: -5px;
  }

  body.scrolled .merchant-header-image img {
    max-height: 80px;
    max-width: 80px;
    border: 3px solid var(--brand-card-bg);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
  }

  /* Dropdown Trigger */
  .top-right-dropdown-wrapper {
    transition: all 0.2s;
    position: fixed;
    top: 8rem;
    right: 1rem;
    margin-left: 30%;
    z-index: 885;
  }

  .top-right-dropdown-wrapper a.dropdown-trigger {
    border-radius: 50%;
    background-color: var(--brand-caramel) !important;
    color: #FFF !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
  }

  .top-right-dropdown-wrapper a.dropdown-trigger:hover {
    filter: brightness(1.1);
  }

  body.scrolled .top-right-dropdown-wrapper {
    top: 5rem;
  }

  .dropdown-content {
    min-width: 180px;
    border-radius: 8px;
    background-color: var(--brand-card-bg) !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  }

  .dropdown-content li>a,
  .dropdown-content li>span {
    color: var(--brand-text-main) !important;
    font-weight: 600;
  }

  .dropdown-content li>a:hover {
    background-color: var(--brand-bg) !important;
  }


  .secondary-category-menu {
    padding: 1rem;
    background-color: var(--brand-card-bg) !important;
    border-radius: 12px !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
  }

  body.nightMode .secondary-category-menu {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
  }

  /* Sidebar Title Header */
  .secondary-category-menu .menu-title {
    font-size: 0.85rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 0.75rem;
    padding-left: 0.75rem;
  }

  .secondary-category-menu a.category-menu-item {
    width: 100%;
    display: block;
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--brand-text-main) !important;
    transition: all 0.2s ease-in-out;
    text-decoration: none;
  }

  /* Hover State */
  .secondary-category-menu a.category-menu-item:hover {
    background-color: var(--brand-pill-bg) !important;
    color: var(--brand-secondary) !important;
    transform: translateX(4px);
  }

  /* Active Category State (Highlight on Scroll or Click) */
  .secondary-category-menu a.category-menu-item.active {
    background-color: var(--brand-primary) !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }

  .dropdown-content li.category-menu-item-back-to-top {
    display: none;
  }

  body.scrolled .dropdown-content li.category-menu-item-back-to-top {
    display: block;
  }


  @media (max-width: 768px) {
    .top-right-dropdown-wrapper {
      right: 1rem;
    }
  }


  @media (min-width: 768px) {
    body.scrolled .merchant-header-image {
      right: calc(50% - 50px);
    }

    .merchant-header-image {
      top: -15px;
      width: 200px;
      right: calc(50% - 100px);
      transition: all 0.5s;
    }

    .merchant-header-image img {
      max-height: 200px;
      max-width: 200px;
      border: 10px solid var(--brand-card-bg);
    }
  }

  @media (min-width: 992px) {
    .container {
      width: 970px;
    }

    #nh-terms-banner {
      font-size: 15px;
    }

  }

  @media (min-width: 1200px) {
    .container {
      width: 1180px;
    }
  }

  @media (min-width: 1400px) {
    .container {
      width: 1400px;
    }
  }

  /* Cozy Bakery Sweets Card */
  .sweets-card {
    background-color: var(--brand-card-bg) !important;
    border-radius: 16px !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08) !important;
    transition: background-color 0.3s ease, border-color 0.3s ease;
  }

  .sweets-text-brown {
    color: var(--brand-text-main) !important;
  }

  .sweets-text-muted {
    color: var(--brand-text-muted) !important;
  }

  .sweets-icon {
    color: var(--brand-caramel) !important;
  }

  /* Adaptive Buttons styling */
  .btn-custom-add {
    background-color: var(--brand-btn-add) !important;
    border-radius: 30px !important;
    font-weight: 600 !important;
    text-transform: none !important;
    transition: all 0.3s ease;
    color: #FFF !important;
  }

  .btn-custom-add:hover {
    background-color: var(--brand-btn-add-hover) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
  }

  .btn-custom-customize {
    background-color: var(--brand-caramel) !important;
    border-radius: 30px !important;
    font-weight: 600 !important;
    text-transform: none !important;
    transition: all 0.3s ease;
    color: #FFF !important;
  }

  .btn-custom-customize:hover {
    filter: brightness(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
  }

  /* Cozy Quantity Control Pill */
  .quantity-pill {
    display: flex;
    align-items: center;
    background-color: var(--brand-pill-bg);
    border: 1.5px solid var(--brand-border);
    border-radius: 25px;
    padding: 2px 8px;
    transition: background-color 0.3s ease;
  }

  .quantity-pill button {
    color: var(--brand-text-muted) !important;
  }

  .quantity-pill button:hover {
    background-color: var(--brand-bg) !important;
  }

  .quantity-pill input {
    color: var(--brand-text-main) !important;
  }

  .modal button {
    background: none;
  }

  .modal button:hover {
    background: none;
  }

  .modal .quantity-pill button {
    margin: 0;
  }

  /* Chrome, Safari, Edge, Opera */
  .nh-card-qty-input::-webkit-outer-spin-button,
  .nh-card-qty-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  /* Firefox */
  .nh-card-qty-input {
    -moz-appearance: textfield;
  }

  .mb-modal-fixed .modal-inner-overlay {
    z-index: initial;
  }


  .sticky-column {
    position: -webkit-sticky;
    /* Support for older Safari */
    position: sticky;
    top: 7rem;
    /* Distance from the top of the viewport when it locks */
    z-index: 10;
    /* Ensures it stays above scrolling content if needed */
    max-height: calc(100vh - 20px);
    /* Keeps the column from expanding past the viewport */
    overflow-y: auto;
    /* Adds an internal scrollbar IF the left list is longer than the screen */
  }
</style>

<div class="container">
  <!-- Merchant Info Card -->
  <div class="card horizontal sweets-card" style="margin-top: 2rem;">
    <div class="card-stacked">
      <div class="card-content">
        <h4 class="sweets-text-brown" style="margin: 0 0 15px 0; font-weight: 800; border-bottom: 2px solid var(--brand-gold); padding-bottom: 8px; display: inline-block;">
          <?php echo htmlspecialchars($merchant->business_name); ?>
        </h4>

        <!-- Address -->
        <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
          <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
            <i class="fas fa-map-marker-alt"></i>
          </span>
          <span class="sweets-text-brown" style="word-break: break-word; font-weight: 500;"><?php echo htmlspecialchars($merchant->address); ?></span>
        </p>

        <!-- Phone -->
        <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
          <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
            <i class="fas fa-phone"></i>
          </span>
          <span class="sweets-text-brown" style="font-weight: 500;"><?php echo htmlspecialchars($merchant->phone); ?></span>
        </p>

        <!-- Facebook -->
        <?php if (!empty($merchant->facebook)): ?>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
              <i class="fab fa-facebook"></i>
            </span>
            <a href="<?php echo htmlspecialchars($merchant->facebook); ?>" target="_blank" rel="noopener noreferrer" style="word-break: break-all; color: var(--brand-caramel); font-weight: 600;">
              Connect on Facebook
            </a>
          </p>
        <?php endif; ?>

        <!-- Website -->
        <?php if (!empty($merchant->website)): ?>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center;">
              <i class="fas fa-globe"></i>
            </span>
            <a href="<?php echo htmlspecialchars($merchant->website); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--brand-caramel); font-weight: 600;">
              Visit Website
            </a>
          </p>
        <?php endif; ?>

        <!-- Store Hours -->
        <?php if (!empty($merchant->store_hours)): ?>
          <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
            <span class="sweets-icon" style="margin-right: 1rem; min-width: 1.25rem; text-align: center; margin-top: 2px;">
              <i class="fas fa-clock"></i>
            </span>
            <span class="sweets-text-brown" style="font-weight: 500;"><?php echo nl2br(htmlspecialchars($merchant->store_hours)); ?></span>
          </p>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- Spotlight Product Panel -->
  <?php if ($spotlightProduct):
    $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
    $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
  ?>
    <div id="nh-spotlight-callout-panel" class="card z-depth-2 animate-fade-in sweets-card" style="border: 2px solid var(--brand-gold); overflow: hidden; margin-top: 30px;">
      <div class="white-text" style="background: linear-gradient(135deg, var(--brand-caramel), var(--brand-gold)); padding: 10px 20px; font-weight: 700; font-size: 15px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-star" style="color: #FFF;"></i> Brown's Sweet Spotlight</span>
      </div>
      <div class="card-content row" style="margin-bottom: 0; padding: 24px;">
        <div class="col s12 m4 center-align">
          <?php if (!empty($spotlightProduct['image_url'])): ?>
            <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 12px; max-height: 220px; object-fit: cover; width:100%; border: 4px solid var(--brand-bg);">
          <?php else: ?>
            <div class="grey lighten-4 grey-text" style="height: 180px; display: flex; justify-content: center; align-items: center; border-radius: 12px;">
              <i class="fas fa-box-open fa-3x" style="color: var(--brand-text-muted);"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="col s12 m8">
          <h4 class="sweets-text-brown" style="margin: 0 0 8px 0; font-weight: 800;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
            <a class="page_link waves-effect waves-light" style="color: var(--brand-caramel);" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&product_id=<?= $prod['id'] ?>"><i class="material-icons">share</i></a>
          </h4>

          <h5 style="color: var(--brand-caramel); margin: 0 0 12px 0; font-weight: 700;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
          <p class="sweets-text-muted" style="font-size: 15px; line-height: 1.6; margin-bottom: 24px; font-weight: 500;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

          <? if ($merchant->status == 'online') : ?>
            <div class="card-action" style="padding: 0; border: none; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">

              <div class="quantity-pill" style="margin-right: 10px;">
                <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
                <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
              </div>

              <?php if ($isSpotCustom): ?>
                <button class="btn btn-large waves-effect waves-light nh-customize-trigger btn-custom-customize"
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

              <button class="btn btn-large waves-effect waves-light nh-add-standard-btn btn-custom-add"
                data-id="<?php echo $spotlightProduct['id']; ?>"
                data-merchant-id="<?php echo $merchant->id; ?>"
                data-merchant-address="<?php echo $merchant->address; ?>"
                data-merchant-lat="<?php echo $merchant->latitude; ?>"
                data-merchant-lon="<?php echo $merchant->longitude; ?>"
                data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
                data-price="<?php echo $spotlightProduct['price']; ?>">
                Add to Basket <i class="fas fa-shopping-basket right"></i>
              </button>
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
      <?php foreach ($menus[$activeMenuId]['categories'] as $category) : ?>
        <?php
        render('components/product_category.php', array(
          'merchant' => $merchant,
          'category' => $category,
          'products' => $category['products'],
          'spotlightProductId' => $spotlightProductId,
        )); ?>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Modal Framework Styling (Cozy Sweets adaptive theme) -->
  <div id="nh-custom-builder-modal" class="modal mb-modal-fixed sweets-card" style="border: 2px solid var(--brand-gold); max-width: 800px;">
    <div class="modal-header" style="max-width: 800px; padding: 24px 24px 10px; background-color: var(--brand-bg); border-bottom: 2px dashed var(--brand-border);">
      <h4 id="builder-modal-title" class="sweets-text-brown" style="font-weight: 800; margin-top: 0;">Build Custom Selection</h4>
      <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row; margin-bottom: 10px;">
        <span class="sweets-text-brown" style="font-size: 14px; font-weight: 700; margin-right: 8px;">Quantity: </span>
        <div class="quantity-pill">
          <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
          <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
          <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
        </div>
      </div>
    </div>
    <div class="modal-content" style="padding: 24px; background-color: var(--brand-card-bg);">
      <p class="sweets-text-muted" style="margin-bottom: 20px; font-weight: 500;">Personalize your sweets and eats options below.</p>
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
        <h5 style="margin:0; font-size: 14px; color: var(--brand-text-muted); font-weight:700;">Total Item Cost:</h5>
        <span class="live-builder-total" style="font-weight: 850; font-size: 24px; color: var(--brand-caramel);">$0.00</span>
      </div>
      <div>
        <a href="#!" class="modal-close waves-effect btn-flat sweets-text-brown" style="margin-right: 12px; font-weight: 700;">Cancel</a>
        <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light btn-custom-customize" style="font-weight:700; height: 45px; padding: 0 24px;">
          Add To Basket <i class="fas fa-plus right"></i>
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
            <?php foreach ($menus[$activeMenuId]['categories'] as $category):
              $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name'])))) ?>
              <li><a class="category-menu-item" data-category-anchor="<?= $cleanId ?>" href="#!"> <?= $category['name'] ?> </a></li>
            <?php endforeach; ?>
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