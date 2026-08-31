<?
if (!defined('MB_RUNNING')) exit;

function decompressOrderMetadata($jsonPayload)
{
  // 1. Decode the compressed JSON back to an associative array
  $compressed = (is_string($jsonPayload)) ? json_decode($jsonPayload, true) : $jsonPayload;

  // 2. Check if the compressor triggered the size safety fallback
  if (!$compressed || isset($compressed['warn'])) {
    return ['error' => 'Payload was truncated or invalid due to size limits.'];
  }

  $decompressedCart = [];

  foreach ($compressed as $item) {
    // Map the compressed keys back to their original descriptive keys
    $decompressedItem = [
      'product_id' => $item['i'] ?? null,
      'quantity'   => $item['q'] ?? 1,
      'customizations' => [
        'doneness'          => $item['c']['d'] ?? null,
        'free_toppings'     => $item['c']['f'] ?? [],
        'premium_additions' => $item['c']['p'] ?? [],
        'size'              => $item['c']['s'] ?? null,
        'crust'             => $item['c']['cr'] ?? null,
        'toppings'          => $item['c']['t'] ?? [],
      ]
    ];

    // Context note: The original array structure was grouped by merchant ID: 
    // $cartData[$merchantId] = [$item1, $item2].
    // If your application requires grouping, swap 'all_items' with the actual merchant ID lookup.
    $decompressedCart['all_items'][] = $decompressedItem;
  }

  return $decompressedCart;
}

$pendingOrder = $_SESSION[get_var('session_key')];
$merchantId = $pendingOrder['merchant_id'];
$merchant = Merchant::getMerchantById($merchantId);
//$pendingOrder['merchant_package'] = decompressOrderMetadata($pendingOrder['merchant_package']);
//echo '<pre class="debugger-info">' . print_r($pendingOrder, true) . '</pre>';
if (!empty($pendingOrder)) {
  $app = $this;
  App::getInstance('vault')->includeModel('vault');

  $db = $app->db;

  try {
    $customerId = intval($pendingOrder['customer_id']);
    $app->set('customer_id', $customerId);
    $deliveryAddress = $pendingOrder['delivery_location']['address'];
    $deliveryFee = $pendingOrder['delivery_fee'];
    $deliveryNotes = $pendingOrder['delivery_location']['notes'];
    $lineItems = $pendingOrder['merchant_package'];

    // ============================================================================
    // 📊 TRUSTED FINANCIAL SPLIT MANAGEMENT ENGINE
    // ============================================================================

    // 🌟 FIX 1: Do not rely on the loop ($item['price_at_order'] is missing from decompression).
    // Pull the catalog-verified item total straight from your secure session matrix.
    $merchantSubtotal = floatval($pendingOrder['subtotal_amount']);

    // 🌟 FIX 2: Calculate your 4% software app usage platform cut
    $merchantAppFeePercent = $merchant->platform_fee_rate;
    $appFeeCutFromMerchant = round($merchantSubtotal * $merchantAppFeePercent, 2);

    // Subtract your cut to find exactly what the merchant takes home
    $finalMerchantPayout = $merchantSubtotal - $appFeeCutFromMerchant;

    // Create the Order Record in the DB
    $newOrderId = Order::create(
      $customerId,
      intval($merchantId),
      $lineItems,
      $merchantSubtotal,
      $pendingOrder['processing_fee'],
      $pendingOrder['platform_fee'],
      $deliveryFee,
      $pendingOrder['tips'],
      $pendingOrder['sales_tax'],
      $pendingOrder['total_amount'],
      $merchant->address,
      $deliveryAddress,
      $deliveryNotes,
      $state = 'PENDING_CONFIRMATION',
      $meta = '{}'
    );



    $_SESSION['notification'] = array(
      'type' => 'success',
      'message' => "Thank you for your order!  Your order is now pending confirmation.",
    );
  } catch (Exception $e) {

    $_SESSION['notification'] = array(
      'type' => 'error',
      'message' => "Fatal database integrity failure processing split merchant orders: " . $e->getMessage(),
    );
  }
}
