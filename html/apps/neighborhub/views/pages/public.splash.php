<style>
  /* 1. Hide the PC-only shortcut container by default (Mobile-first approach) */
  #fallback-shortcut-container {
    display: none !important;
  }

  /* 2. Show it ONLY if the user is on a desktop PC with a mouse/trackpad */
  @media (hover: hover) and (pointer: fine) {
    #fallback-shortcut-container {
      display: block !important;
      /* Overrides the hidden state on PC */
      margin-top: 1.5rem;
      padding: 1rem;
      border: 1px dashed #ccc;
      border-radius: 6px;
      background-color: #f9f9f9;
      text-align: left;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
    }
  }

  .hero-section {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #fff;
    padding: 80px 0;
  }

  .icon-box {
    font-size: 2.5rem;
    margin-bottom: 1rem;
  }

  .card-feature {
    height: 100%;
    border-radius: 8px;
    padding: 1.5rem;
    min-height: 475px;
  }

  .badge-pill {
    background-color: #e0e7ff;
    color: #4338ca;
    padding: 4px 12px;
    border-radius: 16px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
  }
</style>

<div class="container" style="display: flex; flex-direction: column; align-items: center; justify-content: flex-start; text-align: center; margin-top: 2rem;">
  <? /* <a href="?app=neighborhub">
    <img class="responsive-img" src="/apps/neighborhub/images/neighborhub-app-index-logo-lg.png" />
  </a> */ ?>
  <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 1rem;">
    <!-- 1. The PWA Button (Hidden by default, shown ONLY if Chrome/Edge triggers it) -->
    <button id="install-button" class="btn" style="display: none; font-size: 1rem; background-color: #e65100 !important; color: white !important; border: none; border-radius: 4px; cursor: pointer;">
      <i class="fas fa-play left"></i> Install
    </button>
    <a href="?app=neighborhub&view=customer" class="btn green white-text" style="font-size: 1rem; color: white !important; border: none; border-radius: 4px; cursor: pointer;"><i class="fas fa-rocket"></i> Launch</a>
  </div>

  <p style="margin-top: 1rem">Local food, local products, local services, local businesses, local people.</p>
  <!-- 2. The Firefox/Fallback Container (Shown by default, hidden if PWA is supported) -->
  <!-- Cleaned up container container tag -->
</div>

<section class="center-align" style="padding: 60px 0;">
  <div id="fallback-shortcut-container">
    <h3 style="margin-top: 0; font-size: 1.1rem; color: #333;">Add to Desktop or Bookmarks</h3>

    <!-- Copy Link Button -->
    <button id="copy-link-btn" style="width: 100%; padding: 0.5rem; background-color: #007bc2; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-bottom: 0.5rem;">
      📋 Copy App Link
    </button>
    <span id="copy-status" style="display: none; color: green; font-size: 0.85rem; font-weight: bold; display: block; margin-bottom: 0.5rem; text-align: center;"></span>

    <!-- Quick Instructions -->
    <ul style="font-size: 0.9rem; color: #555; padding-left: 20px; margin-bottom: 0;">
      <li style="margin-bottom: 4px;"><strong>Bookmark:</strong> Press <kbd style="background:#eee; padding:2px 4px; border-radius:3px;">Ctrl</kbd> + <kbd style="background:#eee; padding:2px 4px; border-radius:3px;">D</kbd> to save this page.</li>
      <li><strong>Desktop Shortcut:</strong> Drag the 🔒 icon next to the URL bar directly onto your computer desktop!</li>
    </ul>
  </div>

</section>

