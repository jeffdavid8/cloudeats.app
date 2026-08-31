<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Standalone Merchant Screens (Expo + Lobby)
 * Drop-in KDS views exposing the same data attributes as merchant_dashboard.php
 * - ?layout=expo (default): full ticket cards with Print Ticket action
 * - ?layout=lobby: passive two-column TV board (order numbers only)
 */

$app = App::getInstance();
$merchantId = $_SESSION['user']['active_merchant_id'] ?? 0;
$merchantProfile = $app->get('merchant', array());
$pendingOrders = $app->get('pending_orders', array());
$confirmedOrders = $app->get('confirmed_orders', array());
$readyForPickupOrders = $app->get('ready_orders', array());

$screenName = get_var('screen', 'expo');
?>

<style>
  body {
    padding-top: 0;
  }

  header h2 {
    padding: 0 1rem;
  }

  .nh-container {
    max-width: initial;
  }

  /* Keep styles minimal to inherit global theme; only add layout differences for expo/lobby */
  .tv-board {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .tv-tile {
    background: #111;
    color: #fff;
    padding: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    border-radius: 8px;
  }

  .expo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
  }

  .nh-card {
    padding: 1rem;
    border-radius: 8px;
  }

  .nh-card .nh-card-footer .nh-btn {
    margin-right: .25rem;
  }

  .kds-channel-badge {
    padding: .25rem .5rem;
    border-radius: 999px;
    font-weight: 800;
  }
</style>

<header class="nh-role-header" style="margin-bottom:1rem;">
  <h2><?php echo htmlspecialchars($merchantProfile->business_name ?? 'Merchant Screens'); ?> — <?php echo strtoupper($screenName); ?></h2>
</header>

<div class="nh-wrapper">
  <main class="nh-main">
    <div class="nh-container">

      <?php if ($screenName === 'expo'): ?>

        <section class="nh-pending-queue">
          <div class="nh-content" data-orders-section="pending">
            <h3>
              Pending Confirmation (<span data-pending-count><?php echo count($pendingOrders); ?></span>)
              <a id="toggle-kds-mode-pending"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="pending"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>

            </h3>
            <div data-orders-list class="expo-grid" aria-live="polite">
              <?php foreach ($pendingOrders as $order): ?>
                <?php
                // Prefer server-side pre-rendered HTML if provided by Order::getOrdersByMerchantId
                if (!empty($order['html'])) {
                  echo $order['html'];
                } else {
                  echo render('components/cards/pending_order_card.php', array('order' => $order), true);
                }
                ?>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="nh-content" data-orders-section="confirmed" style="margin-top:1.5rem;">
            <h3>Confirmed / In Progress (<span data-confirmed-count><?php echo count($confirmedOrders); ?></span>)
              <a id="toggle-kds-mode-confirmed"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="confirmed"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>
            </h3>
            <div data-orders-list class="expo-grid" aria-live="polite">
              <?php foreach ($confirmedOrders as $order): ?>
                <?php
                if (!empty($order['html'])) {
                  echo $order['html'];
                } else {
                  echo render('components/cards/confirmed_order_card.php', array('order' => $order), true);
                }
                ?>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="nh-content" data-orders-section="ready" style="margin-top:1.5rem;">
            <h3>Ready for Pickup (<span data-ready-count><?php echo count($readyForPickupOrders); ?></span>)
              <a id="toggle-kds-mode-ready"
                class="nh-btn btn-floating btn-large waves-effect waves-light toggle-kds-lane"
                data-lane="ready"
                style="background: #e65100; margin-left: 1rem; font-weight: bold;">
                <i class="fas fa-expand"></i>
              </a>
            </h3>
            <div data-orders-list class="expo-grid" aria-live="polite">
              <?php foreach ($readyForPickupOrders as $order): ?>
                <?php
                if (!empty($order['html'])) {
                  echo $order['html'];
                } else {
                  echo render('components/cards/ready_order_card.php', array('order' => $order), true);
                }
                ?>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

      <?php else: /* lobby */ ?>

        <section class="nh-lobby-board">
          <div class="tv-board">
            <div class="tv-column" aria-label="Pending column">
              <h4>Pending</h4>
              <div data-orders-section="pending">
                <div data-orders-list>
                  <?php foreach ($pendingOrders as $order): ?>
                    <div class="tv-tile" data-order-id="<?php echo intval($order['id']); ?>">#<?php echo htmlspecialchars($order['order_number'] ?? '—'); ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="tv-column" aria-label="Confirmed / Ready column">
              <h4>Confirmed / In Progress</h4>
              <div data-orders-section="confirmed">
                <div data-orders-list>
                  <?php foreach ($confirmedOrders as $order): ?>
                    <div class="tv-tile" data-order-id="<?php echo intval($order['id']); ?>">#<?php echo htmlspecialchars($order['order_number'] ?? '—'); ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <div class="tv-column" aria-label="Ready for Pickup column">
              <h4>Ready for Pickup</h4>
              <div data-orders-section="ready-for-pickup">
                <div data-orders-list>
                  <?php foreach ($readyForPickupOrders as $order): ?>
                    <div class="tv-tile" data-order-id="<?php echo intval($order['id']); ?>">#<?php echo htmlspecialchars($order['order_number'] ?? '—'); ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
        </section>

      <?php endif; ?>

    </div>
  </main>
