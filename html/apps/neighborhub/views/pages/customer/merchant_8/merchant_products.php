<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Generic Merchant Products Catalog & Spotlight Storefront
 * Custom Demo Theme: Pizza King & The Mason Jar
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
$params['menu_id'] = 1;
$pkMenuLink = $current_path . '?' . http_build_query($params);
$params['menu_id'] = 2;
$mjMenuLink = $current_path . '?' . http_build_query($params);
$activeMenuId = get_var('menu_id', array_keys($menus)[0]);
$menuProductsByCategory = Menu::getProductsGroupedByCategory($activeMenuId);
error_log(print_r($menuProductsByCategory, true));
/*
*/
$menu_meta = array(
  1 => array(
    'logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/Pizza-King-logo.png',
    'text-logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/Pizza-King-logo.png',
    'menu-link' => $pkMenuLink,
  ),
  2 => array(
    'logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/The-Mason-Jar-logo-round.png',
    'text-logo-image' => 'https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/The-Mason-Jar-text-logo.png',
    'menu-link' => $mjMenuLink,
  ),
);
//$productCatalog = $this->get('productCatalog', $menus);
//error_log(print_r($productCatalog, true));

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
  /* --- Pizza King & The Mason Jar Theme Palette --- */
  /* Day / Warm Rustic Mode */
  body.dayMode,
  body {
    --brand-bg: #e30101;
    --brand-bg-secondary: #db1f26;
    /* Soft Warm Parchment */
    --brand-card-bg: #FFFFFF;
    /* Clean Card Background */
    --brand-text-main: #1C1917;
    /* Deep Rustic Charcoal */
    --brand-text-muted: #57534E;
    /* Warm Slate Gray */
    --brand-border: rgba(139, 30, 30, 0.18);
    --brand-nav-bg: #fff;
    /* Classic Pizza King Crimson */
    --brand-nav-border: #D97706;
    /* Mason Jar Amber Gold */
    --brand-primary: #8B1E1E;
    --brand-secondary: #ce4b18;
    /* Signature Pizza King Red */
    --brand-gold: #D97706;
    /* Pub Amber Gold */
    --brand-btn-add: #1C1917;
    /* Charcoal Action Button */
    --brand-btn-add-hover: #292524;
    --brand-pill-bg: #F5EFE6;
  }

  /* Night / Pub Mode */
  body.nightMode {
    --brand-bg: #e30101;
    --brand-bg-secondary: #db1f26;
    /* Dark Tavern Backdrop */
    --brand-card-bg: #1C1816;
    /* Deep Timber Panel Card */
    --brand-text-main: #F5F5F4;
    /* Soft Off-White */
    --brand-text-muted: #A8A29E;
    /* Muted Ash */
    --brand-border: rgba(217, 119, 6, 0.25);
    --brand-nav-bg: #0C0A09;
    /* Dark Header */
    --brand-nav-border: #8B1E1E;
    /* Glowing Crimson Stripe */
    --brand-primary: #A32424;
    --brand-secondary: #ce4b18;
    /* Bright Crimson */
    --brand-gold: #F59E0B;
    /* Warm Amber Gold */
    --brand-btn-add: #292524;
    /* Dark Slate Button */
    --brand-btn-add-hover: #3D3735;
    --brand-pill-bg: #26211E;
  }

  .nh-terms-banner-content a {
    color: #ffffff;
  }

  .nh-terms-banner-content a:hover {
    color: #ffcc7c;
  }

  /* Custom Owned Background Image Overlay Support */
  .pk-hero-banner {
    position: relative;
    height: 70vh;
    margin-top: 0;
    padding-top: 9rem;
    /* Slightly darkened
    background-image: linear-gradient(rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.65)),
      url('https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/532a923e-ef31-4bb8-8622-84c25d7db2a7.jpg');
    */
    background-image: url('https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/8/532a923e-ef31-4bb8-8622-84c25d7db2a7.jpg');
    background-size: cover;
    /* Allows smooth sizing shift */
    background-position: top;
    background-attachment: fixed;

    border-bottom: 5px solid var(--brand-gold);
    border-radius: 0 0 12px 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease-out;
  }

  /* Zoom state when scrolled */
  body.scrolled .pk-hero-banner {
    height: 80vh;
    padding-top: 10rem;
    padding-bottom: 5rem;
  }

  body {
    padding-top: 7rem;
    background-color: var(--brand-bg) !important;
    color: var(--brand-text-main);
    font-family: 'Open Sans', 'Roboto', 'Helvetica Neue', sans-serif;
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  body.scrolled {
    padding-top: 6rem;
  }

  .container::after {
    clear: both;
  }

  .container::before,
  .container::after {
    display: table;
    content: " ";
  }

  #nh-terms-banner {
    position: absolute;
    top: 0;
    width: 100%;
    margin: 0;
    font-size: 11px;
    border-bottom: none;
  }

  header.header {
    z-index: 997;
    top: 4rem;
    border-bottom: none;
  }

  header.header nav {
    height: 65px;
    transition: all 0.3s;
    box-shadow: none;
    background-color: var(--brand-nav-bg) !important;
    /* border-bottom: 4px solid var(--brand-nav-border); */
    border-bottom: none;
  }


  body.scrolled header.header {
    top: 0;
    border-bottom: 2px solid #dd3731;
  }

  body.scrolled header.header nav {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
  }

  /* Floating Branding Logo */
  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    top: 10px;
    display: flex;
    align-items: center;
    width: 180px;
    right: calc(50% - 90px);
    z-index: 999;
  }

  .merchant-header-image img {
    margin: 0 auto;
    border: 4px solid var(--brand-gold);
    border-radius: 50%;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    transition: all 0.4s;
  }

  body.scrolled .merchant-header-image {
    top: 2px;
    width: 100px;
    right: calc(50% - 50px);
  }

  body.scrolled .merchant-header-image img {
    border: none;
  }

  /* Card Styling */
  .merchant-info-card {
    border-top: 4px solid var(--brand-gold);
    width: 80%;
    margin: -1.5rem auto 0 auto;
    background-color: #ececec !important;
  }

  .nightMode .merchant-info-card {
    background-color: #1e1e1e !important;
  }

  .pk-card {
    background-color: var(--brand-card-bg) !important;
    border-radius: 10px !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08) !important;
    transition: all 0.3s ease;
  }

  body.nightMode .pk-card {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4) !important;
  }

  .pk-text-main {
    color: var(--brand-text-main) !important;
  }

  .pk-text-muted {
    color: var(--brand-text-muted) !important;
  }

  .pk-icon {
    color: var(--brand-primary) !important;
  }

  /* Buttons */
  .btn-pk-add {
    background-color: var(--brand-btn-add) !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px;
    transition: all 0.2s ease;
  }

  .card-action .btn,
  .nightMode .btn,
  .nightMode .btn-large,
  .nightMode .btn-small {
    color: #eeeeee !important;
  }

  .btn-pk-add:hover {
    background-color: var(--brand-btn-add-hover) !important;
    transform: translateY(-1px);
  }

  .btn-pk-customize {
    background-color: var(--brand-primary) !important;
    border-radius: 6px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px;
    transition: all 0.2s ease;
    color: #FFF !important;
  }

  .btn-pk-customize:hover {
    filter: brightness(1.15);
    transform: translateY(-1px);
  }

  .merchant-logo-image {
    width: 200px;
  }

  /* Quantity Control Pill */
  .quantity-pill {
    display: flex;
    align-items: center;
    background-color: var(--brand-pill-bg);
    border: 1px solid var(--brand-border);
    border-radius: 6px;
    padding: 2px 6px;
  }

  .quantity-pill button {
    color: var(--brand-text-muted) !important;
  }

  .quantity-pill input {
    color: var(--brand-text-main) !important;
  }

  /* Dropdown Anchor */
  .top-right-dropdown-wrapper {
    transition: all 0.2s;
    position: fixed;
    top: 25rem;
    left: 50%;
    margin-left: 32%;
    z-index: 885;
  }

  .top-right-dropdown-wrapper a.dropdown-trigger {
    border-radius: 50%;
    background-color: var(--brand-gold) !important;
    color: #FFF !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  body.scrolled .top-right-dropdown-wrapper {
    top: 6.5rem;
  }

  .dropdown-content {
    min-width: 200px;
    border-radius: 8px;
    background-color: var(--brand-card-bg) !important;
    border: 1px solid var(--brand-border);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
  }

  .dropdown-content li>a {
    color: var(--brand-text-main) !important;
    font-weight: 700;
  }

  .materialboxed {
    cursor: pointer;
  }

  .hero-text-overlay-pill {
    width: 80%;
  }

  /* Dual Menu Button Selector */
  .menu-toggle-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin: 2rem 0;
    flex-wrap: wrap;
  }

  .menu-toggle-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 240px;
    padding: 15px 20px;
    border-radius: 12px;
    border: 2px solid rgba(41, 23, 2, 0.25);
    background-color: #952525;
    color: #eee;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none !important;
  }

  .menu-toggle-btn img {
    max-width: 90%;
    object-fit: contain;
    transition: transform 0.2s ease;
  }

  .menu-toggle-wrapper a.active,
  .menu-toggle-wrapper a:hover {
    background-color: var(--brand-card-bg) !important;
    border-color: var(--brand-gold) !important;
    border-width: 5px;
    color: var(--brand-text-main);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(217, 119, 6, 0.3);
  }

  .menu-toggle-btn .btn-label {
    margin-top: 10px;
    font-weight: 800;
    font-size: 14px;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-align: center;
  }

  .menu-toggle-btn.active .btn-label {
    color: var(--brand-text-main);
  }

  /* Mason Jar SVG Button Styling */
  .mason-jar-logo-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--brand-gold);
    font-family: 'Georgia', serif;
    font-weight: bold;
  }

  .nightMode .modal-content,
  .dayMode .modal-content {
    color: #caf0f8;
    box-shadow: 0 0 40px rgba(228, 160, 72, 0.5), 0 0 80px rgba(0, 119, 182, 0.3);
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

  /* Sidebar Category Navigation Container */
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
    color: #ffa93a;
    margin-bottom: 0.75rem;
    padding-left: 0.75rem;
  }

  /* Sidebar Title Header */
  .dayMode .secondary-category-menu .menu-title {
    color: #ff9430;
  }

  /* Category List & Links */
  .secondary-category-menu ul {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .secondary-category-menu ul li {
    margin-bottom: 0.25rem;
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
    color: var(--brand-gold) !important;
    transform: translateX(4px);
  }

  /* Active Category State (Highlight on Scroll or Click) */
  .secondary-category-menu a.category-menu-item.active {
    background-color: var(--brand-primary) !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  }

  /* Golden Section Divider */
  .pk-gold-divider {
    border: none;
    height: 2px;
    background: linear-gradient(90deg,
        rgba(217, 119, 6, 0) 0%,
        var(--brand-gold) 20%,
        var(--brand-gold) 80%,
        rgba(217, 119, 6, 0) 100%);
    margin: 2.5rem auto;
    width: 90%;
    opacity: 0.85;
    box-shadow: 0 0 10px rgba(217, 119, 6, 0.4);
  }

  .dayMode .card-body-price {
    color: #161616;
  }

  .dayMode .modal-header,
  .dayMode .modal-footer,
  .dayMode .modal-header .btn-flat,
  .dayMode .modal-footer .btn-flat {
    color: #e7e7e7;
  }

  .dayMode .btn,
  .dayMode .btn-large,
  .dayMode .btn-small,
  .dayMode .btn-flat {
    color: #505050;
    /* background-color: transparent; */
  }

  .modal-close:hover {
    background: #ff4b4b;
    color: #eee;
  }

  .dropdown-content li.category-menu-item-back-to-top {
    display: none;
  }

  body.scrolled .dropdown-content li.category-menu-item-back-to-top {
    display: block;
  }

  @media (min-width: 768px) {
    .merchant-header-image {
      top: 0px;
      width: 210px;
      right: calc(50% - 105px);
    }

    .merchant-logo-image {
      width: 290px;
      max-width: 420px;
    }

    .top-right-dropdown-wrapper {
      top: 10.5rem;
    }

    .top-right-dropdown-wrapper {
      right: 1rem;
      left: auto;
      margin-left: 0;
    }

    .hero-text-overlay-pill {
      width: 40%;
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

  .modal-overlay {
    opacity: 1 !important;
    background: #0f0f0ffc;
    backdrop-filter: blur(8px);
  }

  .mb-modal-fixed .modal-inner-overlay {
    z-index: initial;
  }
</style>
<div class="pk-hero-banner">
  <div style="text-align: center; width: 100%;">
    <!-- Merchant Logo -->
    <img class="merchant-logo-image" src="<?= $menu_meta[$activeMenuId]['text-logo-image'] ?>" />

    <!-- Text Overlay Pill -->
    <div class="hero-text-overlay-pill white-text" style="border-radius: 25px; background-color: rgba(0, 0, 0, 0.65); margin: 1.5rem auto 0 auto; padding: 1rem 0;">
      <h3 style="margin: 0 0 6px 0; font-weight: 900; letter-spacing: -0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
        <?php echo htmlspecialchars($merchant->business_name); ?>
      </h3>
      <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: var(--brand-gold); font-weight: 800;">
        ★ Good To the Very Edge ★
      </div>
    </div>
  </div>
</div>


<div class="container">
  <!-- Merchant Info Card -->
  <div class="merchant-info-card card horizontal pk-card" style="margin-top: -1.5rem; border-top: 4px solid var(--brand-gold);">
    <div class="card-stacked">
      <div class="card-content" style="padding: 20px 24px;">
        <div class="row" style="margin-bottom: 0;">
          <div class="col s12 m6">
            <!-- Address -->
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="pk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                <i class="fas fa-map-marker-alt"></i>
              </span>
              <span class="pk-text-main" style="word-break: break-word; font-weight: 600;"><?php echo htmlspecialchars($merchant->address); ?></span>
            </p>

            <!-- Phone -->
            <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
              <span class="pk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; font-size: 16px;">
                <i class="fas fa-phone"></i>
              </span>
              <span class="pk-text-main" style="font-weight: 600;"><?php echo htmlspecialchars($merchant->phone); ?></span>
            </p>
          </div>

          <div class="col s12 m6">
            <!-- Store Hours -->
            <?php if (!empty($merchant->store_hours)): ?>
              <p style="display: flex; align-items: flex-start; margin-bottom: 0.75rem;">
                <span class="pk-icon" style="margin-right: 0.75rem; min-width: 1.25rem; text-align: center; margin-top: 2px; font-size: 16px;">
                  <i class="fas fa-clock"></i>
                </span>
                <span class="pk-text-main" style="font-weight: 600; line-height: 1.5;"><?php echo nl2br(htmlspecialchars($merchant->store_hours)); ?></span>
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Spotlight Product Panel -->
  <?php if ($spotlightProduct):
    $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
    $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
  ?>
    <div id="nh-spotlight-callout-panel" class="card z-depth-2 animate-fade-in pk-card" style="border: 2px solid var(--brand-gold); overflow: hidden; margin-top: 25px;">
      <div class="white-text" style="background-color: var(--brand-primary); padding: 10px 20px; font-weight: 800; font-size: 12px; text-transform: uppercase; letter-spacing: 1.5px; display: flex; align-items: center; justify-content: space-between;">
        <span><i class="fas fa-crown" style="color: var(--brand-gold); margin-right: 6px;"></i> Featured Special</span>
      </div>
      <div class="card-content row" style="margin-bottom: 0; padding: 24px;">
        <?php if (!empty($spotlightProduct['image_url'])): ?>
          <div class="col s12 m4 center-align">
            <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 8px; max-height: 200px; object-fit: cover; width:100%;">
            <? /*
            <div class="grey lighten-4 grey-text" style="height: 180px; display: flex; justify-content: center; align-items: center; border-radius: 8px;">
              <i class="fas fa-pizza-slice fa-3x" style="color: var(--brand-text-muted);"></i>
            </div>
            */ ?>
          </div>
        <?php endif; ?>
        <div class="col s12 m8">
          <h4 class="pk-text-main" style="margin: 0 0 8px 0; font-weight: 800;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
            <a class="page_link waves-effect waves-light" style="" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&product_id=<?= $spotlightProduct['id'] ?>"><i class="material-icons">share</i></a>
          </h4>

          <h5 style="margin: 0 0 12px 0; font-weight: 800;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
          <p class="" style="font-size: 15px; line-height: 1.6; margin-bottom: 20px; font-weight: 500;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

          <? if ($merchant->status == 'online') : ?>
            <div class="card-action" style="padding: 0; border: none; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
              <div class="quantity-pill" style="margin-right: 10px;">
                <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700; -webkit-appearance: none; -moz-appearance: textfield;">
                <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
              </div>

              <?php if ($isSpotCustom): ?>
                <button class="btn btn-large waves-effect waves-light nh-customize-trigger btn-pk-customize"
                  style="background-color:#174a7d !important;"
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

              <button class="btn btn-large waves-effect waves-light nh-add-standard-btn btn-pk-add"
                style="color: #fff; background-color:#3d7329 !important;"
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
          <? endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="menu-toggle-wrapper" style="margin-top: 7rem;">
    <!-- 1. Pizza King Menu Button -->
    <a href="<?= ($activeMenuId == 1) ? 'javascript: void(0)' : $pkMenuLink ?>" class="menu-toggle-btn<?= ($activeMenuId == 1) ? ' active' : '' ?>" id="btn-pk-menu">
      <img src="<?= $menu_meta[1]['text-logo-image'] ?>" alt="Pizza King Menu">
      <span class="btn-label">Pizza King Menu</span>
    </a>

    <!-- 2. The Mason Jar Menu Button -->
    <a href="<?= ($activeMenuId == 2) ? 'javascript: void(0)' : $mjMenuLink ?>" class="menu-toggle-btn<?= ($activeMenuId == 2) ? ' active' : '' ?>" id="btn-mj-menu">
      <img src="<?= $menu_meta[2]['text-logo-image'] ?>" alt="The Mason Jar Menu">
      <span class="btn-label">The Mason Jar Menu</span>
    </a>
  </div>

  <!-- Menu Divider -->
  <hr class="pk-gold-divider">

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
        render('components/product_category.php', array(
          'merchant' => $merchant,
          'products' => $catProducts,
          'spotlightProductId' => $spotlightProductId,
        )); ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Builder Modal Framework -->
<div id="nh-custom-builder-modal" class="modal mb-modal-fixed pk-card" style="border: 2px solid var(--brand-gold); max-width: 800px; border-radius: 10px !important;">

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
    <p class="pk-text-muted" style="margin-bottom: 20px; font-weight: 600;">Select your toppings, crust options, and modifiers below.</p>
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

  <div class="modal-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 15px 24px; background-color: var(--brand-bg); border-top: 1px solid var(--brand-border);">
    <div>
      <h5 style="margin:0; font-size: 13px; font-weight: 700; text-transform: uppercase;">Item Price:</h5>
      <span class="live-builder-total" style="font-weight: 900; font-size: 24px;">$0.00</span>
    </div>
    <div>
      <a href="#!" class="modal-close waves-effect btn-flat" style="margin-right: 12px; font-weight: 700;">Cancel</a>
      <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light btn-pk-customize green" style="font-weight:700; height: 42px; padding: 0 20px;">
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
  window.merchant = <?= json_encode($merchant) ?>;

  $(document).ready(function() {

    $('#nh-custom-builder-modal').modal();
    $('.materialboxed').materialbox();

    var elems = document.querySelectorAll('.dropdown-trigger');
    var instances = M.Dropdown.init(elems, {
      alignment: 'right',
      constrainWidth: false,
      coverTrigger: false
    });

    // Smooth Scroll & Active Class Assignment on Click
    $('a.category-menu-item').on('click', function(e) {
      e.preventDefault();
      const anchor = $(this).attr('data-category-anchor');
      const target = $('#' + anchor);

      if (anchor === 'top') {
        $('html, body').animate({
          scrollTop: 0
        }, 600);
        return;
      }

      if (target.length) {
        // Update active highlight across both sidebar and floating dropdown menus
        $(`a.category-menu-item`).removeClass('active');
        $(`a.category-menu-item[data-category-anchor="${anchor}"]`).addClass('active');
        // Smooth scroll to category section with fixed header offset
        $('html, body').animate({
          scrollTop: target.offset().top - 120
        }, 600);
      }
    });

    // --- Smooth Scroll & Manual Active Class Assignment on Click ---
    let isManualScrolling = false;

    $('a.category-menu-item').on('click', function(e) {
      e.preventDefault();
      const anchor = $(this).attr('data-category-anchor');
      const target = $('#' + anchor);

      if (anchor === 'top') {
        $('html, body').animate({
          scrollTop: 0
        }, 600);
        return;
      }

      if (target.length) {
        // Lock observer temporarily so smooth scrolling doesn't flicker intermediate categories
        isManualScrolling = true;

        $('a.category-menu-item').removeClass('active');
        $(`a.category-menu-item[data-category-anchor="${anchor}"]`).addClass('active');

        $('html, body').animate({
          scrollTop: target.offset().top - 120
        }, 600, function() {
          // Re-enable observer after smooth scroll completes
          setTimeout(() => {
            isManualScrolling = false;
          }, 50);
        });
      }
    });

    // --- High-Performance Scroll Highlight via IntersectionObserver ---
    const observerOptions = {
      // Adjust rootMargin to trigger when section top crosses middle/upper section of screen
      rootMargin: '-120px 0px -50% 0px',
      threshold: [0, 0.2, 0.5]
    };

    const visibleCategories = new Map();

    const categoryObserver = new IntersectionObserver((entries) => {
      if (isManualScrolling) return;

      entries.forEach(entry => {
        // Target the category container using the clean ID from header
        const sectionId = entry.target.querySelector('[id]')?.id || entry.target.id;

        if (entry.isIntersecting) {
          visibleCategories.set(sectionId, entry.boundingClientRect.top);
        } else {
          visibleCategories.delete(sectionId);
        }
      });

      // Highlight the category closest to the top of the viewport
      if (visibleCategories.size > 0) {
        let topCategory = null;
        let minTop = Infinity;

        visibleCategories.forEach((top, id) => {
          if (top < minTop) {
            minTop = top;
            topCategory = id;
          }
        });

        if (topCategory) {
          $('a.category-menu-item').removeClass('active');
          $(`a.category-menu-item[data-category-anchor="${topCategory}"]`).addClass('active');
        }
      }
    }, observerOptions);

    // Observe all category containers/sections
    $('.category-section, [id]').each(function() {
      const anchorId = $(this).attr('id');
      if (anchorId && $(`a.category-menu-item[data-category-anchor="${anchorId}"]`).length > 0) {
        categoryObserver.observe(this);
      }
    });


    $('.menu-toggle-btn').on('click', function(e) {
      $('.menu-toggle-btn').removeClass('active');
      $(this).addClass('active');

      // Custom category filtering or section jumps can be triggered here
    });

    // Quantity Handlers
    $('.nh-card-qty-plus').on('click', function() {
      const input = $(this).siblings('.nh-card-qty-input');
      input.val(parseInt(input.val()) + 1);
    });

    $('.nh-card-qty-minus').on('click', function() {
      const input = $(this).siblings('.nh-card-qty-input');
      const currentVal = parseInt(input.val());
      if (currentVal > 1) {
        input.val(currentVal - 1);
      }
    });

    // Standard Add-To-Cart
    $('.nh-add-standard-btn').on('click', function() {
      const btn = $(this);
      const cardQty = parseInt(btn.closest('.card-action').find('.nh-card-qty-input').val() || 1);

      const productInfo = {
        id: btn.data('id'),
        name: btn.data('name'),
        price: btn.data('price'),
        merchantImage: btn.data('merchant-image'),
        merchantId: btn.data('merchant-id'),
        merchantName: btn.data('merchant-name'),
        merchantAddress: btn.data('merchant-address'),
        merchantLat: btn.data('merchant-lat'),
        merchantLon: btn.data('merchant-lon')
      };
      const targetMerchantId = btn.data('merchant-id') || nh.cart.activeMerchantIdReference;

      NHCart.addItem(targetMerchantId, productInfo, null, cardQty);

      $('#nh-shopping-cart-sidenav').sidenav('open');

      btn.closest('.card-action').find('.nh-card-qty-input').val(1);
      M.toast({
        html: `<i class="fas fa-check-circle"></i> Added (${cardQty}) ${productInfo.name} to basket!`
      });
    });

    // Custom Builder Trigger
    $('.nh-customize-trigger').on('click', function() {
      const btn = $(this);
      const productId = btn.data('id');
      const productName = btn.data('name');
      const price = btn.data('price');
      const merchantImage = btn.data('merchant-image');
      const merchantId = btn.data('merchant-id');
      const merchantName = btn.data('merchant-name');
      const merchantAddress = btn.data('merchant-address');
      data = btn.data();

      const initialCardQty = parseInt(btn.closest('.card-action').find('.nh-card-qty-input').val() || 1);

      nh.cart.activeCustomProductMetadata = {
        id: productId,
        name: productName,
        price: price,
        merchantImage: merchantImage,
        merchantId: merchantId,
        merchantName: btn.data('merchant-name'),
        merchantAddress: merchantAddress,
        merchantLat: btn.data('merchant-lat'),
        merchantLon: btn.data('merchant-lon'),
      };
      $('#builder-modal-title').text('Customize Your ' + productName);
      $('#nh-custom-builder-modal .nh-card-qty-input').val(initialCardQty);

      const $notesInput = $('#nh-custom-builder-modal .nh-card-customer-notes-input');
      $notesInput.val('');
      M.textareaAutoResize($notesInput);

      const modalInstance = M.Modal.getInstance(document.getElementById('nh-custom-builder-modal'));
      modalInstance.open();

      if ($notesInput[0]) {
        M.CharacterCounter.init($notesInput[0]);
      }
      $('#builder-widget-mount-viewport').empty();
      nh.cart.activeBuilderInstance = new CustomOrderBuilder('builder-widget-mount-viewport', productId, price);

      btn.closest('.card-action').find('.nh-card-qty-input').val(1);
    });

    // Modal Submit
    $('#nh-modal-submit-add-to-cart').on('click', function() {
      if (nh.cart.activeBuilderInstance && nh.cart.activeCustomProductMetadata) {
        const receiptBlob = nh.cart.activeBuilderInstance.compileSelections();
        if (!receiptBlob) return;

        const finalModalQty = parseInt($('#nh-custom-builder-modal .nh-card-qty-input').val() || 1);
        nh.cart.activeCustomProductMetadata.customer_notes = $('#nh-custom-builder-modal .nh-card-customer-notes-input').val() || '';

        // Get notes value before clearing
        const customerNotes = $('#nh-custom-builder-modal .nh-card-customer-notes-input').val().trim();
        nh.cart.activeCustomProductMetadata.customer_notes = customerNotes;

        // Clear the notes textarea for next time
        $('#nh-custom-builder-modal .nh-card-customer-notes-input').val('');

        NHCart.addItem(nh.cart.activeCustomProductMetadata.merchantId, nh.cart.activeCustomProductMetadata, receiptBlob, finalModalQty);

        $('#nh-shopping-cart-sidenav').sidenav('open');

        M.Modal.getInstance(document.getElementById('nh-custom-builder-modal')).close();
        M.toast({
          html: `<i class="fas fa-check-circle"></i> Added (${finalModalQty}) custom ${nh.cart.activeCustomProductMetadata.name} to basket!`
        });
      }
    });
  });
</script>