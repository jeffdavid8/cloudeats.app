<?php

class Stitch extends Storage
{
  // 🛰️ ADDITIONAL PROPERTIES
  public $lat;
  public $lng;
  public $projected_to; // 🎯 The 2026 Observer Perspective

  // 🛰️ RELATIONSHIPS
  public $vouch_count = 0;
  public $nexus_list = [];

  public function __construct($data = [])
  {
    parent::__construct($data);

    // 🧶 Process the complex JSON nexus data into objects
    if (!empty($data['nexus_json'])) {
      $decoded = json_decode($data['nexus_json'], true);
      if (is_array($decoded)) {
        $this->nexus_list = array_map(fn($n) => new Nexus($n), $decoded);
      }
    }
    // Fallback for the simple GROUP_CONCAT string if necessary
    elseif (isset($data['nexus_list']) && is_string($data['nexus_list'])) {
      $this->nexus_list = explode(',', $data['nexus_list']);
    }

    // Auto JSON Decode $data['content']
    if (is_string($data['content'])) {
      $this->content = json_decode($data['content'], true);
    } else {
      $this->content = $data['content'];
    }
  }

  /**
   * 📋 CONFIGURATION: Database table name
   */
  protected static function getTableName()
  {
    return 'memory_anchors';
  }

  /**
   * 🧵 THE CHRONOS DESCENT QUERY
   * Logic: Order by the simulated historical date (Now -> Past)
   */
  public static function query($params = [])
  {
    $db = self::getDb();

    // 🎯 Defaults: We want 'epoch_marker' by default on the home page
    $config = array_merge([
      'where'    => "1=1",
      'group_by' => 'a.id',
      'order_by' => 'a.projected_to DESC', // 🛰️ Sort by Discovery Now!
      'limit'    => 20,
      'binds'    => []
    ], $params);

    $sql = "SELECT 
        a.id, 
        a.architect_id,
        a.content_type, 
        a.content, 
        a.lat, 
        a.lng, 
        a.projected_to,
        a.created_at, 
        a.status,
        COUNT(DISTINCT v.id) as vouch_count,
        (
          SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
              'nexus_id', n.id,
              'target_content', n.content,
              'target_type', n.content_type,
              'target_year', n.created_at,
              'lat', n.lat, -- 📍 Add this!
              'lng', n.lng, -- 📍 Add this!
              'nexus_label', sn.nexus_label 
            )
          )
          FROM stitch_nexus sn
          JOIN memory_anchors n ON sn.nexus_id = n.id
          WHERE sn.stitch_id = a.id
        ) as nexus_json
        FROM memory_anchors a 
        LEFT JOIN vouches v ON a.id = v.anchor_id 
        WHERE {$config['where']} 
        GROUP BY {$config['group_by']} 
        ORDER BY {$config['order_by']} 
        LIMIT " . (int)$config['limit'];

    //error_log("DEBUG: SQL: $sql");
    //error_log('PARAMS: ' . print_r($config, true));

    // 🛡️ Prepared Execution: Handles the quotes for us!
    $stmt = $db->prepare($sql);
    $stmt->execute($config['binds']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //error_log('Results: ' . print_r($rows, true));

    return array_map(fn($row) => new self($row), $rows);
  }

  public static function getById($id)
  {
    $db = self::getDb();

    $stmt = $db->prepare("SELECT 
        a.id, 
        a.architect_id,
        a.content_type, 
        a.content, 
        a.projected_to,
        a.created_at, 
        a.status,
        COUNT(DISTINCT v.id) as vouch_count,
        (
          SELECT JSON_ARRAYAGG(
            JSON_OBJECT(
              'id', n.id,
              'content', n.content,
              'content_type', n.content_type,
              'created_at', n.created_at
            )
          )
          FROM stitch_nexus sn
          JOIN memory_anchors n ON sn.nexus_id = n.id
          WHERE sn.stitch_id = a.id
        ) as nexus_json
        FROM memory_anchors a 
        LEFT JOIN vouches v ON a.id = v.anchor_id 
        WHERE a.id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return new self($row);
  }

  public static function render_card($anchor, $attributes = [], $return = false)
  {
    $content_type = $anchor->content_type;
    $attributes = array_merge($attributes, [
      'anchor' => $anchor,
    ]);
    if (file_exists('./apps/stitch/views/components/cards/' . $content_type . '.card.php')) {
      return render('components/cards/' . $content_type . '.card.php', $attributes, $return);
    } else {
      return render('components/cards/default.card.php', $attributes, $return);
    }
  }

  /**
   * ✨ THE CREATION ENGINE
   * Anchors a new memory into the permanent ledger
   */
  public static function create($data)
  {
    $data['status'] = 'active';
    $data['content'] = is_array($data['content'] ?? null) ? json_encode($data['content']) : ($data['content'] ?? null);

    $db = self::getDb();
    $uuid = $data['uuid'] ?? self::generateUuid();

    $stmt = $db->prepare("
        INSERT INTO memory_anchors 
        (uuid, architect_id, content_type, content, lat, lng, projected_to, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");

    $success = $stmt->execute([
      $uuid,
      $data['architect_id'] ?? null,
      $data['content_type'] ?? null,
      $data['content'],
      $data['lat'] ?? null,
      $data['lng'] ?? null,
      $data['projected_to'] ?? null,
      'active'
    ]);

    return $success ? $uuid : false;
  }






  public static function format_urls_with_ellipsis($text='')
  {
    // Regex pattern to find URLs starting with http:// or https://
    $pattern = '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/|\#|\!))#';

    // The HTML replacement template
    // $0 represents the full URL found by the regex
    $replacement = '<a href="$0" title="$0" class="ellipsis-url" target="_blank" rel="noopener">$0</a>';

    // Execute the search and replace
    return preg_replace($pattern, $replacement, $text);
  }

  // 🌍 STITCH-SPECIFIC: Help the community understand "Approximate" time
  public function getFormattedDate($format = 'M j, Y')
  {
    if (!$this->projected_to) {
      return "Unknown Era";
    }
    return date($format, strtotime($this->projected_to));
  }

  public function getEra()
  {
    if (!$this->projected_to) return "Undated Era";
    $date = strtotime($this->projected_to);
    return date('Y', $date); // Or 'F Y' for Month/Year
  }

  /**
   * 👍 THE VOUCH: Validate an anchor
   */
  public function vouch($userId, $db = null)
  {
    $db = self::getDb($db);
    $stmt = $db->prepare("INSERT IGNORE INTO vouches (anchor_id, user_id) VALUES (?, ?)");
    return $stmt->execute([$this->id, $userId]);
  }
}
