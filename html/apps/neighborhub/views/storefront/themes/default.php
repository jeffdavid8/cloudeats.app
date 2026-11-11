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
$storefrontConfig = is_array($configuration ?? null)
  ? $configuration
  : MerchantStorefront::defaultConfiguration();
$storefrontColors = is_array($storefrontConfig['colors'] ?? null)
  ? $storefrontConfig['colors']
  : [];
$storefrontVisibility = is_array($storefrontConfig['visibility'] ?? null)
  ? $storefrontConfig['visibility']
  : [];
$merchantStatus = strtolower(trim((string)($merchant->status ?? '')));
$merchantCanAcceptOrders = in_array($merchantStatus, ['active', 'online', '1'], true)
  || $merchant->status === 1;
$hasMenus = !empty($menus);
$activeMenuId = get_var('menu_id', !empty($menus) ? array_keys($menus)[0] : 0);
$menuProductsByCategory = $hasMenus
  ? Menu::getProductsGroupedByCategory($activeMenuId, true)
  : [];

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
  body {
    --merchant-primary: <?= htmlspecialchars((string)($storefrontColors['primary'] ?? '#4f46e5'), ENT_QUOTES, 'UTF-8') ?>;
    --merchant-background: <?= htmlspecialchars((string)($storefrontColors['background'] ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>;
    --merchant-text: <?= htmlspecialchars((string)($storefrontColors['text'] ?? '#111827'), ENT_QUOTES, 'UTF-8') ?>;
  }

  <?php if (($storefrontVisibility['categories'] ?? true) === false): ?>.secondary-category-menu,
  .menu-toggle-wrapper,
  .category-menu-item-back-to-top {
    display: none !important;
  }

  <?php endif; ?><?php if (($storefrontVisibility['contact'] ?? true) === false): ?>.merchant-info-card {
    display: none !important;
  }

  <?php endif; ?><?php if (!$hasMenus): ?>.secondary-category-menu,
  .top-right-dropdown-wrapper {
    display: none !important;
  }

  <?php endif; ?>

  /* Day Mode (Vanilla-Cream & Cocoa) */
  .dayMode body,
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
  .nightMode body {
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

  body {
    padding-top: 6rem;
  }

  .merchant-storefront-hero {
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 1180px;
    margin: 0 auto 1.5rem;
    padding: 1rem 1.25rem;
    color: var(--brand-text-main);
    background: var(--brand-card-bg);
    border: 1px solid var(--brand-border);
    border-radius: 1rem;
    margin-top: 0;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-split"],
  .merchant-storefront-hero[data-storefront-layout="hero-centered"],
  .merchant-storefront-hero[data-storefront-layout="hero-minimal"] {
    margin-top: 2rem;
  }

  .merchant-storefront-hero-background {
    display: none;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-full"] img.merchant-storefront-hero-logo {
    width: 250px;
    height: auto;
  }

  .merchant-storefront-hero-logo {
    width: 72px;
    height: 72px;
    flex: 0 0 72px;
    object-fit: cover;
    border: 3px solid var(--brand-gold);
    border-radius: 50%;
  }

  .merchant-storefront-hero h1 {
    margin: 0 0 0.35rem;
    font-size: 1.5rem;
    line-height: 1.2;
  }

  .merchant-storefront-hero p {
    margin: 0;
    color: var(--brand-text-muted);
  }

  .merchant-storefront-hero[data-storefront-layout="hero-centered"],
  .merchant-storefront-hero[data-storefront-layout="hero-full"] {
    flex-direction: column;
    justify-content: center;
    text-align: center;
    padding: 2rem 1.25rem;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-full"] {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    width: 100vw;
    max-width: none;
    min-height: 75vh;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
    border-radius: 0;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    color: #fff;
    text-shadow: 0 2px 6px rgba(0, 0, 0, .8);
  }

  .merchant-storefront-hero[data-storefront-layout="hero-full"] .merchant-storefront-hero-background {
    display: block;
    position: absolute;
    inset: -20% 0;
    z-index: -1;
    background-image: inherit;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    transform: translate3d(0, var(--merchant-hero-parallax, 0px), 0);
    pointer-events: none;
  }

  @media (prefers-reduced-motion: reduce) {
    .merchant-storefront-hero[data-storefront-layout="hero-full"] .merchant-storefront-hero-background {
      transform: none;
    }
  }

  .merchant-storefront-hero[data-storefront-layout="hero-full"] h1,
  .merchant-storefront-hero[data-storefront-layout="hero-full"] p {
    color: #fff;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-split"] {
    flex-direction: row;
    justify-content: flex-start;
    text-align: left;
    padding: 1.5rem;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-split"]>div,
  .merchant-storefront-hero[data-storefront-layout="hero-minimal"]>div {
    min-width: 0;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-minimal"] {
    flex-direction: row;
    justify-content: flex-start;
    text-align: left;
    padding: 0.75rem 1rem;
    gap: 0.75rem;
  }

  .merchant-storefront-hero[data-storefront-layout] .merchant-storefront-hero-logo {
    margin: 0;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-minimal"] .merchant-storefront-hero-logo {
    width: 48px;
    height: 48px;
    flex-basis: 48px;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-minimal"] h1 {
    font-size: 1.25rem;
  }

  @media (max-width: 600px) {
    .merchant-storefront-hero[data-storefront-layout="hero-split"] {
      padding: 1rem;
    }

    .merchant-storefront-hero[data-storefront-layout="hero-split"] .merchant-storefront-hero-logo {
      width: 56px;
      height: 56px;
      flex-basis: 56px;
    }
  }

  .merchant-storefront-hero {
    --merchant-logo-size: 72px;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-full"] {
    --merchant-logo-size: 250px;
  }

  .merchant-storefront-hero[data-storefront-layout="hero-minimal"] {
    --merchant-logo-size: 48px;
  }

  @media (max-width: 600px) {
    .merchant-storefront-hero[data-storefront-layout="hero-split"] {
      --merchant-logo-size: 56px;
    }
  }

  .merchant-storefront-hero[data-storefront-logo-style="original"] img.merchant-storefront-hero-logo,
  .merchant-storefront-hero[data-storefront-logo-style="circle"] img.merchant-storefront-hero-logo,
  .merchant-storefront-hero[data-storefront-logo-style="rectangle"] img.merchant-storefront-hero-logo {
    width: var(--merchant-logo-size);
    max-width: 100%;
    flex: 0 0 auto;
  }

  .merchant-storefront-hero[data-storefront-logo-style="original"] img.merchant-storefront-hero-logo {
    width: auto;
    height: auto;
    max-width: min(100%, var(--merchant-logo-size));
    max-height: var(--merchant-logo-size);
    object-fit: contain;
    border: 0;
    border-radius: 0;
  }

  .merchant-storefront-hero[data-storefront-logo-style="circle"] img.merchant-storefront-hero-logo {
    height: var(--merchant-logo-size);
    object-fit: cover;
    border-radius: 50%;
  }

  .merchant-storefront-hero[data-storefront-logo-style="rectangle"] img.merchant-storefront-hero-logo {
    height: calc(var(--merchant-logo-size) * .7);
    object-fit: cover;
    border-radius: .5rem;
  }

  .merchant-storefront-blocks {
    display: grid;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .merchant-storefront-block {
    margin: 0;
    overflow: hidden;
  }

  .merchant-storefront-block h3 {
    margin-top: 0;
  }

  .merchant-storefront-block img {
    display: block;
    width: 100%;
    max-height: 280px;
    object-fit: cover;
  }

  @media (max-width: 600px) {
    .merchant-storefront-hero {
      margin-left: 0.75rem;
      margin-right: 0.75rem;
    }
  }

  header.header {
    z-index: revert-rule;
  }

  header.header nav {
    transition: all 0.3s;
    box-shadow: none;
    border-bottom: 3px solid #613891fd;
  }

  .nightMode body.scrolled header.header nav {
    box-shadow: 0 19px 17px rgba(0, 0, 0, 0.25);
    border-bottom: 3px solid #13131b;
  }

  .dayMode body.scrolled header.header nav {
    box-shadow: 0 19px 17px rgba(0, 0, 0, 0.25);
    border-bottom: 3px solid #ffffff;
  }

  .nightMode .header nav {
    border-bottom: 3px solid #2e2e45;
  }

  body.scrolled .nightMode .header nav {
    border-bottom: none;
  }

  header.header ul.left a.page_link {
    margin-left: 0;
  }

  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    top: 3rem;
    display: flex;
    align-items: center;
    width: 160px;
    right: calc(50% - 80px);
    flex: 1;
  }

  body.scrolled .merchant-header-image {
    right: calc(50% - 80px);
  }

  .merchant-header-image img {
    max-height: 160px;
    max-width: 160px;
    margin: 0 auto;
    position: relative;
    border: 10px solid #f6f0e180;
    transition: all 0.5s;
  }

  body.scrolled .merchant-header-image {
    top: -5px;
    right: calc(50% - 500px);
  }

  body.scrolled .merchant-header-image img {
    max-height: 80px;
    border: 2px solid #f6f0e180;
    box-shadow: 0 19px 17px rgba(0, 0, 0, 0.25);
  }

  /* Position the trigger button */
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
    color: #333;
  }

  body.scrolled .top-right-dropdown-wrapper {
    top: 7rem;
  }

  .nightMode .top-right-dropdown-wrapper a.dropdown-trigger i {
    color: #333;
  }

  .top-right-dropdown-wrapper a.dropdown-trigger:hover {
    background-color: #92929249 !important;
  }

  /* Optional: Ensure the menu aligns correctly under the button */
  .dropdown-content {
    min-width: 150px;
  }

  .header nav ul li a {
    padding: 0 12px;
    text-align: center;
    display: block;
    margin-top: 6px;
  }

  .secondary-category-menu {
    padding: 1rem;
    background-color: var(--brand-card-bg) !important;
    border-radius: 12px !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
  }

  .nightMode body .secondary-category-menu {
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
      width: 100px;
    }

    .header nav ul li a {
      padding: 0 12px;
      text-align: center;
      display: block;
      margin-top: 6px;
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
      margin: 0 auto;
      position: relative;
      border: 10px solid #f6f0e180;
      transition: all 0.2s;
    }

    .merchant-header-image img {
      max-height: 200px;
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

  .nightMode .top-right-dropdown-wrapper a.dropdown-trigger i {
    color: #efefef;
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

<div class="container menu-container">
  <?php require __DIR__ . '/../components/merchant_hero.php'; ?>
  <?php require __DIR__ . '/../components/promotional_blocks.php'; ?>
  <?php require __DIR__ . '/../components/merchant_contact.php'; ?>

  <?php if ($spotlightProduct):
    $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
    $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
  ?>
    <div id="nh-spotlight-callout-panel" class="card z-depth-2 animate-fade-in" style="border: 2px solid #ff9800; border-radius: 8px; overflow: hidden; margin-top: 30px;">
      <div class="orange white-text" style="padding: 8px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-star"></i> Spotlight</span>
        <? /*
        <a href="?p=merchant_products&merchant_id=<?php echo $merchant->id; ?>" class="white-text" style="text-decoration: underline; font-size: 12px;">View Full Catalog</a>
      */ ?>
      </div>
      <div class="card-content row" style="margin-bottom: 0; padding: 24px;">
        <div class="col s12 m4 center-align">
          <?php if (!empty($spotlightProduct['image_url'])): ?>
            <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 6px; max-height: 200px; object-fit: cover; width:100%;">
          <?php else: ?>
            <div class="grey lighten-4 grey-text" style="height: 180px; display: flex; justify-content: center; align-items: center; border-radius: 6px;">
              <i class="fas fa-box-open fa-3x"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="col s12 m8">
          <h4 style="margin: 0 0 8px 0; font-weight: 700;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
            <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&product_id=<?= $prod['id'] ?>"><i class="material-icons">share</i></a>
          </h4>

          <h5 class="grey-text text-lighten-1" style="margin: 0 0 12px 0; font-weight: 600;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
          <p class="text-darken-3" style="font-size: 15px; line-height: 1.6; margin-bottom: 20px;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

          <? if ($merchantCanAcceptOrders) : ?>
            <?php if ($isSpotCustom): ?>
              <button class="nh-btn btn-large waves-effect waves-light nh-customize-trigger"
                style="background-color:#174a7d !important;"
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
            <button class="nh-btn btn-large waves-effect waves-light nh-add-standard-btn"
              style="background-color:#3d7329 !important;"
              data-id="<?php echo $spotlightProduct['id']; ?>"
              data-merchant-id="<?php echo $merchant->id; ?>"
              data-merchant-address="<?php echo $merchant->address . ', ' . htmlspecialchars($merchant->city) . ', ' . htmlspecialchars($merchant->state) . ' ' . htmlspecialchars($merchant->zip_code); ?>"
              data-merchant-lat="<?php echo $merchant->latitude; ?>"
              data-merchant-lon="<?php echo $merchant->longitude; ?>"
              data-customizable="<?= $isSpotCustom ? '1' : '0' ?>"
              data-name="<?php echo htmlspecialchars($spotlightProduct['name']); ?>"
              data-price="<?php echo $spotlightProduct['price']; ?>">
              Add to Basket <i class="fas fa-shopping-basket right"></i>
            </button>
          <? endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$hasMenus): ?>
    <div class="card-panel center-align" role="status" style="margin-top: 2rem;">
      <i class="fas fa-utensils fa-2x" aria-hidden="true"></i>
      <h5>No menu available yet</h5>
      <p>This merchant has not published an active menu. Please check back soon.</p>
    </div>
  <?php else: ?>
    <div class="row">
      <?php require __DIR__ . '/../components/category_navigation.php'; ?>
      <?php require __DIR__ . '/../components/product_catalog.php'; ?>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/../components/custom_builder_modal.php'; ?>

<?php require __DIR__ . '/../components/category_dropdown.php'; ?>

<script>
  $(document).ready(function() {
    window.merchant = <?= json_encode($merchant) ?>;
    // Initialize standard merchant storefront interactions
    window.storefront = new MerchantStorefront({
      toastIcon: 'fa-check-circle', // Customize icon per theme if desired (e.g. 'fa-fire-alt')
      toastVerb: 'basket' // Customize text per theme if desired (e.g. 'order')
    });
  });
</script>