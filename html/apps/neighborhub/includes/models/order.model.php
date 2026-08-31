<?php
if (!defined('MB_RUNNING')) exit;

class Order
{

    public static function sanitize(&$order = null)
    {
        if (!$order) return;

        if (is_array($order)) {
            //$order['user_id'] = intval($order['user_id'] ?? 0);
            //$order['name'] = html_entity_decode(trim($order['name'])) ?? '';
            //$order['description'] = html_entity_decode(trim($order['description'])) ?? '';
            //$order['category'] = html_entity_decode(trim($order['category'])) ?? 'active';
            //$order['status'] = trim($order['status']) ?? 'active';

            // 🌟 Fix: Fallback to empty JSON string if meta is missing, empty, or an empty array
            if (!isset($order['meta']) || empty($order['meta'])) {
                $order['meta'] = '{}';
            } else {
                $order['meta'] = is_array($order['meta']) ? json_encode($order['meta']) : $order['meta'];
            }
            return;
        } else {
            //if (isset($order->id)) $order->id = intval($order->id);
            //if (isset($order->name)) $order->name = html_entity_decode(trim($order->name));
            //if (isset($order->description)) $order->description = html_entity_decode(trim($order->description));
            //if (isset($order->category)) $order->category = html_entity_decode(trim($order->category));
            //if (isset($order->status)) $order->status = trim($order->status);

            // 🌟 Fix: Fallback for Object structures
            if (!isset($order->meta) || empty($order->meta)) {
                $order->meta = '{}';
            } else {
                $order->meta = is_array($order->meta) || is_object($order->meta) ? json_encode($order->meta) : $order->meta;
            }
        }
    }

