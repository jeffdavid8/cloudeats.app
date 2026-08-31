<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Merchant POS Terminal
 *
 * Access: /?app=neighborhub&view=merchant&p=pos&merchant_id={id}
 *
 * Context provided by neighborhub_init_merchant_context():
 * @var object $merchant  - Active merchant profile
 * @var array  $product_catalog - Flat product array from Product::getProductsByMerchant()
 */
$app          = App::getInstance('neighborhub');
$merchant     = $app->get('merchant');
$staffRole    = $_SESSION['user']['merchant_staff_role'] ?? 'staff';
$userName     = htmlspecialchars($_SESSION['user']['username'] ?? 'Staff');
$rawCatalog   = $app->get('product_catalog', []);

if (empty($rawCatalog) && $merchant && $merchant->id) {
  $rawCatalog = Product::getProductsByMerchant($merchant->id, true, 'array');
}

$catalogByCategory = [];
foreach ($rawCatalog as $prod) {
  $tags     = !empty($prod['tags']) ? array_map('trim', explode(',', $prod['tags'])) : [];
  $category = !empty($tags) ? ucfirst($tags[0]) : 'Uncategorized';
  $catalogByCategory[$category][] = $prod;
}
ksort($catalogByCategory);

$merchantId   = intval($merchant->id ?? 0);
$merchantName = htmlspecialchars($merchant->business_name ?? 'POS Terminal');
if (isset($_SESSION[get_var('session_key')]) && get_var('action', false) == 'checkout_success') {
  $pendingOrder = $_SESSION[get_var('session_key')];
  $merchant_id = $pendingOrder['merchant_id'];
  unset($_SESSION[get_var('session_key')]);
?>
  <script>
    $(document).ready(function() {
      NHCart.activeMerchantId = <?= ($merchant_id) ? $merchant_id : 'null' ?>;
      NHCart.clear();
    });
  </script>
<?
}

// Display any session notifications
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
if ($notification) {
  unset($_SESSION['notification']);
}
?>

