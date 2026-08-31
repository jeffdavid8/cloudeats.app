<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Product Model
 * Handles individual product catalog objects, pricing schemas, availability status,
 * and merchant menu listing relations within the Neighborhub app space.
 * All database operations use prepared statements with PDO for security.
 */

class Product
{
  // 🗂️ OBJECT PROPERTIES FOR HYDRATION
  public $id;
  public $merchant_id;
  public $name;
  public $description;
  public $price;
  public $type;
  public $meta;
  public $tags;
  public $is_available;
  public $image_url;
  public $created_at;
  public $updated_at;
  public $business_name; // Included for catalog joins if required
  public $gallery;

  /**
   * Model Constructor
   * Maps associative array keys directly to explicit object properties
   */
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

  /**
   * Get all products for a merchant with optional active filtering and optional menu filter
   * @param int $merchantId
   * @param bool $activeOnly Filter to active products only
   * @param string $format Returns 'array' rows or hydrated 'object' entities
   * @param string|null $menu Optional menu name filter (e.g., 'Main Menu', 'Lunch Specials')
   * @return array Array of product elements matching requested data shape
   */
  public static function getProductsByMerchant($merchantId, $activeOnly = true, $format = 'array', $menu = null)
  {
    try {
      $db = App::getInstance()->db;

      if (!empty($menu)) {
        // Query products scoped to a specific menu via join tables
        $query = "SELECT 
                    p.id, p.merchant_id, p.name, p.description, 
                    COALESCE(mi.override_price, p.price) AS price, 
                    p.tags, p.type, p.meta, 
                    (p.is_available AND mi.is_available) AS is_available, 
                    p.image_url, mc.name AS category, m.name AS menu,
                    p.created_at, p.updated_at
                  FROM neighborhub_products p
                  INNER JOIN neighborhub_menu_items mi ON p.id = mi.product_id
                  INNER JOIN neighborhub_menu_categories mc ON mi.category_id = mc.id
                  INNER JOIN neighborhub_menus m ON mc.menu_id = m.id
                  WHERE p.merchant_id = ? AND m.name = ?";

        $params = [$merchantId, trim($menu)];

        if ($activeOnly) {
          $query .= " AND p.is_available = 1 AND mi.is_available = 1 AND m.is_active = 1";
        }

        $query .= " ORDER BY mc.sort_order ASC, mi.sort_order ASC, p.name ASC";
      } else {
        // Query master catalog for the merchant
        $query = "SELECT 
                    id, merchant_id, name, description, price, tags, type, meta, is_available, image_url, created_at, updated_at
                  FROM neighborhub_products
                  WHERE merchant_id = ?";

        $params = [$merchantId];

        if ($activeOnly) {
          $query .= " AND is_available = 1";
        }

        $query .= " ORDER BY name ASC";
      }

      $stmt = $db->prepare($query);
      $stmt->execute($params);
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

      if (!$products) {
        return array();
      }

      if ($format === 'object') {
        return array_map(function ($row) {
          $obj = new self($row);
          self::sanitize($obj);
          return $obj;
        }, $products);
      }

      return $products;
    } catch (Exception $e) {
      error_log("Product::getProductsByMerchant Error: " . $e->getMessage());
      return array();
    }
  }

