<?
if (!defined('MB_RUNNING')) exit;

$merchant = $this->get('merchant');
?>
<ul class="header-left">
   <li>
      <div style="margin-left: 5px; display: flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.25rem; letter-spacing: -0.02em;">
         <a href="?app=neighborhub" data-target="slide-out" class="header-sidenav-trigger main-menu-btn waves-effect waves-light" style="border-radius: 50%; padding: 0;">

            <? //render('components/nav_trigger_icon.php', array('icon' => 'menu', 'size' => '50')); ?>
            <img src="images/android-chrome-192x192.png" height="40">

         </a>
         <a class="header-ce-logo-home-link hover-grow hide-on-small-only" style="width: auto; margin-left: 0; padding: 0 5px; font-size: 1.3rem;border-radius: 10px;" href="/"><span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span><span class="brand-extension">.app</span></a>
         <?php if ($merchant && !empty($merchant->id) && !empty($merchant->image_url)): ?>
            <span class="header-merchant-brand" title="<?= htmlspecialchars($merchant->business_name ?? 'Merchant storefront') ?>">
               <?php if (!empty($canOpenMerchantMenu)): ?>
                  <a href="#merchant-slide-out" class="merchant-sidenav-trigger" aria-controls="merchant-slide-out" aria-expanded="false" aria-label="Open merchant navigation for <?= htmlspecialchars($merchant->business_name ?? 'Merchant', ENT_QUOTES, 'UTF-8') ?>">
                     <img src="<?= htmlspecialchars($merchant->image_url, ENT_QUOTES, 'UTF-8') ?>" alt="">
                  </a>
               <?php elseif ($this->get('view') === 'merchant'): ?>
                  <a href="?app=neighborhub&view=merchant&p=dashboard&merchant_id=<?= (int)$merchant->id ?>" title="Merchant Dashboard for <?= htmlspecialchars($merchant->business_name ?? 'Merchant') ?>">
                     <img src="<?= htmlspecialchars($merchant->image_url) ?>" alt="<?= htmlspecialchars($merchant->business_name ?? 'Merchant') ?>">
                  </a>
               <?php else: ?>
                  <img class="scroll-to-top-link" src="<?= htmlspecialchars($merchant->image_url) ?>" alt="<?= htmlspecialchars($merchant->business_name ?? 'Merchant') ?>">
               <?php endif; ?>
               <?php if (!empty($operationalStatus)): ?>
                  <span class="header-merchant-status-dot" data-merchant-id="<?= (int)$merchant->id ?>" aria-label="Open now"<?= $operationalStatus['status'] === 'open' ? '' : ' hidden' ?>></span>
               <?php endif; ?>
            </span>
         <?php endif; ?>
         <?php if ($merchant && !empty($merchant->id) && $this->get('view') === 'customer'): ?>

            <button type="button" class="header-merchant-search-trigger" aria-label="Search <?= htmlspecialchars($merchant->business_name ?? 'merchant') ?> menu" aria-controls="header-merchant-search-input" aria-expanded="false">
               <i class="fas fa-search" aria-hidden="true"></i>
            </button>
            <label class="header-merchant-search-overlay" for="header-merchant-search-input">
               <textarea
                  id="header-merchant-search-input"
                  rows="1"
                  placeholder="Search <?= htmlspecialchars($merchant->business_name ?? 'menu') ?>"
                  aria-label="Search <?= htmlspecialchars($merchant->business_name ?? 'merchant') ?> menu"
                  autocomplete="off"></textarea>
            </label>
         <?php endif; ?>
      </div>
   </li>

   <? /*
   <li class="">
      <a class="share_btn" href="<?= $_SERVER['PHP_SELF']. '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">link</i></a>
   </li>
   <li class="hide-on-med-and-up">
      <a class="page_link waves-effect waves-light" href="<?= $this->config['base_url'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] ?>"><i class="material-icons">share</i></a>
   </li>
   */ ?>

</ul>

<?php if ($merchant && !empty($merchant->id) && $this->get('view') === 'customer'): ?>
<script>
$(function() {
  const $search = $('#header-merchant-search-input');
  const $trigger = $('.header-merchant-search-trigger');
  const $overlay = $('.header-merchant-search-overlay');
  if (!$search.length) return;

  function resizeMerchantSearch() {
    $search.css('height', 'auto');
    $search.css('height', `${$search[0].scrollHeight}px`);
  }

  function setSearchExpanded(expanded) {
    $overlay.toggleClass('is-expanded', expanded);
    $trigger.attr('aria-expanded', expanded ? 'true' : 'false');
    if (expanded) {
      resizeMerchantSearch();
      $search.trigger('focus');
    }
  }

  function filterMerchantProducts() {
    const query = String($search.val() || '').toLowerCase().trim();
    const $cards = $('.product-item-card');

    $cards.each(function() {
      const $card = $(this);
      const matches = !query || $card.text().toLowerCase().includes(query);
      $card.closest('.col').toggle(matches);
    });

    $('.custom-section-title').each(function() {
      const $title = $(this);
      const $categoryRow = $title.next('.row');
      if (!$categoryRow.length) return;
      const hasMatches = !query || $categoryRow.find('.product-item-card').filter(function() {
        return $(this).text().toLowerCase().includes(query);
      }).length > 0;
      $title.toggle(hasMatches);
      $categoryRow.toggle(hasMatches);
    });
  }

  $search.on('input', function() {
    resizeMerchantSearch();
    filterMerchantProducts();
  });
  $search.on('focus', function() {
    setSearchExpanded(true);
  });
  $search.on('blur', function() {
    setSearchExpanded(false);
  });
  $trigger.on('click', function() {
    setSearchExpanded(true);
  });
  resizeMerchantSearch();
});
</script>
<?php endif; ?>