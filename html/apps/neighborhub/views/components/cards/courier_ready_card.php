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
      <span class="nh-badge badge-<?php echo strtolower($order['state']); ?> right">
        <?php echo htmlspecialchars(str_replace('_', ' ', $order['state'])); ?>
      </span>
      <p class="nh-order-number flow-text"><?php echo htmlspecialchars($order['order_number']); ?></p>
      <h4 style="margin: 0; margin-bottom: 0.5rem;">Delivery Job</h4>
    </div>
  </div>

  <div class="nh-card-body">
    <div class="nh-order-details">

      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Merchant</span>
        <span class="nh-order-detail-value"><?php echo htmlspecialchars($order['business_name']); ?></span>
      </div>

      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Order Amount</span>
        <span class="nh-order-detail-value">$<?php echo number_format($order['total_amount'], 2); ?></span>
      </div>

      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Pickup</span>
        <span class="nh-order-detail-value" style="font-size: 0.875rem;">
          <?php echo htmlspecialchars(substr($order['pickup_address'], 0, 50)); ?>...
        </span>
      </div>

      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Delivery</span>
        <span class="nh-order-detail-value" style="font-size: 0.875rem;">
          <?php echo htmlspecialchars(substr($order['delivery_address'], 0, 50)); ?>...
        </span>
      </div>

      <div class="nh-order-detail-item">
        <span class="nh-order-detail-label">Posted</span>
        <span class="nh-order-detail-value">
          <?php echo date('M d, h:i A', strtotime($order['created_at'])); ?>
        </span>
      </div>

    </div>
  </div>

  <div class="nh-card-footer">
    <button type="button" class="nh-btn" onclick="acceptDeliveryJob(<?php echo intval($order['id']); ?>)" style="flex: 1;">
      Accept Delivery Job
    </button>
  </div>

</div>