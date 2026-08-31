<?php
if (!defined('MB_RUNNING')) exit;

class MenuCategory
{
  public $id;
  public $menu_id;
  public $name;
  public $sort_order;
  public $created_at;

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
   * Create a new category under a menu.
   */
  public static function create($menuId, $name, $sortOrder = 0)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "INSERT INTO neighborhub_menu_categories (menu_id, name, sort_order)
         VALUES (?, ?, ?)"
      );

      if (!$stmt->execute([intval($menuId), trim($name), intval($sortOrder)])) {
        return false;
      }

      return (int) $db->lastInsertId();
    } catch (Exception $e) {
      error_log("MenuCategory::create Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetch a single category by ID.
   */
  public static function getById($categoryId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, menu_id, name, sort_order, created_at
         FROM neighborhub_menu_categories
         WHERE id = ?
         LIMIT 1"
      );
      $stmt->execute([intval($categoryId)]);
      return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
      error_log("MenuCategory::getById Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Fetch all categories belonging to a menu.
   */
  public static function getByMenuId($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, menu_id, name, sort_order, created_at
         FROM neighborhub_menu_categories
         WHERE menu_id = ?
         ORDER BY sort_order ASC, name ASC"
      );
      $stmt->execute([intval($menuId)]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
      error_log("MenuCategory::getByMenuId Error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Fetch a category by menu and exact name.
   */
  public static function getByMenuAndName($menuId, $name)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare(
        "SELECT id, menu_id, name, sort_order, created_at
         FROM neighborhub_menu_categories
         WHERE menu_id = ? AND name = ?
         LIMIT 1"
      );
      $stmt->execute([intval($menuId), trim($name)]);
      return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
      error_log("MenuCategory::getByMenuAndName Error: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Update one or more fields on a category.
   */
  public static function update($categoryId, array $data)
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

      if (array_key_exists('sort_order', $data)) {
        $fields[] = 'sort_order = ?';
        $params[] = intval($data['sort_order']);
      }

      if (array_key_exists('status', $data)) {
        $fields[] = 'status = ?';
        $params[] = trim($data['status']);
      }

      if (empty($fields)) {
        return false;
      }

      $db = self::getDb();
      $params[] = intval($categoryId);
      $stmt = $db->prepare(
        "UPDATE neighborhub_menu_categories
         SET " . implode(', ', $fields) . "
         WHERE id = ?"
      );

      return $stmt->execute($params);
    } catch (Exception $e) {
      error_log("MenuCategory::update Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Reorder multiple categories in batch.
   * @param array $orderArray Array of ['id' => int, 'sort_order' => int]
   * @return bool
   */
  public static function updateSortOrders(array $orderArray): bool
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("UPDATE neighborhub_menu_categories SET sort_order = ? WHERE id = ?");

      foreach ($orderArray as $item) {
        $stmt->execute([intval($item['sort_order']), intval($item['id'])]);
      }
      return true;
    } catch (Exception $e) {
      error_log("MenuCategory::updateSortOrders Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Update menu status for a given category (used by drag-and-drop reordering).
   * @param int $categoryId The ID of the category to update.
   * @param string $status The new status ('active' or 'inactive').
   * @return bool True on success, false on failure.
   */
  public static function updateStatus(int $categoryId, string $status): bool
  {
    if (!in_array($status, ['active', 'inactive'])) {
      return false; // Invalid status
    }

    try {
      $db = self::getDb();
      $stmt = $db->prepare("UPDATE neighborhub_menu_categories SET status = ? WHERE id = ?");
      return $stmt->execute([$status, intval($categoryId)]);
    } catch (Exception $e) {
      error_log("MenuCategory::updateStatus Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Delete a category and its menu item links.
   */
  public static function delete($categoryId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("DELETE FROM neighborhub_menu_categories WHERE id = ?");
      return $stmt->execute([intval($categoryId)]);
    } catch (Exception $e) {
      error_log("MenuCategory::delete Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Rename an existing category (used by rename_menu endpoint).
   */
  public static function rename($categoryId, $newName)
  {
    return self::update(intval($categoryId), ['name' => trim($newName)]);
  }

  /**
   * Update the sort position for a category.
   */
  public static function setSortOrder($categoryId, $sortOrder)
  {
    return self::update(intval($categoryId), ['sort_order' => intval($sortOrder)]);
  }

  /**
   * Update menu item sort order for a given category (used by drag-and-drop reordering).
   * @param int $categoryId The ID of the category to update.
   * @param array $itemOrder Array of ['id' => int, 'sort_order' => int] representing the new order of items.
   * @return bool True on success, false on failure.  
   * 
   */
  public static function updateMenuItemOrder($categoryId, array $itemOrder): bool
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("UPDATE neighborhub_menu_items SET sort_order = ? WHERE id = ? AND category_id = ?");

      foreach ($itemOrder as $item) {
        $stmt->execute([intval($item['sort_order']), intval($item['menu_item_id']), intval($categoryId)]);
      }
      return true;
    } catch (Exception $e) {
      error_log("MenuCategory::updateMenuItemOrder Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Check whether a category exists.
   */
  public static function exists($categoryId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("SELECT 1 FROM neighborhub_menu_categories WHERE id = ? LIMIT 1");
      $stmt->execute([intval($categoryId)]);
      return (bool) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("MenuCategory::exists Error: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Count categories for a menu.
   */
  public static function countByMenuId($menuId)
  {
    try {
      $db = self::getDb();
      $stmt = $db->prepare("SELECT COUNT(*) FROM neighborhub_menu_categories WHERE menu_id = ?");
      $stmt->execute([intval($menuId)]);
      return (int) $stmt->fetchColumn();
    } catch (Exception $e) {
      error_log("MenuCategory::countByMenuId Error: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Get all categories for a merchant (moved out of Product class).
   */
  public static function getCategoriesByMerchant($merchantId, $menuName = null)
  {
    try {
      $db = self::getDb();

      $query = "SELECT mc.id, mc.menu_id, mc.name, mc.sort_order
                FROM neighborhub_menu_categories mc
                INNER JOIN neighborhub_menus m ON mc.menu_id = m.id
                WHERE m.merchant_id = ?";
      $params = [intval($merchantId)];

      if (!empty($menuName)) {
        $query .= " AND m.name = ?";
        $params[] = trim($menuName);
      }

      $query .= " ORDER BY mc.sort_order ASC, mc.name ASC";

      $stmt = $db->prepare($query);
      $stmt->execute($params);
      return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
      error_log("MenuCategory::getCategoriesByMerchant Error: " . $e->getMessage());
      return [];
    }
  }
}