<style>
  body {
    padding-top: 41px;
  }

  body.scrolled {
    padding-top: 41px;
  }

  .pos-shell {
    display: flex;
    height: calc(100vh - 11.25rem);
    overflow: hidden;
    background: var(--gray-100, #f5f5f5);
  }

  .pos-ticket-panel {
    width: 360px;
    min-width: 300px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    border-right: 2px solid #e0e0e0;
    box-shadow: 4px 0 16px rgba(0, 0, 0, .06);
    z-index: 10;
    overflow-y: scroll;
    overflow-x: hidden;
  }

  .nightMode .pos-ticket-panel {
    background: var(--gray-100, #181a1e);
    border-color: #424242;
  }

  /* Small Screens Only (< 601px) */
  @media only screen and (max-width: 600px) {
    .pos-ticket-panel {
      display: none;
      /* Hide static aside wrapper on phone screens */
    }

    #nh-shopping-cart-sidenav {
      width: 320px;
      max-width: 85vw;
    }
  }

  /* Medium & Large Screens (>= 601px) */
  @media only screen and (min-width: 601px) {

    /* Display static panel container */
    .pos-ticket-panel {
      display: flex;
      flex-direction: column;
      width: 320px;
      /* Slightly narrower width to preserve space for grid on medium screens */
      min-width: 280px;
      flex-shrink: 0;
    }


    /* Force hide backdrop overlay on medium and up */
    .sidenav-overlay {
      display: none !important;
      opacity: 0 !important;
    }
  }

  /* Mobile & Tablet (< 993px) */
  @media only screen and (max-width: 992px) {
    .pos-ticket-panel {
      display: none;
      /* Hide static container wrapper on mobile */
    }

    #nh-shopping-cart-sidenav {
      width: 340px;
      max-width: 85vw;
    }
  }

  /* Desktop (>= 993px) */
  @media only screen and (min-width: 993px) {

    /* Show static left/right layout panel */
    .pos-ticket-panel {
      display: flex;
      flex-direction: column;
      width: 360px;
      min-width: 300px;
      flex-shrink: 0;
    }

    /* Override Materialize Sidenav fixed positioning so it displays inline */
    #nh-shopping-cart-sidenav.sidenav,
    #nh-shopping-cart-sidenav.sidenav.sidenav-fixed {
      transform: translateX(0) !important;
      right: auto !important;
      left: auto !important;
      top: 0 !important;
      width: 100% !important;
      height: 100% !important;
      box-shadow: none !important;
      z-index: 1 !important;
      background: transparent !important;
      display: none !important;
    }

    /* Disable Materialize backdrop overlay on desktop */
    .sidenav-overlay {
      display: none !important;
      opacity: 0 !important;
    }
  }

  .pos-ticket-header h5 {
    margin: 0 0 .2rem;
    font-weight: 800;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  .pos-ticket-header .pos-merchant-label {
    font-size: .75rem;
    color: #757575;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
  }

  .pos-ticket-items {
    flex: 1;
    overflow-y: auto;
    padding: .75rem 1rem;
  }

  .pos-line-item {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    padding: .6rem 0;
    border-bottom: 1px dashed #e0e0e0;
    font-size: .9rem;
  }

  .pos-line-qty-ctrl {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
  }

  .pos-qty-btn {
    width: 24px;
    height: 24px;
    border: 1px solid #bdbdbd;
    border-radius: 4px;
    background: #fafafa;
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    color: #333;
    transition: background .15s;
  }

  .pos-qty-btn:hover {
    background: #e3f2fd;
    border-color: #1976d2;
  }

  .pos-qty-num {
    min-width: 22px;
    text-align: center;
    font-weight: 700;
    font-size: .9rem;
  }

  .pos-line-info {
    flex: 1;
    min-width: 0;
  }

  .pos-line-name {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .pos-line-price {
    font-weight: 700;
    color: #2e7d32;
    white-space: nowrap;
    min-width: 58px;
    text-align: right;
  }

  .pos-line-remove {
    color: #e53935;
    cursor: pointer;
    flex-shrink: 0;
    margin-top: 2px;
  }

  .pos-line-remove:hover {
    color: #b71c1c;
  }

  .pos-ticket-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #9e9e9e;
  }

  .pos-ticket-empty i {
    font-size: 3rem;
    display: block;
    margin-bottom: .75rem;
  }

  .pos-totals-panel {
    border-top: 2px dashed #bdbdbd;
    padding: .85rem 1.25rem;
    background: #fff;
  }

  .pos-totals-row {
    display: flex;
    justify-content: space-between;
    font-size: .9rem;
    margin-bottom: .3rem;
    color: #424242;
  }

  .pos-totals-row.total {
    font-size: 1.25rem;
    font-weight: 900;
    color: #1b5e20;
    margin-top: .4rem;
    padding-top: .4rem;
    border-top: 2px solid #e0e0e0;
  }

  .pos-totals-row .discount-label {
    color: #e65100;
  }

  .pos-order-note-wrap {
    margin: .6rem 0 .5rem;
  }

  .pos-order-note-wrap textarea {
    font-size: .82rem;
    min-height: 48px;
    resize: none;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: .4rem .6rem;
    width: 100%;
    box-sizing: border-box;
    color: #333;
  }

  .pos-order-note-wrap textarea:focus {
    outline: none;
    border-color: #1565c0;
  }

  .pos-tender-btns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .5rem;
    margin-top: .7rem;
  }

  .pos-tender-btns .btn {
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .03em;
    border-radius: 6px;
    padding: 0 .75rem;
    height: 42px;
    line-height: 42px;
  }

  .pos-tender-btns .btn-custom {
    grid-column: 1 / -1;
    background: #546e7a;
  }

  .pos-clear-btn {
    margin-top: .5rem;
    width: 100%;
    height: 36px;
    line-height: 36px;
    font-size: .8rem;
    border-radius: 6px;
    background: transparent;
    border: 1px solid #bdbdbd;
    color: #757575;
    cursor: pointer;
    text-align: center;
    transition: background .15s;
  }

  .pos-clear-btn:hover {
    background: #ffebee;
    border-color: #e53935;
    color: #e53935;
  }

  .pos-catalog-panel {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 1.25rem 5rem;
  }

  body.scrolled .pos-catalog-panel {
    padding-top: 0;
  }

  .pos-catalog-search-bar {
    position: sticky;
    top: 0;
    z-index: 9;
    background: rgba(245, 245, 245, 0.95);
    backdrop-filter: blur(8px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    border-bottom: 1px solid #e0e0e0;
    padding: .75rem 0 .75rem;
    display: flex;
    flex-direction: column;
    /* Stack search bar on top, categories below */
    gap: .75rem;
    width: 100%;
  }

  .nightMode .pos-catalog-search-bar {
    background-color: rgba(30, 30, 30, 0.95);
    border-color: #424242;
  }

  /* Full width search input container */
  .pos-search-input-wrap {
    position: relative;
    width: 100%;
  }

  .pos-search-input-wrap input[type="search"] {
    width: 100%;
    border: 1px solid #bdbdbd;
    border-radius: 8px;
    padding: .65rem 2.25rem .65rem 2.5rem;
    font-size: .95rem;
    background: #fff;
    outline: none;
    box-sizing: border-box;
  }

  .pos-search-input-wrap .fa-search {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #757575;
  }

  .pos-search-input-wrap #pos-catalog-clear-search {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    color: #757575;
  }

  /* Full width wrapped category pills */
  .category-filter-links {
    display: flex;
    flex-wrap: wrap;
    /* Wrap buttons to multiple lines if needed */
    gap: .5rem;
    width: 100%;
  }

  /* Category Pill Styling */
  .pos-category-link {
    display: inline-flex;
    align-items: center;
    font-size: .82rem;
    color: #424242;
    font-weight: 600;
    background: #e0e0e0;
    padding: .4rem .8rem;
    border-radius: 16px;
    text-decoration: none;
    transition: all .15s ease-in-out;
    cursor: pointer;
  }

  .pos-category-link:hover,
  .pos-category-link.active {
    background: #1565c0;
    color: #ffffff;
  }

  .nightMode .pos-catalog-search-bar {
    background-color: #1e1e1ea9;
    border-color: #424242;
  }

  .pos-catalog-search-bar input[type="search"] {
    flex: 1;
    border: 1px solid #bdbdbd;
    border-radius: 8px;
    padding: .5rem 1rem .5rem 2.5rem;
    font-size: .95rem;
    background: #fff;
    outline: none;
  }

  .pos-catalog-search-bar input:focus {
    border-color: #1565c0;
  }

  .pos-category-label {
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #757575;
    margin: 1.25rem 0 .5rem;
    padding-bottom: .25rem;
    border-bottom: 2px solid #e0e0e0;
  }

  .btn.pos-category-filter {
    font-size: .82rem;
    font-weight: 700;
    text-transform: capitalize;
    border-radius: 6px;
    padding: .4rem .75rem;
    background: #e0e0e0;
    color: #424242;
    transition: background .15s, color .15s;
  }

  .nightMode .pos-category-label {
    color: #bdbdbd;
    border-color: #424242;
  }

  .pos-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
    gap: .65rem;
  }

  .nh-customize-trigger {
    background: #fff;
    border: 1.5px solid #e0e0e0;
    border-radius: 10px;
    padding: .85rem .7rem .7rem;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s, transform .1s;
    display: flex;
    flex-direction: column;
    gap: .35rem;
    position: relative;
    user-select: none;
  }

  .nightMode .nh-customize-trigger {
    background: #1e1e1e;
    border-color: #424242;
    color: #e0e0e0;
  }

  .nh-customize-trigger:hover {
    border-color: #1565c0;
    box-shadow: 0 4px 18px rgba(21, 101, 192, .13);
    transform: translateY(-2px);
  }

  .nh-customize-trigger:active {
    transform: scale(.97);
  }

  .nh-customize-trigger .tile-img {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: .2rem;
    background: #f5f5f5;
  }

  .nh-customize-trigger .tile-img-placeholder {
    width: 100%;
    height: 70px;
    border-radius: 6px;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #bdbdbd;
    margin-bottom: .2rem;
  }

  .nightMode .nh-customize-trigger .tile-img-placeholder {
    background: #424242;
    color: #757575;
  }

  .nh-customize-trigger .tile-name {
    font-weight: 700;
    font-size: .88rem;
    line-height: 1.25;
  }

  .nh-customize-trigger .tile-price {
    font-weight: 800;
    font-size: .95rem;
    color: #2e797d;
  }

  .nh-customize-trigger .tile-add-badge {
    position: absolute;
    top: .4rem;
    right: .4rem;
    background: #1565c0;
    color: #fff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    font-size: .7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    opacity: 0;
    transition: opacity .15s;
  }

  .nh-customize-trigger.in-cart {
    border-color: #1565c0;
    background: #e8f0fe;
  }

  .nh-customize-trigger.in-cart .tile-add-badge {
    opacity: 1;
    background: #2e7d32;
  }

  .nh-customize-trigger:hover .tile-add-badge {
    opacity: 1;
  }

  .pos-line-note-input {
    width: 100%;
    border: none;
    border-bottom: 1px dashed #bdbdbd;
    font-size: .78rem;
    color: #e65100;
    background: transparent;
    outline: none;
    margin-top: 3px;
    padding: 0 2px;
  }

  .pos-line-note-input::placeholder {
    color: #bdbdbd;
  }

  .pos-discount-row {
    display: flex;
    align-items: center;
    gap: .4rem;
    margin: .25rem 0 .1rem;
  }

  .pos-discount-row input[type="number"] {
    width: 70px;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: .85rem;
    text-align: right;
  }

  .pos-discount-row select {
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 2px 4px;
    font-size: .82rem;
    background: #fafafa;
  }

  .pos-role-header {
    height: 3.25rem;
    background: #1a237e;
    color: #fff;
    display: flex;
    align-items: center;
    padding: 0 1.25rem;
    gap: 1rem;
    font-size: .88rem;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0, 0, 0, .18);
  }

  .pos-role-header strong {
    font-size: 1rem;
    font-weight: 800;
  }

  .pos-role-header .pos-header-right {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: .75rem;
  }

  .pos-role-header a {
    color: rgba(255, 255, 255, .8);
    font-size: .82rem;
  }

  .pos-role-header a:hover {
    color: #fff;
  }

  @media only screen and (max-width: 768px) {
    .pos-shell {
      flex-direction: column;
      height: auto;
      overflow: visible;
    }

    .pos-ticket-panel {
      width: 100%;
      border-right: none;
      border-bottom: 2px solid #e0e0e0;
    }

    .pos-catalog-panel {
      padding-bottom: 2rem;
    }
  }
