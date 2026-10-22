<?php
if (!defined('MB_RUNNING')) exit;
?>

<div style="width: 100%; max-width: 600px; margin: 0 auto; padding: 0 15px;">
  <div class="card-panel" style="padding: 12px 20px; border-radius: 8px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px; background-color: var(--brand-card-bg);">
    <i class="fas fa-search" style="color: var(--brand-text-muted);"></i>
    <input type="search" id="iv-product-search-input" placeholder="Search menu items..." aria-label="Search products" style="border: none; box-shadow: none; margin: 0; height: 35px; font-size: 15px; width: 100%; color: var(--brand-text-main); background: transparent;">
  </div>

  <!-- Search Results Counter & Filter Chips Row -->
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 0 4px;">
    <? /*
    <div class="iv-product-filters" style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;" aria-label="Filter products by tag">
      <button type="button" class="btn-small waves-effect waves-light iv-filter-chip is-active" data-filter="all" style="border-radius: 20px; background-color: var(--brand-gold); color: #FFF; font-weight: 600;">All Items</button>
      <button type="button" class="btn-small waves-effect waves-light iv-filter-chip" data-filter="customizable" style="border-radius: 20px; background-color: rgba(255,255,255,0.15); color: var(--brand-text-main); font-weight: 600;">Customizable</button>
    </div> 
    */ ?>
    <!-- Result Count Indicator -->
    <span id="iv-result-count" style="font-size: 13px; font-weight: 600; color: #ffcc7c;" aria-live="polite"></span>
  </div>
</div>

<script>
$(document).ready(function() {
  const $searchInput = $('#iv-product-search-input');
  const $productCards = $('.row .col.s12.m6.l4'); 
  const $resultCountSpan = $('#iv-result-count');
  const totalProducts = $productCards.length;

  function filterProducts() {
    const query = $searchInput.val().toLowerCase().trim();
    const activeFilter = $('.iv-filter-chip.is-active').data('filter') || 'all';
    let visibleCount = 0;

    // 1. Reset and show all category sections and rows first before filtering
    $('.custom-section-title').show();
    $('.custom-section-title').next('.row').show();

    // 2. Evaluate each product card
    $productCards.each(function() {
      const $card = $(this);
      const name = $card.find('.card-title.text-darken-4').text().toLowerCase();
      const desc = $card.find('p.clamped').text().toLowerCase();
      const isCustomizable = $card.find('.nh-customize-trigger').length > 0;

      let matchesSearch = query === '' || name.includes(query) || desc.includes(query);
      let matchesFilter = true;

      if (activeFilter === 'customizable') {
        matchesFilter = isCustomizable;
      }

      if (matchesSearch && matchesFilter) {
        $card.show();
        visibleCount++;
      } else {
        $card.hide();
      }
    });

    // 3. Hide category titles/sections if all products within them are filtered out
    $('.custom-section-title').each(function() {
      const $title = $(this);
      const $nextRow = $title.next('.row');
      const visibleInRow = $nextRow.find('.col.s12.m6.l4:visible').length;
      
      if (visibleInRow === 0) {
        $title.hide();
        $nextRow.hide();
      }
    });

    // 4. Update result count text indicator
    if (query !== '' || activeFilter !== 'all') {
      $resultCountSpan.text(`Showing ${visibleCount} of ${totalProducts} items`);
    } else {
      $resultCountSpan.text(`${totalProducts} items available`);
    }
  }

  // Initialize counter text on load
  $resultCountSpan.text(`${totalProducts} items available`);

  // Bind input events for live filtering
  $searchInput.on('input', filterProducts);

  // Bind filter chips click events
  $('.iv-filter-chip').on('click', function() {
    $('.iv-filter-chip').removeClass('is-active').css({'background-color': 'rgba(255,255,255,0.15)', 'color': 'var(--brand-text-main)'});
    $(this).addClass('is-active').css({'background-color': 'var(--brand-gold)', 'color': '#FFF'});
    filterProducts();
  });
});
</script>