    /**
     * Create a new order with items
     * 
     * @param int $customerId
     * @param int $merchantId
     * @param array $itemsArray - Array of ['product_id' => id, 'quantity' => qty, 'price_at_order' => price]
     * @param float $subtotal
     * @param float $totalAmount
     * @param string $pickupAddress
     * @param string $deliveryAddress
     * @param string $deliveryFee
     * @param string $notes
     * @return int|false Order ID on success, false on failure
     */
    public static function create($customerId, $merchantId, $itemsArray, $subtotal, $processingFee, $platformFee, $deliveryFee, $tips, $salesTax, $totalAmount, $pickupAddress, $deliveryAddress, $notes, $state = 'PENDING_CONFIRMATION', $stripePaymentIntentId, $meta = '{}')
    {
        try {
            $db = App::getInstance()->db;
            // Generate unique order number
            $orderNumber = self::generateOrderNumber();
            //error_log('here');
            //error_log(print_r($itemsArray, true));

            // Verify order number uniqueness
            $checkStmt = $db->prepare("SELECT id FROM neighborhub_orders WHERE order_number = ?");
            $checkStmt->execute([$orderNumber]);
            while ($checkStmt->fetch()) {
                $orderNumber = self::generateOrderNumber();
                $checkStmt->execute([$orderNumber]);
            }
            $sanitized = array(
                'customer_id' => $customerId,
                'merchant_id' => $merchantId,
                'total_amount' => $totalAmount,
                'pickup_address' => $pickupAddress,
                'delivery_address' => $deliveryAddress,
                'delivery_fee' => $deliveryFee,
                'order_notes' => $notes,
                'state' => $state,
                'meta' => $meta
            );
            self::sanitize($sanitized);

            // Begin transaction for order creation
            $db->beginTransaction();

            // Insert main order record
            $insertStmt = $db->prepare(
                "INSERT INTO neighborhub_orders 
                (order_number, customer_id, merchant_id, total_amount, subtotal_amount, sales_tax, tips, processing_fee, platform_fee, delivery_fee, pickup_address, delivery_address, order_notes, state, stripe_payment_intent_id, meta, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $insertStmt->execute([
                $orderNumber,
                $customerId,
                $merchantId,
                $totalAmount,
                $subtotal,
                $salesTax,
                $tips,
                $processingFee,
                $platformFee,
                $deliveryFee,
                $pickupAddress,
                $deliveryAddress,
                $notes,
                $state,
                $stripePaymentIntentId,
                $sanitized['meta']
            ]);

            $orderId = $db->lastInsertId();

            // Insert order items
            // Insert order items
            foreach ($itemsArray as $item) {
                $itemStmt = $db->prepare(
                    "INSERT INTO neighborhub_order_items 
                    (order_id, product_id, quantity, price_at_order, subtotal, meta) 
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                // 🌟 FIX: Check for 'customizations', and json_encode it if it's an array
                $itemMeta = '{}';
                if (!empty($item['customizations'])) {
                    $itemMeta = is_array($item['customizations']) ? json_encode($item['customizations']) : $item['customizations'];
                } elseif (!empty($item['meta'])) {
                    $itemMeta = is_array($item['meta']) ? json_encode($item['meta']) : $item['meta'];
                }

                $sanitized = array(
                    'meta' => $itemMeta,
                );
                self::sanitize($sanitized);

                $subtotal = $item['quantity'] * $item['price_at_order'];
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price_at_order'],
                    $subtotal,
                    $sanitized['meta'],
                ]);
            }

            // Commit transaction
            $db->commit();

            return $orderId;
        } catch (Exception $e) {
            try {
                $db->rollBack();
            } catch (Exception $rollbackError) {
                error_log("Rollback failed: " . $rollbackError->getMessage());
            }
            error_log("Order creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch line items belonging to a specific order ID
     * * @param int $orderId
     * @return array Array of order items
     */
    public static function getOrderItems($orderId)
    {
        try {
            $db = App::getInstance()->db;
            $itemsStmt = $db->prepare(
                "SELECT 
                    oi.id,
                    oi.product_id,
                    oi.quantity,
                    oi.price_at_order,
                    oi.subtotal,
                    oi.meta,
                    p.name AS product_name -- 🌟 Pulling the pristine product name from the catalog table
                FROM neighborhub_order_items oi
                INNER JOIN neighborhub_products p ON oi.product_id = p.id -- 🌟 Joining the tables together
                WHERE oi.order_id = ?"
            );
            $itemsStmt->execute([intval($orderId)]);
            return $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Failed to fetch order items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch complete order details with line items
     * * @param int $orderId
     * @return array|false Order array with nested items, false on failure
     */
    public static function getOrderById($orderId)
    {
        try {
            $db = App::getInstance()->db;

            // Fetch main order
            $orderStmt = $db->prepare(
                "SELECT 
                    o.*,
                    c.display_name AS customer_name,
                    c.phone AS customer_phone,
                    c.status AS customer_status
                    -- 🌟 Tip: You can also just use c.* if you want every column from the customer record
                FROM neighborhub_orders o
                LEFT JOIN neighborhub_customers c ON o.customer_id = c.id
                WHERE o.id = ?"
            );
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                return false;
            }

            // Clean implementation using our new shared method
            $order['items'] = self::getOrderItems($orderId);

            return $order;
        } catch (Exception $e) {
            error_log("Failed to fetch order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Accept delivery job with atomic race condition prevention
     * 
     * @param int $orderId
     * @param int $courierId
     * @return bool True on successful assignment, false on failure or conflict
     */
    public static function acceptDeliveryJob($orderId, $courierId)
    {
        try {
            $db = App::getInstance()->db;

            // Begin transaction with Row Locking
            $db->beginTransaction();

            // Read current order state with locked cursor (FOR UPDATE)
            $readStmt = $db->prepare(
                "SELECT state, locked_by_courier_id FROM neighborhub_orders WHERE id = ? FOR UPDATE"
            );
            $readStmt->execute([$orderId]);
            $orderState = $readStmt->fetch(PDO::FETCH_ASSOC);

            // Verify conditions for acceptance
            if (!$orderState) {
                $db->rollBack();
                error_log("Order not found: " . $orderId);
                return false;
            }

            if ($orderState['state'] !== 'READY_FOR_PICKUP') {
                $db->rollBack();
                error_log("Order not in READY_FOR_PICKUP state. Current state: " . $orderState['state']);
                return false;
            }

            if (!empty($orderState['locked_by_courier_id'])) {
                $db->rollBack();
                error_log("Order already locked by another courier: " . $orderState['locked_by_courier_id']);
                return false;
            }

            // Perform atomic update
            $updateStmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = 'IN_TRANSIT', 
                    locked_by_courier_id = ?, 
                    courier_id = ?,
                    locked_at = NOW(),
                    updated_at = NOW() 
                WHERE id = ?"
            );
            $updateStmt->execute([$courierId, $courierId, $orderId]);

            // Commit transaction to release lock
            $db->commit();

            return true;
        } catch (Exception $e) {
            try {
                $db->rollBack();
            } catch (Exception $rollbackError) {
                error_log("Rollback failed during acceptDeliveryJob: " . $rollbackError->getMessage());
            }
            error_log("Failed to accept delivery job: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Confirm order (transition from PENDING_CONFIRMATION to CONFIRMED)
     * 
     * @param int $orderId
     * @return bool True on success, false on failure
     */
    public static function confirmOrder($orderId)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = 'CONFIRMED', 
                    confirmed_at = NOW(),
                    updated_at = NOW() 
                WHERE id = ? AND state = 'PENDING_CONFIRMATION'"
            );
            $stmt->execute([$orderId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Order confirmation failed or already confirmed: " . $orderId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to confirm order: " . $e->getMessage());
            return false;
        }
    }

    public static function revertOrderStatus($orderId, $targetState)
    {
        try {
            $db = App::getInstance()->db;

            // Validate target state
            $validStates = ['PENDING_CONFIRMATION', 'CONFIRMED', 'READY_FOR_PICKUP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'];
            if (!in_array($targetState, $validStates)) {
                error_log("Invalid target state for order revert: " . $targetState);
                return false;
            }

            // Update order state
            $stmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = ?, 
                    updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$targetState, $orderId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Order status revert failed or no change made: " . $orderId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to revert order status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transition order to READY_FOR_PICKUP state (merchant confirms fulfillment)
     * 
     * @param int $orderId
     * @return bool True on success, false on failure
     */
    public static function setReadyForPickup($orderId)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = 'READY_FOR_PICKUP', 
                    ready_at = NOW(),
                    updated_at = NOW() 
                WHERE id = ? AND state = 'CONFIRMED'"
            );
            $stmt->execute([$orderId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Order ready transition failed: " . $orderId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to set order ready: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark order as delivered
     * 
     * @param int $orderId
     * @return bool True on success, false on failure
     */
    public static function completeDelivery($orderId)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = 'DELIVERED', 
                    delivered_at = NOW(),
                    updated_at = NOW() 
                WHERE id = ? AND state = 'IN_TRANSIT'"
            );
            $stmt->execute([$orderId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Order delivery completion failed: " . $orderId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to complete delivery: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel an order
     * 
     * @param int $orderId
     * @return bool True on success, false on failure
     */
    public static function cancelOrder($orderId)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare(
                "UPDATE neighborhub_orders 
                SET state = 'CANCELLED', 
                    cancelled_at = NOW(),
                    updated_at = NOW(),
                    locked_by_courier_id = NULL,
                    locked_at = NULL
                WHERE id = ? AND state NOT IN ('DELIVERED', 'CANCELLED', 'FAILED')"
            );
            $stmt->execute([$orderId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Order cancellation failed: " . $orderId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to cancel order: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get orders by customer with optional state filter
     * 
     * @param int $customerId
     * @param string|null $state Optional state filter
     * @param int $limit
     * @param int $offset
     * @return array|false Array of orders, false on failure
     */
    public static function getOrdersByCustomerId($customerId, $state = null, $limit = 50, $offset = 0)
    {
        try {
            $db = App::getInstance()->db;

            if ($state) {
                $stmt = $db->prepare(
                    "SELECT 
                        o.id,
                        o.order_number,
                        o.customer_id,
                        o.merchant_id,
                        m.business_name AS business_name,
                        o.courier_id,
                        o.total_amount,
                        o.state,
                        o.delivery_assignment_mode,
                        o.order_notes,
                        o.order_phone,
                        o.pickup_address,
                        o.delivery_address,
                        o.created_at,
                        o.updated_at
                    FROM neighborhub_orders o
                    INNER JOIN neighborhub_merchants m ON o.merchant_id = m.id
                    WHERE o.customer_id = ? AND o.state = ?
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?
                    "
                );
                $stmt->execute([$customerId, $state, $limit, $offset]);
            } else {
                $stmt = $db->prepare(
                    "SELECT 
                        o.id,
                        o.order_number,
                        o.customer_id,
                        o.merchant_id,
                        m.business_name AS business_name,
                        o.courier_id,
                        o.total_amount,
                        o.state,
                        o.delivery_assignment_mode,
                        o.order_notes,
                        o.order_phone,
                        o.pickup_address,
                        o.delivery_address,
                        o.created_at,
                        o.updated_at
                    FROM neighborhub_orders o
                    INNER JOIN neighborhub_merchants m ON o.merchant_id = m.id
                    WHERE o.customer_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?
                    "
                );
                $stmt->execute([$customerId, $limit, $offset]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to fetch customer orders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get orders by merchant with optional state filter
     * 
     * @param int $merchantId
     * @param string|null $state Optional state filter
     * @param int $limit
     * @param int $offset
     * @return array|false Array of orders, false on failure
     */
    /**
     * Fetch orders belonging to a specific merchant with optional filtering and line items hydration
     * * @param int $merchantId
     * @param string|null $state
     * @param int $limit
     * @param int $offset
     * @param bool $includeItems Set to true to automatically hydrate each order with its item records
     * @return array|false Array of orders, or false on failure
     */
    public static function getOrdersByMerchantId($merchantId, $state = null, $limit = 50, $offset = 0, $includeItems = false, $template = false)
    {
        try {
            $db = App::getInstance()->db;

            if ($state) {
                $stmt = $db->prepare(
                    "SELECT 
                        id,
                        order_number,
                        customer_id,
                        merchant_id,
                        courier_id,
                        subtotal_amount,
                        delivery_fee,
                        tips,
                        total_amount,
                        state,
                        delivery_assignment_mode,
                        order_notes,
                        order_phone,
                        pickup_address,
                        delivery_address,
                        created_at,
                        updated_at
                    FROM neighborhub_orders 
                    WHERE merchant_id = :merchant_id AND state = :state
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset"
                );

                $stmt->bindValue(':merchant_id', $merchantId);
                $stmt->bindValue(':state', $state);
            } else {
                $stmt = $db->prepare(
                    "SELECT 
                        id,
                        order_number,
                        customer_id,
                        merchant_id,
                        courier_id,
                        total_amount,
                        state,
                        delivery_assignment_mode,
                        order_notes,
                        order_phone,
                        pickup_address,
                        delivery_address,
                        created_at,
                        updated_at
                    FROM neighborhub_orders 
                    WHERE merchant_id = :merchant_id
                    ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset"
                );

                $stmt->bindValue(':merchant_id', $merchantId);
            }

            // 🎯 The Magic Fix: Force MySQL to receive pure numeric integers, removing the quotes
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Hydrate orders with their respective items if requested
            if ($orders && ($includeItems || $template)) {
                foreach ($orders as &$order) {
                    if ($includeItems) $order['items'] = self::getOrderItems($order['id']);
                    if ($template) $order['html'] = render($template, array('order' => $order), true);
                }

                unset($order); // Break reference safeguard
            }

            return $orders;
        } catch (Exception $e) {
            error_log("Failed to fetch merchant orders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available orders for couriers (in READY_FOR_PICKUP state)
     * 
     * @param int $limit
     * @param int $offset
     * @return array|false Array of available orders, false on failure
     */
    public static function getAvailableOrders($limit = 50, $offset = 0, $includeItems = false, $template = false)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare(
                "SELECT 
                    id,
                    order_number,
                    customer_id,
                    merchant_id,
                    total_amount,
                    state,
                    pickup_address,
                    order_notes,
                    order_phone,
                    delivery_address,
                    created_at
                FROM neighborhub_orders 
                WHERE state = 'READY_FOR_PICKUP' AND locked_by_courier_id IS NULL
                ORDER BY created_at ASC
                LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($orders && ($includeItems || $template)) {
                foreach ($orders as &$order) {
                    if ($includeItems) $order['items'] = self::getOrderItems($order['id']);
                    if ($template) $order['html'] = render($template, array('order' => $order), true);
                }
                unset($order); // Break reference safeguard
            }

            return $orders;
        } catch (Exception $e) {
            error_log("Failed to fetch available orders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current state of an order
     * 
     * @param int $orderId
     * @return string|false Current state or false on failure
     */
    public static function getOrderState($orderId)
    {
        try {
            $db = App::getInstance()->db;

            $stmt = $db->prepare("SELECT state FROM neighborhub_orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return $result['state'];
            }

            return false;
        } catch (Exception $e) {
            error_log("Failed to get order state: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique alphanumeric order number
     * 
     * @return string Random alphanumeric order number
     */
    private static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = date('Ymd');
        $randomSegment = strtoupper(bin2hex(random_bytes(4)));

        return $prefix . $timestamp . $randomSegment;
    }
    /**
     * Get an order along with all of its verification/status images attached via AssetManager
     * * @param int $orderId
     * @return array|false The order dictionary containing a 'gallery' array layer, false on failure
     */
    public static function getOrderWithGallery($orderId)
    {
        try {
            $db = App::getInstance()->db;

            // 1. Fetch the core order row
            $stmt = $db->prepare("SELECT * FROM neighborhub_orders WHERE id = ?");
            $stmt->execute([intval($orderId)]);
            $orderData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orderData) {
                error_log("Order::getOrderWithGallery Error: Order ID $orderId not found");
                return false;
            }

            // 2. Fetch all related status, receipt, or verification images
            $app = App::getInstance('neighborhub');
            $app->includeClass('assetmanager');
            $gallery = AssetManager::getImagesByEntity('order', $orderId);

            $orderData['gallery'] = $gallery ? $gallery : array();
            return $orderData;
        } catch (Exception $e) {
            error_log("Order::getOrderWithGallery Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload and attach status/verification images to an order
     * (e.g., bagged confirmation, delivery proof, receipt scans, age verification)
     * * @param int $orderId
     * @param int $merchantId Used to keep file trees grouped under the merchant's folder layout
     * @param array $filesPayload Typically $_FILES['order_verification']
     * @return array List of successfully uploaded GCS URLs
     */
    public static function uploadOrderImages($orderId, $merchantId, $filesPayload)
    {
        if (empty($orderId) || empty($merchantId) || empty($filesPayload['name'])) {
            error_log("Order::uploadOrderImages Error: Missing parameters");
            return array();
        }

        $app = App::getInstance('neighborhub');
        $app->includeClass('assetmanager');

        // Pass context to AssetManager: parent_type='order', parent_id=$orderId, merchant_id=$merchantId
        return AssetManager::uploadMultipleImages('order', $orderId, $merchantId, $filesPayload);
    }

    /**
     * Delete a verification/status image belonging to an order record
     * * @param int $orderId
     * @param int $merchantId
     * @param int $imageId The target entry key from neighborhub_images
     * @return bool True on success, false on failure
     */
    public static function deleteOrderImage($orderId, $merchantId, $imageId)
    {
        try {
            $app = App::getInstance('neighborhub');
            $db = $app->db;

            // 1. Double check ownership constraints before altering filesystem assets
            $stmt = $db->prepare(
                "SELECT image_url FROM neighborhub_images 
                 WHERE id = ? AND parent_type = 'order' AND parent_id = ?"
            );
            $stmt->execute([intval($imageId), intval($orderId)]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$image) {
                error_log("Order::deleteOrderImage Error: Asset verification failed.");
                return false;
            }

            // 2. Wipe the file from Cloud Storage
            require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
            $storageManager = new FileStorageManager('google_cloud');

            // Keeps file tracking mapped nicely under the merchant's target bucket branch
            $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId) . '/orders';
            $filenameWithExtension = pathinfo($image['image_url'], PATHINFO_BASENAME);

            $storageManager->deleteFile($targetPath, $filenameWithExtension);

            // 3. Purge data tracking link from database state
            $deleteStmt = $db->prepare("DELETE FROM neighborhub_images WHERE id = ?");
            return $deleteStmt->execute([intval($imageId)]);
        } catch (Exception $e) {
            error_log("Order::deleteOrderImage Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculates the dynamic delivery fee based on GPS coordinates.
     * @param float $lat1 Starting latitude (Merchant)
     * @param float $lng1 Starting longitude (Merchant)
     * @param float $lat2 Ending latitude (Customer)
     * @param float $lng2 Ending longitude (Customer)
     * @return array 
     */
    public static function calculateDeliveryFee($lat1, $lng1, $lat2, $lng2)
    {
        // 1. Calculate straight-line distance using the Haversine formula
        $earthRadiusMiles = 3958.8;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $haversineCore = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) * sin($lngDelta / 2);

        $angularDistance = 2 * atan2(sqrt($haversineCore), sqrt(1 - $haversineCore));
        $distanceMiles = $earthRadiusMiles * $angularDistance;

        // 2. Define Pricing Tiers
        $baseFee = 3.50;         // Minimum fee for any delivery
        $baseMilesIncluded = 2.0; // Covered by the base fee
        $perMileRate = 1.25;     // Cost per mile after the base miles

        // 3. Compute Fee
        if ($distanceMiles <= $baseMilesIncluded) {
            $deliveryFee = $baseFee;
        } else {
            $extraMiles = $distanceMiles - $baseMilesIncluded;
            $deliveryFee = $baseFee + ($extraMiles * $perMileRate);
        }

        // 4. Long Distance Premium (Optional operational tier)
        if ($distanceMiles > 10.0) {
            $deliveryFee += 3.00; // Extra incentive for drivers traveling over 10 miles
        }

        // Return rounded to standard currency decimal format
        return [
            'fee' => round($deliveryFee, 2),
            'distance_mi' => $distanceMiles,
        ];
    }
}
