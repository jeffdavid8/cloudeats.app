<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Merchant Dashboard
 * 
 * Displays fulfillment queue, order management, and delivery tracking
 * for authenticated merchant staff members.
 */


$merchantId = $_SESSION['user']['active_merchant_id'] ?? 0;
$staffRole = $_SESSION['user']['merchant_staff_role'] ?? 'clerk';
$userId = $_SESSION['user']['id'] ?? 0;
$userName = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Staff';

$merchant = $this->get('merchant', array());
$pendingOrders = $this->get('pending_orders', null);
$confirmedOrders = $this->get('confirmed_orders', null);
$readyForPickupOrders = $this->get('ready_orders', null);
$staffTeam = $this->get('staff_team', array());

$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
if ($notification) {
  unset($_SESSION['notification']);
}

$pendingOrderCount = is_array($pendingOrders) ? count($pendingOrders) : 0;
$confirmedOrderCount = is_array($confirmedOrders) ? count($confirmedOrders) : 0;
$readyOrderCount = is_array($readyForPickupOrders) ? count($readyForPickupOrders) : 0;

// Helper to determine if the shop profile is online
$rawStatus = strtolower(trim($merchant->status ?? ''));
$isShopOnline = ($rawStatus === 'active' || $rawStatus === 'online' || $rawStatus === '1' || $merchant->status === 1);
?>
<style>

</style>
<? /*
<header class="nh-role-header">
  <div class="nh-container">
    <div class="nh-role-header-content">
      <div class="hide-on-med-and-down">
        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
          <?php echo htmlspecialchars($merchant->business_name ?? 'Merchant Dashboard'); ?> -
          <span style="text-transform: capitalize;"><?php echo htmlspecialchars($staffRole); ?></span>
        </p>
      </div>
      <div style="position: fixed; z-index: 890; top: 1rem; right: 1rem; height: 5rem; width: 100%;">
        <div class="right">
          render('components/merchant/merchant_nav_menu.php', array('merchant' => $merchant));
        </div>
      </div>
    </div>
  </div>
</header>
*/ ?>
<ul id="sidenav-right" class="sidenav right" style="width: 320px; padding: 1.25rem 1rem; z-index: 1004;">
  <li style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
    <h5 style="margin: 0; font-size: 1.2rem; font-weight: 700;">Menu Availability</h5>
    <a href="#!" class="sidenav-close grey-text text-darken-1"><i class="fas fa-times"></i></a>
  </li>

  <li class="divider" tabindex="-1"></li>

  <li style="padding-top: 1rem;">
    <?php
    $menus = Merchant::getAllMenuCategories($merchant->id);

    if (!empty($menus)):
      foreach ($menus as $menu):
        $menuId = intval($menu['id'] ?? $menu['menu_id'] ?? 0);
        $menuName = htmlspecialchars($menu['menu_name']);
        $categories = $menu['categories'] ?? [];
        
        // Determine parent menu active status (1/0 or active/inactive)
        $isMenuRawStatus = $menu['status'] ?? $menu['is_active'] ?? 1;
        $isMenuActive = ($isMenuRawStatus === 1 || $isMenuRawStatus === '1' || strtolower((string)$isMenuRawStatus) === 'active');
    ?>
        <div class="card-panel menu-group-panel" data-menu-id="<?= $menuId ?>" style="padding: 0.75rem; margin-bottom: 1.25rem; border: 1px solid #e0e0e0; box-shadow: none;">
          <!-- Master Menu Header & Toggle -->
          <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 2px solid #eeeeee;">
            <strong style="font-size: 1rem; max-width: 150px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
              <?= $menuName ?>
            </strong>
            <div class="switch">
              <label style="font-weight: 600; color: #37474f;">
                <input type="checkbox" 
                       class="toggle-menu-status" 
                       data-menu-id="<?= $menuId ?>" 
                       <?= $isMenuActive ? 'checked' : '' ?>>
                <span class="lever" style="margin: 0 8px;"></span>
              </label>
            </div>
          </div>

          <!-- Menu Categories List -->
          <ul class="collection categories-collection" style="margin: 0.5rem 0 0 0; border: none; opacity: <?= $isMenuActive ? '1' : '0.45' ?>;">
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $category):
                $categoryId = intval($category['category_id']);
                $categoryName = htmlspecialchars($category['category_name']);

                $type = strtolower(trim($category['type'] ?? ''));
                $metaActive = $category['meta']['is_active'] ?? null;

                $isCategoryActive = ($metaActive !== null)
                  ? !empty($metaActive)
                  : ($type !== 'inactive' && $type !== 'disabled' && ($category['status'] ?? '') !== 'inactive');
              ?>
                <li class="collection-item category-item-row" data-category-id="<?= $categoryId ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #e0e0e0; background: transparent;">
                  <span class="category-name" style="font-weight: 500; font-size: 0.875rem; opacity: <?= $isCategoryActive ? '1' : '0.5' ?>;">
                    <?= $categoryName ?>
                  </span>
                  <div class="switch">
                    <label>
                      <input type="checkbox" 
                             class="toggle-category-status" 
                             data-category-id="<?= $categoryId ?>" 
                             <?= ($isCategoryActive && $isMenuActive) ? 'checked' : '' ?>
                             <?= !$isMenuActive ? 'disabled' : '' ?>>
                      <span class="lever" style="margin: 0;"></span>
                    </label>
                  </div>
                </li>
              <?php endforeach; ?>
            <?php else: ?>
              <li class="collection-item grey-text" style="font-style: italic; font-size: 0.8rem; padding: 8px 0; background: transparent;">
                No categories in this menu.
              </li>
            <?php endif; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="grey-text text-darken-1" style="padding: 0 10px;">No menus or categories found.</p>
    <?php endif; ?>
  </li>
