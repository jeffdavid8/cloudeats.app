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
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,800;1,600&family=Source+Sans+3:wght@300;400;600;700&display=swap');

  body {
    padding-top: 0;
    background-color: #FFFDF9;
    color: #2c2c2c;
  }

  #nh-terms-banner {
    margin-top: 4rem !important;

  }

  .app-container {
    font-family: 'Source Sans 3', sans-serif;
    font-size: 18px;
  }

  /* Retro Italian Pizzeria Color Palette */
  .green-section {
    color: #FFFDF9;
    background: linear-gradient(135deg, #2E5A27, #1E3D19) !important;
  }

  .red-section {
    color: #FFFDF9;
    background: linear-gradient(135deg, #9E1B1B, #7A1212) !important;
  }

  .green-section a,
  .red-section a {
    color: #FFFDF9;
  }

  .dayMode .green-section .card a,
  .dayMode .red-section .card a,
  .dayMode .green-section .card a i,
  .dayMode .red-section .card a i {
    color: #333333;
  }

  .green-section i,
  .red-section i {
    color: #FFFDF9;
  }

  /* Header adjustments */
  header.header {
    z-index: revert-rule;
  }

  header.header nav {
    transition: all 0.3s;
    box-shadow: none;
    border-bottom: 3px solid #9E1B1B;
  }

  body.nightMode.scrolled header.header nav {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    border-bottom: 3px solid #13131b;
  }

  body.dayMode.scrolled header.header nav {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    border-bottom: 3px solid #ffffff;
  }

  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    top: 35px;
    display: none;
    align-items: center;
    width: 80px;
    right: calc(50% - 80px);
    z-index: 999;
  }

  body.scrolled .merchant-header-image {
    right: calc(50% - 40px);
    display: block;
    top: -5px;
  }

  .merchant-header-image img {
    max-height: 80px;
    border: 2px solid #FFFDF9;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    border-radius: 50%;
  }

  /* Beautiful modern-retro card styles */
  .spotlight-card {
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-top: -2rem;
    z-index: 2;
    position: relative;
  }

  .spotlight-title {
    font-family: 'Playfair Display', serif;
    font-weight: 800;
    color: #1a1a1a;
  }

  .spotlight-price {
    font-family: 'Source Sans 3', sans-serif;
    color: #9E1B1B;
    font-weight: 700;
  }

  /* Button styling */
  .nh-btn {
    border-radius: 30px !important;
    text-transform: none !important;
    font-weight: 600 !important;
    letter-spacing: 0.5px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15) !important;
    transition: transform 0.2s, box-shadow 0.2s !important;
  }

  .nh-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
  }

  /* Brand Header Banner */
  .tyd-header-image {
    text-align: center;
    padding: 2rem 0;
  }

  .tyd-header-image img {
    transition: all .5s ease-in-out;
    display: block;
    margin: 0 auto;
    width: 60%;
    max-width: 500px;
  }

  body.scrolled .tyd-header-image img {
    width: 0%;
    opacity: 0;
  }

  /* Floating Dropdown Button Style */
  .top-right-dropdown-wrapper {
    transition: all 0.3s;
    position: fixed;
    top: 11.5rem;
    left: 50%;
    margin-left: 35%;
    z-index: 885;
  }

  .top-right-dropdown-wrapper a.dropdown-trigger {
    background-color: #9E1B1B !important;
    box-shadow: 0 4px 15px rgba(158, 27, 27, 0.4);
  }

  .top-right-dropdown-wrapper a:hover {
    color: #333;
  }

  body.scrolled .top-right-dropdown-wrapper {
    top: 5rem;
  }

  /* Pizzeria Box footer info design */
  .pizzeria-box-footer {
    background-color: #2E5A27;
    border: 4px dashed #F5A623 !important;
    border-radius: 16px;
    padding: 2rem;
    color: #FFFDF9;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  }

  /* Quantity Controller Styling */
  .qty-selector-container {
    display: inline-flex;
    align-items: center;
    background: #f7f7f7;
    border: 1px solid #e0e0e0;
    border-radius: 30px;
    padding: 2px 8px;
  }

  .qty-btn {
    color: #9E1B1B !important;
  }

  .modal button {
    background: none;
  }

  .modal button:hover {
    background: none;
  }

  .modal .qty-selector-container button {
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
    color: var(--brand-text-main);
    margin-bottom: 0.75rem;
    padding-left: 0.75rem;
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
      right: 1.5rem;
      left: auto;
      margin-left: 0;
    }

    .tyd-header-image img {
      width: 85%;
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

<!-- Top Hero Header Section -->
<div class="green-section" style="<?= ($customer->terms_accepted_at) ? 'margin-top: 3rem;' : '' ?> padding: 2rem 0 4rem 0; border-bottom: 6px double #F5A623;">
  <div class="container">
    <div class="tyd-header-image">
      <img class="tyd-header" src="https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/7/12108840-768w.png" alt="Tu Your Door Pizza" />
    </div>
  </div>
</div>

<!-- Spotlight Product Section (Overlapping) -->
<?php if ($spotlightProduct) :
  $spotMeta = is_string($spotlightProduct['meta']) ? json_decode($spotlightProduct['meta'], true) : $spotlightProduct['meta'];
  $isSpotCustom = ($spotlightProduct['type'] !== 'default' && isset($spotMeta['form_builder']['steps']));
?>
  <div class="container" style="margin-top: 4rem;">
    <div class="card spotlight-card">
      <div class="card-content row" style="margin-bottom: 0; padding: 32px;">
        <div class="col s12 m5 center-align">

          <?php if (!empty($spotlightProduct['image_url'])): ?>
            <img class="materialboxed" src="<?php echo htmlspecialchars($spotlightProduct['image_url']); ?>" class="responsive-img z-depth-1" style="border-radius: 12px; max-height: 250px; object-fit: cover; width:100%;">
          <?php else: ?>
            <div class="grey lighten-4 grey-text" style="height: 220px; display: flex; justify-content: center; align-items: center; border-radius: 12px; border: 2px dashed #ddd;">
              <i class="fas fa-pizza-slice fa-4x text-lighten-2" style="color: #ccc;"></i>
            </div>
          <?php endif; ?>
        </div>
        <div class="col s12 m7">

          <h4 class="spotlight-title" style="margin: 4px 0 8px 0;"><?php echo htmlspecialchars($spotlightProduct['name']); ?>
            <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] ?>/?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>&product_id=<?= $spotlightProduct['id'] ?>" style="color: #9E1B1B; margin-left: 10px;">
              <i class="material-icons" style="vertical-align: middle;">share</i>
            </a>
          </h4>

          <h5 class="spotlight-price" style="margin: 0 0 16px 0;">$<?php echo number_format($spotlightProduct['price'], 2); ?></h5>
          <p class="grey-text text-darken-3" style="font-size: 16px; line-height: 1.6; margin-bottom: 24px;"><?php echo nl2br(htmlspecialchars($spotlightProduct['description'])); ?></p>

          <? if ($merchant->status == 'online') : ?>
            <div class="card-action" style="padding: 0; border: none; ">

              <div class="quantity-pill" style="margin-right: 10px;margin-bottom: 1rem;">
                <button class="btn-flat nh-card-qty-minus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
                <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700;-webkit-appearance: none; -moz-appearance: textfield;">
                <button class="btn-flat nh-card-qty-plus" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
              </div>

              <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                <?php if ($isSpotCustom): ?>
                  <button class="nh-btn btn-large waves-effect waves-light nh-customize-trigger"
                    style="margin-bottom: 5px;background-color:#9E1B1B !important;"
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
                <button class="nh-btn btn-large waves-effect waves-light nh-add-standard-btn"
                  style="margin-bottom: 5px;background-color:#2E5A27 !important;"
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
            </div>
          <? endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Products Category Catalog -->
<div class="red-section" style="padding: 3rem 0; margin-top: 2rem; border-top: 4px solid #F5A623;">
  <div class="container">
    <div class="category-heading center" style="margin-bottom: 2rem;">
      <h3 style="font-family: 'Playfair Display', serif; font-weight: 800; font-size: 2em; margin: 0;">Our Menu</h3>
      <div style="width: 80px; height: 3px; background: #F5A623; margin: 15px auto;"></div>
    </div>

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
  </div>
</div>

<!-- Creative Info Footer Section ("The Pizza Box" theme) -->
<div class="" style="padding: 4rem 0;">
  <div class="container">
    <div class="row pizzeria-box-footer">
      <div class="col s12 m7">
        <h4 style="font-family: 'Playfair Display', serif; font-weight: 800; margin-top: 0; color: #F5A623;">Tu Your Door Pizza</h4>
        <p style="font-size: 16px; margin-bottom: 1.5rem; opacity: 0.95;">Serving up Marion's favorite authentic recipes. Hot, fresh, and straight to your door!</p>

        <!-- Address -->
        <p style="display: flex; align-items: center; margin-bottom: 0.75rem;">
          <span style="margin-right: 1rem; width: 1.5rem; text-align: center;">
            <i class="fas fa-map-marker-alt" style="color: #F5A623;"></i>
          </span>
          <span style="word-break: break-word;"><?php echo htmlspecialchars($merchant->address); ?></span>
        </p>

        <!-- Phone -->
        <p style="display: flex; align-items: center; margin-bottom: 0.75rem;">
          <span style="margin-right: 1rem; width: 1.5rem; text-align: center;">
            <i class="fas fa-phone" style="color: #F5A623;"></i>
          </span>
          <span style="font-weight: 600;"><?php echo htmlspecialchars($merchant->phone); ?></span>
        </p>

        <!-- Facebook -->
        <?php if (!empty($merchant->facebook)): ?>
          <p style="display: flex; align-items: center; margin-bottom: 0.75rem;">
            <span style="margin-right: 1rem; width: 1.5rem; text-align: center;">
              <i class="fab fa-facebook" style="color: #F5A623;"></i>
            </span>
            <a href="<?php echo htmlspecialchars($merchant->facebook); ?>" target="_blank" rel="noopener noreferrer" style="color: #FFFDF9; text-decoration: none; border-bottom: 1px dashed #fff;">
              Follow Us on Facebook
            </a>
          </p>
        <?php endif; ?>

        <!-- Website -->
        <?php if (!empty($merchant->website)): ?>
          <p style="display: flex; align-items: center; margin-bottom: 0.75rem;">
            <span style="margin-right: 1rem; width: 1.5rem; text-align: center;">
              <i class="fas fa-globe" style="color: #F5A623;"></i>
            </span>
            <a href="<?php echo htmlspecialchars($merchant->website); ?>" target="_blank" rel="noopener noreferrer" style="color: #FFFDF9;">
              <?php echo str_replace('https://www.', '', htmlspecialchars($merchant->website)); ?>
            </a>
          </p>
        <?php endif; ?>

        <!-- Store Hours -->
        <?php if (!empty($merchant->store_hours)): ?>
          <p style="display: flex; align-items: flex-start; margin-top: 1rem;">
            <span style="margin-right: 1rem; width: 1.5rem; text-align: center; margin-top: 2px;">
              <i class="fas fa-clock" style="color: #F5A623;"></i>
            </span>
            <span><?php echo nl2br(htmlspecialchars($merchant->store_hours)); ?></span>
          </p>
        <?php endif; ?>
      </div>

      <div class="col s12 m5 center-align" style="margin-top: 1.5rem;">
        <img class="responsive-img circle z-depth-2" style="border: 5px solid #F5A623; max-width: 220px;" src="https://storage.googleapis.com/mediabrain-system-data/apps/neighborhub/merchants/7/12108230-232h.png" />
      </div>
    </div>
  </div>
</div>

<!-- Modal Customization Builder Overlay -->
<div id="nh-custom-builder-modal" class="modal mb-modal-fixed" style="border-radius: 16px; overflow: hidden;">
  <div class="modal-inner-overlay"></div>
  <div class="modal-header" style="max-width: 800px; padding: 24px 24px 10px 24px;">
    <h4 id="builder-modal-title" style="font-family: 'Playfair Display', serif; font-weight: 800; margin-top: 0; color: #C53333;">Customize Your Order</h4>

    <div style="display: flex; align-items: center; justify-content: flex-end; flex-direction: row; margin-bottom: 10px;">
      <span class="grey-text text-darken-2" style="font-size: 14px; font-weight: 600; margin-right: 10px;">Quantity</span>
      <div class="qty-selector-container">
        <button class="btn-flat nh-card-qty-minus qty-btn" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-minus fa-xs"></i></button>
        <input type="number" class="nh-card-qty-input" value="1" min="1" style="width: 35px; text-align: center; margin: 0; border: none; height: 28px; font-weight: 700; background: transparent;">
        <button class="btn-flat nh-card-qty-plus qty-btn" style="padding: 0 8px; height: 28px; line-height: 28px;"><i class="fas fa-plus fa-xs"></i></button>
      </div>
    </div>
  </div>

  <div class="modal-content" style="padding: 10px 24px 24px 24px; max-width: 700px;">
    <p class="grey-text text-darken-1" style="margin-bottom: 20px;">Personalize your recipe modifiers below.</p>
    <div id="builder-widget-mount-viewport"></div>
  </div>

  <div class="modal-footer" style="padding: 15px 24px; background: #fafafa; border-top: 1px solid #eee;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
      <h5 style="margin:0; font-size: 14px; color:#555; font-weight:600; text-transform: uppercase; letter-spacing: 0.5px;">Total Item Cost:</h5>
      <span class="live-builder-total red-text text-darken-2" style="font-weight: 800; font-size: 24px;">$0.00</span>
    </div>
    <div style="display: flex; align-items: center;">
      <a href="#!" class="modal-close waves-effect waves-red btn-flat" style="margin-right: 12px; font-weight: 600;">Cancel</a>
      <button id="nh-modal-submit-add-to-cart" class="btn waves-effect waves-light nh-btn" style="background-color: #2E5A27 !important; height: 45px; line-height: 45px; padding: 0 24px;">
        Add To Basket <i class="fas fa-plus right"></i>
      </button>
    </div>
  </div>
</div>

<!-- Floating Dropdown Trigger (Top Right) -->
<div style="position: fixed; top: 4rem; height: 5rem; width: 100%; z-index: 899; pointer-events: none;">
  <div class="container" style="pointer-events: auto;">
    <div class="right">
      <div class="top-right-dropdown-wrapper">
        <a class="dropdown-trigger btn-floating btn-large waves-effect waves-light" href="#" data-target="top-right-menu">
          <i class="fas fa-ellipsis-v"></i>
        </a>

        <!-- Dropdown Menu Structure -->
        <ul id="top-right-menu" class="dropdown-content" style="border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
          <?php foreach ($groupedProducts as $category => $categoryProducts): ?>
            <li><a class="category-menu-item" data-category-anchor="<?= strtolower($category) ?>" href="#!" style="color: #2E5A27; font-weight: 600;"> <?= $category ?> </a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    $('#nh-custom-builder-modal').modal();

    var elems = document.querySelectorAll('.dropdown-trigger');
    var instances = M.Dropdown.init(elems, {
      alignment: 'right',
      constrainWidth: false,
      coverTrigger: false
    });

    $('#top-right-menu a.category-menu-item').on('click', function(e) {
      const cleanId = this.dataset.categoryAnchor.toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-');
      $('html, body').animate({
        scrollTop: $('#' + cleanId).offset().top - 100
      }, 800);
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

    // Qty plus/minus selectors
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

    // Standard item add
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
        html: `<i class="fas fa-check-circle" style="color:#FFFDF9; margin-right:8px;"></i> Added (${cardQty}) ${productInfo.name} to basket!`
      });
    });

    // Customize dialog launch
    $('.nh-customize-trigger').on('click', function() {
      const btn = $(this);
      const productId = btn.data('id');
      const productName = btn.data('name');
      const price = btn.data('price');
      const merchantImage = btn.data('merchant-image');
      const merchantId = btn.data('merchant-id');
      const merchantName = btn.data('merchant-name');
      const merchantAddress = btn.data('merchant-address');
      const merchantLat = btn.data('merchant-lat');
      const merchantLon = btn.data('merchant-lon');

      const initialCardQty = parseInt(btn.closest('.card-action').find('.nh-card-qty-input').val() || 1);

      nh.cart.activeCustomProductMetadata = {
        id: productId,
        name: productName,
        price: price,
        merchantImage: merchantImage,
        merchantId: merchantId,
        merchantName: merchantName,
        merchantAddress: merchantAddress,
        merchantLat: merchantLat,
        merchantLon: merchantLon,
      };
      $('#builder-modal-title').text('Customize Your ' + productName);
      $('#nh-custom-builder-modal .nh-card-qty-input').val(initialCardQty);

      const modalInstance = M.Modal.getInstance(document.getElementById('nh-custom-builder-modal'));
      modalInstance.open();

      $('#builder-widget-mount-viewport').empty();
      nh.cart.activeBuilderInstance = new CustomOrderBuilder('builder-widget-mount-viewport', productId, price);

      btn.closest('.card-action').find('.nh-card-qty-input').val(1);
    });

    // Modal Cart Submissions
    $('#nh-modal-submit-add-to-cart').on('click', function() {
      if (nh.cart.activeBuilderInstance && nh.cart.activeCustomProductMetadata) {
        const receiptBlob = nh.cart.activeBuilderInstance.compileSelections();
        if (!receiptBlob) return;

        const finalModalQty = parseInt($('#nh-custom-builder-modal .nh-card-qty-input').val() || 1);

        NHCart.addItem(nh.cart.activeCustomProductMetadata.merchantId, nh.cart.activeCustomProductMetadata, receiptBlob, finalModalQty);

        $('#nh-shopping-cart-sidenav').sidenav('open');


        M.Modal.getInstance(document.getElementById('nh-custom-builder-modal')).close();
        M.toast({
          html: `<i class="fas fa-check-circle" style="color:#FFFDF9; margin-right:8px;"></i> Added (${finalModalQty}) custom ${nh.cart.activeCustomProductMetadata.name} to basket!`
        });
      }
    });
  });
</script>