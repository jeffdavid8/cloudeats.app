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
/* Ivanhoe's retro diner theme — deliberately scoped to this storefront only. */
.ivanhoes-storefront {
  --brand-bg: #fff8e9; --brand-card-bg: #fffdf7; --brand-text-main: #273b3d;
  --brand-text-muted: #647477; --brand-border: rgba(36, 93, 94, .18);
  --brand-nav-bg: #173f43; --brand-nav-border: #d74342; --brand-caramel: #c93237;
  --brand-gold: #e7b94e; --brand-btn-add: #c93237; --brand-btn-add-hover: #a9212a;
  --brand-pill-bg: #e8f4ef; --brand-primary: #c93237; --brand-secondary: #177e83;
  color: var(--brand-text-main); font-family: 'Trebuchet MS', 'Arial Rounded MT Bold', Arial, sans-serif;
  padding-top: 10rem; padding-bottom: 3rem; position: relative;
  background-color: var(--brand-bg);
  background-image: radial-gradient(rgba(23,126,131,.055) .8px, transparent .8px);
  background-size: 14px 14px;
}
.nightMode .ivanhoes-storefront { --brand-bg:#142628; --brand-card-bg:#1c3335; --brand-text-main:#f9f0dc; --brand-text-muted:#c2d2cc; --brand-border:rgba(231,185,78,.24); --brand-pill-bg:#294344; --brand-nav-bg:#0b1d1f; --brand-nav-border:#e45b55; --brand-caramel:#f06a60; --brand-gold:#e7b94e; --brand-btn-add:#c93237; --brand-btn-add-hover:#a9212a; }
.ivanhoes-storefront .container { width: min(94%, 1320px); }
.ivanhoes-storefront .iv-hero { margin: 1rem 0 2rem; padding: clamp(22px,4vw,42px); text-align:center; border-radius:20px; color:#fff9e9; overflow:hidden; position:relative; background:linear-gradient(130deg,#173f43 0%,#177e83 58%,#23565a 100%); box-shadow:0 12px 28px rgba(24,61,62,.18); border:4px solid #e7b94e; }
.ivanhoes-storefront .iv-hero:before,.ivanhoes-storefront .iv-hero:after { content:""; position:absolute; left:0; right:0; height:9px; background:repeating-linear-gradient(90deg,#fff8e9 0 18px,#c93237 18px 36px); opacity:.95; }
.ivanhoes-storefront .iv-hero:before {top:0}.ivanhoes-storefront .iv-hero:after{bottom:0}
.ivanhoes-storefront .iv-kicker { display:inline-block; font-size:11px; font-weight:900; letter-spacing:3px; text-transform:uppercase; color:#ffe49b; margin:0 0 10px; }
.ivanhoes-storefront .iv-hero h2 { color:#fff9e9; font-family:Georgia,'Times New Roman',serif; font-size:clamp(2rem,5vw,3.8rem); line-height:1.04; font-weight:900; margin:0 0 12px; text-shadow:0 3px 0 rgba(0,0,0,.12); }
.ivanhoes-storefront .iv-hero p { max-width:720px; margin:0 auto; font-size:1rem; line-height:1.7; color:#f7f4e8; }
.ivanhoes-storefront .iv-starline { color:#ffe49b; letter-spacing:6px; font-size:18px; margin-top:14px; }
.ivanhoes-storefront .sweets-card,.ivanhoes-storefront .product-item-card { background:var(--brand-card-bg)!important; color:var(--brand-text-main); border:1px solid var(--brand-border)!important; border-radius:16px!important; overflow:hidden; box-shadow:0 6px 18px rgba(24,61,62,.09)!important; }
.ivanhoes-storefront .sweets-text-brown,.ivanhoes-storefront .card-title { color:var(--brand-text-main)!important; }
.ivanhoes-storefront .sweets-text-muted {color:var(--brand-text-muted)!important}
.ivanhoes-storefront .sweets-icon {color:var(--brand-caramel)!important}
.ivanhoes-storefront .secondary-category-menu { background:var(--brand-card-bg)!important; border:2px solid var(--brand-border); border-top:5px solid var(--brand-caramel); border-radius:15px!important; padding:1rem; }
.ivanhoes-storefront .secondary-category-menu .menu-title { color:var(--brand-caramel); font-weight:900; text-transform:uppercase; letter-spacing:1.5px; font-size:.8rem; margin:0 0 .7rem; }
.ivanhoes-storefront .secondary-category-menu ul {margin:0;padding:0;list-style:none}
.ivanhoes-storefront .secondary-category-menu ul li {margin-bottom:4px}
.ivanhoes-storefront .secondary-category-menu a.category-menu-item {display:block;padding:10px 12px;border-radius:9px;color:var(--brand-text-main)!important;font-weight:800;text-decoration:none;transition:background .2s,transform .2s}
.ivanhoes-storefront .secondary-category-menu a.category-menu-item:hover {background:var(--brand-pill-bg)!important;transform:translateX(3px)}
.ivanhoes-storefront .secondary-category-menu a.category-menu-item.active {background:var(--brand-primary)!important;color:#fff!important}
.ivanhoes-storefront .iv-section-title {font-family:Georgia,'Times New Roman',serif;font-weight:900!important;color:var(--brand-text-main);border-bottom:3px dashed var(--brand-border);padding-bottom:12px;margin:2.4rem 0 1.2rem!important;}
.ivanhoes-storefront .iv-section-title:before {content:'✦ ';color:var(--brand-caramel)}
.ivanhoes-storefront .product-item-card {height:100%;display:flex;flex-direction:column;transition:transform .22s,box-shadow .22s;border-bottom:4px solid var(--brand-gold)!important}
.ivanhoes-storefront .product-item-card:hover {transform:translateY(-4px);box-shadow:0 13px 25px rgba(24,61,62,.15)!important}
.ivanhoes-storefront .iv-product-image {height:210px;position:relative;overflow:hidden;background:linear-gradient(135deg,#f8eacb,#e3f0e9);}
.ivanhoes-storefront .iv-product-image img {width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s}
.ivanhoes-storefront .product-item-card:hover .iv-product-image img {transform:scale(1.04)}
.ivanhoes-storefront .iv-price-ribbon {position:absolute;right:12px;bottom:12px;background:var(--brand-caramel);color:white;font-weight:900;padding:7px 12px;border-radius:20px;box-shadow:0 3px 8px #0002}
.ivanhoes-storefront .iv-product-body {padding:17px!important;flex:1;position:relative}
.ivanhoes-storefront .iv-product-name {font-family:Georgia,'Times New Roman',serif;font-size:1.22rem;line-height:1.25;font-weight:900;margin:0 0 8px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.ivanhoes-storefront .iv-product-name a {font-size:16px;color:var(--brand-secondary)!important;flex-shrink:0}
.ivanhoes-storefront .iv-product-description {font-size:13px;line-height:1.6;color:var(--brand-text-muted);margin:0;overflow-wrap:anywhere}
.ivanhoes-storefront .iv-product-description.clamped {display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.ivanhoes-storefront .iv-card-action {padding:13px 15px 16px;border-top:1px dashed var(--brand-border);background:color-mix(in srgb,var(--brand-pill-bg) 42%,transparent)}
.ivanhoes-storefront .iv-quantity-row {display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.ivanhoes-storefront .iv-quantity-label {font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.6px;color:var(--brand-text-muted)}
.ivanhoes-storefront .quantity-pill {display:flex;align-items:center;gap:2px;background:var(--brand-pill-bg);border:1px solid var(--brand-border);border-radius:24px;padding:3px 7px}
.ivanhoes-storefront .quantity-pill button {color:var(--brand-secondary)!important;min-width:26px}
.ivanhoes-storefront .quantity-pill input {color:var(--brand-text-main)!important;background:transparent!important}
.ivanhoes-storefront .iv-action-btn {width:100%;border-radius:24px!important;font-weight:900!important;letter-spacing:.4px;text-transform:uppercase;box-shadow:none!important;margin:4px 0!important;}
.ivanhoes-storefront .nh-add-standard-btn,.ivanhoes-storefront .btn-custom-add {background:var(--brand-caramel)!important;color:#fff!important}
.ivanhoes-storefront .nh-customize-trigger,.ivanhoes-storefront .btn-custom-customize {background:var(--brand-secondary)!important;color:#fff!important}
.ivanhoes-storefront .nh-add-standard-btn:hover,.ivanhoes-storefront .btn-custom-add:hover {background:var(--brand-btn-add-hover)!important}
.ivanhoes-storefront .top-right-dropdown-wrapper a.dropdown-trigger {background:var(--brand-caramel)!important;border:3px solid #fff8e9;box-shadow:0 5px 14px #0002}
.ivanhoes-storefront .dropdown-content {background:var(--brand-card-bg)!important;border-radius:12px;overflow:hidden}
.ivanhoes-storefront .dropdown-content a {color:var(--brand-text-main)!important;font-weight:700}
.ivanhoes-storefront #nh-custom-builder-modal {background:var(--brand-card-bg)!important;border:3px solid var(--brand-gold)!important;border-radius:18px!important;overflow:hidden}
.ivanhoes-storefront #nh-custom-builder-modal .modal-header,.ivanhoes-storefront #nh-custom-builder-modal .modal-footer {background:var(--brand-bg)!important}
.ivanhoes-storefront #nh-custom-builder-modal .modal-content {background:var(--brand-card-bg)!important}
.ivanhoes-storefront .sticky-column {position:sticky;top:7rem;z-index:10;max-height:calc(100vh - 20px);overflow-y:auto}
.ivanhoes-storefront .iv-contact-link {color:var(--brand-text-main)!important;text-transform:none!important;font-weight:700;white-space:normal;height:auto;line-height:1.5;padding:8px 0;display:flex;align-items:flex-start}
.ivanhoes-storefront .iv-contact-link i {color:var(--brand-caramel);margin-right:12px;min-width:18px;margin-top:3px}
.ivanhoes-storefront .iv-menu-intro {font-size:12px;text-transform:uppercase;letter-spacing:2px;font-weight:900;color:var(--brand-caramel);margin:0 0 18px}
.ivanhoes-storefront .iv-spotlight-banner {background:linear-gradient(110deg,var(--brand-caramel),#e7b94e);color:#fff;padding:12px 20px;font-weight:900;text-transform:uppercase;letter-spacing:1px}
.ivanhoes-storefront .nh-card-qty-input::-webkit-outer-spin-button,.ivanhoes-storefront .nh-card-qty-input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.ivanhoes-storefront .nh-card-qty-input{-moz-appearance:textfield}
.ivanhoes-storefront .mb-modal-fixed .modal-inner-overlay{z-index:initial}
@media(max-width:767px){.ivanhoes-storefront{padding-top:8rem}.ivanhoes-storefront .container{width:94%}.ivanhoes-storefront .iv-product-image{height:195px}.ivanhoes-storefront .iv-hero{border-radius:14px}.ivanhoes-storefront .card-content{padding:20px!important}.ivanhoes-storefront .top-right-dropdown-wrapper{right:1rem}}
@media(min-width:768px){.ivanhoes-storefront .iv-product-image{height:220px}}
</style>

<div class="ivanhoes-storefront">
<div class="container">
  <!-- Ivanhoe's welcome panel: merchant name and business details are data-driven. -->
  <section class="iv-hero" aria-label="Welcome to the diner">
    <div class="iv-kicker">A little hometown happiness</div>
    <h2><?= htmlspecialchars($merchant->business_name, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Good food, sweet treats, and the kind of hometown stop that makes a day better. Browse the menu and find a new favorite.</p>
    <div class="iv-starline" aria-hidden="true">★ ★ ★ ★ ★</div>
  </section>

  <div class="card horizontal sweets-card" style="border-top:5px solid var(--brand-caramel);">
    <div class="card-stacked"><div class="card-content" style="padding:24px 28px;">
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
    </div></div>
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
            <a class="page_link waves-effect waves-light" style="color: var(--brand-caramel);" href="<?= htmlspecialchars($this->config['base_url'], ENT_QUOTES, 'UTF-8') ?>/?app=neighborhub&amp;view=customer&amp;p=merchant_products&amp;merchant_id=<?= rawurlencode((string)$merchant->id) ?>&amp;menu_id=<?= rawurlencode((string)$activeMenuId) ?>&amp;product_id=<?= rawurlencode((string)$spotlightProduct['id']) ?>"><i class="material-icons">share</i></a>
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
        render('pages/customer/merchant_'.$merchant->id.'/ivanhoes_product_category.php', array(
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
          <a class="dropdown-trigger btn-floating btn-large waves-effect waves-light" href="#" data-target="top-right-menu">
            <i class="fas fa-utensils"></i>
          </a>

          <!-- Dropdown Categories -->
          <ul id="top-right-menu" class="dropdown-content">
            <?php foreach ($menus[$activeMenuId]['categories'] as $category):
              $cleanId = preg_replace('/\s+/', '-', preg_replace('/[^a-z0-9\s-]/', '', str_replace('&', 'and', strtolower($category['name']))));
            ?>
              <li class="category-menu-item-back-to-top"><a class="category-menu-item" data-category-anchor="top" href="#!">Back to Top</a></li>
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

      $(document).ready(function() {
        // Initialize standard merchant storefront interactions
        window.storefront = new MerchantStorefront({
          toastIcon: 'fa-check-circle', // Customize icon per theme if desired (e.g. 'fa-fire-alt')
          toastVerb: 'order' // Customize text per theme if desired (e.g. 'order')
        });
      });
    });
  </script>