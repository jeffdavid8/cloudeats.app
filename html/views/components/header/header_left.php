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

            <button type="button" class="header-merchant-search-trigger" aria-label="Search <?= htmlspecialchars($merchant->business_name ?? 'merchant', ENT_QUOTES, 'UTF-8') ?> menu" aria-controls="header-merchant-search-panel" aria-expanded="false">
               <i class="fas fa-search" aria-hidden="true"></i>
            </button>
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
  const $panel = $('#header-merchant-search-panel');
  const $clear = $('.header-merchant-search-clear');
  const $noResults = $('#header-merchant-search-no-results');
  const $otherSearch = $('#iv-product-search-input');
  if (!$search.length || !$panel.length) return;

  const header = document.querySelector('header.header');
  const categoryMenu = document.querySelector('#top-right-menu');
  const categoryWrapper = categoryMenu && categoryMenu.closest('.top-right-dropdown-wrapper');
  let headerObserver;

  function positionCategoryMenu() {
    if (!header || !categoryWrapper || $panel.prop('hidden')) return;
    categoryWrapper.style.setProperty('--nh-search-category-offset', '0px');
    const gap = header.getBoundingClientRect().bottom + 8 - categoryWrapper.getBoundingClientRect().top;
    categoryWrapper.style.setProperty('--nh-search-category-offset', `${Math.max(0, gap)}px`);
  }

  function setSearchExpanded(expanded) {
    $panel.prop('hidden', !expanded);
    $('header.header').toggleClass('has-merchant-search', expanded);
    $trigger.attr('aria-expanded', expanded ? 'true' : 'false');
    if (header && categoryWrapper) {
      if (expanded) {
        categoryWrapper.classList.add('nh-search-positioned-category');
        positionCategoryMenu();
        if (window.ResizeObserver) {
          headerObserver = new ResizeObserver(positionCategoryMenu);
          headerObserver.observe(header);
        }
      } else {
        if (headerObserver) headerObserver.disconnect();
        categoryWrapper.classList.remove('nh-search-positioned-category');
        categoryWrapper.style.removeProperty('--nh-search-category-offset');
      }
    }
    if (expanded) {
      $search.trigger('focus');
    }
  }

  $(window).on('scroll resize', positionCategoryMenu);

  function filterMerchantProducts() {
    const query = String($search.val() || '').toLowerCase().trim();
    const $cards = $('.product-item-card');
    let matches = 0;
    $otherSearch.val($search.val());
    $clear.prop('hidden', !$search.val());

    $cards.each(function() {
      const $card = $(this);
      const matched = !query || $card.text().toLowerCase().includes(query);
      if (matched) matches++;
      $card.closest('.col').toggle(matched);
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

    $('.iv-product-category').each(function() {
      const $category = $(this);
      const $categoryCards = $category.find('.product-item-card');
      if (!$categoryCards.length) return;
      $category.toggle(!query || $categoryCards.filter(function() {
        return $(this).text().toLowerCase().includes(query);
      }).length > 0);
    });

    $noResults.prop('hidden', !query || !$cards.length || matches > 0);
    positionCategoryMenu();
    $('#iv-result-count').text(query
      ? `Showing ${matches} of ${$cards.length} items`
      : `${$cards.length} items available`);
  }

  function clearSearch() {
    $search.val('');
    filterMerchantProducts();
  }

  function cancelSearch() {
    clearSearch();
    setSearchExpanded(false);
    $trigger.trigger('focus');
  }

  $search.on('input', filterMerchantProducts);
  $otherSearch.on('input', function() {
    $search.val($(this).val());
    filterMerchantProducts();
  });
  $clear.on('click', function() {
    clearSearch();
    $search.trigger('focus');
  });
  $('.header-merchant-search-cancel').on('click', cancelSearch);
  $panel.on('keydown', function(event) {
    if (event.key === 'Escape') {
      event.preventDefault();
      cancelSearch();
    }
  });
  $trigger.on('click', function() {
    if ($panel.prop('hidden')) {
      setSearchExpanded(true);
    } else {
      cancelSearch();
    }
  });
});
</script>
<?php endif; ?>