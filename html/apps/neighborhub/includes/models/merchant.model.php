<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Merchant Model
 * Handles merchant profile management, staff relationships, and product catalog operations.
 * All database operations use prepared statements with PDO for security.
 * 
 * 
 */

class Merchant
{
    // 🗂️ STANDARD PROPERTIES
    public $id;
    public $user_id;
    public $business_name;
    public $address;
    public $latitude;
    public $longitude;
    public $phone;
    public $image_url;
    public $status;
    public $website;
    public $facebook;
    public $platform_fee_rate;
    public $platform_flat_fee;
    public $store_hours;
    public $menus;
    public $delivery_assignment_mode;
    public $delivery_max_distance;
    public $stripe_api_key;
    public $stripe_percent_fee;
    public $stripe_flat_fee;
    public $type;
    public $meta;
    public $created_at;
    public $updated_at;
    public $gallery;

    public function __construct($data = [])
    {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (property_exists($this, $key)) {
                    $this->$key = $val;
                }
            }
        }
    }

    public function data($data = [])
    {
        if (empty($data)) {
            return [
                'id' => $this->id,
                'user_id' => $this->user_id,
                'business_name' => $this->business_name,
                'address' => $this->address,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'phone' => $this->phone,
                'image_url' => $this->image_url,
                'status' => $this->status,
                'website' => $this->website,
                'facebook' => $this->facebook,
                'platform_fee_rate' => $this->platform_fee_rate,
                'platform_flat_fee' => $this->platform_flat_fee,
                'store_hours' => $this->store_hours,
                'menus' => $this->menus,
                'delivery_assignment_mode' => $this->delivery_assignment_mode,
                'delivery_max_distance' => $this->delivery_max_distance,
                'stripe_api_key' => $this->stripe_api_key,
                'stripe_percent_fee' => $this->stripe_percent_fee,
                'stripe_flat_fee' => $this->stripe_flat_fee,
                'type' => $this->type,
                'meta' => $this->meta,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at
            ];
        } else {
            $this->id = $data['id'] ?? null;
            $this->user_id = $data['user_id'] ?? null;
            $this->business_name = $data['business_name'] ?? null;
            $this->address = $data['address'] ?? null;
            $this->latitude = $data['latitude'] ?? null;
            $this->longitude = $data['longitude'] ?? null;
            $this->phone = $data['phone'] ?? null;
            $this->image_url = $data['image_url'] ?? null;
            $this->status = $data['status'] ?? null;
            $this->website = $data['website'] ?? null;
            $this->facebook = $data['facebook'] ?? null;
            $this->platform_fee_rate = $data['platform_fee_rate'] ?? null;
            $this->platform_flat_fee = $data['platform_flat_fee'] ?? null;
            $this->store_hours = $data['store_hours'] ?? null;
            $this->menus = $data['menus'] ?? null;
            $this->delivery_assignment_mode = $data['delivery_assignment_mode'] ?? null;
            $this->delivery_max_distance = $data['delivery_max_distance'] ?? null;
            $this->stripe_api_key = $data['stripe_api_key'] ?? null;
            $this->stripe_percent_fee = $data['stripe_percent_fee'] ?? null;
            $this->stripe_flat_fee = $data['stripe_flat_fee'] ?? null;
            $this->type = $data['type'] ?? null;
            $this->meta = $data['meta'] ?? null;
            $this->created_at = $data['created_at'] ?? null;
            $this->updated_at = $data['updated_at'] ?? null;
        }
    }

    /**
     * Get merchant profile by ID
     * @param int $merchantId
     * @param string $format Supports 'array' or 'object'
     * @return array|Merchant|null
     */
    public static function getMerchantById($merchantId, $format = 'object')
    {
        try {
            $app = App::getInstance('neighborhub');
            $db = $app->db;
            $app->includeClass('assetmanager');
            $stmt = $db->prepare(
                "SELECT 
                    id, user_id, business_name, address, latitude, longitude, phone, image_url, status, type, website, facebook, platform_fee_rate, platform_flat_fee, store_hours, menus, delivery_assignment_mode, delivery_max_distance, stripe_api_key, stripe_percent_fee, stripe_flat_fee, meta, created_at, updated_at
                FROM neighborhub_merchants
                WHERE id = ?"
            );
            $stmt->execute([$merchantId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return null;

            $row['gallery'] = AssetManager::getImagesByEntity('merchant', $row['id']);

            if ($format === 'object') {
                $obj = new self($row);
                self::sanitize($obj);
                $obj->gallery = AssetManager::getImagesByEntity('merchant', $obj->id);
                return $obj;
            }
            return $row;
        } catch (Exception $e) {
            error_log("Merchant::getMerchantById Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get merchant profile by user ID
     */
    public static function getMerchantByUserId($userId, $format = 'object')
    {
        try {
            $app = App::getInstance('neighborhub');
            $db = $app->db;
            $app->includeClass('assetmanager');
            $stmt = $db->prepare(
                "SELECT 
                    id, user_id, business_name, address, latitude, longitude, phone, image_url, status, type, website, facebook, platform_fee_rate, platform_flat_fee, store_hours, menus, delivery_assignment_mode, delivery_max_distance, stripe_api_key, stripe_percent_fee, stripe_flat_fee, meta, created_at, updated_at
                FROM neighborhub_merchants
                WHERE user_id = ?
                LIMIT 1"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return null;

            $row['gallery'] = AssetManager::getImagesByEntity('merchant', $row['id']);

            if ($format === 'object') {
                $obj = new self($row);
                self::sanitize($obj);
                $obj->gallery = AssetManager::getImagesByEntity('merchant', $obj->id);
                return $obj;
            }
            return $row;
        } catch (Exception $e) {
            error_log("Merchant::getMerchantByUserId Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the products catalog for a specific merchant
     * @param int $merchantId
     * @param bool $availableOnly Filter to available items only
     * @param string $format Supports 'array' or 'object'
     * @return array Array of data rows or fully sanitized Product object instances
     */
    public static function getProductsCatalog($merchantId, $availableOnly = false, $format = 'array')
    {
        try {
            $db = App::getInstance()->db;

            $query = "SELECT 
                id, merchant_id, name, description, price, tags, type, meta, is_available, image_url, created_at, updated_at
            FROM neighborhub_products
            WHERE merchant_id = ?";

            $params = [$merchantId];

            if ($availableOnly) {
                $query .= " AND is_available = 1";
            }

            $query .= " ORDER BY tags ASC, name ASC, updated_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$results) {
                return array();
            }

            // 🌟 OBJECT MAPPING AND EDGE SANITIZATION LAYER
            if ($format === 'object') {
                // Since this framework maps to Product objects, ensure the Product model is ready
                App::getInstance()->includeModel('product');
                return array_map(function ($row) {
                    $productObj = new Product($row);
                    Product::sanitize($productObj); // Safe output escaping right before view generation
                    return $productObj;
                }, $results);
            }

            return $results;
        } catch (Exception $e) {
            error_log("Merchant::getProductsCatalog Error: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Get products grouped by tags
     * 
     * @param int $merchantId
     * @param bool $availableOnly Filter to available products only
     * @return array Array keyed by tags with product arrays
     */
    public static function getProductsByTagsGroup($merchantId, $availableOnly = false)
    {
        try {
            $db = App::getInstance()->db;

            $products = self::getProductsCatalog($merchantId, $availableOnly);

            $grouped = array();
            foreach ($products as $product) {
                $tags = $product['tags'] ?? 'Uncategorized';
                if (!isset($grouped[$tags])) {
                    $grouped[$tags] = array();
                }
                $grouped[$tags][] = $product;
            }

            return $grouped;
        } catch (Exception $e) {
            error_log("Merchant::getProductsByTagsGroup Error: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Update product availability status
     * 
     * @param int $productId
     * @param bool $isAvailable
     * @return bool True on success, false on failure
     */
    public static function updateProductAvailability($productId, $isAvailable)
    {
        try {
            $db = App::getInstance()->db;
            $stmt = $db->prepare(
                "UPDATE neighborhub_products
                SET is_available = ?, updated_at = NOW()
                WHERE id = ?"
            );
            return $stmt->execute([$isAvailable ? 1 : 0, $productId]);
        } catch (Exception $e) {
            error_log("Merchant::updateProductAvailability Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a new merchant profile
     * 
     * Accepts an associative array with the following keys:
     * - business_name (required): Name of the merchant business
     * - address (optional): Business address
     * - latitude (optional): Geographic latitude
     * - longitude (optional): Geographic longitude
     * - phone (optional): Contact phone number
     * - user_id (optional): User ID of the merchant owner
     * 
     * @param array $data Merchant profile data
     * @return int|false The new merchant ID on success, false on failure
     */
    public static function create($data)
    {
        try {
            $db = App::getInstance()->db;

            // Validate required fields
            if (!isset($data['business_name']) || empty($data['business_name'])) {
                error_log("Merchant::create Error: business_name is required");
                return false;
            }

            // Sanitize input data
            $businessName = isset($data['business_name']) ? trim($data['business_name']) : null;
            $address = isset($data['address']) ? trim($data['address']) : null;
            $latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
            $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
            $phone = isset($data['phone']) ? trim($data['phone']) : null;
            $userId = isset($data['user_id']) ? intval($data['user_id']) : null;
            $website = isset($data['website']) ? $data['website'] : null;
            $facebook = isset($data['facebook']) ? $data['facebook'] : null;
            $platform_fee_rate = !empty($data['platform_fee_rate']) ? $data['platform_fee_rate'] : 0.40;
            $platform_flat_fee = !empty($data['platform_flat_fee']) ? $data['platform_flat_fee'] : 1.50;
            $store_hours = isset($data['store_hours']) ? $data['store_hours'] : null;
            $menus = isset($data['menus']) ? $data['menus'] : null;
            $delivery_assignment_mode = isset($data['delivery_assignment_mode']) ? $data['delivery_assignment_mode'] : null;
            $delivery_max_distance = isset($data['delivery_max_distance']) ? $data['delivery_max_distance'] : null;
            $stripeApiKey = isset($data['stripe_api_key']) ? intval($data['stripe_api_key']) : null;
            $stripePercentFee = isset($data['stripe_percent_fee']) ? floatval($data['stripe_percent_fee']) : 0.029; // Default to 2.9%
            $stripeFlatFee = isset($data['stripe_flat_fee']) ? floatval($data['stripe_flat_fee']) : 0.30; // Default to $0.30
            $status = isset($data['status']) ? trim($data['status']) : 'active';
            $type = isset($data['type']) ? trim($data['type']) : 'default';
            $meta = isset($data['meta']) ? $data['meta'] : array();

            $sanitized = array(
                'user_id' => $userId,
                'business_name' => $businessName,
                'address' => $address,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'phone' => $phone,
                'status' => $status,
                'website' => $website,
                'facebook' => $facebook,
                'platform_fee_rate' => $platform_fee_rate,
                'platform_flat_fee' => $platform_flat_fee,
                'store_hours' => $store_hours,
                'delivery_assignment_mode' => $delivery_assignment_mode,
                'delivery_max_distance' => $delivery_max_distance,
                'stripe_api_key' => $stripeApiKey,
                'stripe_percent_fee' => $stripePercentFee,
                'stripe_flat_fee' => $stripeFlatFee,
                'type' => $type,
                'meta' => $meta
            );
            self::sanitize($sanitized);

            // Validate status against allowed values
            $allowedStatuses = array('active', 'paused', 'suspended');
            if (!in_array($status, $allowedStatuses)) {
                $status = 'active';
            }

            // Prepare and execute insert statement
            $stmt = $db->prepare(
                "INSERT INTO neighborhub_merchants
                (user_id, business_name, address, latitude, longitude, phone, status, website, facebook, platform_fee_rate, platform_flat_fee, store_hours, menus, delivery_assignment_mode, delivery_max_distance, stripe_api_key, stripe_percent_fee, stripe_flat_fee, type, meta, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            );

            $success = $stmt->execute([
                $userId,
                $sanitized['business_name'],
                $address,
                $latitude,
                $longitude,
                $phone,
                $status,
                $website,
                $facebook,
                $platform_fee_rate,
                $platform_flat_fee,
                $store_hours,
                $menus,
                $delivery_assignment_mode,
                $delivery_max_distance,
                $stripeApiKey,
                $stripePercentFee,
                $stripeFlatFee,
                $type,
                $sanitized['meta']
            ]);

            if ($success) {
                // Return the ID of the newly created merchant
                return intval($db->lastInsertId());
            } else {
                error_log("Merchant::create Error: Failed to insert merchant record");
                return false;
            }
        } catch (Exception $e) {
            error_log("Merchant::create Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update merchant profile
     * * @param int $merchantId
     * @param array $data Fields to update
     * @return array|bool Status details array on success, false on failure
     */
    public static function update($merchantId, $data)
    {
        try {
            $db = App::getInstance()->db;
            $result = array();

            // Validate required fields
            if (!$merchantId) {
                error_log("Merchant::update Error: merchantId is required");
                return false;
            }

            $merchantId = intval($merchantId);
            $updates = array();
            $params = array();

            // Run structural data mutations by reference (converts meta arrays to JSON string)

            // Build dynamic update query based on provided fields
            if (isset($data['business_name'])) {
                $updates[] = "business_name = ?";
                $params[] = trim($data['business_name']);
            }

            if (isset($data['user_id'])) {
                $updates[] = "user_id = ?";
                $params[] = trim($data['user_id']);
            }

            if (isset($data['address'])) {
                $updates[] = "address = ?";
                $params[] = trim($data['address']);
            }

            if (isset($data['latitude'])) {
                $updates[] = "latitude = ?";
                $params[] = floatval($data['latitude']);
            }

            if (isset($data['longitude'])) {
                $updates[] = "longitude = ?";
                $params[] = floatval($data['longitude']);
            }

            if (isset($data['phone'])) {
                $updates[] = "phone = ?";
                $params[] = trim($data['phone']);
            }

            if (isset($data['type'])) {
                $updates[] = "type = ?";
                $params[] = trim($data['type']);
            }

            if (isset($data['website'])) {
                $updates[] = "website = ?";
                $params[] = $data['website'];
            }

            if (isset($data['facebook'])) {
                $updates[] = "facebook = ?";
                $params[] = $data['facebook'];
            }

            if (!empty($data['platform_fee_rate'])) {
                $updates[] = "platform_fee_rate = ?";
                $params[] = $data['platform_fee_rate'];
            }

            if (!empty($data['platform_flat_fee'])) {
                $updates[] = "platform_flat_fee = ?";
                $params[] = $data['platform_flat_fee'];
            }

            if (isset($data['store_hours'])) {
                $updates[] = "store_hours = ?";
                $params[] = $data['store_hours'];
            }

            if (isset($data['menus'])) {
                $updates[] = "menus = ?";
                $params[] = $data['menus'];
            }

            if (isset($data['delivery_assignment_mode'])) {
                $updates[] = "delivery_assignment_mode = ?";
                $params[] = $data['delivery_assignment_mode'];
            }

            if (isset($data['delivery_max_distance'])) {
                $updates[] = "delivery_max_distance = ?";
                $params[] = $data['delivery_max_distance'];
            }

            if (isset($data['stripe_api_key'])) {
                $updates[] = "stripe_api_key = ?";
                $params[] = $data['stripe_api_key'];
            }

            if (isset($data['stripe_percent_fee'])) {
                $updates[] = "stripe_percent_fee = ?";
                $params[] = $data['stripe_percent_fee'];
            }

            if (isset($data['stripe_flat_fee'])) {
                $updates[] = "stripe_flat_fee = ?";
                $params[] = $data['stripe_flat_fee'];
            }

            if (isset($data['image_url'])) {
                $updates[] = "image_url = ?";
                $params[] = trim($data['image_url']);
            }

            if (isset($data['meta'])) {
                $updates[] = "meta = ?";
                $params[] = $data['meta']; // Already a verified JSON string or '{}' via sanitize()
            }

            if (isset($data['status'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }

            if (empty($updates)) {
                error_log("Merchant::update Error: No valid fields to update");
                return false;
            }

            // Always update the updated_at timestamp
            $updates[] = "updated_at = NOW()";

            $query = "UPDATE neighborhub_merchants SET " . implode(", ", $updates) . " WHERE id = ?";
            $params[] = $merchantId;

            $stmt = $db->prepare($query);
            $success = $stmt->execute($params);

            if ($success) {
                $result = ['success' => true, 'message' => 'Merchant updated successfully'];
            } else {
                $result = ['success' => false, 'message' => 'Failed to update merchant record'];
                error_log("Merchant::update Error: Failed to update merchant record");
            }
            $merchant = Merchant::getMerchantWithGallery($merchantId, 'object');

            // Handle merchant members
            if (isset($data['staff_members'])) {
                $staff_members = $data['staff_members'];
                foreach ($staff_members as $key => $value) {
                    if ($key == 'add') {
                        foreach ($value as $member) {
                            if (isset($member['user_id']) && isset($member['role'])) {
                                // Safeguard: Pass an explicit fallback metadata block array to satisfy fields without defaults
                                $memberMeta = isset($member['meta']) ? $member['meta'] : array();
                                $success = self::addStaffMember($merchantId, $member['user_id'], $member['role'], $memberMeta);
                                if (!$success) {
                                    $result['success'] = false;
                                    $result['message'] = "Failed to add staff member";
                                    error_log("Merchant::update Error: Failed to add staff member");
                                }
                            }
                        }
                    } else if ($key == 'remove') {
                        foreach ($value as $member) {
                            if (isset($member['id'])) {
                                $success = self::removeStaffMember($merchantId, $member['id']);
                                if (!$success) {
                                    $result['success'] = false;
                                    $result['message'] = "Failed to remove staff member";
                                    error_log("Merchant::update Error: Failed to remove staff member");
                                }
                            }
                        }
                    }
                }
            }

            return $result;
        } catch (Exception $e) {
            error_log("Merchant::update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a staff member to a merchant
     * 
     * Creates a relationship between a user and a merchant in the bridge table.
     * Validates the merchant and user exist before creating the relationship.
     * 
     * @param int $merchantId The merchant ID
     * @param int $userId The user ID to add as staff
     * @param string $role The staff role (owner, staff)
     * @return bool True on success, false on failure
     */
    public static function addStaffMember($merchantId, $userId, $role = 'staff', $type = 'default', $meta = array())
    {
        try {
            $db = App::getInstance()->db;

            // Validate required parameters
            if (!$merchantId || !$userId) {
                error_log("Merchant::addStaffMember Error: merchantId and userId are required");
                return false;
            }

            // Sanitize inputs
            $merchantId = intval($merchantId);
            $userId = intval($userId);
            $role = trim($role);


            // Validate role against allowed values
            $allowedRoles = array('owner', 'staff', 'delivery', 'screen');
            if (!in_array($role, $allowedRoles)) {
                $role = 'staff';
            }

            // Verify merchant exists
            $merchantCheck = $db->prepare("SELECT id FROM neighborhub_merchants WHERE id = ?");
            $merchantCheck->execute([$merchantId]);
            if (!$merchantCheck->fetch()) {
                error_log("Merchant::addStaffMember Error: Merchant ID $merchantId does not exist");
                return false;
            }

            // Verify user exists
            $userCheck = $db->prepare("SELECT id FROM users WHERE id = ?");
            $userCheck->execute([$userId]);
            if (!$userCheck->fetch()) {
                error_log("Merchant::addStaffMember Error: User ID $userId does not exist");
                return false;
            }

            // Check if relationship already exists
            $existingCheck = $db->prepare(
                "SELECT id FROM neighborhub_merchant_users WHERE merchant_id = ? AND user_id = ?"
            );
            $existingCheck->execute([$merchantId, $userId]);
            if ($existingCheck->fetch()) {
                error_log("Merchant::addStaffMember Warning: Staff relationship already exists for merchant $merchantId and user $userId");
                return false;
            }

            // Insert the staff relationship
            $stmt = $db->prepare(
                "INSERT INTO neighborhub_merchant_users
                (merchant_id, user_id, staff_role, status, type, meta, invited_at, created_at, updated_at)
                VALUES (?, ?, ?, 'active', ?, ?, NOW(), NOW(), NOW())"
            );

            $metaJson = empty($meta) ? '{}' : (is_array($meta) ? json_encode($meta) : $meta);

            $success = $stmt->execute([
                $merchantId,
                $userId,
                $role,
                $type,
                $metaJson
            ]);

            if ($success) {
                return true;
            } else {
                error_log("Merchant::addStaffMember Error: Failed to insert staff relationship");
                return false;
            }
        } catch (Exception $e) {
            error_log("Merchant::addStaffMember Exception: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Remove a staff member from a merchant
     * * Deletes the relationship record between a user and a merchant from the bridge table.
     * Validates that the relationship exists before attempting deletion.
     * * @param int $merchantId The merchant ID
     * @param int $userId The user ID to remove from staff
     * @return bool True on success, false on failure
     */
    public static function removeStaffMember($merchantId, $userId)
    {
        try {
            $db = App::getInstance()->db;

            // Validate required parameters
            if (!$merchantId || !$userId) {
                error_log("Merchant::removeStaffMember Error: merchantId and userId are required");
                return false;
            }

            // Sanitize inputs
            $merchantId = intval($merchantId);
            $userId = intval($userId);

            // Verify that the relationship actually exists before trying to delete it
            $existingCheck = $db->prepare(
                "SELECT id FROM neighborhub_merchant_users WHERE merchant_id = ? AND user_id = ?"
            );
            $existingCheck->execute([$merchantId, $userId]);
            if (!$existingCheck->fetch()) {
                error_log("Merchant::removeStaffMember Warning: No staff relationship found for merchant $merchantId and user $userId");
                return false;
            }

            // Execute the deletion statement from the bridge table
            $stmt = $db->prepare(
                "DELETE FROM neighborhub_merchant_users WHERE merchant_id = ? AND user_id = ?"
            );
            $success = $stmt->execute([$merchantId, $userId]);

            if ($success) {
                return true;
            } else {
                error_log("Merchant::removeStaffMember Error: Failed to execute deletion query");
                return false;
            }
        } catch (Exception $e) {
            error_log("Merchant::removeStaffMember Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all merchant staff
     */
    public static function getStaffRelations($merchantId, $status = null)
    {
        try {
            $db = App::getInstance()->db;

            $query = "SELECT 
                mu.id, mu.merchant_id, mu.user_id, mu.staff_role, mu.status, mu.invited_at, mu.joined_at, mu.created_at, mu.updated_at,
                u.username, u.email
            FROM neighborhub_merchant_users mu
            JOIN users u ON mu.user_id = u.id
            WHERE mu.merchant_id = ?";

            $params = [$merchantId];

            if ($status !== null) {
                $query .= " AND mu.status = ?";
                $params[] = $status;
            }

            $query .= " ORDER BY mu.staff_role DESC, mu.joined_at ASC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Merchant::getStaffRelations Error: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Delete a merchant (soft or hard delete)
     * 
     * @param int $merchantId
     * @param bool $hardDelete If true, permanently delete; if false, mark as suspended
     * @return bool True on success, false on failure
     */
    public static function deleteMerchant($merchantId, $hardDelete = false)
    {
        try {
            $db = App::getInstance()->db;

            if (!$merchantId) {
                error_log("Merchant::deleteMerchant Error: merchantId is required");
                return false;
            }

            $merchantId = intval($merchantId);

            if ($hardDelete) {
                // Hard delete
                $stmt = $db->prepare("DELETE FROM neighborhub_merchants WHERE id = ?");
                return $stmt->execute([$merchantId]);
            } else {
                // Soft delete - mark as suspended
                $stmt = $db->prepare(
                    "UPDATE neighborhub_merchants SET status = 'suspended', updated_at = NOW() WHERE id = ?"
                );
                return $stmt->execute([$merchantId]);
            }
        } catch (Exception $e) {
            error_log("Merchant::deleteMerchant Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all merchants (with optional filtering)
     * 
     * @param string|null $status Filter by status (active/paused/suspended)
     * @param int|null $limit Maximum number of results to return
     * @param int $offset Offset for pagination
     * @return array Array of merchants or empty array if none found
     */
    public static function getAllMerchants($status = null, $limit = null, $offset = 0, $order_by = 'business_name ASC', $format = 'object')
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            // 🌟 NORMALIZE INPUTS: Convert empty strings from HTTP requests into true nulls or clean integers
            $limit = ($limit !== null && $limit !== '') ? intval($limit) : null;
            $offset = ($offset !== null && $offset !== '') ? intval($offset) : 0;
            $status = ($status !== null && $status !== '') ? $status : null;

            $query = "SELECT 
        id, user_id, business_name, address, latitude, longitude, phone, image_url, status, type, meta, created_at, updated_at
        FROM neighborhub_merchants";

            // Build conditions dynamically
            $whereClauses = [];
            if ($status !== null) {
                $whereClauses[] = "status = :status";
            }

            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }

            $query .= " ORDER BY " . $order_by;

            // 🌟 Safe structural check: Limit clause is only appended if a true numeric limit exists
            if ($limit !== null) {
                $query .= " LIMIT :limit OFFSET :offset";
            }

            $stmt = $db->prepare($query);

            if ($status !== null) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }

            // 🌟 Safe parameter binding: Exactly mirrors the query structure above
            if ($limit !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$results) return array();

            if ($format === 'object') {
                return array_map(function ($row) {
                    $obj = new self($row);
                    self::sanitize($obj);
                    return $obj;
                }, $results);
            }
            return $results;
        } catch (Exception $e) {
            error_log("Merchant::getAllMerchants Error: " . $e->getMessage());
            return array();
        }
    }


    public static function sanitize(&$merchant = null)
    {
        if (!$merchant) return;

        if (is_array($merchant)) {
            $merchant['user_id'] = intval($merchant['user_id'] ?? 0);
            $merchant['business_name'] = html_entity_decode(trim($merchant['business_name'] ?? ''));
            $merchant['address'] = html_entity_decode(trim($merchant['address'] ?? ''));
            $merchant['phone'] = htmlspecialchars(trim($merchant['phone'] ?? ''));
            $merchant['status'] = trim($merchant['status'] ?? 'active');

            // 🌟 Fix: Fallback to empty JSON string if meta is missing, empty, or an empty array
            if (!isset($merchant['meta']) || empty($merchant['meta'])) {
                $merchant['meta'] = '{}';
            } else {
                $merchant['meta'] = is_array($merchant['meta']) ? json_encode($merchant['meta']) : $merchant['meta'];
            }
            return;
        } else {
            if (isset($merchant->id)) $merchant->id = intval($merchant->id);
            if (isset($merchant->user_id)) $merchant->user_id = intval($merchant->user_id);
            if (isset($merchant->business_name)) $merchant->business_name = html_entity_decode(trim($merchant->business_name));
            if (isset($merchant->address)) $merchant->address = html_entity_decode(trim($merchant->address));
            if (isset($merchant->phone)) $merchant->phone = htmlspecialchars(trim($merchant->phone));
            if (isset($merchant->status)) $merchant->status = trim($merchant->status);

            // 🌟 Fix: Fallback for Object structures
            if (!isset($merchant->meta) || empty($merchant->meta)) {
                $merchant->meta = '{}';
            } else {
                $merchant->meta = is_array($merchant->meta) || is_object($merchant->meta) ? json_encode($merchant->meta) : $merchant->meta;
            }
        }
    }
    public static function deleteImage($merchantId)
    {
        try {
            // 1. Grab the product to see if an image even exists
            $merchant = self::getMerchantById($merchantId);
            if (!$merchant || empty($merchant->image_url)) {
                error_log("Product::deleteImage Error: Product not found or has no image_url.");
                return false;
            }

            // 2. Setup the storage manager system

            $storageManager = new FileStorageManager('google_cloud');

            // 3. Isolate the target paths and target filename
            $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId);
            $publicUrl = $merchant->image_url;
            $merchantImage = pathinfo($publicUrl, PATHINFO_BASENAME);

            // 4. Trigger the storage engine removal
            $storageManager->deleteFile($targetPath, $merchantImage);

            // 5. Update the Database record to clean out the image URL reference
            self::update($merchantId, array('image_url' => ''));

            return array('success' => true, 'message' => 'Merchant image deleted successfully');
        } catch (Exception $e) {
            error_log("Product::deleteImage Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a merchant along with all of its gallery images attached via AssetManager
     * * @param int $merchantId
     * @return Merchant|array|false The hydrated merchant containing a 'gallery' array property
     */
    public static function getMerchantWithGallery($merchantId, $format = 'array')
    {
        // 1. Fetch the core merchant using your existing method
        $merchantData = self::getMerchantById($merchantId, 'array');
        if (!$merchantData) {
            return false;
        }
        error_log('Getting ' . $merchantId);
        // 2. Fetch the related images from the AssetManager bridge table
        $app = App::getInstance('neighborhub');
        $app->includeClass('assetmanager'); // Make sure the asset model is included
        $merchantData['gallery'] = AssetManager::getImagesByEntity('merchant', $merchantId);

        if ($format === 'object') {
            $merchantObj = new self($merchantData);
            return $merchantObj;
        }

        return $merchantData;
    }

    /**
     * Upload and attach multiple gallery images to this merchant
     * * @param int $merchantId
     * @param array $filesPayload Typically $_FILES['merchant_gallery']
     * @return array List of successfully uploaded public GCS URLs
     */
    public static function uploadGalleryImages($merchantId, $filesPayload)
    {
        if (empty($merchantId) || empty($filesPayload['name'])) {
            error_log("Merchant::uploadGalleryImages Error: Missing merchantId or files payload");
            return array();
        }

        $app = App::getInstance('neighborhub');
        $app->includeClass('assetmanager');

        // Pass the context to AssetManager: parent_type='merchant', parent_id=$merchantId, merchant_id=$merchantId
        return AssetManager::uploadMultipleImages('merchant', $merchantId, $merchantId, $filesPayload);
    }

    /**
     * Delete a single gallery image belonging to this merchant
     * * @param int $merchantId
     * @param int $imageId The ID from the neighborhub_images table
     * @return bool True on success, false on failure
     */
    public static function deleteGalleryImage($merchantId, $imageId)
    {
        try {
            $app = App::getInstance('neighborhub');
            $db = $app->db;
            $app->includeClass('assetmanager');

            // 1. Verify the image exists and actually belongs to this merchant context
            $stmt = $db->prepare(
                "SELECT image_url FROM neighborhub_images 
                 WHERE id = ? AND parent_type = 'merchant' AND parent_id = ?"
            );
            $stmt->execute([intval($imageId), intval($merchantId)]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$image) {
                error_log("Merchant::deleteGalleryImage Error: Image record not found for merchant $merchantId");
                return false;
            }

            // 2. Clean it out of Google Cloud Storage using path isolation
            //require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
            $storageManager = new FileStorageManager('google_cloud');

            $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId) . '/merchants';
            $filenameWithExtension = pathinfo($image['image_url'], PATHINFO_BASENAME);

            $storageManager->deleteFile($targetPath, $filenameWithExtension);

            // 3. Remove the database tracking reference record
            $deleteStmt = $db->prepare("DELETE FROM neighborhub_images WHERE id = ?");
            return $deleteStmt->execute([intval($imageId)]);
        } catch (Exception $e) {
            error_log("Merchant::deleteGalleryImage Exception: " . $e->getMessage());
            return false;
        }
    }

    public static function getAllMenuCategories($merchantId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            // Query selects menu fields + category fields (including type/meta/status attributes)
            $query = "SELECT 
            m.id AS menu_id,
            m.name AS menu_name,
            m.is_active AS menu_is_active,
            m.sort_order AS menu_sort,
            mc.id AS category_id,
            mc.name AS category_name,
            mc.sort_order AS cat_sort,
            mc.type AS category_type,
            mc.meta AS category_meta
          FROM neighborhub_menus m
          LEFT JOIN neighborhub_menu_categories mc ON mc.menu_id = m.id
          WHERE m.merchant_id = ?
          ORDER BY 
            COALESCE(CAST(m.sort_order AS UNSIGNED), 999999) ASC, 
            m.id ASC, 
            COALESCE(CAST(mc.sort_order AS UNSIGNED), 999999) ASC, 
            mc.id ASC";

            $stmt = $db->prepare($query);
            $stmt->execute([intval($merchantId)]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $menus = [];
            foreach ($rows as $row) {
                $menuId = intval($row['menu_id']);

                // Group menus by ID
                if (!isset($menus[$menuId])) {
                    $menus[$menuId] = [
                        'menu_id'        => $menuId,
                        'menu_name'      => $row['menu_name'],
                        'menu_is_active' => intval($row['menu_is_active']),
                        'menu_sort'      => intval($row['menu_sort']),
                        'categories'     => []
                    ];
                }

                // Append category if present (handles menus without categories)
                if (!empty($row['category_id'])) {
                    $menus[$menuId]['categories'][] = [
                        'category_id'   => intval($row['category_id']),
                        'category_name' => $row['category_name'],
                        'cat_sort'      => intval($row['cat_sort']),
                        'type'          => $row['category_type'],
                        'meta'          => !empty($row['category_meta']) ? json_decode($row['category_meta'], true) : []
                    ];
                }
            }

            // Return indexed array of menus
            return array_values($menus);
        } catch (Exception $e) {
            error_log("Merchant::getAllMenuCategories Error: " . $e->getMessage());
            return [];
        }
    }
}
