<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub API Router
 * 
 * Transactional API layer for routing incoming requests to model operations.
 * Handles order management, delivery assignments, geolocation tracking,
 * merchant provisioning, user lookup, file uploads, and merchant staff authorization validation.
 * 
 * All responses return HTTP status codes and JSON payloads.
 */
// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

$db = App::getInstance('neighborhub')->db;

$decodedJson = [];

// 2. Safely capture the raw body stream
$input = file_get_contents('php://input');

// 3. Only attempt json_decode if data starts with a valid JSON curly bracket
if (!empty($input) && strpos(trim($input), '{') === 0) {
  $parsed = json_decode($input, true);
  if (is_array($parsed)) {
    $decodedJson = $parsed;
  }
}

// 4. Consolidate ALL inputs into a single trusted tracking workspace array.
// This handles standard GET parameters, multipart form data ($request), and raw JSON strings.
$request = array_merge($_GET, $_POST, $decodedJson);

// Log the consolidated tracking array to verify the variables are present
//error_log("Consolidated API Context: " . print_r($request, true));


$action = $_REQUEST['action'] ?? $request['action'] ?? null;

if (!$action) {
  http_response_code(400);
  exit(json_encode(array(
    'success' => false,
    'error' => 'Action parameter is required'
  )));
}
// Verify authentication for secure requests
if (!in_array($action, array(
  'reverse_geocode_proxy',
  'create_checkout_session',
  'list_customer_orders',
  'get_order',
  'get_product_builder_view',
))) {
  if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    http_response_code(401);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Authentication required'
    )));
  }
}

App::getInstance('neighborhub')->includeClass('assetmanager');
App::getInstance('neighborhub')->includeClass('HubSignalingEngine');


