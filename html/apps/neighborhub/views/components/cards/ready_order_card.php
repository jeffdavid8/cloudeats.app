<?
if (!defined('MB_RUNNING')) exit;
/**
 * 
 * @var Object $order
 */
?>

<div class="nh-card nh-order-card" data-order-id="<?php echo intval($order['id']); ?>" data-created-at="<?php echo $order['created_at']; ?>">

  <div class="nh-card-header">
    <div style="width: 100%">
      <p class="nh-order-number flow-text"><?php echo htmlspecialchars($order['order_number']); ?></p>
      <span class="nh-badge badge-<?php echo strtolower($order['state']); ?> right">
        <?php echo htmlspecialchars(str_replace('_', ' ', $order['state'])); ?>
      </span>
      <h4 style="margin: 0; margin-bottom: 0.5rem;">Delivery Job</h4>
    </div>
  </div>

  <div class="nh-card-body">
    <div class="nh-order-details">
      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Amount</span>
        <span class="nh-order-detail-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
      </div>
      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Placed</span>
        <span class="nh-order-detail-value"><?php echo date('M d, h:i A', strtotime($order['created_at'])); ?></span>
      </div>
      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Pickup Address</span>
        <span class="nh-order-detail-value" style="font-size: 0.875rem;"><?php echo htmlspecialchars($order['pickup_address']); ?></span>
      </div>
      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Delivery Address</span>
        <span class="nh-order-detail-value" style="font-size: 0.875rem;"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
      </div>
    </div>
  </div>

  <div class="nh-card-footer">
    <?php if ($order['state'] === 'CONFIRMED'): ?>
      <button type="button" class="nh-btn" onclick="markReadyForPickup(<?php echo intval($order['id']); ?>)" style="flex: 1;">
        Mark Ready for Pickup
      </button>
    <?php else: ?>
      <button type="button" class="nh-btn" disabled style="flex: 1;">
        Awaiting Courier
      </button>
    <?php endif; ?>
  </div>

</div>