<?php

class Courier
{
    public static function sanitize(&$courier = null)
    {
        if (!$courier) return;

        if (is_array($courier)) {
            //$courier['user_id'] = intval($courier['user_id'] ?? 0);
            //$courier['name'] = html_entity_decode(trim($courier['name'])) ?? '';
            //$courier['description'] = html_entity_decode(trim($courier['description'])) ?? '';
            //$courier['category'] = html_entity_decode(trim($courier['category'])) ?? 'active';
            //$courier['status'] = trim($courier['status']) ?? 'active';

            // 🌟 Fix: Fallback to empty JSON string if meta is missing, empty, or an empty array
            if (!isset($courier['meta']) || empty($courier['meta'])) {
                $courier['meta'] = '{}';
            } else {
                $courier['meta'] = is_array($courier['meta']) ? json_encode($courier['meta']) : $courier['meta'];
            }
            return;
        } else {
            //if (isset($courier->id)) $courier->id = intval($courier->id);
            //if (isset($courier->name)) $courier->name = html_entity_decode(trim($courier->name));
            //if (isset($courier->description)) $courier->description = html_entity_decode(trim($courier->description));
            //if (isset($courier->category)) $courier->category = html_entity_decode(trim($courier->category));
            //if (isset($courier->status)) $courier->status = trim($courier->status);

            // 🌟 Fix: Fallback for Object structures
            if (!isset($courier->meta) || empty($courier->meta)) {
                $courier->meta = '{}';
            } else {
                $courier->meta = is_array($courier->meta) || is_object($courier->meta) ? json_encode($courier->meta) : $courier->meta;
            }
        }
    }