// Route to appropriate handler
try {
  switch ($action) {
    // Inside neighborhub.api.php or your main endpoint switch
    case 'get_product_builder_view':
      $app = App::getInstance('neighborhub');
      $app->includeModel('product');
      $productId = $request['product_id'] ?? null;
      $productObj = Product::getProductById($productId, 'object'); // Assuming a single lookup helper exists
      if (!$productObj) {
        http_response_code(404);
        exit(json_encode(['success' => false, 'error' => 'Product not found']));
      }

      // Isolate configuration steps matrix from the product's meta column
      $meta = is_string($productObj->meta) ? json_decode($productObj->meta, true) : $productObj->meta;
      $hasBuilder = false;
      $builder_file = "components/builders/order/default.builder.php";
      if (
        !empty($meta['form_builder'])
        && is_array($meta['form_builder'])
        && (!empty($meta['form_builder']))
        && (!empty($meta['form_builder']['builder_template']))
      ) {
        $builder_file = "components/builders/order/{$meta['form_builder']['builder_template']}.builder.php";
        $hasBuilder = (file_exists($app->app_dir . '/views/' . $builder_file));
      }

      if (!$hasBuilder) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'This product does not have a custom builder configuration for ' . $builder_file . '.']));
      }

      $form_builder = ($hasBuilder) ? $meta['form_builder'] : [];
      $steps = $form_builder['steps'] ?? [];

      // Call MediaBrain's render function to build the template string natively
      // Note: We use relative path routing starting inside the app's views folder
      $htmlOutput = $app->render($builder_file, [
        'steps' => $steps,
        'productType' => $productObj->type,
        'productId' => $productId
      ]);

      exit(json_encode([
        'success' => true,
        'html' => $htmlOutput
      ]));

    case 'get_order':
      handle_get_order($request);
      break;

    case 'get_order_ticket':
      // Return print-friendly HTML for a single order ticket
      $app = App::getInstance('neighborhub');
      $app->includeModel('order');

      $orderId = isset($request['order_id']) ? intval($request['order_id']) : 0;
      if (!$orderId) {
        send_json_response(['success' => false, 'error' => 'order_id parameter is required'], 400);
      }

      $order = Order::getOrderById($orderId);
      if (!$order) {
        send_json_response(['success' => false, 'error' => 'Order not found'], 404);
      }

      // Optionally include merchant context if available
      $merchant = null;
      if (!empty($order['merchant_id']) || !empty($order->merchant_id)) {
        try {
          $app->includeModel('merchant');
          $mid = intval($order['merchant_id'] ?? $order->merchant_id ?? 0);
          if ($mid) {
            $merchant = Merchant::getMerchantById($mid);
          }
        } catch (Exception $e) {
          // ignore merchant fetch failures; not critical for printing
        }
      }

      // Render the print template via the app render helper (views/components/print-receipt.php)
      $htmlOutput = $app->render('components/print-receipt.php', [
        'order' => $order,
        'merchant' => $merchant
      ]);

      send_json_response(['success' => true, 'html' => $htmlOutput]);
      break;

    case 'accept_terms':
      $app = App::getInstance('neighborhub');
      if (!$app->user || !isset($app->user->id)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
      }
      if (!isset($request['accepted']) || $request['accepted'] !== true) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid terms payload']);
        exit;
      }
      $db = $app->db;

      // 3. Update DB Record
      try {
        $now = date('Y-m-d H:i:s');

        // Assuming $db is your PDO instance
        $stmt = $db->prepare("
        UPDATE neighborhub_customers
        SET terms_accepted_at = ?
        WHERE user_id = ?
      ");

        $stmt->execute([
          $now,
          $app->user->id,
        ]);

        echo json_encode([
          'success'     => true,
          'accepted_at' => $now
        ]);
        exit;
      } catch (PDOException $e) {
        // Log error internally
        error_log("Failed to accept terms for customer {$app->user->id}: " . $e->getMessage());

        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
        exit;
      }
      break;

    case 'list_customer_orders':
      handle_list_customer_orders($request);
      break;

    // ============================================================================
    // IN-STORE MERCHANT POINT OF SALE (POS) TICKET SUBMISSION
    // ============================================================================
    case 'create_pos_order':
      handle_create_pos_order($request);
      break;

    case 'confirm_order':
      handle_confirm_order($request);
      break;

    case 'admin_save_courier':
      handle_admin_save_courier($request);
      break;

    case 'get_pending_orders':
      handle_get_pending_orders($request);
      break;

    case 'update_product_availability':
      handle_update_product_availability($request);
      break;

    case 'get_available_jobs':
      handle_get_available_jobs($request);
      break;

    case 'accept_delivery':
      handle_accept_delivery($request);
      break;

    case 'update_location':
      handle_update_location($request);
      break;

    case 'get_admin_live_tracking_metrics':
      get_admin_live_tracking_metrics();
      break;

    case 'complete_delivery':
      handle_complete_delivery($request);
      break;

    case 'get_merchants':
      handle_get_merchants($request);
      break;

    case 'get_merchant_products':
      handle_get_merchant_products($request);
      break;

    case 'get_courier_profile':
      handle_get_courier_profile($request);
      break;

    case 'lookup_user_by_email':
      handle_lookup_user_by_email($request);
      break;

    case 'provision_merchant':
      handle_provision_merchant($request);
      break;

    case 'upload_merchant_image':
      handle_merchant_upload_image($request);
      break;
    case 'update_merchant_settings':
      authenticate_user($request);

      $merchant_id = isset($request['merchant_id']) ? intval($request['merchant_id']) : 0;

      // Authorization: Validate the user is an owner or staff member with access
      ensure_merchant_access($request['user_id'], $merchant_id, ['owner', 'staff']);

      // Map fields directly to your table schema
      $update_data = [];

      if (isset($request['business_name']))            $update_data['business_name'] = $request['business_name'];
      if (isset($request['store_hours']))              $update_data['store_hours'] = $request['store_hours'];
      if (isset($request['address']))                  $update_data['address'] = $request['address'];
      if (isset($request['phone']))                    $update_data['phone'] = $request['phone'];
      if (isset($request['website']))                  $update_data['website'] = $request['website'];
      if (isset($request['facebook']))                 $update_data['facebook'] = $request['facebook'];
      if (isset($request['delivery_max_distance']))     $update_data['delivery_max_distance'] = (float)$request['delivery_max_distance'];

      // Status validation matching your CHECK constraint ('online', 'offline', 'active', etc.)
      if (isset($request['status'])) {
        $allowed_statuses = ['online', 'offline', 'active', 'paused', 'suspended', 'disabled'];
        if (in_array($request['status'], $allowed_statuses)) {
          $update_data['status'] = $request['status'];
        }
      }

      // Delivery assignment mode matching your CHECK constraint ('auto', 'manual', 'disabled')
      if (isset($request['delivery_assignment_mode'])) {
        $allowed_modes = ['auto', 'manual', 'disabled'];
        if (in_array($request['delivery_assignment_mode'], $allowed_modes)) {
          $update_data['delivery_assignment_mode'] = $request['delivery_assignment_mode'];
        }
      }

      if (empty($update_data)) {
        send_json_response(['success' => false, 'message' => 'No settings payload provided.'], 400);
      }

      // Call your model's update function
      $result = Merchant::update($merchant_id, $update_data);

      if ($result && $result['success']) {
        send_json_response(['success' => true, 'message' => 'Merchant configurations updated successfully.']);
      } else {
        send_json_response(['success' => false, 'message' => $result['message'] ?? 'Failed to update configurations.'], 400);
      }
      break;

    case 'manage_staff_members':
      authenticate_user($request);
      $merchant_id = intval($request['merchant_id'] ?? 0);
      ensure_merchant_access($request['user_id'], $merchant_id, ['owner']);

      $staff_action = $request['staff_action'] ?? '';
      $db = App::getInstance('neighborhub')->db;

      if ($staff_action === 'add') {
        $target_email = trim($request['target_email'] ?? '');
        $staff_role = $request['staff_role'] ?? 'staff';

        if (empty($target_email)) {
          send_json_response(['success' => false, 'message' => 'Please provide a valid email address.'], 400);
        }

        // 🔍 Lookup user ID from the main users table by their email address
        // 💡 Adaptation: Adjust the table name ('users') and column name ('email') if your platform names them differently!
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$target_email]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$target_user) {
          send_json_response([
            'success' => false,
            'message' => 'No user account found matching that email address.'
          ], 404); // Throw an explicit feedback code or 404
        }

        $target_user_id = intval($target_user['id']);

        // Check if relationship already exists
        $stmt = $db->prepare("SELECT id FROM neighborhub_merchant_users WHERE merchant_id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$merchant_id, $target_user_id]);
        if ($stmt->fetch()) {
          send_json_response(['success' => false, 'message' => 'This user is already linked to this merchant.'], 400);
        }

        // Insert new relation record
        $stmt = $db->prepare(
          "INSERT INTO neighborhub_merchant_users (merchant_id, user_id, staff_role, status, created_at) 
             VALUES (?, ?, ?, 'active', NOW())"
        );
        $stmt->execute([$merchant_id, $target_user_id, $staff_role]);

        send_json_response(['success' => true, 'message' => 'Staff member linked successfully.']);
      } elseif ($staff_action === 'remove') {
        // "Remove" remains based on user ID since we click a specific user row on the table
        $target_user_id = intval($request['target_user_id'] ?? 0);

        if (!$target_user_id) {
          send_json_response(['success' => false, 'message' => 'Missing target user identification.'], 400);
        }

        $stmt = $db->prepare("DELETE FROM neighborhub_merchant_users WHERE merchant_id = ? AND user_id = ?");
        $stmt->execute([$merchant_id, $target_user_id]);

        send_json_response(['success' => true, 'message' => 'Staff member unlinked successfully.']);
      }
      break;

    case 'update_merchant_status':
      // Ensure request is a POST
      if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
      }

      $merchant_id = isset($request['merchant_id']) ? intval($request['merchant_id']) : 0;
      $status = isset($request['status']) ? trim($request['status']) : '';

      if (!$merchant_id || empty($status)) {
        echo json_encode(['success' => false, 'error' => 'Missing merchant ID or status']);
        exit;
      }

      // Map frontend states to whatever your DB column expects (e.g., 'active'/'inactive' or 1/0)
      // Adjust this query to match your exact column names
      $db = App::getInstance('neighborhub')->db;
      $stmt = $db->prepare("UPDATE neighborhub_merchants SET status = ? WHERE id = ?");

      $updated = $stmt->execute([
        $status,
        $merchant_id
      ]);

      if ($updated) {
        echo json_encode(['success' => true]);
      } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
      }
      exit;

    case 'upload_product_image':
      handle_product_upload_image($request);
      break;

    case 'delete_merchant_image':
      handle_merchant_delete_image($request);
      break;

    case 'create_product':
      handle_create_product($request);
      break;

    case 'get_product':
      handle_get_product($request);
      break;

    case 'update_product':
      handle_update_product($request);
      break;

    case 'delete_product':
      handle_delete_product($request);
      break;

    case 'delete_product_image':
      handle_product_delete_image($request);
      break;

    case 'delete_product_gallery_image':
      handle_delete_product_gallery_image($request);
      break;

    case 'mark_ready_for_pickup':
      handle_mark_ready_for_pickup($request);
      break;

    case 'cancel_order':
      handle_cancel_order($request);
      break;

    case 'calculate_delivery_fee':
      calculate_delivery_fee($request);
      break;

    // ============================================================================
    // SECURE MULTI-TENANT STRIPE CHECKOUT ROUTINE
    // ============================================================================
    case 'create_checkout_session':
      //error_log(print_r($request, true)); 
      // 1. Isolate the transmitted basket object state wrapper
      $app = App::getInstance('neighborhub');
      $app->includeModel('merchant');
      $app->includeModel('customer');
      $app->includeModel('order');
      $results = array();
      $checkout_return_url = (!empty($request['return_url'])) 
      ? $request['return_url'] 
      : config('base_url') . '/?app=neighborhub&view=customer&p=dashboard';

      $basket = $request['basket'] ?? null;
      if (!$basket || empty($basket['items'])) {
        http_response_code(400);
        exit(json_encode([
          'success' => false,
          'error' => 'Your shopping basket is completely empty.'
        ]));
      }

      $stripe_key = getenv('STRIPE_SECRET_KEY');
      $stripePercent = getenv('STRIPE_FEE_PERCENT') ?? 0.029;
      $stripeFlat = getenv('STRIPE_FEE_FLAT') ?? 0.30;

      $customerId = (isset($_SESSION['customer_id'])) ? $_SESSION['customer_id'] : 0;
      $merchantId = $request['merchant_id'];
      $merchant = Merchant::getMerchantById($merchantId);
      $merchantItems = [];
      $stripeLineItems = [];
      $platformTotal = 0;
      $deliveryFee = 0;
      $deliveryMi = 0;
      $merchant_coordinates = array();

      try {
        // 2. Loop through selections and reconstruct values directly from DB models
        foreach ($basket['items'] as $itemKey => $itemData) {
          $productId = intval($itemData['product_id'] ?? 0);
          $clientQty = intval($itemData['quantity'] ?? 1);
          $finalUnitPrice = isset($itemData['unit_price']) ? floatval($itemData['unit_price']) : floatval($itemData['base_price']);
          $merchant_coordinates[$merchantId] = array(
            'lat' => $itemData['merchant_lat'],
            'lon' => $itemData['merchant_lon'],
          );

          if ($productId <= 0 || $merchantId <= 0) continue;

          // Pull pristine database snapshot to safeguard pricing calculations
          $stmt = $db->prepare("SELECT name, price, description, type, meta FROM neighborhub_products WHERE id = ?");
          $stmt->execute([$productId]);
          $dbProduct = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$dbProduct) {
            throw new Exception("Product code ID {$productId} could not be verified in our catalog.");
          }

          // Evaluate base price securely from server rows
          $verifiedUnitPrice = floatval($dbProduct['price']);
          $customizerReceiptText = "";

          // If customized, trace selection nodes to append/subtract modifier price adjustments safely
          if (!empty($itemData['customizations']) && is_array($itemData['customizations'])) {
            $dbMeta = is_string($dbProduct['meta']) ? json_decode($dbProduct['meta'], true) : $dbProduct['meta'];
            $dbSteps = $dbMeta['form_builder']['steps'] ?? [];
            $selectionsSummary = [];

            foreach ($dbSteps as $targetStep) {
              $stepId = $targetStep['id'] ?? '';
              $userChoices = $itemData['customizations'][$stepId] ?? [];
              $choicesArray = is_array($userChoices) ? $userChoices : [$userChoices];

              foreach ($targetStep['options'] as $opt) {
                $optName = $opt['name'] ?? '';
                $optPrice = floatval($opt['price'] ?? 0);
                $isIncluded = !empty($opt['included']);
                $isSelected = in_array($optName, $choicesArray, true);

                if ($isSelected) {
                  $selectionsSummary[] = $optName;

                  // If it's a standard extra option, add its price
                  if (!$isIncluded) {
                    $verifiedUnitPrice += $optPrice;
                  }
                } else {
                  // If an INCLUDED option was unchecked by the user, deduct its price!
                  if ($isIncluded) {
                    $verifiedUnitPrice -= $optPrice;
                  }
                }
              }
            }

            if (!empty($selectionsSummary)) {
              $customizerReceiptText = " (" . implode(', ', $selectionsSummary) . ")";
            }
          }

          // Compute total pricing metrics lines
          $lineTotalCost = $verifiedUnitPrice * $clientQty;
          $platformTotal += $lineTotalCost;

          // Group into order items for fulfillment records later
          $merchantItems[] = [
            'product_id' => $productId,
            'name' => $dbProduct['name'],
            'quantity' => $clientQty,
            'price_at_order' => $verifiedUnitPrice,
            'customizations' => $itemData['customizations'] ?? null
          ];

          // 3. Formulate standard parameters expected by the Stripe API
          $stripeLineItems[] = [
            'price_data' => [
              'currency' => 'usd',
              'product_data' => [
                'name' => $dbProduct['name'] . $customizerReceiptText,
              ],
              'unit_amount' => round($verifiedUnitPrice * 100),
            ],
            'quantity' => $clientQty,
          ];
        }

        if ($request['delivery'] && $merchant->delivery_assignment_mode !== 'disabled') {
          foreach ($merchant_coordinates as $coordinate) {
            $deliveryFeeCalc = Order::calculateDeliveryFee($request['delivery_lat'], $request['delivery_lon'], $coordinate['lat'], $coordinate['lon']);
            $deliveryFee += $deliveryFeeCalc['fee'];
            $deliveryMi += $deliveryFeeCalc['distance_mi'];
          }

          // Delivery Fee
          if ($request['delivery']) {
            $stripeLineItems[] = [
              'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                  'name' => 'Delivery (' . round($deliveryMi, 2) . ' mi)',
                ],
                'unit_amount' => round($deliveryFee * 100),
              ],
              'quantity' => 1,
            ];
          }
        }


        // ============================================================================
        // 🌟 SYNCHRONIZED REVENUE & FEE ENGINE
        // ============================================================================

        // 1. Sales Tax (8.25% of subtotal items)
        $sales_tax = round($platformTotal * 0.0825, 2);

        // 2. Platform Fee Flat Profit Anchor
        $platform_fee = $merchant->platform_flat_fee;
        $stripeLineItems[] = [
          'price_data' => [
            'currency' => 'usd',
            'product_data' => [
              'name' => 'Platform/Basket',
            ],
            'unit_amount' => round($platform_fee * 100),
          ],
          'quantity' => 1,
        ];

        // 3. Extract safe tips from requested dataset
        $tips = 0;
        if (!empty($request['totals']['tips'])) {
          $tips = $request['totals']['tips'];

          $stripeLineItems[] = [
            'price_data' => [
              'currency' => 'usd',
              'product_data' => [
                'name' => 'Tips',
              ],
              'unit_amount' => (int)round($tips * 100, 2),
            ],
            'quantity' => 1,
          ];
        } else if (isset($request['totals']['tips'])) {
          $tips = floatval($request['totals']['tips']);
        }

        // 4. Group all hard costs together before applying Stripe fee protection
        $totalBeforeProcessing = $platformTotal + $deliveryFee + $platform_fee + $tips + $sales_tax;

        // 5. Compute the exact processing fee against the whole card swipe amount
        $processing_fee = round(($stripeFlat + $totalBeforeProcessing * $stripePercent) / (1 - $stripePercent), 2);

        // Recompute absolute matching checkout grand total
        $totalCheckoutAmountWithFees = $totalBeforeProcessing + $processing_fee;

        // Now drop these exact matching variables straight into your $stripeLineItems arrays!
        $stripeLineItems[] = [
          'price_data' => [
            'currency' => 'usd',
            'product_data' => [
              'name' => 'Processing',
            ],
            'unit_amount' => (int)round($processing_fee * 100),
          ],
          'quantity' => 1,
        ];

        // FIX: Missing Sales Tax Line Item injected directly into Stripe manifest
        $stripeLineItems[] = [
          'price_data' => [
            'currency' => 'usd',
            'product_data' => [
              'name' => 'Sales Tax',
            ],
            'unit_amount' => (int)round($sales_tax * 100),
          ],
          'quantity' => 1,
        ];

        $customer = 0;
        if ($customerId) {
          $customer = Customer::getCustomerById($customerId);
        } else if (!empty($request['customer_phone'])) {
          $customer = Customer::getCustomerByPhone($request['customer_phone']);
          $customerId = $customer->id;
        } else if ($app->user->id) {
          $customer = Customer::getCustomerByUserId($app->user->id);
          $customerId = $customer->id;
        }

        if (!$customer) {
          $new_customer = array(
            'user_id' => $app->user->id,
            'display_name' => '',
            'phone' => $request['customer_phone'],
            'status' => 'active',
            'delivery_locations' => array(
              array(
                'address' => $request['delivery_address'],
                'latitude' => $request['delivery_lat'],
                'longitude' => $request['delivery_lon'],
                'delivery_notes' => $request['delivery_notes'],
              ),
            ),
          );
          $newCustomerId = Customer::create($new_customer);
          $customer = Customer::getCustomerById($newCustomerId);
          $customerId = $customer->id;
        } else {

          $update = array();
          if (!empty($request['customer_phone'])) {
            $update['phone'] = $request['customer_phone'];
          };

          $delivery_locations = $customer->delivery_locations;
          $found = false;
          foreach ($delivery_locations as &$location) {
            if (strtolower($location['address']) === strtolower($request['delivery_address'])) {
              $found = true;
              $location['latitude'] = $request['delivery_lat'];
              $location['longitude'] = $request['delivery_lon'];
              $location['delivery_notes'] = $request['delivery_notes'];
            }
          }
          if (!$found) {
            array_unshift($delivery_locations, array(
              'address' => $request['delivery_address'],
              'latitude' => $request['delivery_lat'],
              'longitude' => $request['delivery_lon'],
              'delivery_notes' => $request['delivery_notes'],
            ));
          }

          $update['delivery_locations'] = $delivery_locations;
          $results['update_result'] = Customer::update($customer->id, $update);

          $customer = Customer::getCustomerByUserId($app->user->id);
          $customerId = $customer->id;
        }

        $return_key = bin2hex(random_bytes(16));

        \Stripe\Stripe::setApiKey($stripe_key);

        $session = \Stripe\Checkout\Session::create([
          'payment_method_types' => ['card'],
          'line_items' => $stripeLineItems,
          'mode' => 'payment',
          // Tell Stripe to authorize now, capture manually later
          'payment_intent_data' => [
            'capture_method' => 'manual',
          ],
          'success_url' => $checkout_return_url . '&action=checkout_success&session_key=' . $return_key,
          'cancel_url' => $checkout_return_url . '&action=checkout_cancelled',
          'metadata' => [
            'customer_id' => $customer->id,
            'session_key' => $return_key
          ],
        ]);

        $results['stripe_session'] = $session;

        $pendingOrder = [
          'merchant_id' => $merchantId,
          'customer_id' => $customerId,
          'merchant_package' => $merchantItems,
          'merchants_coordinates' => $merchant_coordinates,
          'delivery_location' => array(
            'address' => $request['delivery_address'],
            'lat' => $request['delivery_lat'],
            'lon' => $request['delivery_lon'],
            'notes' => $request['notes'] ?? '',
          ),
          'subtotal_amount' => $platformTotal,
          'delivery_fee' => $deliveryFee,
          'processing_fee' => $processing_fee,
          'platform_fee' => $platform_fee,
          'tips' => $tips,
          'sales_tax' => $sales_tax,
          'total_amount' => round($totalCheckoutAmountWithFees, 2)
        ];

        $_SESSION[$return_key] = $pendingOrder;
        $_SESSION['customer_id'] = $customerId;

        echo json_encode([
          'success' => true,
          'results' => $results,
          'checkout_url' => $session->url
        ]);
      } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
          'success' => false,
          'error' => $e->getMessage()
        ]);
      }
      break;

    case 'setup_stripe_connect':
      $app = App::getInstance('neighborhub');
      $db = $app->db;
      $userId = $_SESSION['user']['id'];
      $userEmail = $_SESSION['user']['email'] ?? '';

      try {
        $app->includeModel('stripe');

        $stmt = $db->prepare("SELECT stripe_connect_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $stripeConnectId = $stmt->fetchColumn();

        if (!$stripeConnectId) {
          $stmtMerchant = $db->prepare("SELECT id FROM neighborhub_merchants WHERE user_id = ?");
          $stmtMerchant->execute([$userId]);
          $roleType = ($stmtMerchant->fetch()) ? 'merchant' : 'driver';

          $stripeConnectId = StripeModel::createExpressAccount($userId, $userEmail, $roleType);

          $updateStmt = $db->prepare("UPDATE users SET stripe_connect_id = ? WHERE id = ?");
          $updateStmt->execute([$stripeConnectId, $userId]);
        }

        $onboardingUrl = StripeModel::getOnboardingLink($stripeConnectId);

        echo json_encode([
          'success' => true,
          'onboarding_url' => $onboardingUrl
        ]);
      } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
          'success' => false,
          'error' => 'Processor Link Failure: ' . $e->getMessage()
        ]);
      }
      break;

    case 'mock_stripe_success_webhook':
      $pendingOrder = $_SESSION['nh_pending_checkout'] ?? null;
      if (!$pendingOrder) {
        die("No active checkout context session was found to process.");
      }

      $db->beginTransaction();
      try {
        $customerId = intval($pendingOrder['customer_id']);

        foreach ($pendingOrder['merchants_package'] as $merchantId => $lineItems) {

          $merchantSubtotal = 0;
          foreach ($lineItems as $item) {
            $merchantSubtotal += ($item['unit_price'] * $item['quantity']);
          }

          $orderStmt = $db->prepare(
            "INSERT INTO neighborhub_orders (customer_id, merchant_id, total_amount, state) 
                 VALUES (?, ?, ?, 'PENDING_CONFIRMATION')"
          );
          $orderStmt->execute([$customerId, intval($merchantId), $merchantSubtotal]);
          $newOrderId = $db->lastInsertId();

          foreach ($lineItems as $item) {
            $itemStmt = $db->prepare(
              "INSERT INTO neighborhub_order_items (order_id, product_id, quantity, unit_price, customizations) 
                     VALUES (?, ?, ?, ?, ?)"
            );
            $itemStmt->execute([
              intval($newOrderId),
              intval($item['product_id']),
              intval($item['quantity']),
              floatval($item['unit_price']),
              json_encode($item['customizations'])
            ]);
          }
        }

        $db->commit();
        unset($_SESSION['nh_pending_checkout']);

        echo json_encode([
          'success' => true,
          'redirect' => '?view=customer_orders&checkout_complete=1',
        ]);
      } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => "Fatal database integrity failure processing split merchant orders: " . $e->getMessage()]);
      }
      break;

    case 'rtc_create_channel':
      $sid = HubSignalingEngine::createSession($request['my_role'], $request['my_id'], $request['target_role'], $request['target_id'] ?? null);
      echo json_encode(['success' => (bool)$sid, 'session_id' => $sid]);
      exit;

    case 'rtc_post_offer':
      $ok = HubSignalingEngine::postOffer($request['session_id'], $request['offer']);
      echo json_encode(['success' => $ok]);
      exit;

    case 'rtc_check_offers':
      $offers = HubSignalingEngine::findPendingOffers($request['my_role'], $request['my_id']);
      echo json_encode(['success' => true, 'offers' => $offers]);
      exit;

    case 'rtc_post_answer':
      $ok = HubSignalingEngine::postAnswer($request['session_id'], $request['answer'], $request['my_id']);
      echo json_encode(['success' => $ok]);
      exit;

    case 'revert_order_status':
      $ok = handle_revert_order_status($request);
      echo json_encode(['success' => $ok]);
      exit;

    case 'rtc_get_answer':
      $answer = HubSignalingEngine::getAnswer($request['session_id']);
      echo json_encode(['success' => true, 'answer' => $answer]);
      exit;

    case 'reverse_geocode_proxy':
      $lat = filter_var($request['lat'] ?? null, FILTER_VALIDATE_FLOAT);
      $lng = filter_var($request['lng'] ?? null, FILTER_VALIDATE_FLOAT);

      if ($lat === false || $lng === false) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'error' => 'Invalid map coordinates format.']));
      }

      $targetUrl = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat={$lat}&lon={$lng}";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $targetUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'MediaBrain App (contact: admin@mediabrain.app)');
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($httpCode !== 200) {
        http_response_code(502);
        exit(json_encode(['success' => false, 'error' => 'External geocoding authority error response.']));
      }

      echo $response;
      exit;
      break;

    case 'assign_product_category':
      authenticate_user($request);
      $app = App::getInstance('neighborhub');
      $app->includeModel('product');

      $productId    = intval($request['product_id'] ?? 0);
      $menuId     = intval($request['menu_id'] ?? -1);
      $menuName     = trim($request['menu_name'] ?? '');
      $categoryId = intval($request['category_id'] ?? null);
      $categoryName = trim($request['category_name'] ?? '');
      $merchantId   = intval($request['merchant_id'] ?? $_SESSION['user']['merchant_id'] ?? 0);

      if ($productId <= 0 || !isset($request['menu_id']) || !isset($request['category_id']) || $merchantId <= 0) {
        send_json_response([
          'success' => false,
          'message' => 'Product ID, menu name, category name, and merchant ID are required.'
        ], 400);
      }

      try {
        // Calling the static method matching your model signature
        $updated = Product::assignToCategory($productId, $menuId, $menuName, $categoryId, $categoryName, $merchantId);

        if (!$updated['success']) {
          send_json_response([
            'success' => false,
            'message' => 'Failed to assign category. Product, menu, or category not found.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Product assigned to category successfully.',
          'data'    => [
            'product_id'    => $productId,
            'menu_id'       => $updated['menu_id'],
            'menu_name'     => $menuName,
            'category_id'   => $updated['category_id'],
            'category_name' => $categoryName,
            'menu_item_id'  => $updated['menu_item_id'],
          ]
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while assigning the category.'
        ], 500);
      }
      break;

    case 'rename_category':
      authenticate_user($request);
      $app = App::getInstance('neighborhub');
      $app->includeModel('menucategory');

      $categoryId = intval($request['category_id'] ?? 0);
      $newName    = trim($request['new_name'] ?? '');

      if ($categoryId <= 0 || empty($newName)) {
        send_json_response([
          'success' => false,
          'message' => 'Category ID and new name are required.'
        ], 400);
      }

      try {
        $renamed = MenuCategory::rename($categoryId, $newName);

        if (!$renamed) {
          send_json_response([
            'success' => false,
            'message' => 'Failed to rename category. Category not found.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Category renamed successfully.',
          'data'    => [
            'category_id' => $categoryId,
            'new_name'    => $newName
          ]
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while renaming the category.'
        ], 500);
      }
      break;
    case 'update_menu_status':
      authenticate_user($request);
      $app = App::getInstance('neighborhub');
      $app->includeModel('menu');

      $menuId    = intval($request['menu_id'] ?? 0);
      $newStatus = trim($request['new_status'] ?? '');

      if ($menuId <= 0 || empty($newStatus)) {
        send_json_response([
          'success' => false,
          'message' => 'Menu ID and new status are required.'
        ], 400);
      }

      try {
        $updated = Menu::updateStatus($menuId, $newStatus);

        if (!$updated) {
          send_json_response([
            'success' => false,
            'message' => 'Failed to update menu status. Menu not found.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Menu status updated successfully.',
          'data'    => [
            'menu_id'    => $menuId,
            'new_status' => $newStatus
          ]
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while updating the menu status.'
        ], 500);
      }
      break;
      
    case 'update_menu_category_status':
      authenticate_user($request);
      $app = App::getInstance('neighborhub');
      $app->includeModel('menucategory');

      $categoryId = intval($request['category_id'] ?? 0);
      $newStatus  = trim($request['new_status'] ?? '');

      if ($categoryId <= 0 || empty($newStatus)) {
        send_json_response([
          'success' => false,
          'message' => 'Category ID and new status are required.'
        ], 400);
      }

      try {
        $updated = MenuCategory::updateStatus($categoryId, $newStatus);

        if (!$updated) {
          send_json_response([
            'success' => false,
            'message' => 'Failed to update category status. Category not found.'
          ], 404);
        }
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while updating the category status.'
        ], 500);
      }
      break;       


    case 'rename_menu':
      try {
        $merchant_id = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
        $menu_id = isset($request['menu_id']) ? intval($request['menu_id']) : null;
        $new_name = isset($request['menu_name']) ? trim($request['menu_name']) : '';

        $app = App::getInstance();
        $app->includeModel('merchant');
        $app->includeModel('menu');
        $app->includeModel('menucategory');
        $merchant = Merchant::getMerchantById($merchant_id);

        if (!$merchant_id || empty($new_name)) {
          http_response_code(400);
          echo json_encode([
            'success' => false,
            'error' => 'merchant_id and name are required.'
          ]);
          exit;
        }

        if (!$menu_id) {
          //$menu_id = Menu::create($merchant_id, $new_name);
          if ($menu_id) {
            echo json_encode([
              'success' => true,
              'message' => 'Category created successfully.',
              'data' => ['merchant_id' => $merchant_id, 'menu_id' => $menu_id, 'name' => html_entity_decode($new_name)]
            ]);
          } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database update failed.']);
          }
        } else {
          if (Menu::rename($menu_id, $new_name)) {
            echo json_encode([
              'success' => true,
              'message' => 'Category renamed successfully.',
              'data' => ['merchant_id' => $merchant_id, 'menu_id' => $menu_id, 'name' => html_entity_decode($new_name)]
            ]);
          } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database update failed.']);
          }
        }
      } catch (Exception $e) {
        error_log("rename_menu Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal server error.']);
      }
      break;

    case 'delete_menu':
      try {
        $menu_id = isset($request['menu_id']) ? intval($request['menu_id']) : null;

        if (!$menu_id) {
          http_response_code(400);
          echo json_encode(['success' => false, 'error' => 'menu_id is required.']);
          exit;
        }

        $app = App::getInstance();
        $app->includeModel('menu');

        if (Menu::delete($menu_id)) {
          echo json_encode(['success' => true, 'message' => 'Category deleted successfully.']);
        } else {
          http_response_code(500);
          echo json_encode(['success' => false, 'error' => 'Database update failed.']);
        }
      } catch (Exception $e) {
        error_log("delete_menu Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Internal server error.']);
      }
      break;

    case 'reorder_categories':
      $order = $request['order'] ?? [];

      if (empty($order) || !is_array($order)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid order payload']);
        exit;
      }

      $app = App::getInstance();
      $app->includeModel('menucategory');
      if (MenuCategory::updateSortOrders($order)) {
        echo json_encode(['success' => true]);
      } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
      }
      break;

    case 'remove_category_from_menu':
      $categoryId = intval($request['category_id'] ?? 0);

      if ($categoryId <= 0) {
        send_json_response([
          'success' => false,
          'message' => 'Valid category ID and menu ID are required.'
        ], 400);
      }

      try {
        $app = App::getInstance();
        $app->includeModel('menucategory');

        $removed = MenuCategory::delete($categoryId);

        if (!$removed) {
          send_json_response([
            'success' => false,
            'message' => 'Category or menu association could not be found.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Category removed from menu successfully.'
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while removing category from menu.'
        ], 500);
      }
      break;

    case 'reorder_products':
      $categoryId = $request['category_id'] ?? [];
      $order = $request['order'] ?? [];

      if (empty($order) || !is_array($order)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid order payload']);
        exit;
      }

      $app = App::getInstance();
      $app->includeModel('menucategory');
      if (MenuCategory::updateMenuItemOrder($categoryId, $order)) {
        echo json_encode(['success' => true]);
      } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
      }
      break;

    case 'remove_product_from_menu':
      authenticate_user($request);

      $productId = intval($request['product_id'] ?? 0);
      $menuId    = intval($request['menu_id'] ?? 0);

      if ($productId <= 0 || $menuId <= 0) {
        send_json_response([
          'success' => false,
          'message' => 'Valid product ID and menu ID are required.'
        ], 400);
      }

      try {
        $app = App::getInstance();
        $app->includeModel('product');

        $removed = Product::removeFromMenu($productId, $menuId);

        if (!$removed) {
          send_json_response([
            'success' => false,
            'message' => 'Product or menu association could not be found.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Product removed from menu successfully.'
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while removing product from menu.'
        ], 500);
      }
      break;

    case 'update_product_price':
      authenticate_user($request);

      $productId = intval($request['product_id'] ?? 0);
      $rawPrice  = $request['price'] ?? null;

      if ($productId <= 0 || !is_numeric($rawPrice) || floatval($rawPrice) < 0) {
        send_json_response([
          'success' => false,
          'message' => 'Valid product ID and non-negative numeric price are required.'
        ], 400);
      }

      $price = round(floatval($rawPrice), 2);

      try {
        $updated = Product::updatePrice($productId, $price);

        if (!$updated) {
          send_json_response([
            'success' => false,
            'message' => 'Product not found or price unchanged.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Product price updated successfully.',
          'data'    => ['product_id' => $productId, 'price' => $price]
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while updating the product price.'
        ], 500);
      }
      break;

    case 'toggle_product_availability':
      authenticate_user($request);

      $productId = intval($request['product_id'] ?? 0);

      if ($productId <= 0) {
        send_json_response([
          'success' => false,
          'message' => 'Valid product ID is required.'
        ], 400);
      }

      $isAvailable = isset($request['is_available'])
        ? filter_var($request['is_available'], FILTER_VALIDATE_BOOLEAN)
        : null;

      try {
        $newStatus = Product::updateAvailability($productId, $isAvailable);

        if ($newStatus === false) {
          send_json_response([
            'success' => false,
            'message' => 'Product not found or availability status could not be changed.'
          ], 404);
        }

        send_json_response([
          'success' => true,
          'message' => 'Product availability updated.',
          'data'    => ['product_id' => $productId, 'is_available' => $newStatus]
        ]);
      } catch (Exception $e) {
        send_json_response([
          'success' => false,
          'message' => 'An error occurred while toggling product availability.'
        ], 500);
      }
      break;

    case 'export_data':
      handle_export_data($request);
      break;

    default:
      http_response_code(404);
      exit(json_encode(array(
        'success' => false,
        'error' => 'Action not found: ' . $action
      )));
  }
} catch (Exception $e) {
  error_log("API Error in action '$action': " . $e->getMessage());
  http_response_code(500);
  exit(json_encode(array(
    'success' => false,
    'error' => 'An unexpected error occurred'
  )));
}

function calculate_delivery_fee($request)
{
  $app = App::getInstance();
  $app->includeModel('order');
  $deliveryFee = Order::calculateDeliveryFee($request['merchant_lat'], $request['merchant_lon'], $request['delivery_lat'], $request['delivery_lon']);

  echo json_encode([
    'success' => true,
    'deliveryFee' => $deliveryFee,
  ]);
}

function compressOrderMetadata($cartData)
{
  $compressed = [];

  foreach ($cartData as $item) {
    $compressed[] = [
      'i' => $item['product_id'],
      'q' => $item['quantity'],
      'c' => array_filter([
        'd' => $item['customizations']['doneness'] ?? null,
        'f' => !empty($item['customizations']['free_toppings']) ? $item['customizations']['free_toppings'] : null,
        'p' => !empty($item['customizations']['premium_additions']) ? $item['customizations']['premium_additions'] : null,
        's' => $item['customizations']['size'] ?? null,
        'cr' => $item['customizations']['crust'] ?? null,
        't' => !empty($item['customizations']['toppings']) ? $item['customizations']['toppings'] : null,
      ])
    ];
  }

  $json = json_encode($compressed);

  if (strlen($json) > 500) {
    return json_encode(['warn' => 'Payload too large, check local logs']);
  }

  return $json;
}

function validateMerchantStaff($userId, $merchantId)
{
  $db = App::getInstance()->db;
  try {
    $stmt = $db->prepare(
      "SELECT id, staff_role FROM neighborhub_merchant_users 
             WHERE user_id = ? AND merchant_id = ? AND status = 'active'"
    );
    $stmt->execute([$userId, $merchantId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
      return false;
    }

    return $result;
  } catch (Exception $e) {
    error_log("Staff validation error: " . $e->getMessage());
    return false;
  }
}


function handle_get_order($request)
{
  $app = App::getInstance();
  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;

  if (!$orderId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id parameter is required'
    )));
  }

  $app->includeModel('order');
  $app->includeModel('merchant');
  $order = Order::getOrderById($orderId);

  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  $merchant = Merchant::getMerchantById($order['merchant_id']);
  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order' => $order,
    'merchant' => $merchant
  )));
}

