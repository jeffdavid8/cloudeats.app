<?php

class Customer
{
  public $id;
  public $user_id;
  public $display_name;
  public $delivery_locations;
  public $phone;
  public $status;
  public $terms_accepted_at;
  public $type;
  public $meta;
  public $created_at;
  public $updated_at;

  public function __construct($data = [])
  {
    if (is_array($data)) {
      foreach ($data as $key => $val) {
        if (property_exists($this, $key)) {
          $this->$key = $val;
        }
      }
    }
    if (is_string($data['delivery_locations'])) {
      $this->delivery_locations = json_decode($data['delivery_locations'], true);
    } else {
      $this->delivery_locations = $data['delivery_locations'];
    }
  }

  public function data($data = [])
  {
    if (empty($data)) {
      return [
        'id' => $this->id,
        'user_id' => $this->user_id,
        'display_name' => $this->display_name,
        'delivery_locations' => $this->delivery_locations,
        'phone' => $this->phone,
        'status' => $this->status,
        'terms_accepted_at' => $this->terms_accepted_at,
        'type' => $this->type,
        'meta' => $this->meta,
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at
      ];
    } else {
      $this->id = $data['id'] ?? null;
      $this->user_id = $data['user_id'] ?? null;
      $this->display_name = $data['business_name'] ?? null;
      $this->delivery_locations = $data['delivery_locations'] ?? null;
      $this->phone = $data['phone'] ?? null;
      $this->status = $data['status'] ?? null;
      $this->terms_accepted_at = $data['terms_accepted_at'] ?? null;
      $this->type = $data['type'] ?? null;
      $this->meta = $data['meta'] ?? null;
      $this->created_at = $data['created_at'] ?? null;
      $this->updated_at = $data['updated_at'] ?? null;
    }
  }

  public static function sanitize(&$customer = null)
  {
    if (!$customer) return;

    if (is_array($customer)) {
      //$customer['user_id'] = intval($customer['user_id'] ?? 0);
      //$customer['name'] = html_entity_decode(trim($customer['name'])) ?? '';
      //$customer['description'] = html_entity_decode(trim($customer['description'])) ?? '';
      //$customer['category'] = html_entity_decode(trim($customer['category'])) ?? 'active';
      //$customer['status'] = trim($customer['status']) ?? 'active';

      // 🌟 Fix: Fallback to empty JSON string if meta is missing, empty, or an empty array
      if (!isset($customer['meta']) || empty($customer['meta'])) {
        $customer['meta'] = '{}';
      } else {
        $customer['meta'] = is_array($customer['meta']) ? json_encode($customer['meta']) : $customer['meta'];
      }
      if (!isset($customer['delivery_locations']) || empty($customer['delivery_locations'])) {
        $customer['delivery_locations'] = '{}';
      } else {
        $customer['delivery_locations'] = is_array($customer['delivery_locations']) ? json_encode($customer['delivery_locations']) : $customer['delivery_locations'];
      }
      return;
    } else {
      //if (isset($customer->id)) $customer->id = intval($customer->id);
      //if (isset($customer->name)) $customer->name = html_entity_decode(trim($customer->name));
      //if (isset($customer->description)) $customer->description = html_entity_decode(trim($customer->description));
      //if (isset($customer->category)) $customer->category = html_entity_decode(trim($customer->category));
      //if (isset($customer->status)) $customer->status = trim($customer->status);

      // 🌟 Fix: Fallback for Object structures
      if (!isset($customer->meta) || empty($customer->meta)) {
        $customer->meta = '{}';
      } else {
        $customer->meta = is_array($customer->meta) || is_object($customer->meta) ? json_encode($customer->meta) : $customer->meta;
      }
      if (!isset($customer->delivery_locations) || empty($customer->delivery_locations)) {
        $customer->delivery_locations = '{}';
      } else {
        $customer->delivery_locations = is_array($customer->delivery_locations) ? json_encode($customer->delivery_locations) : $customer->delivery_locations;
      }
    }
  }

