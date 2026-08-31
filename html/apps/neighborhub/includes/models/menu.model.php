<?php
if (!defined('MB_RUNNING')) exit;

class Menu
{
  public $id;
  public $merchant_id;
  public $name;
  public $description;
  public $is_active;
  public $sort_order;

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

  private static function getDb()
  {
    return App::getInstance()->db;
  }

  /**
   * Create a new menu for a merchant.
   */
  public static function create($merchantId, $name, $description = null, $isActive = 1, $sortOrder = 0)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "INSERT INTO neighborhub_menus (merchant_id, name, description, is_active, sort_order)
         VALUES (?, ?, ?, ?, ?)"
      );

      if (!$stmt->execute([intval($merchantId), trim($name), $description, intval($isActive), intval($sortOrder)])) {
        return false;
      }

      return (int) $db->lastInsertId();
    } catch (Exception $e) {
      error_log("Menu::create Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetch a single menu by ID.
   */
  public static function getById($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, merchant_id, name, description, is_active, sort_order
         FROM neighborhub_menus
         WHERE id = ?
         LIMIT 1"
      );
      $stmt->execute([intval($menuId)]);
      return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
      error_log("Menu::getById Error: " . $e->getMessage());
      return null;
    }
  }
  /**
   * Rename a top-level menu.
   * 
   * @param int $menuId
   * @param string $newName
   * @return bool
   */
  public static function rename($menuId, $newName)
  {
    return self::update(intval($menuId), ['name' => trim($newName)]);
  }

  public static function getCategoriesByMenuId($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, menu_id, name, sort_order
         FROM neighborhub_menu_categories
         WHERE menu_id = ?
         ORDER BY sort_order ASC, name ASC"
      );
      $stmt->execute([intval($menuId)]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
      error_log("Menu::getMenuCategories Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Get all menus for a merchant.
   */
  public static function getMenusByMerchantId($merchantId)
  {
    try {
      $db = self::getDb();

      $stmt = $db->prepare(
        "SELECT id, merchant_id, name, description, is_active, sort_order
         FROM neighborhub_menus
         WHERE merchant_id = ?
         ORDER BY sort_order ASC, name ASC"
      );
      $stmt->execute([intval($merchantId)]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
      error_log("Menu::getMenusByMerchantId Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Fetch a menu by merchant and exact name.
   */
  public static function getByMerchantAndName($merchantId, $name)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, merchant_id, name, description, is_active, sort_order
         FROM neighborhub_menus
         WHERE merchant_id = ? AND name = ?
         LIMIT 1"
      );
      $stmt->execute([intval($merchantId), trim($name)]);
      return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
      error_log("Menu::getByMerchantAndName Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Fetch all menus for a merchant with their categorized products.
   *
   * Returns an array of menus, each containing nested categories and products.
   *
   * @param int $merchantId
   * @return array
   */
  public static function getMenusWithProductsByMerchantId($merchantId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT
                m.id AS menu_id,
                m.merchant_id,
                m.name AS menu_name,
                m.description AS menu_description,
                m.is_active AS menu_is_active,
                m.sort_order AS menu_sort_order,
                mc.id AS category_id,
                mc.name AS category_name,
                mc.sort_order AS category_sort_order,
                p.id AS product_id,
                p.name AS product_name,
                COALESCE(mi.override_price, p.price) AS product_price,
                p.meta AS product_meta,
                p.merchant_id, 
                p.description, 
                p.tags, 
                p.type,
                (p.is_available AND mi.is_available) AS is_available, 
                p.image_url,
                mi.id AS menu_item_id,
                mi.sort_order AS item_sort_order
             FROM neighborhub_menus m
             INNER JOIN neighborhub_menu_categories mc ON mc.menu_id = m.id
             INNER JOIN neighborhub_menu_items mi ON mi.category_id = mc.id
             INNER JOIN neighborhub_products p ON p.id = mi.product_id
             WHERE m.merchant_id = ?
             ORDER BY m.sort_order ASC, m.name ASC, mc.sort_order ASC, mc.name ASC, mi.sort_order ASC, p.name ASC"
      );
      $stmt->execute([intval($merchantId)]);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

      $menus = [];

      foreach ($rows as $row) {
        $menuId = intval($row['menu_id']);

        // 1. Key menu directly by $menuId
        if (!isset($menus[$menuId])) {
          $menus[$menuId] = [
            'id' => $menuId,
            'merchant_id' => intval($row['merchant_id']),
            'name' => $row['menu_name'],
            'description' => $row['menu_description'],
            'is_active' => intval($row['menu_is_active']),
            'sort_order' => intval($row['menu_sort_order']),
            'categories' => []
          ];
        }

        $categoryId = intval($row['category_id'] ?? 0);

        // 2. Key category directly by $categoryId inside $menus[$menuId]['categories']
        if ($categoryId && !isset($menus[$menuId]['categories'][$categoryId])) {
          $menus[$menuId]['categories'][$categoryId] = [
            'id' => $categoryId,
            'name' => $row['category_name'],
            'sort_order' => intval($row['category_sort_order'] ?? 0),
            'products' => []
          ];
        }

        // Skip row if there is no category or valid product
        if (!$categoryId || empty($row['product_id'])) {
          continue;
        }

        $metaData = [];
        if (!empty($row['product_meta'])) {
          $metaData = is_array($row['product_meta'])
            ? $row['product_meta']
            : json_decode($row['product_meta'], true);

          if (!is_array($metaData)) {
            $metaData = [];
          }
        }

        // 3. Append products directly using key-indexed paths
        $menus[$menuId]['categories'][$categoryId]['products'][] = [
          'id' => intval($row['product_id']),
          'menu_item_id' => intval($row['menu_item_id'] ?? 0),
          'name' => $row['product_name'],
          'description' => $row['description'],
          'image_url' => $row['image_url'],
          'tags' => $row['tags'],
          'price' => floatval($row['product_price'] ?? 0),
          'sku' => $metaData['sku'] ?? '',
          'meta' => $row['product_meta'],
          'is_available' => intval($row['is_available'] ?? 0)
        ];
      }

      return $menus;
    } catch (Exception $e) {
      error_log("Menu::getMenusWithProductsByMerchantId Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Update one or more fields on a menu.
   */
  public static function update($menuId, array $data)
  {
    try {
      if (empty($data)) {
        return false;
      }

      $fields = [];
      $params = [];

      if (array_key_exists('name', $data)) {
        $fields[] = 'name = ?';
        $params[] = trim($data['name']);
      }

      if (array_key_exists('description', $data)) {
        $fields[] = 'description = ?';
        $params[] = $data['description'];
      }

      if (array_key_exists('is_active', $data)) {
        $fields[] = 'is_active = ?';
        $params[] = intval($data['is_active']);
      }

      if (array_key_exists('sort_order', $data)) {
        $fields[] = 'sort_order = ?';
        $params[] = intval($data['sort_order']);
      }

      if (empty($fields)) {
        return false;
      }

      $db = self::getDb();
      $params[] = intval($menuId);
      $stmt = $db->prepare(
        "UPDATE neighborhub_menus
         SET " . implode(', ', $fields) . "
         WHERE id = ?"
      );

      return $stmt->execute($params);
    } catch (Exception $e) {
      error_log("Menu::update Error: " . $e->getMessage());
      return false;
    }
  }

  public static function updateStatus($menuId, $isActive)
  {
    return self::update(intval($menuId), ['status' => intval($isActive)]);
  }

  /**
   * Fetch all products for a menu grouped by category name.
   * Structure: ['Category Name' => [[product_data], ...]]
   * 
   * @param int $menuId
   * @param bool $activeOnly Optional filter for active items only
   * @return array
   */
  public static function getProductsGroupedByCategory($menuId, $activeOnly = false)
  {
    try {
      $db = self::getDb();

      $query = "SELECT 
            mc.id AS category_id,
            mc.name AS category_name,
            mc.sort_order AS cat_sort,
            p.id,
            p.name,
            p.description,
            p.image_url,
            p.meta,
            COALESCE(mi.override_price, p.price) AS price,
            p.is_available AND mi.is_available AS is_available,
            p.type,
            p.meta,
            mi.id AS menu_item_id,
            mi.sort_order AS item_sort_order
          FROM neighborhub_menu_categories mc
          LEFT JOIN neighborhub_menu_items mi ON mc.id = mi.category_id
          LEFT JOIN neighborhub_products p ON mi.product_id = p.id
          WHERE mc.menu_id = ?";

      $params = [intval($menuId)];

      if ($activeOnly) {
        $query .= " AND mi.is_available = 1 AND p.is_available = 1";
      }

      $query .= " ORDER BY mc.sort_order ASC, mc.name ASC, mi.sort_order ASC, p.name ASC";

      $stmt = $db->prepare($query);
      $stmt->execute($params);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

      // Group products into array keyed by category name
      $grouped = [];
      foreach ($rows as $row) {
        $categoryName = $row['category_name'];

        // Extract sku from json meta if present
        $sku = '';
        if (!empty($row['meta'])) {
          $metaData = is_array($row['meta']) ? $row['meta'] : json_decode($row['meta'], true);
          $sku = $metaData['sku'] ?? '';
        }

        $grouped[$row['category_id']][] = [
          'id'            => intval($row['id']),
          'category_id'   => intval($row['category_id']),
          'category_name' => $row['category_name'],
          'menu_item_id'  => intval($row['menu_item_id']),
          'name'          => $row['name'],
          'description'   => $row['description'],
          'image_url'     => $row['image_url'],
          'price'         => floatval($row['price']),
          'sku'           => $sku,
          'is_available'  => intval($row['is_available']),
          'sort_order'    => intval($row['item_sort_order']),
          'meta'          => $row['meta'],
        ];
      }

      return $grouped;
    } catch (Exception $e) {
      error_log("Menu::getProductsGroupedByCategory Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Delete a menu and any linked categories/items via cascade constraints.
   */
  public static function delete($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("DELETE FROM neighborhub_menus WHERE id = ?");
      return $stmt->execute([intval($menuId)]);
    } catch (Exception $e) {
      error_log("Menu::delete Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Toggle active state.
   */
  public static function setActive($menuId, $isActive)
  {
    return self::update(intval($menuId), ['is_active' => intval($isActive)]);
  }

  /**
   * Update the sort order.
   */
  public static function setSortOrder($menuId, $sortOrder)
  {
    return self::update(intval($menuId), ['sort_order' => intval($sortOrder)]);
  }

  /**
   * Check if a menu exists.
   */
  public static function exists($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("SELECT 1 FROM neighborhub_menus WHERE id = ? LIMIT 1");
      $stmt->execute([intval($menuId)]);
      return (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("Menu::exists Error: " . $e->getMessage());
      return false;
    }
  }
}