function handle_list_customer_orders($request)
{
  $app = App::getInstance('neighborhub');
  $app->includeModel('order');

  // FIX: point parameters safely to $request context workspace
  $customerId = isset($request['customer_id']) ? intval($request['customer_id']) : null;
  $state = isset($request['state']) ? sanitize_text_field($request['state']) : null;
  $limit = isset($request['limit']) ? intval($request['limit']) : 50;
  $offset = isset($request['offset']) ? intval($request['offset']) : 0;

  if ((int)$customerId == 0) {
    http_response_code(200);
    exit(json_encode(array(
      'success' => true,
      'orders' => array(),
      'count' => 0
    )));
  }

  if ((int)$customerId !== (int)$_SESSION['customer_id'] && $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'You can only view your own orders'
    )));
  }

  $orders = ($app->user->id) ? Order::getOrdersByCustomerId($customerId, $state, $limit, $offset) : array();

  if ($orders === false) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to retrieve orders'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'orders' => $orders,
    'count' => count($orders)
  )));
}

function handle_create_pos_order($request)
{
  $app = App::getInstance('neighborhub');
  $db = $app->db;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $items = isset($request['items']) && is_array($request['items']) ? $request['items'] : [];
  $paymentMethod = isset($request['payment_method']) ? sanitize_text_field($request['payment_method']) : 'CASH';

  if (!$merchantId || empty($items)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'POS orders require a valid merchant scope and an items selection array.']));
  }

  if (!validateMerchantStaff($_SESSION['user']['id'], $merchantId) && !($app->user->is_admin ?? false)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Access Denied: Your account profile is not registered to this checkout terminal.']));
  }

  $platformSubtotal = 0;
  $processedItems = [];

  foreach ($items as $item) {
    $productId = intval($item['product_id']);
    $qty = intval($item['quantity']);

    $stmt = $db->prepare("SELECT name, price FROM neighborhub_products WHERE id = ? AND merchant_id = ?");
    $stmt->execute([$productId, $merchantId]);
    $productRow = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$productRow) continue;

    $verifiedUnitPrice = floatval($productRow['price']);
    $platformSubtotal += ($verifiedUnitPrice * $qty);

    $processedItems[] = [
      'product_id' => $productId,
      'quantity' => $qty,
      'price_at_order' => $verifiedUnitPrice,
      'customizations' => $item['customizations'] ?? null
    ];
  }

  $salesTaxCalculated = round($platformSubtotal * 0.0825, 2);
  $totalAmountFinal = $platformSubtotal + $salesTaxCalculated;

  $db->beginTransaction();
  try {
    $orderStmt = $db->prepare(
      "INSERT INTO neighborhub_orders 
         (customer_id, merchant_id, subtotal_amount, sales_tax, total_amount, state, order_type, payment_method, notes) 
         VALUES (NULL, ?, ?, ?, ?, 'PAID_RETAIL', 'WALK_IN', ?, 'Counter POS Transaction')"
    );
    $orderStmt->execute([
      $merchantId,
      $platformSubtotal,
      $salesTaxCalculated,
      $totalAmountFinal,
      $paymentMethod
    ]);
    $newOrderId = $db->lastInsertId();

    foreach ($processedItems as $pItem) {
      $itemStmt = $db->prepare(
        "INSERT INTO neighborhub_order_items (order_id, product_id, quantity, unit_price, customizations) 
           VALUES (?, ?, ?, ?, ?)"
      );
      $itemStmt->execute([
        $newOrderId,
        $pItem['product_id'],
        $pItem['quantity'],
        $pItem['price_at_order'],
        json_encode($pItem['customizations'])
      ]);
    }

    $db->commit();

    echo json_encode([
      'success' => true,
      'order_id' => $newOrderId,
      'message' => 'Counter sale completed successfully.'
    ]);
  } catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'Database commit failure on terminal registration: ' . $e->getMessage()]));
  }
}

