<?php

/**
 * 🛢️ STORAGE BASE MODEL
 * 
 * The foundational abstract model for all database entities.
 * Provides common CRUD operations, JSON handling, and soft delete support.
 * 
 * Child classes should override:
 * - getTableName() - The database table to use
 * - getStatusValues() - The allowed status states for this model
 */
abstract class Storage
{
  // 🗂️ STANDARD PROPERTIES - All entities have these
  public $id;
  public $uuid;
  public $architect_id;
  public $content_type;
  public $content;
  public $created_at;
  public $status;

  /**
   * 🏗️ CONSTRUCTOR: Hydrate an object from DB data
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
   * 📋 CONFIGURATION: Child classes override these
   */
  protected static function getTableName()
  {
    return 'memory_anchors';
  }

  public function data($data = [])
  {
    if (empty($data)) {
      return [
        'id' => $this->id,
        'uuid' => $this->uuid,
        'architect_id' => $this->architect_id,
        'content_type' => $this->content_type,
        'content' => $this->content,
        'created_at' => $this->created_at,
        'status' => $this->status,
      ];
    } else {
      $this->id = $data['id'] ?? null;
      $this->uuid = $data['uuid'] ?? null;
      $this->architect_id = $data['architect_id'] ?? null;
      $this->content_type = $data['content_type'] ?? null;
      $this->content = $data['content'] ?? null;
      $this->created_at = $data['created_at'] ?? null;
      $this->status = $data['status'] ?? null;
    }
  }

  /**
   * The Master Definition of allowed content types.
   * This powers the DB Schema AND the UI Dropdowns.
   */
  public static function getAllowedTypes()
  {
    // Maybe this could be collected from all of the app_hook_info()
    return [
      'default'           => 'Default',
      'pure_heart'        => 'Pure Heart (Memory)',
      'ancestry'          => 'Legacy (Family)',
      'story'             => 'Oral History (Interview)',
      'goober_watch'      => 'Goober Watch (Observations)',
      'heirloom'          => 'Physical Artifact (Heirloom)',
      'land_mark'         => 'Echo of the Earth (Landmark)',
      'weather_event'     => 'Atmospheric Event (Weather)',
      'collection'        => 'Collection Folder',
      'observation'       => 'Field Report (Standard)',
      'research_report'   => 'AI Research Report',
      'sovereign_truth'   => 'Sovereign Truth',
      'historical_snapshot' => 'Historical Snapshot',
      'epoch_marker'      => 'Time Capsule / Epoch',
      'token_credit'      => 'Sovereign Token (Credit)',
      'token_debit'       => 'Sovereign Token (Debit)',
      'daily_provision'   => 'Daily Town Provision',
      'ledger_transaction' => 'Ledger Transaction',
      'ledger_account'    => 'Ledger Account',
      'ledger_entry'      => 'Ledger Entry',
      'ledger_bill'       => 'Ledger Bill',
      'ledger_vendor'     => 'Ledger Vendor',
      'ledger_customer'   => 'Ledger Customer',
      'ledger_category'   => 'Ledger Category',
      'ledger_payment'    => 'Ledger Payment',
      'ledger_recurring'  => 'Ledger Recurring',
      'ledger_summary'    => 'Ledger Summary',
    ];
  }

  /**
   * 🎯 STATUS CONFIGURATION
   * Returns array like: ['active' => 'Active', 'archived' => 'Archived']
   * Child classes can override to define their own status values
   */
  protected static function getStatusValues()
  {
    return [
      'active'   => 'Active',
      'archived' => 'Archived',
    ];
  }

  protected static function getDefaultStatus()
  {
    return 'active';
  }

  /**
   * 🗄️ GET DATABASE INSTANCE
   * Tries to use App singleton first, falls back to parameter
   */
  protected static function getDb()
  {
    // Try to get from App singleton
    try {
      $app = App::getInstance();
      return $app->db;
    } catch (Exception $e) {
      throw new Exception("Database not provided and App::getInstance() failed: " . $e->getMessage());
    }
  }

  /**
   * 🆔 UUID GENERATION: Create a new UUID
   */
  public static function generateUuid()
  {
    return bin2hex(random_bytes(16));
  }

  /**
   * 📝 JSON ENCODING: Safely encode content
   */
  protected static function encodeContent($data)
  {
    if (is_array($data) || is_object($data)) {
      return json_encode($data);
    }
    return $data;
  }

  /**
   * 🧠 JSON DECODING: Get content as object/array
   */
  public function getContent($asArray = false)
  {
    if (is_null($this->content)) {
      return $asArray ? [] : (object)[];
    }
    if (is_array($this->content) || is_object($this->content)) {
      return $this->content;
    }

    return json_decode($this->content, $asArray) ?? ($asArray ? [] : (object)[]);
  }