<!-- HERO SECTION -->
<section class="hero-section center-align">
  <div class="container">
    <span class="badge-pill">Built for Independent Restaurants</span>
    <h3 style="font-weight: 800; margin-top: 15px;">Stop Losing 30% of Your Profits to Delivery Apps</h3>
    <p class="flow-text grey-text text-lighten-1" style="max-width: 750px; margin: 0 auto 30px auto;">
      Cloud Eats gives your restaurant its own branded online ordering system. Keep up to 95% of your food revenue, own your customer relationships, and offer better prices.
    </p>
    <a href="#merchants" class="btn-large waves-effect waves-light green darken-1" style="border-radius: 6px; font-weight: 600;">Explore Merchant Benefits</a>
    <a href="#customers" class="btn-large waves-effect waves-light outline light-blue darken-3" style="border-radius: 6px; font-weight: 600; margin-left: 10px;">Why Diners Love It</a>
  </div>
</section>

<!-- SECTION 1: FOR MERCHANTS -->
<section id="merchants" style="padding: 60px 0;">
  <div class="container">
    <div class="center-align" style="margin-bottom: 40px;">
      <span class="blue-text text-darken-2" style="font-weight: 700; text-transform: uppercase;">For Restaurant Owners</span>
      <h4 style="font-weight: 700; margin-top: 5px;">Take Back Control of Your Business</h4>
      <p class="grey-text text-darken-1">Everything you need to accept online orders directly from your custom website.</p>
    </div>

    <div class="row">
      <!-- Feature 1 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box green-text"><i class="fas fa-hand-holding-usd"></i></div>
          <h6 style="font-weight: 700;">Zero Commissions</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Stop paying 15%–30% per order to third-party marketplaces. You earned the revenue—keep it in your bank account.
          </p>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box blue-text"><i class="fas fa-users"></i></div>
          <h6 style="font-weight: 700;">100% Customer Data Ownership</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Build your own direct email and SMS subscriber lists. Reach out to regular guests with promos and menu updates whenever you want.
          </p>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box orange-text"><i class="fas fa-globe"></i></div>
          <h6 style="font-weight: 700;">Your Brand, Your Domain</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Run your online store under your custom domain without competing alongside rival restaurants on a shared marketplace app.
          </p>
        </div>
      </div>

      <!-- Feature 4 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box purple-text"><i class="fas fa-bolt"></i></div>
          <h6 style="font-weight: 700;">Direct & Fast Payouts</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Funds go directly into your merchant processor account. Enjoy standard rolling payouts without third-party payout holds.
          </p>
        </div>
      </div>

      <!-- Feature 5 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box red-text"><i class="fas fa-sliders-h"></i></div>
          <h6 style="font-weight: 700;">Real-Time Menu Controls</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Toggle item and category availability instantly on the fly directly from your dashboard or kitchen display system.
          </p>
        </div>
      </div>

      <!-- Feature 6 -->
      <div class="col s12 m6 l4">
        <div class="card white card-feature center-align z-depth-1">
          <div class="icon-box teal-text"><i class="fas fa-headset"></i></div>
          <h6 style="font-weight: 700;">Dedicated Support</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.95rem;">
            Get direct help from real system technicians instead of automated call centers and chatbot response scripts.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SECTION 2: FOR CUSTOMERS -->