function handle_confirm_order($request)
{
  $app = App::getInstance();
  $app->includeModel('merchant');
  $app->includeModel('order');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $merchant = Merchant::getMerchantById($merchantId);

  if (!$orderId || !$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id and merchant_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $order = Order::getOrderById($orderId);
  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  if ($order['merchant_id'] !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order does not belong to this merchant'
    )));
  }

  // --- NEW: CAPTURE STRIPE AUTHORIZATION ---
  if (!empty($order['stripe_payment_intent_id'])) {
    try {
      // Ensure API key is configured
      $stripe_key = getenv('STRIPE_SECRET_KEY'); // or your key getter
      \Stripe\Stripe::setApiKey($stripe_key);

      $intent = \Stripe\PaymentIntent::retrieve($order['stripe_payment_intent_id']);

      // Only capture if it's currently sitting in requires_capture status
      if ($intent->status === 'requires_capture') {
        $intent->capture();

        // If the merchant does not have a Stripe API key, we will handle the funds in our platform's central vault.
        if (!$merchant->stripe_api_key) {
          // ============================================================================
          // 📊 TRUSTED FINANCIAL SPLIT MANAGEMENT ENGINE
          // ============================================================================

          // 🌟 FIX 1: Do not rely on the loop ($item['price_at_order'] is missing from decompression).
          // Pull the catalog-verified item total straight from your secure session matrix.
          $merchantSubtotal = floatval($order['subtotal_amount']);

          // 🌟 FIX 2: Calculate your 4% software app usage platform cut
          $merchantAppFeePercent = $merchant->platform_fee_rate;
          $appFeeCutFromMerchant = round($merchantSubtotal * $merchantAppFeePercent, 2);

          // Subtract your cut to find exactly what the merchant takes home
          $finalMerchantPayout = $merchantSubtotal - $appFeeCutFromMerchant;


          // Add ALL funds from the Stripe transaction card swipe into the platform's central pool account (User ID: 1)
          Vault::addFundsToUser(1, $order['total_amount'], "Customer Payment Deposit for Order #{$order['id']}");

          // Transfer the merchant's final home-take share (Subtotal minus 4% fee) to their linked user wallet account
          Vault::transfer(1, $merchant->user_id, $finalMerchantPayout, "Merchant Net Share Distribution for Order #{$order['id']} (4% App Fee Deducted)");
        }
      }
    } catch (\Exception $e) {
      error_log("Stripe Capture Failed for Order {$orderId}: " . $e->getMessage());
      http_response_code(500);
      exit(json_encode(array(
        'success' => false,
        'error' => 'Payment capture failed: ' . $e->getMessage()
      )));
    }
  }

  $result = Order::confirmOrder($orderId);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to confirm order'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Order confirmed successfully'
  )));
}