</style>

<header class="pos-role-header">
  <a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchantId ?>&p=dashboard" title="Back to Dashboard">
    <i class="fas fa-arrow-left"></i>
  </a>
  <div class="hide-on-med-and-down">
    <strong><?= $merchantName ?></strong>
    <span style="opacity:.7;">— Point of Sale</span>
  </div>
  <div class="pos-header-right">
    <span style="opacity:.65;"><span class="hide-on-med-and-down"><?= $userName ?> &middot; </span><?= htmlspecialchars(ucfirst($staffRole)) ?></span>
    <? /* render('components/merchant/merchant_nav_menu.php', array('merchant' => $merchant)); */ ?>
  </div>
</header>

<div class="pos-shell" id="pos-shell">

  <aside class="pos-ticket-panel">
    <? render('components/sidenav/shopping_cart.php', array('classList' => 'pos-cart-sidenav-override')); ?>
  </aside>

  <section class="pos-catalog-panel">
    <div class="pos-catalog-search-bar">
      <div class="pos-search-input-wrap">
        <i class="fas fa-search"></i>
        <input type="search" id="pos-catalog-search" placeholder="Search products..." autocomplete="off">
        <button class="btn-flat" id="pos-catalog-clear-search" style="display:none;"><i class="fas fa-times"></i></button>
      </div>

      <div class="category-filter-links">
        <a href="javascript:void(0)" class="pos-category-link active" data-category="all">
          All
        </a>
        <?php foreach ($catalogByCategory as $category => $products): ?>
          <a href="javascript:void(0)" class="pos-category-link" data-category="<?= htmlspecialchars($category) ?>">
            <?= htmlspecialchars($category) ?> (<?= count($products) ?>)
          </a>
        <?php endforeach; ?>
      </div>
    </div>


    <?php if (empty($catalogByCategory)): ?>
      <div class="pos-ticket-empty" style="margin-top:3rem;">
        <i class="fas fa-box-open"></i>
        No active products in catalog.<br>
        <a href="/?app=neighborhub&view=merchant&merchant_id=<?= $merchantId ?>&p=products"
          class="btn blue darken-2 waves-effect waves-light" style="margin-top:1rem;">
          <i class="fas fa-plus"></i> Add Products
        </a>
      </div>
    <?php else: ?>
      <?php foreach ($catalogByCategory as $category => $products): ?>
        <div class="pos-category-label pos-category-section" data-category="<?= htmlspecialchars($category) ?>">
          <?= htmlspecialchars($category) ?>
          <span style="font-weight:400; opacity:.7;">(<?= count($products) ?>)</span>
        </div>
        <div class="pos-product-grid pos-category-grid">
          <?php foreach ($products as $prod):
            $prodId    = intval($prod['id']);
            $meta = is_string($prod['meta']) ? json_decode($prod['meta'], true) : $prod['meta'];
            $isCustomizable = (isset($meta['form_builder']['steps']));
            $prodName  = htmlspecialchars($prod['name']);
            $prodPrice = floatval($prod['price']);
            $prodImg   = htmlspecialchars($prod['image_url'] ?? '');
            $prodDesc  = htmlspecialchars($prod['description'] ?? '');
            $searchStr = strtolower($prod['name'] . ' ' . ($prod['tags'] ?? ''));
            $productType = $prod['type'] ?? 'default';
          ?>
            <div class="nh-customize-trigger"
              id="pos-tile-<?= $prodId ?>"
              data-product-id="<?= $prodId ?>"
              data-search="<?= htmlspecialchars($searchStr) ?>"
              data-id="<?php echo $prod['id']; ?>"
              data-name="<?php echo htmlspecialchars($prod['name']); ?>"
              data-type="<?php echo htmlspecialchars($productType); ?>"
              data-price="<?php echo $prod['price']; ?>"
              data-merchant-image="<?php echo $merchant->image_url; ?>"
              data-merchant-id="<?php echo $merchant->id; ?>"
              data-merchant-name="<?php echo $merchant->business_name; ?>"
              data-merchant-address="<?php echo $merchant->address; ?>"
              data-merchant-lat="<?php echo $merchant->latitude; ?>"
              data-merchant-lon="<?php echo $merchant->longitude; ?>"
              title="<?= $prodDesc ?>">
              <?php if ($prodImg): ?>
                <img src="<?= $prodImg ?>" class="tile-img" alt="<?= $prodName ?>" loading="lazy">
              <?php else: ?>
                <div class="tile-img-placeholder"><i class="fas fa-image" style="font-size:1.6rem;"></i></div>
              <?php endif; ?>
              <div class="tile-name"><?= $prodName ?></div>
              <div class="tile-price">$<?= number_format($prodPrice, 2) ?></div>
              <div class="tile-add-badge"><i class="fas fa-plus"></i></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