<section id="customers" style="padding: 60px 0;">
  <div class="container">
    <div class="center-align" style="margin-bottom: 40px;">
      <span class="teal-text text-darken-2" style="font-weight: 700; text-transform: uppercase;">For Your Diners</span>
      <h4 style="font-weight: 700; margin-top: 5px;">A Better Way for Locals to Support Local</h4>
      <p class="grey-text text-darken-1">Why customers prefer ordering directly from your custom website.</p>
    </div>

    <div class="row">
      <!-- Benefit 1 -->
      <div class="col s12 m6 l3">
        <div class="center-align" style="padding: 10px;">
          <i class="fas fa-tags teal-text" style="font-size: 2rem; margin-bottom: 10px;"></i>
          <h6 style="font-weight: 700;">No Inflated Menu Prices</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.9rem;">
            Diners enjoy regular in-store menu pricing without the 20% markups found on third-party apps.
          </p>
        </div>
      </div>

      <!-- Benefit 2 -->
      <div class="col s12 m6 l3">
        <div class="center-align" style="padding: 10px;">
          <i class="fas fa-store teal-text" style="font-size: 2rem; margin-bottom: 10px;"></i>
          <h6 style="font-weight: 700;">Direct Local Support</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.9rem;">
            100% of the food purchase price goes straight to supporting their favorite local neighborhood spot.
          </p>
        </div>
      </div>

      <!-- Benefit 3 -->
      <div class="col s12 m6 l3">
        <div class="center-align" style="padding: 10px;">
          <i class="fas fa-utensils teal-text" style="font-size: 2rem; margin-bottom: 10px;"></i>
          <h6 style="font-weight: 700;">Accurate & Fresh Orders</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.9rem;">
            Orders go straight to the kitchen display without middleman delays or broken menu syncs.
          </p>
        </div>
      </div>

      <!-- Benefit 4 -->
      <div class="col s12 m6 l3">
        <div class="center-align" style="padding: 10px;">
          <i class="fas fa-shield-alt teal-text" style="font-size: 2rem; margin-bottom: 10px;"></i>
          <h6 style="font-weight: 700;">Fast & Secure Checkout</h6>
          <p class="grey-text text-darken-1" style="font-size: 0.9rem;">
            Smooth checkout experience on any device, fully optimized for smartphones and tablets.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA FOOTER SECTION -->
<section class="blue-grey darken-4 white-text center-align" style="padding: 50px 0;">
  <div class="container">
    <h4 style="font-weight: 700;">Ready to Upgrade Your Online Ordering?</h4>
    <p class="grey-text text-lighten-1" style="margin-bottom: 25px;">
      Let’s set up your custom domain and menu system today.
    </p>
    <a href="#contact" class="btn-large green darken-1 waves-effect waves-light" style="font-weight: 700;">
      Get Started Now
    </a>
  </div>
</section>


<script>
  // Declare the PWA prompt variable globally
  let deferredPrompt;

  // 1. Listen for the native install prompt (Chrome, Edge, Opera)
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;

    // Since native PWA installation is supported, show the PWA button...
    const installBtn = document.getElementById('install-button');
    if (installBtn) installBtn.style.display = 'block';

    // Hides the PC fallback container if native PWA installation is supported
    const fallbackContainer = document.getElementById('fallback-shortcut-container');
    if (fallbackContainer) {
      // We use setProperty to cleanly override our CSS '!important' rule
      fallbackContainer.style.setProperty('display', 'none', 'important');
    }
  });

  $(document).ready(function() {
    // 2. Set up native PWA click action
    const installBtn = document.getElementById('install-button');
    if (installBtn) {
      installBtn.addEventListener('click', async () => {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        const {
          outcome
        } = await deferredPrompt.userChoice;
        console.log(`User response to prompt: ${outcome}`);
        deferredPrompt = null;
        installBtn.style.display = 'none';
      });
    }

    // 3. Set up the "Copy Link" functionality for Firefox/Fallbacks
    const copyBtn = document.getElementById('copy-link-btn');
    const copyStatus = document.getElementById('copy-status');

    if (copyBtn) {
      copyBtn.addEventListener('click', function() {
        // Gets the exact current URL of your web app page
        const appUrl = window.location.href;

        // Modern Clipboard API
        navigator.clipboard.writeText(appUrl).then(() => {
          copyStatus.textContent = "Link copied! Paste it anywhere.";
          copyStatus.style.color = "green";

          // Reset message after 3 seconds
          setTimeout(() => {
            copyStatus.textContent = "";
          }, 3000);
        }).catch(err => {
          console.error('Could not copy text: ', err);
          copyStatus.textContent = "Failed to copy. Please copy the URL bar manually.";
          copyStatus.style.color = "red";
        });
      });
    }

    // Clear prompt tracking if successfully installed natively
    window.addEventListener('appinstalled', () => {
      deferredPrompt = null;
    });
  });
</script>