    /**
     * Create a brand new courier record
     * * @param array $data Assoc array of properties
     * @return int|false Inserts row and returns ID, false on failure
     */
    public static function create($data)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            $stmt = $db->prepare(
                "INSERT INTO neighborhub_couriers (
                    user_id, business_name, phone, vehicle_type, status, 
                    total_deliveries, rating, meta, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 0, 5.0, ?, NOW(), NOW())"
            );

            $sanitized = array(
                'meta' => (isset($data['meta'])) ? $data['meta'] : '{}',
            );

            $stmt->execute([
                intval($data['user_id']),
                $data['business_name'],
                $data['phone'],
                $data['vehicle_type'] ?? 'WALKING',
                $data['status'] ?? 'pending',
                $sanitized['meta'],
            ]);

            return $db->lastInsertId();
        } catch (Exception $e) {
            error_log("Failed to create courier: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch courier profile by user ID
     * 
     * @param int $userId
     * @return array|false Courier record, false on failure
     */
    public static function getCourierByUserId($userId)
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
                    last_location_update,
                    total_deliveries,
                    rating,
                    created_at,
                    updated_at
                FROM neighborhub_couriers
                WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $courier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$courier) {
                error_log("Courier not found for user: " . $userId);
                return false;
            }

            return $courier;
        } catch (Exception $e) {
            error_log("Failed to fetch courier by user ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch courier profile by courier ID
     * 
     * @param int $courierId
     * @return array|false Courier record, false on failure
     */
    public static function getCourierById($courierId)
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
                    last_location_update,
                    total_deliveries,
                    rating,
                    created_at,
                    updated_at
                FROM neighborhub_couriers
                WHERE id = ?"
            );
            $stmt->execute([$courierId]);
            $courier = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$courier) {
                error_log("Courier not found: " . $courierId);
                return false;
            }

            return $courier;
        } catch (Exception $e) {
            error_log("Failed to fetch courier by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update courier geolocation
     * 
     * @param int $courierId
     * @param float $latitude
     * @param float $longitude
     * @return bool True on success, false on failure
     */
    public static function updateLocation($courierId, $latitude, $longitude)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            // Validate coordinates
            if (!is_numeric($latitude) || !is_numeric($longitude)) {
                error_log("Invalid coordinates for courier: " . $courierId);
                return false;
            }

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                error_log("Coordinates out of range for courier: " . $courierId);
                return false;
            }

            $stmt = $db->prepare(
                "UPDATE neighborhub_couriers 
                SET latitude = ?, 
                    longitude = ?, 
                    last_location_update = NOW(),
                    updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$latitude, $longitude, $courierId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Location update failed for courier: " . $courierId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to update courier location: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update courier online status
     * 
     * @param int $courierId
     * @param string $status One of: 'available', 'on_delivery', 'offline'
     * @return bool True on success, false on failure
     */
    public static function updateStatus($courierId, $status)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            $validStatuses = ['available', 'on_delivery', 'offline'];
            if (!in_array($status, $validStatuses)) {
                error_log("Invalid status for courier: " . $status);
                return false;
            }

            $stmt = $db->prepare(
                "UPDATE neighborhub_couriers 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$status, $courierId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Status update failed for courier: " . $courierId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to update courier status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all available delivery jobs (orders ready for pickup)
     * 
     * @param int $limit
     * @param int $offset
     * @return array|false Array of available jobs, false on failure
     */
    public static function getAvailableLocalJobs($limit = 50, $offset = 0)
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
                    m.business_name,
                    m.latitude as merchant_lat,
                    m.longitude as merchant_lng
                FROM neighborhub_orders o
                JOIN neighborhub_merchants m ON o.merchant_id = m.id
                WHERE o.state = 'READY_FOR_PICKUP' 
                    AND o.locked_by_courier_id IS NULL
                ORDER BY o.created_at ASC
                LIMIT ? OFFSET ?"
            );
            $stmt->execute([$limit, $offset]);

            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $jobs ? $jobs : [];
        } catch (Exception $e) {
            error_log("Failed to fetch available local jobs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get nearby available jobs based on current courier location
     * 
     * @param float $courierLat Courier current latitude
     * @param float $courierLng Courier current longitude
     * @param float $radiusKm Search radius in kilometers
     * @param int $limit
     * @return array|false Array of nearby jobs sorted by distance, false on failure
     */
    public static function getNearbyAvailableJobs($courierLat, $courierLng, $radiusKm = 5, $limit = 20)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            // Validate coordinates
            if (!is_numeric($courierLat) || !is_numeric($courierLng)) {
                error_log("Invalid courier coordinates");
                return false;
            }

            // Haversine formula for distance calculation
            // Returns distance in kilometers
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
                    m.business_name,
                    m.latitude as merchant_lat,
                    m.longitude as merchant_lng,
                    (
                        6371 * acos(
                            cos(radians(?)) * cos(radians(m.latitude)) * 
                            cos(radians(m.longitude) - radians(?)) + 
                            sin(radians(?)) * sin(radians(m.latitude))
                        )
                    ) AS distance_km
                FROM neighborhub_orders o
                JOIN neighborhub_merchants m ON o.merchant_id = m.id
                WHERE o.state = 'READY_FOR_PICKUP' 
                    AND o.locked_by_courier_id IS NULL
                    AND m.latitude IS NOT NULL
                    AND m.longitude IS NOT NULL
                HAVING distance_km <= ?
                ORDER BY distance_km ASC
                LIMIT ?"
            );

            $stmt->execute([$courierLat, $courierLng, $courierLat, $radiusKm, $limit]);

            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $jobs ? $jobs : [];
        } catch (Exception $e) {
            error_log("Failed to fetch nearby available jobs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get courier's active delivery assignments
     * 
     * @param int $courierId
     * @return array|false Array of active deliveries, false on failure
     */
    public static function getActiveDeliveries($courierId)
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
                WHERE o.courier_id = ? 
                    AND o.state IN ('IN_TRANSIT', 'READY_FOR_PICKUP')
                ORDER BY o.created_at ASC"
            );
            $stmt->execute([$courierId]);

            $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $deliveries ? $deliveries : [];
        } catch (Exception $e) {
            error_log("Failed to fetch active deliveries: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment delivery completion count
     * 
     * @param int $courierId
     * @return bool True on success, false on failure
     */
    public static function incrementDeliveryCount($courierId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            $stmt = $db->prepare(
                "UPDATE neighborhub_couriers 
                SET total_deliveries = total_deliveries + 1,
                    updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$courierId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Delivery count increment failed: " . $courierId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to increment delivery count: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update courier rating
     * 
     * @param int $courierId
     * @param float $rating Rating between 0.0 and 5.0
     * @return bool True on success, false on failure
     */
    public static function updateRating($courierId, $rating)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            // Validate rating range
            if (!is_numeric($rating) || $rating < 0 || $rating > 5) {
                error_log("Invalid rating value for courier: " . $rating);
                return false;
            }

            $stmt = $db->prepare(
                "UPDATE neighborhub_couriers 
                SET rating = ?, updated_at = NOW() 
                WHERE id = ?"
            );
            $stmt->execute([$rating, $courierId]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            error_log("Rating update failed: " . $courierId);
            return false;
        } catch (Exception $e) {
            error_log("Failed to update courier rating: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available couriers for automatic assignment (online and available)
     * 
     * @param int $limit
     * @return array|false Array of available couriers, false on failure
     */
    public static function getAvailableCouriers($limit = 100)
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
                FROM neighborhub_couriers
                WHERE status = 'available'
                ORDER BY rating DESC, total_deliveries ASC
                LIMIT ?"
            );
            $stmt->execute([$limit]);

            $couriers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $couriers ? $couriers : [];
        } catch (Exception $e) {
            error_log("Failed to fetch available couriers: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Get a courier profile along with all related gallery assets
     * * @param int $courierId
     * @return array|false The courier array containing a 'gallery' field, false on failure
     */
    public static function getCourierWithGallery($courierId)
    {
        $courierData = self::getCourierById($courierId);
        if (!$courierData) {
            return false;
        }

        $app = App::getInstance('neighborhub');
        $app->includeClass('assetmanager');

        $gallery = AssetManager::getImagesByEntity('courier', $courierId);
        $courierData['gallery'] = $gallery ? $gallery : array();

        return $courierData;
    }

    /**
     * Upload and attach multiple gallery files to this courier account
     * * @param int $courierId
     * @param array $filesPayload Typically $_FILES['courier_gallery']
     * @return array List of uploaded URLs
     */
    public static function uploadGalleryImages($courierId, $filesPayload)
    {
        if (empty($courierId) || empty($filesPayload['name'])) {
            error_log("Courier::uploadGalleryImages Error: Missing parameters");
            return array();
        }

        $app = App::getInstance('neighborhub');
        $app->includeClass('assetmanager');

        // Note: For couriers, we scope their file storage tree root under merchants/0/couriers
        return AssetManager::uploadMultipleImages('courier', $courierId, 0, $filesPayload);
    }

    /**
     * Delete a single gallery image tracking file belonging to this courier profile
     * * @param int $courierId
     * @param int $imageId
     * @return bool
     */
    public static function deleteGalleryImage($courierId, $imageId)
    {
        try {
            $db = App::getInstance('neighborhub')->db;

            $stmt = $db->prepare(
                "SELECT image_url FROM neighborhub_images 
                 WHERE id = ? AND parent_type = 'courier' AND parent_id = ?"
            );
            $stmt->execute([intval($imageId), intval($courierId)]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$image) {
                error_log("Courier::deleteGalleryImage Error: Verification failure.");
                return false;
            }

            //require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
            $storageManager = new FileStorageManager('google_cloud');

            $targetPath = 'apps/neighborhub/merchants/0/couriers';
            $filenameWithExtension = pathinfo($image['image_url'], PATHINFO_BASENAME);

            $storageManager->deleteFile($targetPath, $filenameWithExtension);

            $deleteStmt = $db->prepare("DELETE FROM neighborhub_images WHERE id = ?");
            return $deleteStmt->execute([intval($imageId)]);
        } catch (Exception $e) {
            error_log("Courier::deleteGalleryImage Exception: " . $e->getMessage());
            return false;
        }
    }
}