function handle_revert_order_status($request)
{
  $app = App::getInstance();
  $app->includeModel('order');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $revertToState = isset($request['revert_to']) ? sanitize_text_field($request['revert_to']) : null;

  if (!$orderId || !$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id and merchant_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $order = Order::getOrderById($orderId);
  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  if ($order['merchant_id'] !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order does not belong to this merchant'
    )));
  }

  $result = Order::revertOrderStatus($orderId, $revertToState);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to revert order status'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Order status reverted successfully'
  )));
}

function handle_admin_save_courier($request)
{
  $app = App::getInstance('neighborhub');
  $db = $app->db;
  $app->includeModel('courier');

  if (empty($_SESSION['user']['is_admin']) && !$app->user->is_admin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Administrative privileges required.']);
    exit;
  }

  $courierId   = intval($request['id'] ?? 0);
  $ownerEmail  = trim($request['user_email'] ?? '');

  if (empty($ownerEmail)) {
    echo json_encode(['success' => false, 'error' => 'User account email is required.']);
    exit;
  }

  $courierData = [
    'business_name' => trim($request['business_name'] ?? ''),
    'phone'         => trim($request['phone'] ?? ''),
    'vehicle_type'  => $request['vehicle_type'] ?? 'WALKING',
    'status'        => $request['status'] ?? 'offline'
  ];

  if (empty($courierData['business_name']) || empty($courierData['phone'])) {
    echo json_encode(['success' => false, 'error' => 'Missing operational profile field information.']);
    exit;
  }

  try {
    $userQuery = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $userQuery->execute([$ownerEmail]);
    $userRow = $userQuery->fetch(PDO::FETCH_ASSOC);

    if (!$userRow) {
      echo json_encode(['success' => false, 'error' => "No registered system account found matching: {$ownerEmail}"]);
      exit;
    }

    $targetUserId = intval($userRow['id']);
    $courierData['user_id'] = $targetUserId;

    if ($courierId > 0) {
      $stmt = $db->prepare("
                    UPDATE neighborhub_couriers 
                    SET business_name = ?, phone = ?, vehicle_type = ?, status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
      $success = $stmt->execute([
        $courierData['business_name'],
        $courierData['phone'],
        $courierData['vehicle_type'],
        $courierData['status'],
        $courierId
      ]);
    } else {
      if (Courier::getCourierByUserId($targetUserId)) {
        echo json_encode(['success' => false, 'error' => 'This user account email already has a courier profile registered.']);
        exit;
      }
      $success = Courier::create($courierData);
    }

    echo json_encode(['success' => (bool)$success]);
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server lookup error: ' . $e->getMessage()]);
  }
  exit;
}

function handle_get_pending_orders($request)
{
  $app = App::getInstance();
  $app->includeModel('order');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;

  if (!$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id parameter is required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $pendingConfirmOrders = Order::getOrdersByMerchantId($merchantId, 'PENDING_CONFIRMATION', 50, 0, true, 'components/cards/pending_order_card.php');
  $confirmedOrders = Order::getOrdersByMerchantId($merchantId, 'CONFIRMED', 50, 0, true, 'components/cards/confirmed_order_card.php');
  $readyOrders = Order::getOrdersByMerchantId($merchantId, 'READY_FOR_PICKUP', 50, 0, true, 'components/cards/ready_order_card.php');

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'orders' => array(
      'pending' => $pendingConfirmOrders,
      'confirmed' => $confirmedOrders,
      'ready' => $readyOrders,
    ),
    'count' => count($pendingConfirmOrders) + count($confirmedOrders) + count($readyOrders),
  )));
}

function handle_update_product_availability($request)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $isAvailable = isset($request['is_available']) ? intval($request['is_available']) : null;

  if (!$productId || !$merchantId || $isAvailable === null) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'product_id, merchant_id, and is_available parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $product = Product::getProductById($productId);

  if (!$product) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product not found'
    )));
  }

  if (intval($product['merchant_id']) !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product does not belong to this merchant'
    )));
  }

  $result = Product::updateAvailability($productId, (bool)$isAvailable);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to update product availability'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'product_id' => $productId,
    'is_available' => (bool)$isAvailable,
    'message' => 'Product availability updated successfully'
  )));
}

function handle_get_available_jobs($request)
{
  $app = App::getInstance();
  $app->includeModel('order');

  $limit = isset($request['limit']) ? intval($request['limit']) : 50;
  $offset = isset($request['offset']) ? intval($request['offset']) : 0;

  $jobs = Order::getAvailableOrders($limit, $offset, false, 'components/cards/courier_ready_card.php');

  if ($jobs === false) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to retrieve available jobs'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'jobs' => $jobs,
    'count' => count($jobs)
  )));
}