</div>


<div id="nh-custom-builder-modal" class="modal mb-modal-fixed" style="border-radius: 8px;">

  <div class="modal-inner-overlay"></div>

  <div class="modal-header" style="max-width: 800px;">
    <h4 id="builder-modal-title" style="font-weight: 700; margin-top: 0;">Build Custom Selection</h4>
    <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row;margin-bottom: 10px;">
      <span class="grey-text text-darken-2" style="font-size: 13px; font-weight: 600; margin-right: 4px;">Quantity </span>
      <div style="display: flex; align-items: center; background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; padding: 2px;">
        <button class="btn-flat nh-card-qty-minus" style="margin: 0.5em; padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
        <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 600;-webkit-appearance: none; margin: 0; -moz-appearance: textfield;">
        <button class="btn-flat nh-card-qty-plus" style="margin: 0.5em; padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
      </div>
    </div>
  </div>
  <div class="modal-content" style="padding: 24px; max-width: 700px;">
    <p class="grey-text text-darken-1" style="margin-bottom: 20px;">Personalize your recipe modifiers below.</p>

    <div id="builder-widget-mount-viewport"></div>
  </div>


  <div class="row" style="width:100%; margin: 0; padding: 0 24px 15px; ">
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


  <div class="modal-footer" style="display: flex; align-items: center; justify-content: space-between; padding: 0 24px;">
    <div>
      <h5 style="margin:0; font-size: 15px; color:#555; font-weight:600;">Total Item Cost:</h5>
      <span class="live-builder-total teal-text text-darken-2" style="font-weight: 800; font-size: 20px;">$0.00</span>
    </div>
    <div>
      <a href="#!" class="modal-close waves-effect waves-red btn-flat" style="margin-right: 8px;">Cancel</a>
      <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light orange" style="font-weight:600;">
        Add To Basket <i class="fas fa-plus right"></i>
      </button>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Initialize standard merchant storefront interactions
    window.storefront = new MerchantStorefront({
      toastIcon: 'fa-check-circle',
      toastVerb: 'basket',
      checkoutReturnUrl: '<?= config('base_url') . '/?app=neighborhub&view=merchant&p=pos&merchant_id=' . $merchantId ?>'
    });

    let activeCategory = 'all';

    // Central filter method matching both search query and active category
    function filterCatalog() {
      const searchTerm = $('#pos-catalog-search').val().toLowerCase().trim();

      $('.pos-category-section').each(function() {
        const categorySection = $(this);
        const categoryName = categorySection.data('category');
        const productGrid = categorySection.next('.pos-category-grid');

        const categoryMatches = (activeCategory === 'all' || activeCategory === categoryName);
        let matchesInGrid = 0;

        if (categoryMatches) {
          productGrid.find('.nh-customize-trigger').each(function() {
            const productTile = $(this);
            const productSearchData = (productTile.data('search') || '').toLowerCase();

            if (!searchTerm || productSearchData.indexOf(searchTerm) !== -1) {
              productTile.show();
              matchesInGrid++;
            } else {
              productTile.hide();
            }
          });
        } else {
          productGrid.find('.nh-customize-trigger').hide();
        }

        if (categoryMatches && matchesInGrid > 0) {
          categorySection.show();
          productGrid.show();
        } else {
          categorySection.hide();
          productGrid.hide();
        }
      });
    }

    const catelogPanel = $('section.pos-catalog-panel')[0];
    catelogPanel.addEventListener('scroll', () => {
      console.log(catelogPanel);
      if (catelogPanel.scrollTop > 40) {
        document.body.classList.add('scrolled');
      } else {
        document.body.classList.remove('scrolled');
      }
    });

    // Add .scrolled to body if document scroll position is not 0
    if (window.scrollY > 0) {
      document.body.classList.add('scrolled');
    }

    // Handle Input Typing
    $('#pos-catalog-search').on('input', function() {
      if ($(this).val().length > 0) {
        $('#pos-catalog-clear-search').show();
      } else {
        $('#pos-catalog-clear-search').hide();
      }
      filterCatalog();
    });

    // Handle Clear Search Button Click
    $('#pos-catalog-clear-search').on('click', function() {
      $('#pos-catalog-search').val('');
      $(this).hide();
      filterCatalog();
    });

    // Handle Category Pill Clicks
    $('.pos-category-link').on('click', function(e) {
      e.preventDefault();
      $('.pos-category-link').removeClass('active');
      $(this).addClass('active');

      activeCategory = $(this).data('category');
      filterCatalog();
    });
  });
</script>