</div>

<script>
  // ============================================================================
  // NEIGHBORHUB STANDALONE KDS CORE ENGINE
  // ============================================================================
  var lastPendingOrderCount = <?php echo intval($pendingOrderCount ?? 0); ?>;
  var lastBumpedOrder = null;

  // --- 4. STATE ENGINE INTERACTION ---
  function confirmOrder(orderId, skipConfirm = true) {
    if (!skipConfirm && !confirm('Confirm this order?')) return;

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

  // --- 5. UNDO ENGINE ---
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

    if (Date.now() - lastBumpedOrder.timestamp > 20000) {
      M.toast({
        html: 'Undo window expired'
      });
      lastBumpedOrder = null;
      return;
    }

    const orderId = lastBumpedOrder.id;
    const previousLane = lastBumpedOrder.lane;

    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=revert_order_status',
      data: JSON.stringify({
        order_id: orderId,
        revert_to: previousLane,
        merchant_id: <?php echo intval($merchantId); ?>
      }),
      complete: function() {
        animateOrderStateTransition(orderId, previousLane === 'PENDING_CONFIRMATION' ? 'PENDING_CONFIRMATION' : 'CONFIRMED');
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

  // --- 6. ADVANCED LANE TRANSITIONS ---
  function animateOrderStateTransition(orderId, newStatus, orderHtml = null) {
    let orderCard = document.querySelector(`[data-order-id="${orderId}"]`);

    // Scenario A: Brand new ticket landing from Polling
    if (!orderCard) {
      if (!orderHtml) return;

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
    // 🚨 FIXED: Animate out, destroy the old template, insert the FRESH backend template with new buttons!
    orderCard.style.transition = 'all 300ms ease';
    orderCard.style.opacity = '0';
    orderCard.style.transform = 'scale(0.8)';

    setTimeout(() => {
      let targetSectionName = 'pending';
      if (newStatus === 'CONFIRMED') targetSectionName = 'confirmed';
      if (newStatus === 'READY_FOR_PICKUP' || newStatus === 'READY') targetSectionName = 'ready';

      const destinationContainer = document.querySelector(`[data-orders-section="${targetSectionName}"] [data-orders-list]`);
      if (destinationContainer) {
        // Remove the old out-of-date DOM node entirely
        orderCard.remove();

        // If the poller passed fresh backend HTML (with the new action buttons), use it!
        // Otherwise, fallback to the old card structure if we did an optimistic instant bump
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


  // --- 7. DOCUMENT LIFECYCLE INITIALIZER ---
  document.addEventListener('DOMContentLoaded', function() {
    // Instantiate Poller instance directly bound to the global window space
    window.neighborhubPoller = new NeighborhubPoller('merchant', <?php echo intval($merchantId); ?>);

    // Click behavior on order checklist items
    document.addEventListener('click', function(e) {
      const item = e.target.closest('.nh-order-item');
      if (item) item.classList.toggle('kds-item-done');
    });

    // Run Engine Initializers
    refreshBumpBarSlots();
    startKdsTimers();
    document.addEventListener('keydown', handleKitchenHotkeys, true);
    document.body.setAttribute('tabindex', '0');
    document.body.focus();
  });
</script>