function handle_accept_delivery($request)
{
  $app = App::getInstance();
  $db = $app->db;
  $app->includeModel('order');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;

  if (!$orderId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id parameter is required'
    )));
  }

  $courierStmt = $db->prepare(
    "SELECT id FROM neighborhub_couriers WHERE user_id = ?"
  );
  $courierStmt->execute([$_SESSION['user']['id']]);
  $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);

  if (!$courier) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'You are not registered as a courier'
    )));
  }

  $courierId = $courier['id'];
  $result = Order::acceptDeliveryJob($orderId, $courierId);

  if (!$result) {
    http_response_code(409);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order is not available for delivery or has already been accepted'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'courier_id' => $courierId,
    'message' => 'Delivery accepted successfully'
  )));
}

function handle_update_location($request)
{
  $app = App::getInstance();
  $db = $app->db;
  $app->includeModel('courier');

  $latitude = isset($request['latitude']) ? floatval($request['latitude']) : null;
  $longitude = isset($request['longitude']) ? floatval($request['longitude']) : null;
  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;
  $courierUserId = $app->user->id;
  $statusUpdate = isset($request['status_update']) ? sanitize_text_field($request['status_update']) : null;

  if ($latitude === null || $longitude === null) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'latitude and longitude parameters are required'
    )));
  }

  if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Invalid coordinates: latitude must be -90 to 90, longitude must be -180 to 180'
    )));
  }

  $courierStmt = $db->prepare(
    "SELECT id FROM neighborhub_couriers WHERE user_id = ?"
  );
  $courierStmt->execute([$courierUserId]);
  $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);

  if (!$courier) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'You are not registered as a courier'
    )));
  }

  $courierId = $courier['id'];
  $locationUpdated = Courier::updateLocation($courierId, $latitude, $longitude);

  if (!$locationUpdated) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to update location'
    )));
  }

  if ($orderId) {
    $trackingStmt = $db->prepare(
      "INSERT INTO neighborhub_delivery_tracking 
            (order_id, courier_id, latitude, longitude, status_update, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())"
    );

    try {
      $trackingStmt->execute([
        $orderId,
        $courierId,
        $latitude,
        $longitude,
        $statusUpdate
      ]);
    } catch (Exception $e) {
      error_log("Tracking history insert failed: " . $e->getMessage());
    }
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'courier_id' => $courierId,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'message' => 'Location updated successfully'
  )));
}

function get_admin_live_tracking_metrics()
{
  try {
    $db = App::getInstance()->db;

    $couriersStmt = $db->query(
      "SELECT id, business_name, phone, status, latitude, longitude, last_location_update 
             FROM neighborhub_couriers 
             WHERE status IN ('available', 'on_delivery')"
    );
    $couriers = $couriersStmt->fetchAll(PDO::FETCH_ASSOC);

    $merchantsStmt = $db->query(
      "SELECT id, business_name, address, latitude, longitude 
             FROM neighborhub_merchants 
             WHERE status = 'active'"
    );
    $merchants = $merchantsStmt->fetchAll(PDO::FETCH_ASSOC);

    $ordersStmt = $db->query(
      "SELECT id, order_number, merchant_id, courier_id, state, pickup_address, delivery_address 
             FROM neighborhub_orders 
             WHERE state IN ('READY_FOR_PICKUP', 'IN_TRANSIT')"
    );
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
      'success'  => true,
      'couriers' => $couriers,
      'merchants' => $merchants,
      'orders'   => $orders
    ]);
    exit;
  } catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
  }
}

function handle_complete_delivery($request)
{
  $app = App::getInstance();
  $db = $app->db;
  $app->includeModel('order');
  $app->includeModel('courier');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;

  if (!$orderId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id parameter is required'
    )));
  }

  $courierStmt = $db->prepare(
    "SELECT id FROM neighborhub_couriers WHERE user_id = ?"
  );
  $courierStmt->execute([$_SESSION['user']['id']]);
  $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);

  if (!$courier) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'You are not registered as a courier'
    )));
  }

  $courierId = $courier['id'];

  $order = Order::getOrderById($orderId);
  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  if ($order['courier_id'] !== $courierId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'This order is not assigned to you'
    )));
  }

  $deliveryCompleted = Order::completeDelivery($orderId);

  if (!$deliveryCompleted) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to complete delivery'
    )));
  }

  Courier::incrementDeliveryCount($courierId);

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Delivery completed successfully'
  )));
}

function handle_get_merchants()
{
  $app = App::getInstance();
  $db = $app->db;
  $app->includeModel('merchant');

  $merchants = array();

  $stmt = $db->prepare(
    "SELECT 
            id, business_name, address, latitude, longitude, phone, status
        FROM neighborhub_merchants
        WHERE status = 'active'
        ORDER BY business_name ASC"
  );

  try {
    $stmt->execute();
    $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to retrieve merchants'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'merchants' => $merchants ? $merchants : array(),
    'count' => count($merchants)
  )));
}

function handle_get_merchant_products($request)
{
  $app = App::getInstance();
  $app->includeModel('merchant');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;

  if (!$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id parameter is required'
    )));
  }

  $products = Merchant::getProductsCatalog($merchantId, true, 'object');

  if ($products === false) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to retrieve products'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'merchant_id' => $merchantId,
    'products' => $products,
    'count' => count($products)
  )));
}

function handle_get_courier_profile()
{
  $app = App::getInstance();
  $app->includeModel('courier');

  $profile = Courier::getCourierByUserId($_SESSION['user']['id']);

  if (!$profile) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Courier profile not found'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'profile' => $profile
  )));
}

function handle_lookup_user_by_email($request)
{
  $app = App::getInstance();
  $db = $app->db;

  if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Administrator privileges required to lookup user emails'
    )));
  }

  $email = isset($request['email']) ? sanitize_text_field($request['email']) : null;

  if (!$email) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'email parameter is required'
    )));
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Invalid email format'
    )));
  }

  try {
    $stmt = $db->prepare(
      "SELECT id FROM users WHERE email = ? LIMIT 1"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      http_response_code(404);
      exit(json_encode(array(
        'success' => false,
        'error' => 'User with this email address not found'
      )));
    }

    http_response_code(200);
    exit(json_encode(array(
      'success' => true,
      'user_id' => intval($user['id']),
      'message' => 'User lookup successful'
    )));
  } catch (Exception $e) {
    error_log("User email lookup error: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'An error occurred during user lookup'
    )));
  }
}

function handle_provision_merchant($request = null)
{
  $app = App::getInstance('neighborhub');
  $db = $app->db;
  $app->includeModel('merchant');

  if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Administrator privileges required to provision merchants'
    )));
  }

  $merchantId = isset($request['merchant_id']) ? sanitize_text_field($request['merchant_id']) : null;
  $businessName = isset($request['business_name']) ? $request['business_name'] : null;
  $address = isset($request['address']) ? sanitize_text_field($request['address']) : null;
  $latitude = isset($request['latitude']) ? floatval($request['latitude']) : null;
  $longitude = isset($request['longitude']) ? floatval($request['longitude']) : null;
  $phone = isset($request['phone']) ? sanitize_text_field($request['phone']) : null;
  $ownerUserId = isset($request['owner_user_id']) ? intval($request['owner_user_id']) : null;
  $website = isset($request['website']) ? $request['website'] : null;
  $facebook = isset($request['facebook']) ? $request['facebook'] : null;
  $platform_fee_rate = isset($request['platform_fee_rate']) ? $request['platform_fee_rate'] : null;
  $platform_flat_fee = isset($request['platform_flat_fee']) ? $request['platform_flat_fee'] : null;
  $store_hours = isset($request['store_hours']) ? $request['store_hours'] : null;
  $menus = isset($request['menus']) ? $request['menus'] : null;
  $delivery_assignment_mode = isset($request['delivery_assignment_mode']) ? $request['delivery_assignment_mode'] : null;
  $delivery_max_distance = isset($request['delivery_max_distance']) ? $request['delivery_max_distance'] : null;
  $stripeApiKey = isset($request['stripe_api_key']) ? $request['stripe_api_key'] : null;
  $stripe_percent_fee = isset($request['stripe_percent_fee']) ? $request['stripe_percent_fee'] : null;
  $stripe_flat_fee = isset($request['stripe_flat_fee']) ? $request['stripe_flat_fee'] : null;
  $status = isset($request['status']) ? $request['status'] : null;

  if (!$businessName) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'business_name parameter is required'
    )));
  }

  try {
    $db->beginTransaction();

    $merchantData = array(
      'business_name' => $businessName,
      'address' => $address,
      'latitude' => $latitude,
      'longitude' => $longitude,
      'phone' => $phone,
      'user_id' => $ownerUserId,
      'website' => $website,
      'facebook' => $facebook,
      'platform_fee_rate' => $platform_fee_rate,
      'platform_flat_fee' => $platform_flat_fee,
      'store_hours' => $store_hours,
      'menus' => $menus,
      'delivery_assignment_mode' => $delivery_assignment_mode,
      'delivery_max_distance' => $delivery_max_distance,
      'stripe_api_key' => $stripeApiKey,
      'stripe_percent_fee' => $stripe_percent_fee,
      'stripe_flat_fee' => $stripe_flat_fee,
      'status' => $status,
    );

    if ($merchantId === '-1') {
      $merchantId = Merchant::create($merchantData);
      if (!$merchantId) {
        $db->rollBack();
        http_response_code(500);
        exit(json_encode(array(
          'success' => false,
          'error' => 'Failed to create merchant profile'
        )));
      }

      if ($ownerUserId) {
        $staffAdded = Merchant::addStaffMember($merchantId, $ownerUserId, 'owner');

        if (!$staffAdded) {
          $db->rollBack();
          http_response_code(500);
          exit(json_encode(array(
            'success' => false,
            'error' => 'Failed to bind staff ownership to merchant'
          )));
        }
      }
    } else {
      $updated = Merchant::update($merchantId, $merchantData);
      if (!$updated) {
        $db->rollBack();
        http_response_code(500);
        exit(json_encode(array(
          'success' => false,
          'error' => 'Failed to update merchant profile properties'
        )));
      }
    }
    $image_result = null;
    if (!$merchantId) {
      http_response_code(500);
      exit(json_encode(array(
        'success' => false,
        'error' => 'Failed to update merchant'
      )));
    } else {
      if (!empty($_FILES)) {
        $image_result = handle_merchant_upload_image($request);
      }
    }

    $db->commit();

    http_response_code(200);
    exit(json_encode(array(
      'success' => true,
      'message' => 'Merchant profile and staff ownership badges provisioned successfully via model layers.',
      'merchant_id' => intval($merchantId),
      'image_upload_result' => $image_result,
    )));
  } catch (Exception $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }

    error_log("Merchant provisioning error: " . $e->getMessage());

    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'An error occurred during merchant provisioning'
    )));
  }
}

