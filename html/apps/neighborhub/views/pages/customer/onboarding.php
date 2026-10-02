<?
if (!defined('MB_RUNNING')) exit;
?>

<main class="ce-landing" id="ce-landing">

  <section class="ce-hero relative overflow-hidden" aria-labelledby="ce-heading">

    <!-- Parallax Background Layer & Mode Overlay -->
    <div class="hero-bg-parallax" id="heroBgParallax" aria-hidden="true">
      <div class="hero-overlay"></div>
    </div>

    <!-- Hero Content Container -->
    <div class="ce-hero-container relative z-10">
      <div class="ce-hero-copy">
        <p class="ce-eyebrow"><span></span> GOOD THINGS, CLOSE BY</p>
        <h1 id="ce-heading" style="">
          <? render('components/nav_trigger_icon.php', array('icon' => 'menu', 'size' => '50')); ?>

          <span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span><span class="brand-extension">.app</span>
        </h1>
        <p class="ce-intro">Find the local places you love, ready to bring the good stuff to you.</p>
      </div>

      <form class="ce-search" id="ce-search-form" autocomplete="off">
        <label class="ce-location-icon" for="ce-address"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></label>
        <input id="ce-address" name="address" type="search" placeholder="Enter your delivery address" aria-label="Delivery address" aria-controls="ce-suggestions" aria-autocomplete="list">
        <button class="ce-locate" id="ce-locate" type="button" title="Use my current location" aria-label="Use my current location"><i class="fas fa-crosshairs" aria-hidden="true"></i></button>
        <button class="ce-search-submit" type="submit"><span>Find places</span><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
        <ul class="ce-suggestions" id="ce-suggestions" role="listbox" hidden></ul>
      </form>
      <p class="ce-search-note" id="ce-location-note" aria-live="polite">Choose an address to see what delivers nearby.</p>

      <nav class="ce-categories" aria-label="Filter by category">
        <button type="button" class="is-active" data-category="">All nearby</button>
        <button type="button" data-category="Pizza">Pizza</button>
        <button type="button" data-category="Wings">Wings</button>
        <button type="button" data-category="Grocery">Grocery</button>
        <button type="button" data-category="Bakery">Bakery</button>
        <button type="button" data-category="Late Night">Late night</button>
      </nav>
    <? if ($this->user): ?>
      <div class="my-cloudeats-link" style="">
        <a href="/?app=neighborhub&view=customer&p=dashboard" class="btn waves-effect waves-light">My <span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span><span class="brand-extension">.app</span></a>
      </div>
    <? endif; ?>
    </div>


  </section>

  <section id="merchant-grid-section" class="ce-results" aria-labelledby="ce-results-heading">
    <div class="ce-results-heading">
      <div>
        <p class="ce-eyebrow">THE LOCAL LINEUP</p>
        <h2 id="ce-results-heading">Around your corner</h2>
      </div>
      <span class="ce-result-count" id="ce-result-count" aria-live="polite"></span>
    </div>
    <div class="ce-merchant-grid" id="ce-merchant-grid" aria-live="polite">
      <div class="ce-empty-state" id="ce-empty-state">
        <span class="ce-empty-icon"><i class="fas fa-location-arrow" aria-hidden="true"></i></span>
        <h3>Your next favorite is out there.</h3>
        <p>Enter your address to discover local food and shops that deliver to you.</p>
      </div>
    </div>
  </section>

  <footer class="ce-footer">
    <span>Made for the places that make a neighborhood.</span>
    <span><a href="https://www.openstreetmap.org/copyright" rel="noreferrer">© OpenStreetMap contributors</a> · <a href="/?p=login">Own a local business? <strong>Join Cloud Eats</strong></a></span>
  </footer>

</main>