  /**
   * Get a single product by ID
   * @param int $productId
   * @param string $format 'array' or 'object'
   * @return array|Product|false Dependent on configuration parameters
   */
  public static function getProductById($productId, $format = 'array')
  {
    try {
      $app = App::getInstance();
      $db = $app->db;

      $stmt = $db->prepare(
        "SELECT 
          id, merchant_id, name, description, price, tags, type, meta, is_available, image_url, created_at, updated_at
       FROM neighborhub_products
       WHERE id = ?"
      );
      $stmt->execute([$productId]);
      $product = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$product) {
        return false;
      }

      $app->includeClass('assetmanager');
      $galleryImages = AssetManager::getImagesByEntity('product', $productId);

      if ($format === 'object') {
        $obj = new self($product);
        $obj->gallery = $galleryImages;
        self::sanitize($obj);
        return $obj;
      }

      $product['gallery'] = $galleryImages;
      return $product;
    } catch (Exception $e) {
      error_log("Product::getProductById Error: " . $e->getMessage());
      return false;
    }
  }

  public static function sanitize(&$product = null)
  {
    if (!$product) return;

    if (is_array($product)) {
      $product['user_id'] = intval($product['user_id'] ?? 0);
      $product['name'] = html_entity_decode(trim($product['name'] ?? ''));
      $product['description'] = html_entity_decode(trim($product['description'] ?? ''));
      $product['tags'] = html_entity_decode(trim($product['tags'] ?? 'active'));
      $product['status'] = trim($product['status'] ?? 'active');

      if (!isset($product['meta']) || empty($product['meta'])) {
        $product['meta'] = '{}';
      } else {
        $product['meta'] = is_array($product['meta']) ? json_encode($product['meta']) : $product['meta'];
      }
      return;
    } else {
      if (isset($product->id)) $product->id = intval($product->id);
      if (isset($product->name)) $product->name = html_entity_decode(trim($product->name));
      if (isset($product->description)) $product->description = html_entity_decode(trim($product->description));
      if (isset($product->tags)) $product->tags = html_entity_decode(trim($product->tags));
      if (isset($product->status)) $product->status = trim($product->status);

      if (!isset($product->meta) || empty($product->meta)) {
        $product->meta = '{}';
      } else {
        $product->meta = is_array($product->meta) || is_object($product->meta) ? json_encode($product->meta) : $product->meta;
      }
    }
  }

  public static function create($merchantId, $data)
  {
    try {
      $db = App::getInstance()->db;

      if (!isset($data['name']) || empty($data['name'])) {
        error_log("Product::create Error: name is required");
        return false;
      }

      if (!isset($data['price'])) {
        error_log("Product::create Error: price is required");
        return false;
      }

      $merchantId = intval($merchantId);
      $name = trim($data['name']);
      $description = isset($data['description']) ? trim($data['description']) : null;
      $price = floatval($data['price']);
      $type = isset($data['type']) ? trim($data['type']) : null;
      $tags = isset($data['tags']) ? trim($data['tags']) : null;
      $imageUrl = isset($data['image_url']) ? trim($data['image_url']) : null;
      $isAvailable = isset($data['is_available']) ? intval($data['is_available']) : 1;
      $meta = isset($data['meta']) ? $data['meta'] : null;

      if ($price < 0) {
        error_log("Product::create Error: price must be non-negative");
        return false;
      }

      $sanitized = array(
        'merchant_id' => $merchantId,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'type' => $type,
        'tags' => $tags,
        'meta' => $meta
      );
      self::sanitize($sanitized);

      $isAvailable = $isAvailable ? 1 : 0;

      $stmt = $db->prepare(
        "INSERT INTO neighborhub_products
                (merchant_id, name, description, price, tags, type, meta, is_available, image_url, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
      );

      $success = $stmt->execute([
        $merchantId,
        $sanitized['name'],
        $sanitized['description'],
        $price,
        $sanitized['tags'],
        $sanitized['type'],
        $sanitized['meta'],
        $isAvailable,
        $imageUrl
      ]);

      if ($success) {
        return intval($db->lastInsertId());
      } else {
        error_log("Product::create Error: Failed to insert product record");
        return false;
      }
    } catch (Exception $e) {
      error_log("Product::create Exception: " . $e->getMessage());
      return false;
    }
  }

  public static function updateAvailability($productId, $isAvailable)
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
      error_log("Product::updateAvailability Error: " . $e->getMessage());
      return false;
    }
  }

  public static function update($productId, $data)
  {
    try {
      $db = App::getInstance()->db;
      if (!$productId) return false;
      $productId = intval($productId);
      $updates = array();
      $params = array();
      $sanitized = array(
        'name' => (isset($data['name'])) ? $data['name'] : '',
        'description' => (isset($data['description'])) ? $data['description'] : '',
        'price' => (isset($data['price'])) ? $data['price'] : '0.00',
        'type' => (isset($data['type'])) ? $data['type'] : '',
        'meta' => (isset($data['meta'])) ? $data['meta'] : '{}',
        'tags' => (isset($data['tags'])) ? $data['tags'] : '',
      );
      self::sanitize($sanitized);

      if (isset($data['name'])) {
        $updates[] = "name = ?";
        $params[] = $sanitized['name'];
      }
      if (isset($data['description'])) {
        $updates[] = "description = ?";
        $params[] = $sanitized['description'];
      }
      if (isset($data['price'])) {
        $price = floatval($data['price']);
        if ($price < 0) return false;
        $updates[] = "price = ?";
        $params[] = $sanitized['price'];
      }
      if (isset($data['tags'])) {
        $updates[] = "tags = ?";
        $params[] = $sanitized['tags'];
      }
      if (isset($data['type'])) {
        $updates[] = "type = ?";
        $params[] = $sanitized['type'];
      }
      if (isset($data['image_url'])) {
        $updates[] = "image_url = ?";
        $params[] = $data['image_url'];
      }
      if (isset($data['is_available'])) {
        $updates[] = "is_available = ?";
        $params[] = $data['is_available'] ? 1 : 0;
      }
      if (isset($data['meta'])) {
        $updates[] = "meta = ?";
        $params[] = $sanitized['meta'];
      }

      if (empty($updates)) return false;

      $updates[] = "updated_at = NOW()";
      $query = "UPDATE neighborhub_products SET " . implode(", ", $updates) . " WHERE id = ?";
      $params[] = $productId;

      $stmt = $db->prepare($query);
      return $stmt->execute($params);
    } catch (Exception $e) {
      error_log("Product::update Error: " . $e->getMessage());
      return false;
    }
  }

  public static function updatePrice($productId, $newPrice)
  {
    try {
      $db = App::getInstance()->db;

      $newPrice = floatval($newPrice);
      if ($newPrice < 0) {
        error_log("Product::updatePrice Error: price must be non-negative");
        return false;
      }

      $stmt = $db->prepare(
        "UPDATE neighborhub_products
                SET price = ?, updated_at = NOW()
                WHERE id = ?"
      );

      return $stmt->execute([$newPrice, $productId]);
    } catch (Exception $e) {
      error_log("Product::updatePrice Error: " . $e->getMessage());
      return false;
    }
  }

  public static function delete($productId)
  {
    try {
      $db = App::getInstance()->db;
      if (!$productId) return false;
      $stmt = $db->prepare("DELETE FROM neighborhub_products WHERE id = ?");
      return $stmt->execute([$productId]);
    } catch (Exception $e) {
      error_log("Product::delete Error: " . $e->getMessage());
      return false;
    }
  }

  public static function getProductCount($merchantId, $activeOnly = true)
  {
    try {
      $db = App::getInstance()->db;
      $query = "SELECT COUNT(*) as count FROM neighborhub_products WHERE merchant_id = ?";
      if ($activeOnly) $query .= " AND is_available = 1";

      $stmt = $db->prepare($query);
      $stmt->execute([$merchantId]);
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return intval($result['count'] ?? 0);
    } catch (Exception $e) {
      return 0;
    }
  }

  /**
   * Get products by Category/Tag for a merchant using the menu categories system
   * @param int $merchantId
   * @param string $categoryName Category name
   * @param bool $activeOnly Filter to active products only
   * @param string|null $menu Optional menu name
   * @return array Array of products in category
   */
  public static function getProductsByTags($merchantId, $categoryName, $activeOnly = true, $menu = null)
  {
    try {
      $db = App::getInstance()->db;

      $query = "SELECT 
                  p.id, p.merchant_id, p.name, p.description, 
                  COALESCE(mi.override_price, p.price) AS price, 
                  p.tags, p.type, p.meta, 
                  (p.is_available AND mi.is_available) AS is_available, 
                  p.image_url, p.created_at, p.updated_at
                FROM neighborhub_products p
                INNER JOIN neighborhub_menu_items mi ON p.id = mi.product_id
                INNER JOIN neighborhub_menu_categories mc ON mi.category_id = mc.id
                INNER JOIN neighborhub_menus m ON mc.menu_id = m.id
                WHERE p.merchant_id = ? AND mc.name = ?";

      $params = [$merchantId, $categoryName];

      if (!empty($menu)) {
        $query .= " AND m.name = ?";
        $params[] = trim($menu);
      }

      if ($activeOnly) {
        $query .= " AND p.is_available = 1 AND mi.is_available = 1 AND m.is_active = 1";
      }

      $query .= " ORDER BY mi.sort_order ASC, p.name ASC";

      $stmt = $db->prepare($query);
      $stmt->execute($params);
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $products ? $products : array();
    } catch (Exception $e) {
      error_log("Product::getProductsByTags Error: " . $e->getMessage());
      return array();
    }
  }

  /**
   * Search products by name within a merchant's catalog
   * @param int $merchantId
   * @param string $searchTerm Search query
   * @param bool $activeOnly Filter to active products only
   * @return array Array of matching products
   */
  public static function searchByName($merchantId, $searchTerm, $activeOnly = true)
  {
    try {
      $db = App::getInstance()->db;

      $query = "SELECT 
                id, merchant_id, name, description, price, tags, is_available, image_url, type, meta, created_at, updated_at
            FROM neighborhub_products
            WHERE merchant_id = ? AND (name LIKE ? OR description LIKE ?)";

      $searchPattern = '%' . $searchTerm . '%';
      $params = [$merchantId, $searchPattern, $searchPattern];

      if ($activeOnly) {
        $query .= " AND is_available = 1";
      }

      $query .= " ORDER BY name ASC";

      $stmt = $db->prepare($query);
      $stmt->execute($params);
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $products ? $products : array();
    } catch (Exception $e) {
      error_log("Product::searchByName Error: " . $e->getMessage());
      return array();
    }
  }

  /**
   * Assign a catalog product to a menu category (Creates category and menu records if missing)
   * @param int $productId
   * @param string $menuName
   * @param string $categoryName
   * @param int $merchantId
   * @return array
   */
  public static function assignToCategory($productId, $menuId, $menuName, $categoryId, $categoryName, $merchantId)
  {
    try {
      $db = App::getInstance()->db;
      if (intval($menuId) === -1) {
        // Create New menu
        $stmt = $db->prepare("INSERT INTO neighborhub_menus (merchant_id, name) VALUES (?, ?)");
        $stmt->execute([$merchantId, $menuName]);
        $menuId = $db->lastInsertId();
      }

      // 2. Get or create category
      $category = null;

      // Only query the DB if the ID falls within a safe, valid range
      if ($categoryId > 0 && $categoryId <= 2147483647) {
        $stmt = $db->prepare("SELECT id FROM neighborhub_menu_categories WHERE menu_id = ? AND id = ?");
        $stmt->execute([$menuId, $categoryId]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
      }

      if (!$category) {
        // FIX: Include the 'name' field in the INSERT query
        // Using $menuName as a fallback if a specific category name isn't provided
        $stmt = $db->prepare("INSERT INTO neighborhub_menu_categories (menu_id, name) VALUES (?, ?)");
        $stmt->execute([$menuId, $categoryName]);
        $categoryId = $db->lastInsertId();
      } else {
        $categoryId = $category['id'];
      }


      // 3. Upsert menu item association
      $stmt = $db->prepare(
        "INSERT INTO neighborhub_menu_items (category_id, product_id, is_available) 
        VALUES (?, ?, 1) 
        ON DUPLICATE KEY UPDATE 
        id = LAST_INSERT_ID(id), 
        category_id = VALUES(category_id)"
      );
      $success = $stmt->execute([$categoryId, $productId]);

      if (!$success) {
        error_log("Product::assignToCategory Error: Failed to assign product to category");
        return array(
          'success' => false
        );
      }

      $menu_item_id = $db->lastInsertId();

      return array(
        'success' => true,
        'menu_id' => $menuId,
        'category_id' => $categoryId,
        'menu_item_id' => $menu_item_id
      );
    } catch (Exception $e) {
      error_log("Product::assignToCategory Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Remove a product assignment from a specific menu or all menus
   * @param int $productId
   * @param string|null $menuName
   * @return bool
   */
  public static function removeFromMenu($productId, $menuId = null)
  {
    try {
      $db = App::getInstance()->db;

      if (!empty($menuName)) {
        $stmt = $db->prepare(
          "DELETE mi FROM neighborhub_menu_items mi
           INNER JOIN neighborhub_menu_categories mc ON mi.category_id = mc.id
           INNER JOIN neighborhub_menus m ON mc.menu_id = m.id
           WHERE mi.product_id = ? AND m.id = ?"
        );
        return $stmt->execute([$productId, $menuId]);
      } else {
        $stmt = $db->prepare("DELETE FROM neighborhub_menu_items WHERE product_id = ?");
        return $stmt->execute([$productId]);
      }
    } catch (Exception $e) {
      error_log("Product::removeFromMenu Error: " . $e->getMessage());
      return false;
    }
  }

  public static function uploadImage($productId, $merchantId, $fileData)
  {
    try {
      if ($fileData['error'] !== UPLOAD_ERR_OK) {
        error_log("Product::uploadImage Error: File upload code " . $fileData['error']);
        return false;
      }

      $storageManager = new FileStorageManager('google_cloud');

      $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId) . '/products';
      $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
      $extension = strtolower(preg_replace('/[^a-z0-9]/', '', $extension));

      if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp'))) {
        error_log("Product::uploadImage Error: Invalid file extension " . $extension);
        return false;
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
        $fileData,
        $targetPath,
        $uniqueFilename,
        $uploadOptions
      );

      if (!$uploadResult['success']) {
        error_log("Product::uploadImage Error: Storage engine upload failure.");
        return false;
      }

      $publicUrl = $storageManager->getFileUrl($targetPath, $uniqueFilename);
      if (!$publicUrl) {
        error_log("Product::uploadImage Error: Failed to generate public target URL.");
        return false;
      }

      self::update($productId, array('image_url' => $publicUrl));

      return $publicUrl;
    } catch (Exception $e) {
      error_log("Product::uploadImage Exception: " . $e->getMessage());
      return false;
    }
  }

  public static function deleteImage($productId, $merchantId)
  {
    try {
      $product = self::getProductById($productId);
      if (!$product || empty($product['image_url'])) {
        error_log("Product::deleteImage Error: Product not found or has no image_url.");
        return false;
      }

      $storageManager = new FileStorageManager('google_cloud');

      $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId) . '/products';
      $publicUrl = $product['image_url'];
      $productImage = pathinfo($publicUrl, PATHINFO_BASENAME);

      $storageManager->deleteFile($targetPath, $productImage);

      self::update($productId, array('image_url' => ''));

      return $productImage;
    } catch (Exception $e) {
      error_log("Product::deleteImage Exception: " . $e->getMessage());
      return false;
    }
  }

  public static function getProductWithGallery($productId, $format = 'array')
  {
    $productData = self::getProductById($productId);
    if (!$productData) {
      return false;
    }

    $app = App::getInstance('neighborhub');
    $app->includeClass('assetmanager');
    $gallery = AssetManager::getImagesByEntity('product', $productId);

    if ($format === 'object') {
      $productObj = new self($productData);
      self::sanitize($productObj);
      $productObj->gallery = $gallery ? $gallery : array();
      return $productObj;
    }

    $productData['gallery'] = $gallery ? $gallery : array();
    return $productData;
  }

  public static function uploadGalleryImages($productId, $merchantId, $filesPayload)
  {
    if (empty($productId) || empty($merchantId) || empty($filesPayload['name'])) {
      error_log("Product::uploadGalleryImages Error: Missing parameters");
      return array();
    }

    $app = App::getInstance('neighborhub');
    $app->includeClass('assetmanager');

    return AssetManager::uploadMultipleImages('product', $productId, $merchantId, $filesPayload);
  }

  public static function deleteGalleryImage($productId, $merchantId, $imageId)
  {
    try {
      $app = App::getInstance('neighborhub');
      $app->includeClass('assetmanager');
      $db = $app->db;

      $stmt = $db->prepare(
        "SELECT image_url FROM neighborhub_images 
                 WHERE id = ? AND parent_type = 'product' AND parent_id = ?"
      );
      $stmt->execute([intval($imageId), intval($productId)]);
      $image = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$image) {
        error_log("Product::deleteGalleryImage Error: Asset record not verified.");
        return false;
      }

      $storageManager = new FileStorageManager('google_cloud');

      $targetPath = 'apps/neighborhub/merchants/' . intval($merchantId) . '/products';
      $filenameWithExtension = pathinfo($image['image_url'], PATHINFO_BASENAME);

      $storageManager->deleteFile($targetPath, $filenameWithExtension);

      $deleteStmt = $db->prepare("DELETE FROM neighborhub_images WHERE id = ?");
      return $deleteStmt->execute([intval($imageId)]);
    } catch (Exception $e) {
      error_log("Product::deleteGalleryImage Exception: " . $e->getMessage());
      return false;
    }
  }
}