</ul>

<div class="nh-wrapper">
  <main class="nh-main">
    <div class="nh-container">

      <!-- Queue Section -->
      <section class="nh-pending-queue" style="margin-bottom: 3rem;">
        <!-- Menu Category Status & Controls Sidenav -->

        <div class="action_menu right hide-on-small-only" style="">

          <!-- Dropdown Trigger -->
          <button
            type="button"
            id="store-status-toggle"
            class="status-dropdown-trigger kds-store-status-btn <?= $isShopOnline ? 'is-online' : 'is-offline' ?>"
            data-target="store-status-dropdown"
            data-status="<?= $isShopOnline ? 'active' : 'inactive' ?>">
            <span class="kds-status-dot"></span>
            <span class="kds-status-label"><?= $isShopOnline ? 'STORE ONLINE' : 'STORE OFFLINE'; ?></span>
            <i class="material-icons right" style="margin-left: 8px; font-size: 1.2rem; vertical-align: middle;">arrow_drop_down</i>
          </button>

          <!-- Dropdown Structure -->
          <ul id="store-status-dropdown" class="dropdown-content">
            <li>
              <a href="#!" class="store-status-option green-text" data-status="online">
                <span class="status-dot-indicator online"></span> Go Online
              </a>
            </li>
            <li class="divider" tabindex="-1"></li>
            <li>
              <a href="#!" class="store-status-option red-text" data-status="offline">
                <span class="status-dot-indicator offline"></span> Go Offline
              </a>
            </li>
          </ul>
          <button
            class="btn-floating btn-large waves-effect waves-light" id="toggle-sidenav-right-btn"
            data-target="sidenav-right"
            style="background: #e65100;">
            <i class="material-icons">access_time</i>
          </button>

        </div>
        <div style="margin: 1.5rem; ">
          <h2>Fulfillment Queue</h2>
          <a id="toggle-kds-mode" tabindex="0" class="nh-btn btn-floating btn-large waves-effect waves-light" style="background: #e65100; margin-left: 1rem; font-weight: bold; margin: 0 auto;">
            <i class="fas fa-expand"></i>
          </a>

        </div>

        <!-- Pending Tickets -->
        <div class="nh-content" data-orders-section="pending" style="margin-bottom: 2rem; border-left: 4px solid var(--status-pending-bg);">
          <div class="content-inner">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--status-pending-text);">
              Pending Confirmation (<span data-pending-count><?php echo $pendingOrderCount; ?></span>)
              <a id="toggle-kds-mode-pending"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="pending"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>
            </h3>
            <div data-orders-list class="nh-grid nh-grid-2" style="gap: 1.5rem;">
              <?php if (!empty($pendingOrders)): ?>
                <?php foreach ($pendingOrders as $order): ?>
                  <?= $order['html'] ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Confirmed / In Progress -->
        <div class="nh-content" data-orders-section="confirmed" style="margin-bottom: 2rem; border-left: 4px solid var(--status-confirmed-bg);">
          <div class="content-inner">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--status-confirmed-text);">
              Confirmed / "In Progress" (<span data-confirmed-count><?php echo $confirmedOrderCount; ?></span>)
              <a id="toggle-kds-mode-confirmed"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="confirmed"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>
            </h3>
            <div data-orders-list class="nh-grid nh-grid-2" style="gap: 1.5rem;">
              <?php if (!empty($confirmedOrders)): ?>
                <?php foreach ($confirmedOrders as $order): ?>
                  <?= $order['html'] ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Ready for Pickup Queue -->
        <div class="nh-content" data-orders-section="ready" style="margin-bottom: 2rem; border-left: 4px solid var(--status-ready-bg);">
          <div class="content-inner">
            <h3 style="margin-top: 0; margin-bottom: 1.5rem; color: var(--status-ready-text);">
              Ready for Pickup / In Preparation (<span data-ready-count><?php echo $readyOrderCount; ?></span>)
              <a id="toggle-kds-mode-ready"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="ready"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>
            </h3>
            <div data-orders-list class="nh-grid nh-grid-2" style="gap: 1.5rem;">
              <?php if (!empty($readyForPickupOrders)): ?>
                <?php foreach ($readyForPickupOrders as $order): ?>
                  <?= $order['html'] ?>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

    </div>
  </main>
