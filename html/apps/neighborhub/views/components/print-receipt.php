<?php
/**
 * Simple print-friendly order receipt
 * Expects: $order (array/object)
 */
$merchant = $merchant ?? null;
$order = $order ?? [];
$orderId = intval($order['id'] ?? $order->id ?? 0);
$orderNumber = htmlspecialchars($order['order_number'] ?? $order->order_number ?? 'N/A');
$createdAt = htmlspecialchars($order['created_at'] ?? $order->created_at ?? date('Y-m-d H:i'));
$items = is_array($order['items'] ?? null) ? $order['items'] : ($order->items ?? []);
$total = number_format(floatval($order['total_amount'] ?? $order->total_amount ?? 0), 2);
$merchantName = htmlspecialchars($merchant->business_name ?? $merchant['business_name'] ?? '');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ticket #<?php echo $orderNumber; ?></title>
  <style>
    /* Minimal print-friendly styles for receipt printers */
    html,body{margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff}
    .receipt{width:100%;max-width:320px;padding:8px}
    h1{font-size:18px;margin:0 0 4px}
    h2{font-size:14px;margin:0 0 6px;font-weight:600}
    .meta{font-size:12px;color:#222;margin-bottom:6px}
    .items{margin:6px 0;border-top:1px dashed #000;border-bottom:1px dashed #000}
    .item{display:flex;justify-content:space-between;padding:6px 0;font-size:13px}
    .total{display:flex;justify-content:space-between;padding:8px 0;font-weight:900;font-size:14px}
    .small{font-size:11px;color:#333}
    @media print{body{margin:0} .receipt{box-shadow:none}}
  </style>
</head>
<body>
  <div class="receipt">
    <h1><?php echo $merchantName ?: 'Merchant'; ?></h1>
    <h2>Order #<?php echo $orderNumber; ?></h2>
    <div class="meta">Placed: <?php echo $createdAt; ?> | Ticket: <?php echo $orderId; ?></div>

    <div class="items">
      <?php if (!empty($items) && is_array($items)): ?>
        <?php foreach ($items as $it):
          $name = htmlspecialchars($it['product_name'] ?? $it->product_name ?? 'Item');
          $qty = intval($it['quantity'] ?? $it->qty ?? 1);
          $price = number_format(floatval($it['price_at_order'] ?? $it->unit_price ?? 0), 2);
        ?>
        <div class="item"><div><?php echo $qty; ?> x <?php echo $name; ?></div><div>$<?php echo $price; ?></div></div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="item">No items listed</div>
      <?php endif; ?>
    </div>

    <div class="total"><div>Total</div><div>$<?php echo $total; ?></div></div>
    <div class="small">Thank you. Please keep this ticket for pickup.</div>
  </div>
</body>
</html>
