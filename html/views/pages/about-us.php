<?
if (!defined('MB_RUNNING')) exit;
/**
 * About Us Page - Kammy's Kafe (Jonesboro, IN)
 */
$nh = App::getInstance('neighborhub');
$nh->includeModel('merchant');
$merchant = Merchant::getMerchantById(1);

// Gallery Image Array - Add your image URLs and captions here
$galleryImages = [
  [
    'url' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80',
    'title' => 'Warm Dinning Room',
    'caption' => 'A cozy, welcoming spot right in the heart of downtown Jonesboro.'
  ],
  [
    'url' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=800&q=80',
    'title' => 'Hearty Hoosier Breakfasts',
    'caption' => 'Thick-cut bacon, cooked-to-order eggs, and fresh biscuits & gravy.'
  ],
  [
    'url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80',
    'title' => 'Giant Hand-Breaded Tenderloins',
    'caption' => 'Made the traditional Indiana way—crispy, massive, and delicious.'
  ],
  [
    'url' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=800&q=80',
    'title' => 'Fresh Oven-Baked Pizzas',
    'caption' => 'Loaded toppings and baked fresh for lunch, dinner, or carryout.'
  ],
  [
    'url' => 'https://images.unsplash.com/photo-1509722747041-616f39b57569?auto=format&fit=crop&w=800&q=80',
    'title' => 'Homestyle Favorites & Specials',
    'caption' => 'Generous portions cooked fresh with real ingredients every single day.'
  ],
  [
    'url' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80',
    'title' => 'Good Friends & Great Food',
    'caption' => 'Serving our neighbors in Grant County with a smile.'
  ]
];
?>

<style>
  .merchant-header-image {
    position: fixed;
    transition: all 0.3s;
    top: 5rem;
    display: flex;
    align-items: center;
    width: 180px;
    left: calc(50% - 90px);
    z-index: 999;
  }

  .merchant-header-image img {
    margin: 0 auto;
    border: 4px solid var(--brand-gold);
    border-radius: 50%;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
    transition: all 0.4s;
    left: auto;
  }

  body.scrolled .merchant-header-image {
    top: 2px;
    width: 100px;
    left: calc(50% - 50px);
  }

  body.scrolled .merchant-header-image img {
    border: 3px solid var(--brand-gold);
  }

  .merchant-header-image .online-status-dot {
    width: 20px;
    height: 20px;
    bottom: 16px;
    right: 11px;
  }

  body.scrolled .merchant-header-image .online-status-dot {
    width: 14px;
    height: 14px;
    bottom: 7px;
    right: 1px;
  }

  @media (min-width: 768px) {
    .merchant-header-image {
      width: 210px;
      left: calc(50% - 105px);
    }

    .merchant-logo-image {
      width: 310px;
      max-width: 440px;
    }

    .top-right-dropdown-wrapper {
      top: 10.5rem;
      right: 1rem;
      left: auto;
      margin-left: 0;
    }

    .hero-text-overlay-pill {
      width: 45%;
    }
  }

  .kk-about-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.75)),
      url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1600&q=80');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    color: #ffffff;
    padding: 18rem 0 7rem 0;
    text-align: center;
    border-bottom: 4px solid #d97706;
  }

  .kk-section-title {
    font-weight: 800;
    color: #1c1917;
    margin-bottom: 0.5rem;
  }

  .kk-gold-accent {
    color: #d97706;
  }

  .kk-feature-card {
    border-radius: 12px;
    border: 1px solid #e7e5e4;
    padding: 24px;
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .kk-feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
  }

  .kk-gallery-card {
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 24px;
    transition: transform 0.2s ease;
  }

  .kk-gallery-card:hover {
    transform: scale(1.02);
  }

  .kk-gallery-img {
    height: 220px;
    width: 100%;
    object-fit: cover;
    cursor: pointer;
  }

  .kk-hours-badge {
    background-color: #fef3c7;
    color: #92400e;
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-block;
  }

  /* Ensure the Materialize overlay and active image sit above all CSS stacking contexts */
  /* Force the backdrop overlay to cover the full viewport unconditionally */
  /* Force the overlay to cover the full viewport */
  /* 1. Ensure the backdrop covers the full viewport */
  #materialbox-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 9998 !important;
  }

  /* 2. Elevate the active image cleanly above all elements */
  .materialboxed.active {
    z-index: 9999 !important;
  }

  /* 3. CRITICAL: Remove CSS transforms and relative positioning from parent cards. 
      This prevents Materialize from calculating wrong negative offsets when opening/closing. */
  .kk-gallery-card,
  .card {
    transform: none !important;
    position: static !important;
  }

  /* 4. Optional: If you want a subtle hover effect without breaking Materialize coordinates, 
      use box-shadow instead of transform scale */
  .kk-gallery-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
  }

  /* Prevent parent cards from creating conflicting stacking contexts when materialbox is active */
  .card:has(.materialboxed.active),
  .kk-gallery-card:has(.materialboxed.active) {
    z-index: 10000 !important;
    transform: none !important;
  }