  /**
   * Create a brand new customer record
   * * @param array $data Assoc array of properties
   * @return int|false Inserts row and returns ID, false on failure
   */
  public static function create($data)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "INSERT INTO neighborhub_customers (
                    user_id, display_name, phone, delivery_locations, status, 
                    meta, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())"
      );

      $sanitized = array(
        'meta' => (isset($data['meta'])) ? $data['meta'] : '{}',
      );

      $stmt->execute([
        intval($data['user_id']),
        $data['display_name'],
        $data['phone'],
        json_encode($data['delivery_locations']),
        $data['status'] ?? 'active',
        $sanitized['meta'],
      ]);

      return $db->lastInsertId();
    } catch (Exception $e) {
      error_log("Failed to create customer: " . $e->getMessage());
      return false;
    }
  }
  /**
   * Update merchant profile
   * * @param int $merchantId
   * @param array $data Fields to update
   * @return array|bool Status details array on success, false on failure
   */
  public static function update($customerId, $data)
  {
    try {
      $db = App::getInstance()->db;
      $result = array();

      // Validate required fields
      if (!$customerId) {
        error_log("Merchant::update Error: customerId is required");
        return false;
      }

      $customerId = intval($customerId);
      $updates = array();
      $params = array();

      // Run structural data mutations by reference (converts meta arrays to JSON string)

      // Build dynamic update query based on provided fields
      if (isset($data['display_name'])) {
        $updates[] = "display_name = ?";
        $params[] = trim($data['display_name']);
      }

      if (isset($data['phone'])) {
        $updates[] = "phone = ?";
        $params[] = trim($data['phone']);
      }

      if (isset($data['delivery_locations'])) {
        $updates[] = "delivery_locations = ?";
        $params[] = json_encode($data['delivery_locations']) ?? '{}';
      }

      if (isset($data['type'])) {
        $updates[] = "type = ?";
        $params[] = trim($data['type']);
      }

      if (isset($data['meta'])) {
        $updates[] = "meta = ?";
        $params[] = $data['meta'] ?? '{}'; // Already a verified JSON string or '{}' via sanitize()
      }

      if (isset($data['status'])) {
        $allowedStatuses = array('active', 'paused', 'suspended');
        if (in_array($data['status'], $allowedStatuses)) {
          $updates[] = "status = ?";
          $params[] = $data['status'];
        }
      }

      if (empty($updates)) {
        error_log("Customer::update Error: No valid fields to update");
        return false;
      }

      // Always update the updated_at timestamp
      $updates[] = "updated_at = NOW()";

      $query = "UPDATE neighborhub_customers SET " . implode(", ", $updates) . " WHERE id = ?";
      $params[] = $customerId;

      $stmt = $db->prepare($query);
      $success = $stmt->execute($params);

      if ($success) {
        $result = ['success' => true, 'message' => 'Customer updated successfully'];
      } else {
        $result = ['success' => false, 'message' => 'Failed to update customer record'];
        error_log("Customer::update Error: Failed to update customer record");
      }

      return $result;
    } catch (Exception $e) {
      error_log("Merchant::update Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetch customer profile by user ID
   * 
   * @param int $userId
   * @return object|false customer record, false on failure
   */
  public static function getCustomerByUserId($userId, $format = 'object')
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT 
                    *
                FROM neighborhub_customers
                WHERE user_id = ?"
      );
      $stmt->execute([$userId]);
      $customer = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$customer) {
        error_log("customer not found for user: " . $userId);
        return false;
      }
      if ($format === 'object') {
        $obj = new self($customer);
        return $obj;
      }
      return $customer;
    } catch (Exception $e) {
      error_log("Failed to fetch customer by user ID: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetch customer profile by customer ID
   * 
   * @param int $customerId
   * @return object|false customer record, false on failure
   */
  public static function getCustomerById($customerId, $format = 'object')
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT 
                    *
                FROM neighborhub_customers
                WHERE id = ?"
      );
      $stmt->execute([$customerId]);
      $customer = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$customer) {
        error_log("customer not found: " . $customerId);
        return false;
      }
      if ($format === 'object') {
        $obj = new self($customer);
        return $obj;
      }
      return $customer;
    } catch (Exception $e) {
      error_log("Failed to fetch customer by ID: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetch customer profile by customer ID
   * 
   * @param int $phone
   * @return object|false customer record, false on failure
   */
  public static function getCustomerByPhone($phone, $format = 'object')
  {
    if (empty($phone)) return false;
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT 
                    *
                FROM neighborhub_customers
                WHERE phone = ?"
      );
      $stmt->execute([$phone]);
      $customer = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$customer) {
        error_log("customer not found: " . $phone);
        return false;
      }
      if ($format === 'object') {
        $obj = new self($customer);
        return $obj;
      }
      return $customer;
    } catch (Exception $e) {
      error_log("Failed to fetch customer by Phone: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update customer geolocation
   * 
   * @param int $customerId
   * @param float $latitude
   * @param float $longitude
   * @return bool True on success, false on failure
   */
  public static function updateLocation($customerId, $latitude, $longitude)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      // Validate coordinates
      if (!is_numeric($latitude) || !is_numeric($longitude)) {
        error_log("Invalid coordinates for customer: " . $customerId);
        return false;
      }

      if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        error_log("Coordinates out of range for customer: " . $customerId);
        return false;
      }

      $stmt = $db->prepare(
        "UPDATE neighborhub_customers 
                SET latitude = ?, 
                    longitude = ?, 
                    last_location_update = NOW(),
                    updated_at = NOW() 
                WHERE id = ?"
      );
      $stmt->execute([$latitude, $longitude, $customerId]);

      if ($stmt->rowCount() > 0) {
        return true;
      }

      error_log("Location update failed for customer: " . $customerId);
      return false;
    } catch (Exception $e) {
      error_log("Failed to update customer location: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update customer online status
   * 
   * @param int $customerId
   * @param string $status One of: 'available', 'on_delivery', 'offline'
   * @return bool True on success, false on failure
   */
  public static function updateStatus($customerId, $status)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $validStatuses = ['available', 'on_delivery', 'offline'];
      if (!in_array($status, $validStatuses)) {
        error_log("Invalid status for customer: " . $status);
        return false;
      }

      $stmt = $db->prepare(
        "UPDATE neighborhub_customers 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?"
      );
      $stmt->execute([$status, $customerId]);

      if ($stmt->rowCount() > 0) {
        return true;
      }

      error_log("Status update failed for customer: " . $customerId);
      return false;
    } catch (Exception $e) {
      error_log("Failed to update customer status: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get customer's active delivery assignments
   * 
   * @param int $customerId
   * @return array|false Array of active deliveries, false on failure
   */
  public static function getActiveDeliveries($customerId)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT 
                    o.id,
                    o.order_number,
                    o.customer_id,
                    o.merchant_id,
                    o.total_amount,
                    o.state,
                    o.pickup_address,
                    o.delivery_address,
                    o.created_at,
                    o.updated_at,
                    m.business_name,
                    m.latitude as merchant_lat,
                    m.longitude as merchant_lng
                FROM neighborhub_orders o
                JOIN neighborhub_merchants m ON o.merchant_id = m.id
                WHERE o.customer_id = ? 
                    AND o.state IN ('IN_TRANSIT', 'READY_FOR_PICKUP')
                ORDER BY o.created_at ASC"
      );
      $stmt->execute([$customerId]);

      $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $deliveries ? $deliveries : [];
    } catch (Exception $e) {
      error_log("Failed to fetch active deliveries: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update customer rating
   * 
   * @param int $customerId
   * @param float $rating Rating between 0.0 and 5.0
   * @return bool True on success, false on failure
   */
  public static function updateRating($customerId, $rating)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      // Validate rating range
      if (!is_numeric($rating) || $rating < 0 || $rating > 5) {
        error_log("Invalid rating value for customer: " . $rating);
        return false;
      }

      $stmt = $db->prepare(
        "UPDATE neighborhub_customers 
                SET rating = ?, updated_at = NOW() 
                WHERE id = ?"
      );
      $stmt->execute([$rating, $customerId]);

      if ($stmt->rowCount() > 0) {
        return true;
      }

      error_log("Rating update failed: " . $customerId);
      return false;
    } catch (Exception $e) {
      error_log("Failed to update customer rating: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Get available customers for automatic assignment (online and available)
   * 
   * @param int $limit
   * @return array|false Array of available customers, false on failure
   */
  public static function getAvailableCustomers($limit = 100)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT 
                    id,
                    user_id,
                    business_name,
                    phone,
                    vehicle_type,
                    status,
                    latitude,
                    longitude,
                    total_deliveries,
                    rating,
                    updated_at
                FROM neighborhub_customers
                WHERE status = 'available'
                ORDER BY rating DESC, total_deliveries ASC
                LIMIT ?"
      );
      $stmt->execute([$limit]);

      $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $customers ? $customers : [];
    } catch (Exception $e) {
      error_log("Failed to fetch available customers: " . $e->getMessage());
      return false;
    }
  }
  /**
   * Get a customer profile along with all related gallery assets
   * * @param int $customerId
   * @return array|false The customer array containing a 'gallery' field, false on failure
   */
  public static function getCustomerWithGallery($customerId)
  {
    $customerData = self::getCustomerById($customerId);
    if (!$customerData) {
      return false;
    }

    $app = App::getInstance('neighborhub');
    $app->includeClass('assetmanager');

    $gallery = AssetManager::getImagesByEntity('customer', $customerId);
    $customerData['gallery'] = $gallery ? $gallery : array();

    return $customerData;
  }

  /**
   * Upload and attach multiple gallery files to this customer account
   * * @param int $customerId
   * @param array $filesPayload Typically $_FILES['customer_gallery']
   * @return array List of uploaded URLs
   */
  public static function uploadGalleryImages($customerId, $filesPayload)
  {
    if (empty($customerId) || empty($filesPayload['name'])) {
      error_log("customer::uploadGalleryImages Error: Missing parameters");
      return array();
    }

    $app = App::getInstance('neighborhub');
    $app->includeClass('assetmanager');

    // Note: For customers, we scope their file storage tree root under merchants/0/customers
    return AssetManager::uploadMultipleImages('customer', $customerId, 0, $filesPayload);
  }

  /**
   * Delete a single gallery image tracking file belonging to this customer profile
   * * @param int $customerId
   * @param int $imageId
   * @return bool
   */
  public static function deleteGalleryImage($customerId, $imageId)
  {
    try {
      $db = App::getInstance('neighborhub')->db;

      $stmt = $db->prepare(
        "SELECT image_url FROM neighborhub_images 
                 WHERE id = ? AND parent_type = 'customer' AND parent_id = ?"
      );
      $stmt->execute([intval($imageId), intval($customerId)]);
      $image = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$image) {
        error_log("customer::deleteGalleryImage Error: Verification failure.");
        return false;
      }

      //require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
      $storageManager = new FileStorageManager('google_cloud');

      $targetPath = 'apps/neighborhub/merchants/0/customers';
      $filenameWithExtension = pathinfo($image['image_url'], PATHINFO_BASENAME);

      $storageManager->deleteFile($targetPath, $filenameWithExtension);

      $deleteStmt = $db->prepare("DELETE FROM neighborhub_images WHERE id = ?");
      return $deleteStmt->execute([intval($imageId)]);
    } catch (Exception $e) {
      error_log("customer::deleteGalleryImage Exception: " . $e->getMessage());
      return false;
    }
  }
}