</div>
<!-- Data Export Modal -->
<div id="data-export-modal" class="modal mb-modal-fixed">
  <div class="modal-content">
    <h4><i class="fas fa-file-export green-text"></i> Export Activity Report</h4>
    <p class="grey-text">Select a date range and file format to download your history.</p>

    <form id="export-data-form" style="margin-top: 1.5rem;">
      <div class="row">
        <!-- Start Date -->
        <div class="input-field col s12 m6">
          <input type="text" id="export_start_date" class="datepicker" required>
          <label for="export_start_date">Start Date</label>
        </div>
        <!-- End Date -->
        <div class="input-field col s12 m6">
          <input type="text" id="export_end_date" class="datepicker" required>
          <label for="export_end_date">End Date</label>
        </div>
      </div>

      <div class="row">
        <!-- Report Type / Entity Scope (Optional for Merchants/Admins) -->
        <div class="input-field col s12 m6">
          <select id="export_report_type">
            <option value="orders_summary" selected>Orders Summary</option>
            <option value="line_items">Itemized Product Breakdown</option>
            <option value="payouts">Payouts & Fees</option>
          </select>
          <label>Report Type</label>
        </div>

        <!-- Format Selection -->
        <div class="input-field col s12 m6">
          <p style="margin-top: 0; color: #9e9e9e; font-size: 0.8rem;">Export Format</p>
          <label style="margin-right: 1.5rem;">
            <input name="export_format" type="radio" value="csv" checked />
            <span>CSV (Excel)</span>
          </label>
          <label>
            <input name="export_format" type="radio" value="json" />
            <span>JSON</span>
          </label>
        </div>
      </div>
    </form>
  </div>

  <div class="modal-footer">
    <button type="button" class="modal-close btn-flat grey-text">Cancel</button>
    <button type="button" id="btn-download-export" class="btn waves-effect waves-light green">
      <i class="fas fa-download left"></i> Download
    </button>
  </div>
</div>