function handle_merchant_upload_image($request = null)
{
  $app = App::getInstance();
  $app->includeModel('merchant');
  $errors = array();

  if (!isset($_FILES['gallery_images']) && (!isset($_FILES['merchant_image']) || empty($_FILES['merchant_image']['name']))) {
    http_response_code(400);
    return array(
      'success' => false,
      'error' => 'No image file provided. Please upload an image file.'
    );
  }

  $imageFile = $_FILES['merchant_image'];

  if (isset($_FILES['merchant_image']) && ($imageFile['error'] !== UPLOAD_ERR_OK)) {
    $errorMessages = array(
      UPLOAD_ERR_INI_SIZE => 'File exceeds php.ini upload_max_filesize',
      UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
      UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
      UPLOAD_ERR_NO_FILE => 'No file was uploaded',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
      UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    );
    $errors['merchant_image'] = $errorMessages[$imageFile['error']] ?? 'Unknown upload error';
  }

  $merchantId = intval($request['merchant_id']);

  if (!$merchantId) {
    return array(
      'success' => false,
      'error' => 'merchant_id parameter is required'
    );
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord)  && (!$app->user->is_admin)) {
    $errors['auth'] = 'Unauthorized: No active staff record for this merchant';
  }

  require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';

  try {
    $storageManager = new FileStorageManager('google_cloud');
  } catch (Exception $e) {
    error_log("Failed to initialize FileStorageManager: " . $e->getMessage());
    $errors['storage'] = 'Storage system initialization failed';
  }

  try {
    $publicUrl = null;
    $uniqueFilename = null;

    if (isset($_FILES['merchant_image']) && !empty($_FILES['merchant_image']['name'])) {

      $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId);

      $extension = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
      $extension = strtolower(preg_replace('/[^a-z0-9]/', '', $extension));

      if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
        $errors['storage'] = 'Invalid image file type. Allowed: jpg, jpeg, png, gif, webp';
      }

      $uniqueFilename = bin2hex(random_bytes(16)) . '.' . $extension;

      $uploadOptions = array(
        'process_image' => true,
        'max_width' => 800,
        'max_height' => 800,
        'quality' => 85,
        'convert_to_webp' => true
      );

      $uploadResult = $storageManager->uploadFile(
        $imageFile,
        $targetPath,
        $uniqueFilename,
        $uploadOptions
      );

      if (!$uploadResult['success']) {
        $errors['storage'] = 'Image upload failed: ' . ($uploadResult['error'] ?? 'Unknown error');
      }

      $publicUrl = $uploadResult['url'] ?? null;

      if (!$publicUrl) {
        error_log("Failed to generate public URL for uploaded image: " . $uniqueFilename);
        $errors['storage'] = 'Failed to generate public URL for uploaded image';
      }

      Merchant::update($merchantId, array('image_url' => $publicUrl));
    }

    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
      $galleryUrls = AssetManager::uploadMultipleImages('merchant', $merchantId, $merchantId, $_FILES['gallery_images']);
    }

    if (!empty($errors)) {
      http_response_code(400);
      return array(
        'success' => false,
        'errors' => $errors
      );
    }

    return array(
      'success' => true,
      'image_url' => $publicUrl,
      'gallery_urls' => $galleryUrls ?? array(),
      'filename' => $uniqueFilename,
      'type' => 'merchant', // FIX: Fixed referencing of non-existent variable
      'merchant_id' => intval($merchantId),
      'message' => 'Image uploaded and optimized successfully'
    );
  } catch (Exception $e) {
    error_log("Image upload handler exception: " . $e->getMessage());
    return array(
      'success' => false,
      'error' => 'An error occurred during image upload: ' . $e->getMessage()
    );
  }
}

function handle_product_upload_image($request)
{
  $app = App::getInstance();
  $app->includeModel('product');
  $app->includeClass('assetmanager');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;

  if (!$merchantId || !$productId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id and product_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: Active merchant staff credentials required'
    )));
  }

  $primaryUrl = null;
  $galleryUrls = array();
  $processedAnything = false;

  if (isset($_FILES['product_image']) && !empty($_FILES['product_image']['name'])) {
    $imageFile = $_FILES['product_image'];
    if ($imageFile['error'] === UPLOAD_ERR_OK) {
      $primaryUrl = Product::uploadImage($productId, $merchantId, $imageFile);
      if (!$primaryUrl) {
        http_response_code(500);
        exit(json_encode(array('success' => false, 'error' => 'Primary image processing lifecycle failed')));
      }
      $processedAnything = true;
    }
  }

  if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
    $galleryUrls = AssetManager::uploadMultipleImages('product', $productId, $merchantId, $_FILES['gallery_images']);
    $processedAnything = true;
  }

  if (!$processedAnything) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'No active image or gallery payloads detected.'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'public_url' => $primaryUrl,
    'gallery_urls' => $galleryUrls,
    'merchant_id' => $merchantId,
    'product_id' => $productId,
    'message' => 'Image assets processed and locked successfully.'
  )));
}

function handle_product_delete_image($request)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;

  if (!$merchantId || !$productId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id and product_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $deletedFilename = Product::deleteImage($productId, $merchantId);

  if (!$deletedFilename) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to delete product image from system tracking'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'filename' => $deletedFilename,
    'merchant_id' => $merchantId,
    'product_id' => $productId,
    'message' => 'Product image deleted and cleared successfully'
  )));
}

function handle_merchant_delete_image($request)
{
  $app = App::getInstance();
  $app->includeModel('merchant');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;

  if (!$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id and product_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $deleteImage = Merchant::deleteImage($merchantId);

  if (!$deleteImage['success']) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to delete merchant image'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'message' => 'Merchant image deleted and cleared successfully'
  )));
}

function handle_delete_product_gallery_image($request)
{
  $app = App::getInstance('neighborhub');
  $productId = intval($request['product_id'] ?? 0);
  $merchantId = intval($request['merchant_id'] ?? 0);
  $imageId = intval($request['image_id'] ?? 0);

  if (!validateMerchantStaff($_SESSION['user']['id'], $merchantId) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized Access permissions.']));
  }

  $app->includeModel('product');
  $deleted = Product::deleteGalleryImage($productId, $merchantId, $imageId);

  if ($deleted) {
    exit(json_encode(['success' => true]));
  } else {
    exit(json_encode(['success' => false, 'error' => 'Failed to drop database or target cloud file.']));
  }
}

function handle_create_product($request = null)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $name = isset($request['name']) ? sanitize_text_field($request['name']) : null;
  $price = isset($request['price']) ? floatval($request['price']) : null;
  $meta = $request['meta'] ?? '';

  if (!$merchantId || !$name || $price === null) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'merchant_id, name, and price parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $productData = array(
    'name' => $name,
    'price' => $price,
  );

  if (isset($request['description'])) {
    $productData['description'] = sanitize_text_field($request['description']);
  }

  if (isset($request['tags'])) {
    $productData['tags'] = sanitize_text_field($request['tags']);
  }

  if (isset($request['image_url'])) {
    $productData['image_url'] = sanitize_text_field($request['image_url']);
  }

  if (!empty(trim($meta))) {
    $decoded = json_decode($meta, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      $productData['meta'] = json_encode($decoded);
    } else {
      $productData['meta'] = '[]';
    }
  } else {
    $productData['meta'] = '[]';
  }

  $productId = Product::create($merchantId, $productData);
  $request['product_id'] = $productId;


  if (!$productId) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to create product'
    )));
  } else {
    if (!empty($_FILES)) {
      handle_product_upload_image($request);
    }
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'product_id' => intval($productId),
    'merchant_id' => intval($merchantId),
    'message' => 'Product created successfully'
  )));
}

function handle_get_product($request = null)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;

  if (!$productId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'product_id parameter is required'
    )));
  }

  $product = Product::getProductById($productId);

  if (!$product) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to find product'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'product' => $product,
  )));
}

function handle_update_product($request)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;
  $meta = isset($request['meta']) ? $request['meta'] : '';

  if (!$productId || !$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'product_id and merchant_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $product = Product::getProductById($productId);
  if (!$product) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product not found'
    )));
  }

  if ($product['merchant_id'] !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product does not belong to this merchant'
    )));
  }

  $updateData = array();

  if (isset($request['name'])) {
    $updateData['name'] = sanitize_text_field($request['name']);
  }

  if (isset($request['description'])) {
    $updateData['description'] = sanitize_text_field($request['description']);
  }

  if (isset($request['price'])) {
    $updateData['price'] = floatval($request['price']);
  }

  if (isset($request['type'])) {
    $updateData['type'] = sanitize_text_field($request['type']);
  }

  if (isset($request['tags'])) {
    $updateData['tags'] = sanitize_text_field($request['tags']);
  }

  if (isset($request['image_url'])) {
    $updateData['image_url'] = sanitize_text_field($request['image_url']);
  }

  if (isset($request['is_available'])) {
    $updateData['is_available'] = intval($request['is_available']);
  }

  if (!empty(trim($meta))) {
    $decoded = json_decode($meta, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      $updateData['meta'] = json_encode($decoded);
    } else {
      $updateData['meta'] = '{}';
    }
  } else {
    $updateData['meta'] = '{}';
  }

  if (empty($updateData)) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'No fields provided for update'
    )));
  }

  $result = Product::update($productId, $updateData);
  $updatedProduct = Product::getProductById($productId);

  if (!$updatedProduct) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to update product'
    )));
  } else {
    if (!empty($_FILES)) {
      handle_product_upload_image($request);
    }
  }

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to update product'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'product_id' => intval($productId),
    'merchant_id' => intval($merchantId),
    'message' => 'Product updated successfully'
  )));
}

function handle_delete_product($request = null)
{
  $app = App::getInstance();
  $app->includeModel('product');

  $productId = isset($request['product_id']) ? intval($request['product_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;

  if (!$productId || !$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'product_id and merchant_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);

  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $product = Product::getProductById($productId);
  if (!$product) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product not found'
    )));
  }

  if ($product['merchant_id'] !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Product does not belong to this merchant'
    )));
  }

  $result = Product::delete($productId);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to delete product'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'product_id' => intval($productId),
    'merchant_id' => intval($merchantId),
    'message' => 'Product deleted successfully'
  )));
}

function handle_mark_ready_for_pickup($request)
{
  $app = App::getInstance();
  $app->includeModel('order');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;
  $merchantId = isset($request['merchant_id']) ? intval($request['merchant_id']) : null;

  if (!$orderId || !$merchantId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id and merchant_id parameters are required'
    )));
  }

  $staffRecord = validateMerchantStaff($_SESSION['user']['id'], $merchantId);
  if ((!$staffRecord) && (!$app->user->is_admin)) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: No active staff record for this merchant'
    )));
  }

  $order = Order::getOrderById($orderId);
  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  if ($order['merchant_id'] !== $merchantId) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order does not belong to this merchant'
    )));
  }

  if ($order['state'] !== 'CONFIRMED') {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => "Cannot mark ready. Current state: {$order['state']}"
    )));
  }

  $result = Order::setReadyForPickup($orderId);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to mark order ready for pickup'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Order marked ready for pickup'
  )));
}

