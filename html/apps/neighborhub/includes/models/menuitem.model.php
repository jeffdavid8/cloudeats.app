<?php
if (!defined('MB_RUNNING')) exit;

class MenuItem
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: App::getInstance()->db;
    }

    private function getDb()
    {
        return $this->db;
    }

    /**
     * Add a product to a menu category with an optional override price.
     */
    public function addProductToCategory(int $categoryId, int $productId, ?float $overridePrice = null, int $sortOrder = 0): bool
    {
        $stmt = $this->getDb()->prepare("
            INSERT INTO neighborhub_menu_items (category_id, product_id, override_price, sort_order)
            VALUES (:category_id, :product_id, :override_price, :sort_order)
            ON DUPLICATE KEY UPDATE
                override_price = VALUES(override_price),
                sort_order = VALUES(sort_order)
        ");

        return $stmt->execute([
            ':category_id'    => $categoryId,
            ':product_id'     => $productId,
            ':override_price' => $overridePrice,
            ':sort_order'     => $sortOrder
        ]);
    }

    /**
     * Create a new menu item link directly.
     */
    public function create(int $categoryId, int $productId, ?float $overridePrice = null, int $sortOrder = 0, int $isAvailable = 1): bool
    {
        $stmt = $this->getDb()->prepare(
            "INSERT INTO neighborhub_menu_items (category_id, product_id, override_price, sort_order, is_available)
             VALUES (?, ?, ?, ?, ?)"
        );

        return $stmt->execute([$categoryId, $productId, $overridePrice, $sortOrder, $isAvailable]);
    }

    /**
     * Retrieve a menu item entry by ID.
     */
    public function getById(int $menuItemId)
    {
        $stmt = $this->getDb()->prepare(
            "SELECT id, category_id, product_id, override_price, is_available, sort_order, created_at, updated_at
             FROM neighborhub_menu_items
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$menuItemId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Retrieve all menu item entries for a category.
     */
    public function getByCategoryId(int $categoryId): array
    {
        $stmt = $this->getDb()->prepare(
            "SELECT id, category_id, product_id, override_price, is_available, sort_order, created_at, updated_at
             FROM neighborhub_menu_items
             WHERE category_id = ?
             ORDER BY sort_order ASC, id ASC"
        );
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update override price for a specific item entry.
     */
    public function setOverridePrice(int $menuItemId, ?float $overridePrice): bool
    {
        $stmt = $this->getDb()->prepare(
            "UPDATE neighborhub_menu_items
             SET override_price = :override_price, updated_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([
            ':override_price' => $overridePrice,
            ':id'             => $menuItemId
        ]);
    }

    /**
     * Toggle item availability.
     */
    public function setAvailability(int $menuItemId, bool $isAvailable): bool
    {
        $stmt = $this->getDb()->prepare(
            "UPDATE neighborhub_menu_items
             SET is_available = :is_available, updated_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([
            ':is_available' => $isAvailable ? 1 : 0,
            ':id'           => $menuItemId
        ]);
    }

    /**
     * Update sort order.
     */
    public function setSortOrder(int $menuItemId, int $sortOrder): bool
    {
        $stmt = $this->getDb()->prepare(
            "UPDATE neighborhub_menu_items
             SET sort_order = :sort_order, updated_at = NOW()
             WHERE id = :id"
        );

        return $stmt->execute([
            ':sort_order' => $sortOrder,
            ':id'         => $menuItemId
        ]);
    }

    /**
     * Update multiple fields.
     */
    public function update(int $menuItemId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $params = [];

        if (array_key_exists('category_id', $data)) {
            $fields[] = 'category_id = ?';
            $params[] = intval($data['category_id']);
        }

        if (array_key_exists('product_id', $data)) {
            $fields[] = 'product_id = ?';
            $params[] = intval($data['product_id']);
        }

        if (array_key_exists('override_price', $data)) {
            $fields[] = 'override_price = ?';
            $params[] = $data['override_price'];
        }

        if (array_key_exists('is_available', $data)) {
            $fields[] = 'is_available = ?';
            $params[] = intval($data['is_available']);
        }

        if (array_key_exists('sort_order', $data)) {
            $fields[] = 'sort_order = ?';
            $params[] = intval($data['sort_order']);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $menuItemId;
        $stmt = $this->getDb()->prepare(
            "UPDATE neighborhub_menu_items
             SET " . implode(', ', $fields) . ", updated_at = NOW()
             WHERE id = ?"
        );

        return $stmt->execute($params);
    }

    /**
     * Remove an item from a category.
     */
    public function remove(int $menuItemId): bool
    {
        $stmt = $this->getDb()->prepare("DELETE FROM neighborhub_menu_items WHERE id = ?");
        return $stmt->execute([$menuItemId]);
    }
}