</style>
<div class="app-container">

  <!-- HERO HEADER -->
  <div class="kk-about-hero">
    <div class="container">
      <span class="kk-hours-badge mb-2">117 E 4th St • Jonesboro, IN</span>
      <h3 style="font-weight: 900; margin-top: 15px;">Welcome to Kammy's Kafe</h3>
      <p class="flow-text light-blue-text text-lighten-5" style="max-width: 750px; margin: 0 auto;">
        Hearty homestyle cooking, generous portions, and friendly neighborhood service right in the heart of Grant County.
      </p>
    </div>
  </div>

  <div class="container" style="margin-top: 50px; margin-bottom: 60px;">

    <!-- STORY SECTION -->
    <div class="row align-items-center">
      <div class="col s12 m6">
        <h4 class="kk-section-title">Good Food, Big Portions & Real Hoosier Hospitality</h4>
        <p class="grey-text text-darken-2" style="font-size: 1.05rem; line-height: 1.7;">
          At <strong>Kammy’s Kafe</strong>, we believe that a great meal starts with real ingredients and plenty of care. Whether you’re stopping in early for our famous thick-cut bacon breakfast, grabbing a giant hand-breaded tenderloin for lunch, or bringing home hot pizzas and subs for the family, we treat every guest like a regular.
        </p>
        <p class="grey-text text-darken-2" style="font-size: 1.05rem; line-height: 1.7;">
          Located on 4th Street in downtown Jonesboro, our mission is simple: serve delicious, scratch-made comfort food at prices that make sense for our neighbors.
        </p>
        <a href="/?app=neighborhub&view=customer&p=merchant_products&merchant_id=1"
          class="btn-large waves-effect waves-light green darken-2"
          style="border-radius: 8px; font-weight: 700; margin-top: 15px;">
          <i class="fas fa-utensils left"></i> View Our Full Menu
        </a>
      </div>

      <div class="col s12 m6 center-align" style="margin-top: 20px;">
        <div class="card z-depth-2" style="border-radius: 12px; overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?auto=format&fit=crop&w=800&q=80"
            class="responsive-img"
            style="display: block; width: 100%; max-height: 380px; object-fit: cover;"
            alt="Kammy's Kafe Cooking">
        </div>
      </div>
    </div>

    <hr style="border: 0; border-top: 1px dashed #e7e5e4; margin: 50px 0;">

    <!-- FEATURE HIGHLIGHTS -->
    <div class="row">
      <div class="col s12 center-align" style="margin-bottom: 30px;">
        <h4 class="kk-section-title">What Makes Us Special</h4>
        <p class="grey-text text-darken-1">Why our local regulars keep coming back week after week.</p>
      </div>

      <!-- Highlight 1 -->
      <div class="col s12 m4">
        <div class="card white kk-feature-card center-align z-depth-1">
          <i class="fas fa-bacon fa-3x kk-gold-accent" style="margin-bottom: 15px;"></i>
          <h6 style="font-weight: 800; font-size: 1.2rem;">Thick-Cut Bacon & Breakfasts</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem; line-height: 1.6;">
            No paper-thin slices here. We serve thick, full-flavor bacon, hot egg platters, pancakes, and breakfast bowls cooked fresh to order.
          </p>
        </div>
      </div>

      <!-- Highlight 2 -->
      <div class="col s12 m4">
        <div class="card white kk-feature-card center-align z-depth-1">
          <i class="fas fa-hamburger fa-3x kk-gold-accent" style="margin-bottom: 15px;"></i>
          <h6 style="font-weight: 800; font-size: 1.2rem;">Hand-Breaded Tenderloins</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem; line-height: 1.6;">
            A true Indiana classic! Made big, crispy, and cooked to golden perfection every single time.
          </p>
        </div>
      </div>

      <!-- Highlight 3 -->
      <div class="col s12 m4">
        <div class="card white kk-feature-card center-align z-depth-1">
          <i class="fas fa-pizza-slice fa-3x kk-gold-accent" style="margin-bottom: 15px;"></i>
          <h6 style="font-weight: 800; font-size: 1.2rem;">Pizzas, Subs & Daily Specials</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem; line-height: 1.6;">
            From specialty pizzas and Philly steak subs to monster baked potatoes, we have something hearty for everyone.
          </p>
        </div>
      </div>
    </div>

    <hr style="border: 0; border-top: 1px dashed #e7e5e4; margin: 50px 0;">

    <!-- DYNAMIC IMAGE GALLERY -->
    <div class="row">
      <div class="col s12 center-align" style="margin-bottom: 30px;">
        <h4 class="kk-section-title"><i class="fas fa-camera kk-gold-accent"></i> Life at the Kafe</h4>
        <p class="grey-text text-darken-1">A quick look at our kitchen, dishes, and neighborhood atmosphere.</p>
      </div>

      <?php if (!empty($galleryImages)): ?>
        <?php foreach ($galleryImages as $index => $img): ?>
          <div class="col s12 m6 l4">
            <div class="card white kk-gallery-card z-depth-1">
              <div class="card-image">
                <img class="materialboxed kk-gallery-img"
                  src="<?= htmlspecialchars($img['url']) ?>"
                  data-caption="<?= htmlspecialchars($img['title'] . ' - ' . $img['caption']) ?>"
                  alt="<?= htmlspecialchars($img['title']) ?>">
              </div>
              <div class="card-content" style="padding: 14px 16px;">
                <span class="card-title" style="font-size: 1.05rem; font-weight: 700; color: #292524; margin-bottom: 4px;">
                  <?= htmlspecialchars($img['title']) ?>
                </span>
                <p class="grey-text text-darken-1" style="font-size: 0.85rem; margin: 0;">
                  <?= htmlspecialchars($img['caption']) ?>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="center-align grey-text">No images added to gallery yet.</p>
      <?php endif; ?>
    </div>

    <!-- LOCATION & HOURS CARD -->
    <div class="card z-depth-2" style="border-radius: 12px; margin-top: 40px; border-top: 5px solid #d97706; padding: 20px;">
      <div class="card-content">
        <div class="row" style="margin-bottom: 0;">
          <div class="col s12 m6">
            <h5 style="font-weight: 800; margin-top: 0;"><i class="fas fa-map-marker-alt red-text"></i> Visit Us</h5>
            <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 6px;">Kammy's Kafe</p>
            <p class="grey-text text-darken-2" style="margin: 0 0 10px 0;">
              <?= nl2br(htmlspecialchars($merchant->address)) ?>
            </p>
            <p class="grey-text text-darken-2" style="margin: 0;">
              <strong>Phone:</strong> <a href="tel:<?= $merchant->phone ?>" style="color: #0d9488; font-weight: 700;"><?= $merchant->phone ?></a>
            </p>
          </div>

          <div class="col s12 m6" style="margin-top: 15px;">
            <h5 style="font-weight: 800; margin-top: 0;"><i class="fas fa-clock gold-text"></i> Hours of Operation</h5>
            <div class="grey-text text-darken-2" style="margin: 0; line-height: 1.8;">
              <?= nl2br(htmlspecialchars($merchant->store_hours)) ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<script>
  $(document).ready(function() {
    // Initialize Materialize Materialbox Lightbox on Gallery Images
    $('.materialboxed').materialbox();
  });
</script>