<script>
  // ============================================================================
  // NEIGHBORHUB ULTRA-FAST KDS CORE ENGINE
  // ============================================================================

  var lastPendingOrderCount = <?php echo intval($pendingOrderCount); ?>;
  var queueObserver = null;
  var activeTransitioningOrderIds = new Set();

  // Track the last bumped order for the industry-standard "Undo" feature
  var lastBumpedOrder = null;

  function updateStoreStatusButton(status) {
    var button = document.getElementById('store-status-toggle');
    if (!button) return;

    var normalized = String(status || '').toLowerCase();
    var isOnline = normalized === 'active' || normalized === 'online' || normalized === '1';

    button.classList.toggle('is-online', isOnline);
    button.classList.toggle('is-offline', !isOnline);
    button.dataset.status = isOnline ? 'active' : 'inactive';

    var label = button.querySelector('.kds-status-label');
    if (label) {
      label.textContent = isOnline ? 'STORE ONLINE' : 'STORE OFFLINE';
    }
  }

  function toggleStoreStatus() {
    var button = document.getElementById('store-status-toggle');
    if (!button) return;

    var currentStatus = button.dataset.status;
    var nextStatus = (currentStatus === 'active') ? 'inactive' : 'active';

    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=update_merchant_status',
      data: JSON.stringify({
        merchant_id: <?php echo intval($merchantId); ?>,
        status: nextStatus
      }),
      success: function(response) {
        if (response.success || response.status === 'success') {
          updateStoreStatusButton(nextStatus);
          M.toast({
            html: 'Store status updated successfully!'
          });
        } else {
          M.toast({
            html: 'Error updating status'
          });
        }
      },
      error: function() {
        M.toast({
          html: 'Network connection error.'
        });
      }
    });
  }

  // INDUSTRY STANDARD: Instant execution, zero blocking popups
  function confirmOrder(orderId, skipConfirm = true) {
    if (!skipConfirm && !confirm('Confirm this order?')) return;

    // Save state for Undo bar before altering columns
    trackOrderForUndo(orderId, 'PENDING_CONFIRMATION');

    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=confirm_order',
      data: JSON.stringify({
        order_id: orderId,
        merchant_id: <?php echo intval($merchantId); ?>
      }),
      success: function(response) {
        if (response.success) {
          M.toast({
            html: 'Order confirmed! Press Z to undo.',
            displayLength: 2500
          });
          animateOrderStateTransition(orderId, 'CONFIRMED');
        } else {
          resetCardVisuals(orderId);
        }
      },
      error: function() {
        resetCardVisuals(orderId);
      }
    });
  }

  function markReadyForPickup(orderId, skipConfirm = true) {
    if (!skipConfirm && !confirm('Mark as ready?')) return;

    trackOrderForUndo(orderId, 'CONFIRMED');

    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=mark_ready_for_pickup',
      data: JSON.stringify({
        order_id: orderId,
        merchant_id: <?php echo intval($merchantId); ?>
      }),
      success: function(response) {
        if (response.success) {
          M.toast({
            html: 'Ticket Ready! Press Z to undo.',
            displayLength: 2500
          });
          animateOrderStateTransition(orderId, 'READY_FOR_PICKUP');
        } else {
          resetCardVisuals(orderId);
        }
      },
      error: function() {
        resetCardVisuals(orderId);
      }
    });
  }

  // Keep track of the last bump so cooks can rescue a mistake instantly
  function trackOrderForUndo(orderId, originalLane) {
    lastBumpedOrder = {
      id: orderId,
      lane: originalLane,
      timestamp: Date.now()
    };
  }

  function triggerKdsUndo() {
    if (!lastBumpedOrder) {
      M.toast({
        html: 'Nothing to undo'
      });
      return;
    }

    // Limit undo window to 20 seconds to prevent state breaking deep database mismatches
    if (Date.now() - lastBumpedOrder.timestamp > 20000) {
      M.toast({
        html: 'Undo window expired'
      });
      lastBumpedOrder = null;
      return;
    }

    const orderId = lastBumpedOrder.id;
    const previousLane = lastBumpedOrder.lane;

    // Send inverse action request back up to API to revert state machine row
    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=revert_order_status', // Ensure backend has handler if tracking strictly
      data: JSON.stringify({
        order_id: orderId,
        revert_to: previousLane,
        merchant_id: <?php echo intval($merchantId); ?>
      }),
      complete: function() {
        // Optimistically snap card backwards locally to feel fast
        animateOrderStateTransition(orderId, previousLane === 'PENDING' ? 'PENDING' : 'CONFIRMED');
        M.toast({
          html: 'Bump undone successfully'
        });
        lastBumpedOrder = null;
      }
    });
  }

  function resetCardVisuals(orderId) {
    const card = document.querySelector(`.nh-order-card[data-order-id="${orderId}"]`);
    if (card) {
      card.style.borderColor = '';
      card.style.transform = '';
    }
  }


  function bumpOrder(orderId, $cardElement) {
    // Your AJAX logic to tell the server the order is complete goes here
    $cardElement.fadeOut(300, function() {
      $(this).remove();
      // Refresh layout or let polling populate new tiles
    });
  }

  function recallLastOrder() {
    console.log("KB9000 Recall triggered: Undoing last bumped order.");

    // Check if the triggerKdsUndo function exists globally before calling it
    if (typeof triggerKdsUndo === "function") {
      triggerKdsUndo();
    } else {
      console.warn("triggerKdsUndo is not defined. Ensure your main KDS script is loaded.");
    }
  }

  function scrollCardItems($card, direction) {
    // 1. Find the container holding the order items (adjust selector if yours is different, e.g., '.collection')
    let $container = $card.find('.card-content, .order-items-list');
    let $items = $card.find('li, .order-item'); // The individual rows/items

    if (!$items.length) return; // Nothing to scroll

    // 2. Find the currently highlighted item (if any)
    let $currentHighlight = $items.filter('.item-highlight');
    let currentIndex = $items.index($currentHighlight);
    let nextIndex;

    // 3. Calculate the next item index based on the KB9000 arrow direction
    if (direction === 'up') { // KB9000 ArrowLeft
      nextIndex = currentIndex > 0 ? currentIndex - 1 : $items.length - 1; // Loop to bottom if at top
    } else { // KB9000 ArrowRight
      nextIndex = currentIndex < $items.length - 1 ? currentIndex + 1 : 0; // Loop to top if at bottom
    }

    // 4. Update the visual highlight class
    $items.removeClass('item-highlight');
    let $nextHighlight = $items.eq(nextIndex).addClass('item-highlight');

    // 5. Automatically scroll the container to keep the highlighted item in view
    if ($container.length && $nextHighlight.length) {
      let containerTop = $container.scrollTop();
      let containerHeight = $container.height();
      let elemTop = $nextHighlight.position().top + containerTop;
      let elemHeight = $nextHighlight.outerHeight();

      // Scroll down if item is below the viewable area
      if ((elemTop + elemHeight) > (containerTop + containerHeight)) {
        $container.scrollTop(elemTop - containerHeight + elemHeight);
      }
      // Scroll up if item is above the viewable area
      else if (elemTop < containerTop) {
        $container.scrollTop(elemTop);
      }
    }
  }

  function animateOrderStateTransition(orderId, newStatus, orderHtml = null) {
    let orderCard = document.querySelector(`[data-order-id="${orderId}"]`);

    // Scenario A: It's a brand new order card
    if (!orderCard) {
      if (!orderHtml) return; // Safety check

      let targetSectionName = 'pending';
      if (newStatus === 'CONFIRMED') targetSectionName = 'confirmed';
      if (newStatus === 'READY_FOR_PICKUP' || newStatus === 'READY') targetSectionName = 'ready';

      const destinationContainer = document.querySelector(`[data-orders-section="${targetSectionName}"] [data-orders-list]`);
      if (destinationContainer) {
        let $newCard = $(orderHtml);

        $newCard.css({
          transition: 'all 300ms ease',
          opacity: '0',
          transform: 'scale(0.8)'
        });

        $(destinationContainer).append($newCard);

        requestAnimationFrame(() => {
          $newCard.css({
            opacity: '1',
            transform: 'scale(1)'
          });
        });
      }

      if (typeof refreshBumpBarSlots === "function") refreshBumpBarSlots();
      return;
    }

    // Scenario B: Existing order transitioning lanes
    orderCard.style.transition = 'all 300ms ease';
    orderCard.style.opacity = '0';
    orderCard.style.transform = 'scale(0.8)';

    setTimeout(() => {
      let targetSectionName = 'pending';
      if (newStatus === 'CONFIRMED') targetSectionName = 'confirmed';
      if (newStatus === 'READY_FOR_PICKUP' || newStatus === 'READY') targetSectionName = 'ready';

      const destinationContainer = document.querySelector(`[data-orders-section="${targetSectionName}"] [data-orders-list]`);
      if (destinationContainer) {
        // 🚨 FIX: Remove the old DOM node completely to discard out-of-date buttons
        orderCard.remove();

        // Swap in the fresh template if available (e.g. from polling loop), 
        // or fall back to the old one if we did an optimistic user-triggered bump.
        let $freshCard = orderHtml ? $(orderHtml) : $(orderCard);

        $freshCard.css({
          transform: '',
          borderColor: '',
          opacity: '1'
        });

        $(destinationContainer).append($freshCard);
      }

      if (typeof refreshBumpBarSlots === "function") refreshBumpBarSlots();
    }, 300);
  }

  document.addEventListener('DOMContentLoaded', function() {

    var elems = document.querySelectorAll('.merchant-nav-dropdown-trigger');
    M.Dropdown.init(elems, {
      alignment: 'right',
      constrainWidth: false,
      coverTrigger: false
    });

    $('.materialboxed').materialbox();

    let rightSidenavElems = document.getElementById('sidenav-right');
    let sidenavInstances = M.Sidenav.init(rightSidenavElems, {
      edge: 'right',
      draggable: true
    });

    // Explicit click handler fallback to guarantee opening:
    $('#toggle-sidenav-right-btn').on('click', function(e) {
      e.preventDefault();
      let instance = M.Sidenav.getInstance(document.getElementById('sidenav-right'));
      if (instance) {
        instance.open();
      }
    });

    window.neighborhubPoller = new NeighborhubPoller('merchant', <?php echo intval($merchantId); ?>);

    document.addEventListener('click', function(e) {
      const item = e.target.closest('.nh-order-item');
      if (item) item.classList.toggle('kds-item-done');
    });

    // Fire engines
    refreshBumpBarSlots();
    startKdsTimers(); // 👈 Clocks fixed and spinning!
    document.addEventListener('keydown', handleKitchenHotkeys, true);
    document.body.setAttribute('tabindex', '0');
    document.body.focus();
    // Initialize Materialize Components
    $('.modal').modal();
    $('select').formSelect();

    // Initialize Datepickers with default 30-day range
    const dateToday = new Date();
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);

    $('.datepicker').datepicker({
      autoClose: true,
      format: 'yyyy-mm-dd',
      defaultDate: dateToday,
      maxDate: dateToday,
      showClearBtn: true
    });

    // Pre-fill Start/End inputs
    $('#export_start_date').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#export_end_date').val(dateToday.toISOString().split('T')[0]);

    M.updateTextFields(); // Ensure labels float correctly for pre-filled inputs

    // Handle Export Click
    $('#btn-download-export').on('click', function() {
      const startDate = $('#export_start_date').val();
      const endDate = $('#export_end_date').val();
      const reportType = $('#export_report_type').val();
      const format = $('input[name="export_format"]:checked').val();

      if (!startDate || !endDate) {
        M.toast({
          html: 'Please select both start and end dates.',
          classes: 'orange'
        });
        return;
      }

      const $btn = $(this);
      $btn.prop('disabled', true).text('Generating...');

      const queryParams = $.param({
        api: 'neighborhub',
        action: 'export_data',
        start_date: startDate,
        end_date: endDate,
        report_type: reportType,
        format: format,
        merchant_id: '<?= intval($merchantId ?? 0); ?>'
      });

      const xhr = new XMLHttpRequest();
      xhr.open('GET', '/?' + queryParams, true);
      xhr.responseType = 'blob';

      // 🔑 Attach the exact Authorization & CSRF headers expected by validate_csrf_request()
      const token = mb.csrf_token || $('meta[name="csrf-token"]').attr('content');
      if (token) {
        xhr.setRequestHeader('Authorization', 'Bearer ' + token);
        xhr.setRequestHeader('X-CSRF-TOKEN', token);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      }

      xhr.onload = function() {
        if (this.status === 200) {
          const blob = this.response;
          const downloadUrl = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.style.display = 'none';
          a.href = downloadUrl;
          a.download = `export_${reportType}_${startDate}_to_${endDate}.${format}`;
          document.body.appendChild(a);
          a.click();

          window.URL.revokeObjectURL(downloadUrl);
          a.remove();

          $('#data-export-modal').modal('close');
          M.toast({
            html: 'Download complete!',
            classes: 'green'
          });
        } else {
          M.toast({
            html: 'Export failed with status ' + this.status,
            classes: 'red'
          });
        }
        $btn.prop('disabled', false).html('<i class="fas fa-download left"></i> Download');
      };

      xhr.onerror = function() {
        M.toast({
          html: 'Network error generating export.',
          classes: 'red'
        });
        $btn.prop('disabled', false).html('<i class="fas fa-download left"></i> Download');
      };

      xhr.send();
    });

    // Initialize status dropdown with autoTrigger disabled to block arrow keys opening it
    $('.status-dropdown-trigger').dropdown({
      constrainWidth: true,
      coverTrigger: false,
      alignment: 'left',
      autoTrigger: false // 🚨 Disables default keyboard opening mechanics (like ArrowDown)
    });

    $('#top-right-menu .store-status-option').on('click', function(e) {
      e.preventDefault();
      const targetStatus = $(this).data('status'); // 'online' or 'offline'

      if (targetStatus === 'online') {
        $(this).html(`<span class="status-dot-indicator online"></span>
                  Go Offline `);
        $(this).data('status', 'offline');
      } else {
        $(this).html(`<span class="status-dot-indicator offline"></span>
                  Go Online `);
        $(this).data('status', 'online');
      }
      updateStoreStatusOnServer(targetStatus);
    });

    // 2. Handle status changes from dropdown choices
    $('#store-status-dropdown').on('click', '.store-status-option', function(e) {
      e.preventDefault();

      const targetStatus = $(this).data('status'); // 'active' or 'inactive'
      const $button = $('#store-status-toggle');
      const currentStatus = $button.attr('data-status');

      // If the state is already identical, do nothing!
      if (targetStatus === currentStatus) return;

      // Send status change payload to your server endpoint
      updateStoreStatusOnServer(targetStatus);
    });
  });

  // Helper to push the status to the server and update UI
  function updateStoreStatusOnServer(status) {
    const $btn = $('#store-status-toggle');
    // Optimistically set loading/updating state
    $btn.css('opacity', '0.6');

    mb.ajax({
      url: '/?api=neighborhub&action=update_merchant_status', // Replace with your actual merchant backend controller route
      method: 'POST',
      data: JSON.stringify({
        merchant_id: <?php echo intval($merchantId); ?>,
        status: status
      }),
      success: function(response) {
        if (response && response.success) {
          // Toggle active button attributes & classes based on return status
          if (status === 'online') {
            $btn.removeClass('is-offline').addClass('is-online');
            $btn.attr('data-status', 'active');
            $btn.find('.kds-status-label').text('STORE ONLINE');
            M.toast({
              html: 'Store is now Online!',
              classes: 'green'
            });
          } else {
            $btn.removeClass('is-online').addClass('is-offline');
            $btn.attr('data-status', 'inactive');
            $btn.find('.kds-status-label').text('STORE OFFLINE');
            M.toast({
              html: 'Store is now Offline!',
              classes: 'red'
            });
          }
        } else {
          M.toast({
            html: 'Failed to update store status.',
            classes: 'orange'
          });
        }
      },
      error: function() {
        M.toast({
          html: 'Network error. Try again.',
          classes: 'red'
        });
      },
      complete: function() {
        $btn.css('opacity', '1');
      }
    });
  }
</script>