  /**
   * ✨ THE CREATION ENGINE
   * Insert a new record into the database
   */
  public static function create($data)
  {
    $db = self::getDb();
    $uuid = self::generateUuid();
    $table = static::getTableName();
    $defaultStatus = static::getDefaultStatus();

    // Encode content if it's an array
    $content = isset($data['content']) ? self::encodeContent($data['content']) : null;

    $stmt = $db->prepare("
      INSERT INTO {$table} 
      (uuid, architect_id, content_type, content, status, created_at) 
      VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $success = $stmt->execute([
      $uuid,
      $data['architect_id'] ?? null,
      $data['content_type'] ?? null,
      $content,
      $defaultStatus
    ]);

    return $success ? $uuid : false;
  }

  /**
   * 🔍 GET BY ID
   * Retrieve a single record by primary key
   */
  public static function getById($id)
  {
    $db = self::getDb();
    $table = static::getTableName();

    $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? new static($row) : null;
  }

  /**
   * 🔍 GET BY UUID
   * Retrieve a single record by UUID
   */
  public static function getByUuid($uuid)
  {
    $db = self::getDb();
    $table = static::getTableName();

    $stmt = $db->prepare("SELECT * FROM {$table} WHERE uuid = ?");
    $stmt->execute([$uuid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? new static($row) : null;
  }

  /**
   * 🛠️ UPDATE BY ID
   * Update a record by primary key
   */
  public static function update($id, $data)
  {
    $db = self::getDb();
    $table = static::getTableName();
    $fields = [];
    $values = [];

    foreach ($data as $key => $val) {
      $fields[] = "{$key} = ?";
      $values[] = ($key === 'content') ? self::encodeContent($val) : $val;
    }

    if (empty($fields)) {
      return false;
    }

    $values[] = $id;
    $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
  }

  /**
   * 🛠️ UPDATE BY UUID
   * Update a record by UUID with support for JSON_MERGE_PATCH
   */
  public static function updateByUuid($uuid, $data)
  {
    $db = self::getDb();
    $table = static::getTableName();
    $fields = [];
    $values = [];

    foreach ($data as $key => $val) {
      if ($key === 'content' && (is_array($val) || is_object($val))) {
        // We bind the encoded JSON string directly for MySQL JSON columns
        $fields[] = "content = ?";
        $values[] = json_encode($val);
      } else {
        $fields[] = "{$key} = ?";
        $values[] = ($key === 'content') ? self::encodeContent($val) : $val;
      }
    }

    if (empty($fields)) {
      return false;
    }

    $values[] = $uuid;
    $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE uuid = ?";
    $stmt = $db->prepare($sql);
    return $stmt->execute($values);
  }

  /**
   * 🗑️ SOFT DELETE (Default)
   * Archive a record by marking status as 'archived' (safer default)
   */
  public static function delete($id)
  {
    $db = self::getDb();
    return self::update($id, ['status' => 'archived']);
  }

  /**
   * 🗑️ SOFT DELETE BY UUID
   * Archive a record by UUID
   */
  public static function deleteByUuid($uuid)
  {
    $db = self::getDb();
    return self::updateByUuid($uuid, ['status' => 'archived']);
  }

  /**
   * 💥 HARD DELETE (Permanent)
   * Permanently remove a record from the database (cannot be undone)
   */
  public static function hardDelete($id)
  {
    $db = self::getDb();
    $table = static::getTableName();

    $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
    return $stmt->execute([$id]);
  }

  /**
   * 💥 HARD DELETE BY UUID (Permanent)
   * Permanently remove a record by UUID (cannot be undone)
   */
  public static function hardDeleteByUuid($uuid)
  {
    $db = self::getDb();
    $table = static::getTableName();

    $stmt = $db->prepare("DELETE FROM {$table} WHERE uuid = ?");
    return $stmt->execute([$uuid]);
  }

  /**
   * ♻️ RESTORE
   * Un-archive a record
   */
  public function restore()
  {
    $db = self::getDb();
    $defaultStatus = static::getDefaultStatus();
    return self::update($this->id, ['status' => $defaultStatus], $db);
  }

  /**
   * 🔍 FLEXIBLE QUERY
   * Base query builder for custom queries
   * 
   * Usage:
   *   $results = ChildModel::query([
   *     'where'    => 'status = ? AND architect_id = ?',
   *     'binds'    => ['active', 123],
   *     'order_by' => 'created_at DESC',
   *     'limit'    => 50
   *   ]);
   */
  public static function query($params = [])
  {
    $db = self::getDb();
    $table = static::getTableName();

    $config = array_merge([
      'select'   => '*',
      'where'    => '1=1',
      'order_by' => 'created_at DESC',
      'limit'    => 20,
      'offset'   => 0,
      'binds'    => []
    ], $params);

    $sql = "SELECT {$config['select']} FROM {$table}";
    $sql .= " WHERE {$config['where']}";
    if (!empty($config['order_by'])) {
      $sql .= " ORDER BY {$config['order_by']}";
    }
    if ($config['limit'] > 0) {
      $sql .= " LIMIT " . (int)$config['limit'];
      if ($config['offset'] > 0) {
        $sql .= " OFFSET " . (int)$config['offset'];
      }
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($config['binds']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return array_map(fn($row) => new static($row), $rows);
  }

  /**
   * 🔢 COUNT RECORDS
   * Get the count of records matching criteria
   */
  public static function count($where = '1=1', $binds = [])
  {
    $db = self::getDb();
    $table = static::getTableName();

    $stmt = $db->prepare("SELECT COUNT(*) as total FROM {$table} WHERE {$where}");
    $stmt->execute($binds);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row['total'] ?? 0;
  }

  /**
   * 📋 GET ALL ACTIVE RECORDS
   * Retrieve all non-archived records
   */
  public static function getActive($limit = 0)
  {
    return self::query([
      'where'  => 'status = ?',
      'binds'  => ['active'],
      'limit'  => $limit
    ]);
  }

  /**
   * 📋 GET ALL ARCHIVED RECORDS
   * Retrieve all archived records
   */
  public static function getArchived($limit = 0)
  {
    return self::query([
      'where'  => 'status = ?',
      'binds'  => ['archived'],
      'limit'  => $limit
    ]);
  }

  /**
   * 🔄 BATCH CREATE
   * Insert multiple records efficiently
   */
  public static function batchCreate($records)
  {
    $db = self::getDb();
    $table = static::getTableName();
    $defaultStatus = static::getDefaultStatus();
    $inserted = [];

    $db->beginTransaction();
    try {
      $stmt = $db->prepare("
        INSERT INTO {$table} 
        (uuid, architect_id, content_type, content, status, created_at) 
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
      ");

      foreach ($records as $data) {
        $uuid = self::generateUuid();
        $content = isset($data['content']) ? self::encodeContent($data['content']) : null;

        $stmt->execute([
          $uuid,
          $data['architect_id'] ?? null,
          $data['content_type'] ?? null,
          $content,
          $defaultStatus
        ]);

        $inserted[] = $uuid;
      }

      $db->commit();
      return $inserted;
    } catch (Exception $e) {
      $db->rollBack();
      error_log("Batch create error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * 🔄 BATCH UPDATE
   * Update multiple records by ID
   */
  public static function batchUpdate($updates)
  {
    $db = self::getDb();
    $table = static::getTableName();
    $results = [];

    $db->beginTransaction();
    try {
      foreach ($updates as $id => $data) {
        $success = self::update($id, $data, $db);
        $results[$id] = $success;
      }
      $db->commit();
      return $results;
    } catch (Exception $e) {
      $db->rollBack();
      error_log("Batch update error: " . $e->getMessage());
      return [];
    }
  }

  /**
   * 🔄 BATCH DELETE (Soft)
   * Archive multiple records by ID (default safe delete)
   */
  public static function batchDelete($ids)
  {
    $db = self::getDb();
    $table = static::getTableName();

    if (empty($ids)) {
      return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("UPDATE {$table} SET status = 'archived' WHERE id IN ({$placeholders})");

    return $stmt->execute($ids) ? count($ids) : 0;
  }

  /**
   * 🔄 BATCH HARD DELETE (Permanent)
   * Permanently delete multiple records by ID (cannot be undone)
   */
  public static function batchHardDelete($ids)
  {
    $db = self::getDb();
    $table = static::getTableName();

    if (empty($ids)) {
      return 0;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("DELETE FROM {$table} WHERE id IN ({$placeholders})");

    return $stmt->execute($ids) ? count($ids) : 0;
  }

  /**
   * 📍 TIMESTAMP HELPERS
   */
  public function getYear()
  {
    return date('Y', strtotime($this->created_at));
  }

  public function getFormattedDate($format = 'M j, Y')
  {
    if (!$this->created_at) {
      return "Unknown";
    }
    return date($format, strtotime($this->created_at));
  }

  /**
   * ✅ VALIDATION: Check if record is active
   */
  public function isActive()
  {
    return $this->status === 'active';
  }

  /**
   * ✅ VALIDATION: Check if record is archived
   */
  public function isArchived()
  {
    return $this->status === 'archived';
  }

  /**
   * 🧪 DEBUG: Convert to array for inspection
   */
  public function toArray()
  {
    $reflector = new ReflectionClass($this);
    $properties = $reflector->getProperties(ReflectionProperty::IS_PUBLIC);

    $array = [];
    foreach ($properties as $property) {
      $array[$property->getName()] = $property->getValue($this);
    }
    return $array;
  }
}