function handle_cancel_order($request)
{
  $app = App::getInstance();
  $app->includeModel('order');

  $orderId = isset($request['order_id']) ? intval($request['order_id']) : null;

  if (!$orderId) {
    http_response_code(400);
    exit(json_encode(array(
      'success' => false,
      'error' => 'order_id parameter is required'
    )));
  }

  $order = Order::getOrderById($orderId);
  if (!$order) {
    http_response_code(404);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Order not found'
    )));
  }

  $isCustomer = ($order['customer_id'] == $_SESSION['user']['id']);
  $isMerchant = ($order['merchant_id'] !== null) ? validateMerchantStaff($_SESSION['user']['id'], $order['merchant_id']) : false;
  $isAdmin = $app->user->is_admin ?? false;

  if (!$isCustomer && !$isMerchant && !$isAdmin) {
    http_response_code(403);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Unauthorized: You can only cancel your own orders or orders for your merchant'
    )));
  }

  // Strictly enforce that refunds/cancellations only happen BEFORE merchant acceptance
  if ($order['state'] !== 'PENDING' && $order['state'] !== 'PENDING_CONFIRMATION') {
    http_response_code(400);
    exit(json_encode([
      'success' => false,
      'error' => 'Order has already been confirmed or processed by merchant. Cannot auto-cancel.'
    ]));
  }

  // 1. Release Stripe Hold if applicable
  if (!empty($order['stripe_payment_intent_id'])) {
    try {
      \Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));
      $intent = \Stripe\PaymentIntent::retrieve($order['stripe_payment_intent_id']);

      // Cancel authorization if not captured
      if ($intent->status === 'requires_capture') {
        $intent->cancel();
      }
    } catch (\Exception $e) {
      error_log("Failed to cancel Stripe Payment Intent for Order {$orderId}: " . $e->getMessage());
    }
  }

  $result = Order::cancelOrder($orderId);

  if (!$result) {
    http_response_code(500);
    exit(json_encode(array(
      'success' => false,
      'error' => 'Failed to cancel order'
    )));
  }

  http_response_code(200);
  exit(json_encode(array(
    'success' => true,
    'order_id' => $orderId,
    'message' => 'Order cancelled successfully'
  )));
}

function handle_export_data($request)
{
  // Validate CSRF / Auth Token
  if (!validate_csrf_request()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
  }

  $app = App::getInstance();
  $db = $app->db; // Standard DB wrapper

  $userId = $_SESSION['user']['id'] ?? 0;
  $userRole = $_SESSION['user']['role'] ?? 'customer'; // admin, merchant, courier, customer

  $startDate = filter_var($request['start_date'] ?? '', FILTER_SANITIZE_STRING);
  $endDate = filter_var($request['end_date'] ?? '', FILTER_SANITIZE_STRING);
  $format = strtolower($request['format'] ?? 'csv') === 'json' ? 'json' : 'csv';
  $reportType = $request['report_type'] ?? 'orders_summary';
  $merchantId = intval($request['merchant_id'] ?? 0);

  if (!$startDate || !$endDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date range']);
    exit;
  }

  $params = [
    ':start_date' => $startDate . ' 00:00:00',
    ':end_date'   => $endDate . ' 23:59:59'
  ];

  // 1. SELECT SQL BASED ON REPORT TYPE & ROLE
  if ($reportType === 'line_items') {
    // Detailed Itemized Breakdown
    $sql = "SELECT 
              o.order_number,
              o.created_at AS order_date,
              p.name AS product_name,
              p.sku,
              p.tags,
              oi.quantity,
              oi.price_at_order,
              oi.subtotal AS item_subtotal,
              o.state AS order_status
            FROM neighborhub_order_items oi
            JOIN neighborhub_orders o ON oi.order_id = o.id
            JOIN neighborhub_products p ON oi.product_id = p.id
            WHERE o.created_at BETWEEN :start_date AND :end_date";

    if ($merchantId > 0) {
      $sql .= " AND o.merchant_id = :merchant_id";
      $params[':merchant_id'] = $merchantId;
    }
    $sql .= " ORDER BY o.created_at DESC";
  } else if ($reportType === 'payouts') {
    // Financial & Fees Summary
    $sql = "SELECT 
              o.order_number,
              o.created_at AS order_date,
              o.payment_method,
              o.subtotal_amount,
              o.processing_fee,
              o.platform_fee,
              o.sales_tax,
              o.delivery_fee,
              o.tips,
              o.total_amount,
              (o.subtotal_amount + o.sales_tax - o.platform_fee - o.processing_fee) AS net_merchant_payout,
              o.state AS order_status
            FROM neighborhub_orders o
            WHERE o.created_at BETWEEN :start_date AND :end_date";

    if ($merchantId > 0) {
      $sql .= " AND o.merchant_id = :merchant_id";
      $params[':merchant_id'] = $merchantId;
    }
    $sql .= " ORDER BY o.created_at DESC";
  } else {
    // Default: Orders Summary
    $sql = "SELECT 
              o.order_number,
              o.created_at AS order_date,
              c.display_name AS customer_name,
              m.business_name AS merchant_name,
              cour.business_name AS courier_name,
              o.subtotal_amount,
              o.delivery_fee,
              o.tips,
              o.sales_tax,
              o.total_amount,
              o.payment_method,
              o.state AS order_status,
              o.delivered_at
            FROM neighborhub_orders o
            LEFT JOIN neighborhub_customers c ON o.customer_id = c.id
            LEFT JOIN neighborhub_merchants m ON o.merchant_id = m.id
            LEFT JOIN neighborhub_couriers cour ON o.courier_id = cour.id
            WHERE o.created_at BETWEEN :start_date AND :end_date";

    // Scope query based on user context
    if ($merchantId > 0) {
      $sql .= " AND o.merchant_id = :merchant_id";
      $params[':merchant_id'] = $merchantId;
    } else if ($userRole === 'customer') {
      // Find customer record for logged in user
      $sql .= " AND c.user_id = :user_id";
      $params[':user_id'] = $userId;
    } else if ($userRole === 'courier') {
      // Find courier record for logged in user
      $sql .= " AND cour.user_id = :user_id";
      $params[':user_id'] = $userId;
    }
    $sql .= " ORDER BY o.created_at DESC";
  }

  // Execute query safely
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 2. STREAM OUTPUT TO BROWSER
  $filename = "export_{$reportType}_{$startDate}_to_{$endDate}.{$format}";

  if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    echo json_encode($records, JSON_PRETTY_PRINT);
    exit;
  }

  // Stream CSV
  header('Content-Type: text/csv; charset=utf-8');
  header("Content-Disposition: attachment; filename=\"{$filename}\"");

  $output = fopen('php://output', 'w');

  if (!empty($records)) {
    // Add CSV column headers dynamically from keys
    fputcsv($output, array_keys($records[0]));
    foreach ($records as $row) {
      fputcsv($output, $row);
    }
  } else {
    fputcsv($output, ['No records found for the selected period']);
  }

  fclose($output);
  exit;
}



function sanitize_action($action)
{
  return preg_replace('/[^a-zA-Z0-9_]/', '', $action);
}


/**
 * Sends a structured JSON response and terminates script execution.
 * 
 * @param array $data The payload to return
 * @param int $status_code HTTP response code (default 200)
 */
function send_json_response(array $data, int $status_code = 200)
{
  // Clear any accidental output or whitespace buffers
  if (ob_get_length()) ob_clean();

  // Set JSON content-type and HTTP response status code
  header('Content-Type: application/json; charset=utf-8');
  http_response_code($status_code);

  echo json_encode($data);
  exit;
}

/**
 * Ensures the incoming request is made by a logged-in user.
 * Injects 'user_id' into the tracking workspace array if validated.
 * 
 * @param array &$request Reference to your global extracted request workspace
 */
function authenticate_user(array &$request)
{
  // 💡 Adaptation: Update this logic to match how your platform tracks sessions (e.g., $_SESSION or JWT Bearer Headers)
  if (!isset($_SESSION['user'])) {
    send_json_response([
      'success' => false,
      'message' => 'Authentication required. Please log in.'
    ], 401);
  }
  /*
  // Ensure the tracking workspace array absolutely has the user_id locked down
  if (!isset($request['user_id'])) {
    $request['user']['id'] = intval($_SESSION['user']['id']);
  }
  */
}

/**
 * Enforces Role-Based Access Control (RBAC) for a specific merchant.
 * Checks against neighborhub_merchant_users bridge data constraints.
 * 
 * @param int $user_id Current user's ID
 * @param int $merchant_id Target merchant profile ID
 * @param array $allowed_roles Array of string roles allowed (e.g. ['owner', 'staff'])
 */
function ensure_merchant_access(int $user_id, int $merchant_id, array $allowed_roles = [])
{
  if (!$user_id || !$merchant_id) {
    send_json_response([
      'success' => false,
      'message' => 'Access denied. Missing identification parameters.'
    ], 400);
  }

  try {
    $db = App::getInstance('neighborhub')->db;

    // Check if user is linked to the merchant, active, and matching allowed roles
    $stmt = $db->prepare(
      "SELECT staff_role, status 
             FROM neighborhub_merchant_users 
             WHERE merchant_id = ? AND user_id = ?
             LIMIT 1"
    );
    $stmt->execute([$merchant_id, $user_id]);
    $relation = $stmt->fetch(PDO::FETCH_ASSOC);

    // 🚨 Safety Check 1: Do they exist in the system?
    if (!$relation) {
      send_json_response([
        'success' => false,
        'message' => 'Forbidden. You do not have permission to manage this merchant.'
      ], 403);
    }

    // 🚨 Safety Check 2: Is their staff invitation pending or inactive?
    if ($relation['status'] !== 'active') {
      send_json_response([
        'success' => false,
        'message' => 'Forbidden. Your employee status for this profile is currently ' . $relation['status'] . '.'
      ], 403);
    }

    // 🚨 Safety Check 3: Is their role approved for this explicit endpoint case?
    if (!empty($allowed_roles) && !in_array($relation['staff_role'], $allowed_roles)) {
      send_json_response([
        'success' => false,
        'message' => 'Unauthorized. This feature requires elevated roles: ' . implode(', ', $allowed_roles) . '.'
      ], 403);
    }
  } catch (Exception $e) {
    error_log("Security Router Gatekeeper Exception: " . $e->getMessage());
    send_json_response([
      'success' => false,
      'message' => 'Internal server gatekeeper error occurred.'
    ], 500);
  }
}
