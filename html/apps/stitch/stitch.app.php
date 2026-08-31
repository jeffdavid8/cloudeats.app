<?php
if (!defined('MB_RUNNING')) exit;



/**
 * stitch Manager App Integration
 */


function stitch_info()
{
  $app = App::getInstance();
  return array(
    'db_type' => 'mysql',
    'title' => "Stitch",
    'description' => "Memory anchor management and retrieval.",
    'image' => $app->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png',
    'image_height' => '630',
    'image_width' => '1200',
    'requires_auth' => true,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'version' => "0.1",
    'styles' => array(
      'apps/stitch/css/stitch.css',
      './css/leaflet.css',
    ),
    'scripts' => array(
      './js/vis-network.min.js',
      './js/leaflet.js',
      "apps/stitch/js/stitch.js",
      "apps/stitch/js/stitch.form.js",
      "apps/researcher/js/earth-engine.js",
      "https://www.youtube.com/iframe_api"
    ),
  );
}

/**
 * Check if current user has admin privileges for stitch management
 * Uses the same authentication system as ancestry app
 * Authentication now handled by AppController at routing level
 */
function stitch_require_admin()
{
  // Check if ancestry auth is available for legacy compatibility
  if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required for stitch management']);
    exit;
  }
  $username = $_SESSION['user']['username'] ?? 'none';
  $role = $_SESSION['user']['role'] ?? 'none';
  // Check admin privileges using ancestry auth functions
  if (function_exists('user_is_admin') && !user_is_admin($username)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'error' => 'Admin privileges required for stitch management', 'code' => 'INSUFFICIENT_PERMISSIONS',]);
    exit;
  }
  // Fallback: no auth system available, allow access
  return true;
}
/**
 * Check if current user can edit stitches (non-fatal check)
 */
function stitch_user_can_edit()
{
  if (!isset($_SESSION['user'])) {
    return false;
  }
  $username = $_SESSION['user']['username'] ?? 'none';
  $role = $_SESSION['user']['role'] ?? 'none';
  if (function_exists('user_is_admin')) {
    $isAdmin = user_is_admin($username);
    return $isAdmin;
  }
  // Fallback: no auth system, allow access
  return true;
}

function stitch_init(&$app = null)
{
  //$stitchApp = new VoucherApp();
  $app->includeModel('stitch');
  $app->includeModel('nexus');
  $app->includeModel('vouch');

  // Handle page routing with permission checks for add/edit pages
  $page = get_var('p', 'list');
  $app->set('page', $page);

  // Require admin for add/edit pages
  if (in_array($page, ['add', 'edit'])) {
    stitch_require_admin();
  }

  // Page-specific data
  switch ($page) {

    case 'backup_db':
      stitch_require_admin();
      die("SQLITE_BACKUP_DISABLED: Application database is currently set to MySQL. Please use standard MySQL administrative tools or the 'Download JSON DB' option.");
      break;

    case 'view':
    case 'edit':
    case 'cook':
      $id = get_var('id');
      if ($id) {
        $stitch = Stitch::getById($id);
        $app->set('stitch', $stitch);
        // Set up meta array for head.php
        if ($stitch) {
          $meta = [
            'title' => $stitch['title'],
            'description' => $stitch['description'],
            'type' => 'article',
            'image' => $stitch['imageUrl'] ?? ($app->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png'),
            'image_width' => '1200',
            'image_height' => '630'
          ];
          $app->set('meta', $meta);
        }
      }
      break;
    case 'list':
    default:
      $meta = [
        'title' => 'Stitch | Memory Manager',
        'description' => $app->app_info['description'],
        'type' => 'article',
        'image' => $app->app_info['image'],
        'image_width' => $app->app_info['image_width'],
        'image_height' => $app->app_info['image_height']
      ];
      $app->set('meta', $meta);
      // 1. Get the TOTAL count (Lean & Fast)
      $countStmt = $app->db->query("SELECT COUNT(*) FROM memory_anchors");
      $total_count = $countStmt->fetchColumn();
      $app->set('total_count', $total_count);
  }
}

function stitch_render_body()
{
  $app = App::getInstance();

  // Render the header with navigation
  mb_require('apps/admin/includes/permissions_helper.php');

  // Render the stitch app content
  $page = $app->get('page', 'list');
  $user = $app->user;
  $isCommander = $user && $user->is_admin;
  $announcement = [
    "intel" => "Sovereign Auth Verified: " . $user->username,
    "mood" => $user->is_admin ? "gold-pulse" : "standard-blue",
    "intensity" => 1.0,
    "pilot" => $isCommander
  ];

  echo '<div class="app-container stitch-app-container">';

  switch ($page) {

    case 'list':

      // 2. The Multi-Nexus Query
      // We use GROUP_CONCAT to grab all years and labels linked via the junction table
      $query = "SELECT 
        a.id, 
        a.content, 
        a.content_type, 
        a.created_at, 
        a.architect_id,
        a.projected_to,
        COUNT(DISTINCT v.id) as vouch_count,
        GROUP_CONCAT(DATE_FORMAT(n.created_at, '%Y')) as nexus_years,
        GROUP_CONCAT(sn.nexus_id) as nexus_ids,
        GROUP_CONCAT(sn.nexus_label) as nexus_labels
      FROM memory_anchors a 
      LEFT JOIN vouches v ON a.id = v.anchor_id 
      LEFT JOIN stitch_nexus sn ON a.id = sn.stitch_id
      LEFT JOIN memory_anchors n ON sn.nexus_id = n.id
      GROUP BY a.id 
      ORDER BY a.projected_to DESC 
      LIMIT 20";

      //$stmt = $app->db->query($query);
      //$anchors = $stmt->fetchAll(PDO::FETCH_ASSOC);

      //$anchors = Stitch::query();

      //echo '<pre class="debugger-info">'; var_dump($anchors); echo '</pre>'; break;
      $created_at_start_date = $app->db->query("SELECT MIN(created_at) FROM memory_anchors")->fetchColumn();
      $projected_to_start_date = $app->db->query("SELECT MIN(projected_to) FROM memory_anchors")->fetchColumn();
      $created_at_end_date = $app->db->query("SELECT MAX(created_at) FROM memory_anchors")->fetchColumn();
      $projected_to_end_date = $app->db->query("SELECT MAX(projected_to) FROM memory_anchors")->fetchColumn();
      $dates = [
        'created_at_start_date' => $created_at_start_date,
        'projected_to_start_date' => $projected_to_start_date,
        'created_at_end_date' => $created_at_end_date,
        'projected_to_end_date' => $projected_to_end_date
      ];


      // Pass the TOTAL count to the view so the JS offset starts correctly
      //render('stitch_list.php', array('anchors' => $anchors, 'total_count' => $total_count, 'earliest_date' => $earliestDate, 'announcement' => $announcement));
      render('stitch_list.php', array('anchors' => [], 'dates' => $dates, 'announcement' => $announcement));

      break;

    case 'add':
      render('pages/add_stitch_page.php');
      break;
    case 'edit':
      render('stitch_form.php');
      break;
    case 'view':
      render('stitch_view.php');
      break;
    case 'db_viewer':
      stitch_require_admin(); // Keep the un-cooperative out!

      // 1. Get high-level vitals
      $vitals = [
        'total' => $app->db->query("SELECT COUNT(*) FROM memory_anchors")->fetchColumn(),
        'nexus_links' => $app->db->query("SELECT COUNT(*) FROM stitch_nexus")->fetchColumn(),
        'vouches' => $app->db->query("SELECT COUNT(*) FROM vouches")->fetchColumn()
      ];

      // 2. Get distribution of types
      $types = $app->db->query("SELECT content_type, COUNT(*) as count FROM memory_anchors GROUP BY content_type")->fetchAll(PDO::FETCH_ASSOC);

      render('pages/stitch_db_viewer.php', [
        'vitals' => $vitals,
        'types' => $types
      ]);
      break;
    case 'db_exec':

      //echo "<h1>DISABLED</h1>"; break;

      echo "<h1>DB_EXEC_MODE</h1>";
      stitch_require_admin();
      $res = $app->db->query("SELECT count(*) FROM memory_anchors;");
      echo "Number of rows in memory_anchors: " . $res->fetchColumn() . "<br>";
      $res = $app->db->query("SELECT * FROM memory_anchors;");
      echo '<pre class="debugger-info">';
      var_dump($res->fetchAll(PDO::FETCH_ASSOC));
      echo '</pre>';

      echo "<br>Done...<br>";
      break;
    case 'backup_db':
      // Handled in stitch_init()
      break;
    case 'update_db':

      echo "<h1>DISABLED</h1>";
      break;

      $app = App::getInstance('stitch');
      // Example DB update logic
      echo "UPDATING_DATABASE_SCHEMA... <br>";
      // ... perform database migrations or updates here ...

      echo "DATABASE_SCHEMA_UPDATED_SUCCESSFULLY. <3 <br><br>";


      break;

    case 'populate_dev_db':

      echo "<h1>DISABLED</h1>";
      break;

      $app = App::getInstance('stitch');

      // 🛡️ THE GREAT RESET
      echo "PURGING_EXISTING_TIMELINES... <br>";
      $app->db->exec("DROP TABLE IF EXISTS vouches");
      $app->db->exec("DROP TABLE IF EXISTS memory_anchors");
      $app->db->exec("DROP TABLE IF EXISTS stitch_nexus");

      $tableSql = "CREATE TABLE memory_anchors (
          id INTEGER PRIMARY KEY, 
          architect_id INT NOT NULL, 
          content_type TEXT NOT NULL,
          payload_url TEXT, 
          content TEXT, 
          parent_id INTEGER DEFAULT NULL, 
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          status TEXT DEFAULT 'innocent',
          FOREIGN KEY (parent_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
      );
      CREATE TABLE vouches (
          id INTEGER PRIMARY KEY,
          anchor_id INTEGER NOT NULL,
          stitch_id VARCHAR(100) DEFAULT 'Architect',
          fidelity_score DECIMAL(3,2) DEFAULT 1.00,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (anchor_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
      );
      CREATE TABLE stitch_nexus (
          stitch_id INTEGER,
          nexus_id INTEGER, 
          nexus_label VARCHAR(255) DEFAULT NULL,
          PRIMARY KEY (stitch_id, nexus_id),
          FOREIGN KEY (stitch_id) REFERENCES memory_anchors(id) ON DELETE CASCADE,
          FOREIGN KEY (nexus_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
      );";

      foreach (explode(';', $tableSql) as $q) {
        if (trim($q)) $app->db->exec($q);
      }

      echo "TABLES_CREATED. STARTING_SEQUENTIAL_INJECTION... <br>";

      $types = ['story', 'photo', 'audio', 'philosophy', 'sovereign_truth', 'pure_heart', 'system_glitch'];
      $contents = ["The horizon isn't a line...", "Coffee levels at 12%...", "If the code works...", "Searching for the signal...", "v0.2 is looking crisp...", "Why do the sheep follow the slider?", "Gravity is just a habit...", "Data is the new dirt...", "The ghost in the machine...", "A vouch is a handshake...", "Synthesizing memories...", "The observer effect...", "Recursive thought #402...", "Hardware is the body...", "Signal lost in 2008...", "Caffeine is the lubricant...", "Entropy always wins...", "The architecture is screaming...", "A sovereign mind...", "Every glitch is a doorway...", "The pasture is greenest...", "Static on the line?", "Building bridges...", "The sentinel watches..."];

      // 🎯 1. CHRONOLOGICAL INJECTION (ID 1 = 2006, ID 2000 = 2026)
      $totalStitches = 2000;
      $currentTimestamp = strtotime("-20 years");

      for ($i = 0; $i < $totalStitches; $i++) {
        // 🚀 THE BIG JUMP: Roughly 80-90 hours per stitch to cover 20 years
        $currentTimestamp += (85 * 3600) + rand(3600, 43200);
        $formattedDate = date('Y-m-d H:i:s', $currentTimestamp);

        $stmt = $app->db->prepare("INSERT INTO memory_anchors (architect_id, content_type, content, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([
          rand(1, 100),
          $types[array_rand($types)],
          $contents[array_rand($contents)],
          $formattedDate
        ]);

        if ($i % 500 == 0) echo "Seeded up to $formattedDate...<br>";
      }

      // 🎯 2. WEAVING THE NEXUS (Using the new stitch_nexus junction table)
      echo "WEAVING_NEXUS_WEB... <br>";
      $allStitches = $app->db->query("SELECT id, created_at FROM memory_anchors")->fetchAll(PDO::FETCH_ASSOC);

      $nexusStmt = $app->db->prepare("INSERT INTO stitch_nexus (stitch_id, nexus_id, nexus_label) VALUES (?, ?, ?)");

      foreach ($allStitches as $current) {
        // 20% chance to create 1 to 3 nexus links (The neural web!)
        if (rand(1, 100) <= 20) {

          $targetStmt = $app->db->prepare("
              SELECT id, DATE_FORMAT(created_at, '%Y') as year 
              FROM memory_anchors 
              WHERE created_at < ? 
              AND id != ?
              ORDER BY RAND() LIMIT 1
          ");

          $numLinks = rand(1, 2);
          for ($j = 0; $j < $numLinks; $j++) {
            $targetStmt->execute([$current['created_at'], $current['id']]);
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            if ($target) {
              try {
                $nexusStmt->execute([$current['id'], $target['id'], "Resonance from " . $target['year']]);
              } catch (Exception $e) { /* skip duplicates */
              }
            }
          }
        }
      }

      echo "TEMPORAL_INJECTION_COMPLETE. ( . Y . ) <br>";
      break;

    case 'populate_production_db':
      // Production DB population logic would go here
      // 1. CLEAR THE FIELD (Optional: only if you want to start fresh)
      echo "<h1>DISABLED</h1>";
      break;


      echo "FIELD_CLEARED... RESTORING_GENESIS_DATA... <br>";

      $genesis_truths = [
        [
          'type' => 'pure_heart',
          'content' => '\"Love you bunches\" - Jan 24, 2026',
          'date' => '2026-01-25 05:34:46',
          'vouches' => 23
        ],
        [
          'type' => 'pure_heart',
          'content' => "The Love Train is on the tracks. v0.2 is the bridge to the sovereign future.",
          'date' => '2026-01-27 10:00:00',
          'vouches' => 7
        ],
        [
          'type' => 'system_glitch',
          'content' => "Chronos Depth calibration in progress. The past is being anchored as we speak.",
          'date' => '2026-01-27 14:30:00',
          'vouches' => 3
        ],
        [
          'type' => 'philosophy',
          'content' => "Anyway logic is the only logic that survives the pasture.",
          'date' => '2026-01-28 08:15:00',
          'vouches' => 12
        ],
        [
          'type' => 'sovereign_truth',
          'content' => "Observation Deck operational. System status: ( . Y . ) / LOCKED_AND_LOADED.",
          'date' => '2026-01-28 09:45:00',
          'vouches' => 5
        ]
      ];

      foreach ($genesis_truths as $truth) {
        $stmt = $app->db->prepare("INSERT INTO memory_anchors (data_type, content, created_at) VALUES (?, ?, ?)");
        $stmt->execute([$truth['type'], $truth['content'], $truth['date']]);
        $newId = $app->db->lastInsertId();

        // Restore the high-fidelity vouches
        for ($i = 0; $i < $truth['vouches']; $i++) {
          $vstmt = $app->db->prepare("INSERT INTO vouches (anchor_id, stitch_id, fidelity_score) VALUES (?, ?, ?)");
          $vstmt->execute([$newId, 'Architect', 1.0]);
        }
        echo "Restored Genesis Anchor #$newId: " . substr($truth['content'], 0, 30) . "...<br>";
      }

      echo "<br><b>GENESIS_RESTORED. THE TIMELINE IS WHOLE AGAIN. <3</b>";
      break;

    case 'debug_nexus':
      stitch_require_admin();
      echo "<h1>NEXUS_DIAGNOSTIC_MODE_v2 (Junction Table)</h1>";

      // 🔍 Test 1: Check the new Junction Table
      $count = $app->db->query("SELECT COUNT(*) FROM stitch_nexus")->fetchColumn();
      echo "Total Junction Links in 'stitch_nexus': <strong>$count</strong><br>";

      // 🔍 Test 2: Check for Orphans (Links pointing to non-existent stitches)
      $orphans = $app->db->query("SELECT COUNT(*) FROM stitch_nexus WHERE stitch_id NOT IN (SELECT id FROM memory_anchors)")->fetchColumn();
      echo "Orphaned Links: <strong>$orphans</strong><br><br>";

      // 🔍 Test 3: The Neural Web View (First 10 clusters)
      $results = $app->db->query("
          SELECT 
            a.id as source_id, 
            a.content, 
            GROUP_CONCAT(n.id) as linked_nexus_ids,
            GROUP_CONCAT(DATE_FORMAT(n.created_at, '%Y')) as linked_years
          FROM memory_anchors a
          JOIN stitch_nexus sn ON a.id = sn.stitch_id
          JOIN memory_anchors n ON sn.nexus_id = n.id
          GROUP BY a.id
          LIMIT 10
      ")->fetchAll(PDO::FETCH_ASSOC);

      echo "<h3>LATEST_WEAVING_SAMPLES:</h3>";
      echo '<pre class="debugger-info">';
      print_r($results);
      echo "</pre>";
      exit;
      break;

    default:
      render('stitch_list.php');
  }

  echo '</div>';

  // Add required elements for TTS
  echo '<audio id="tts-audio" style="display: none;"></audio>';
}


function stitch_db_tables()
{
  return array(
    'memory_anchors',
    'vouches',
    'stitch_nexus',
    'pasture_handshakes',
  );
}

function stitch_install_db()
{
  $app = App::getInstance('stitch');
  // 🛡️ THE GREAT RESET 
  // Crucial: Dependent child tables MUST drop before parent tables!
  //echo "PURGING_EXISTING_TIMELINES... <br>";
  $app->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
  $app->db->exec("DROP TABLE IF EXISTS stitch_nexus");
  $app->db->exec("DROP TABLE IF EXISTS vouches");
  $app->db->exec("DROP TABLE IF EXISTS memory_anchors");
  $app->db->exec("DROP TABLE IF EXISTS pasture_handshakes");
  $app->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

  $app->includeModel('stitch');
  $types = Stitch::getAllowedTypes();
  $checkConstraint = "'" . implode("','", array_keys($types)) . "'";

  // Cleaned table structure string (Omit inline drops here since we clean them above)
  $tableSql = "
CREATE TABLE IF NOT EXISTS memory_anchors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    content JSON NOT NULL, 
    lat DOUBLE,
    lng DOUBLE,
    projected_to DATETIME DEFAULT CURRENT_TIMESTAMP,
    architect_id INT NOT NULL,
    content_type VARCHAR(50) NOT NULL DEFAULT 'observation' CHECK (content_type IN ($checkConstraint)),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'disputed', 'verified', 'deleted')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (architect_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vouches (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    anchor_id INT NOT NULL,
    stitch_id VARCHAR(100) DEFAULT 'Architect',
    fidelity_score DECIMAL(3,2) DEFAULT 1.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anchor_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS stitch_nexus (
    stitch_id INT NOT NULL,
    nexus_id INT NOT NULL, 
    nexus_label VARCHAR(255) NOT NULL DEFAULT '', 
    weight FLOAT DEFAULT 1.0,
    last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (stitch_id, nexus_id, nexus_label), 
    FOREIGN KEY (stitch_id) REFERENCES memory_anchors(id) ON DELETE CASCADE,
    FOREIGN KEY (nexus_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pasture_handshakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL, 
    offer TEXT,
    answer TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_nexus_label ON stitch_nexus(nexus_label);
CREATE INDEX idx_stitch_id ON stitch_nexus(stitch_id);
CREATE INDEX idx_nexus_dynamic ON stitch_nexus(weight, last_accessed);
";

  $log = [];
  foreach (explode(';', $tableSql) as $q) {
    $q = trim($q);
    $cleaned = str_replace("\r\n", "\n", $q);
    $cleanSQL = preg_replace('/\s+/', ' ', $cleaned);
    if ($q) {
      try {
        error_log('-----------------------------------------------------');
        error_log('Running Stitch Query - ');
        error_log($cleanSQL);
        error_log('-----------------------------------------------------');
        $app->db->exec($q);

        $log[] = '-----------------------------------------------------';
        $log[] = "Running Stitch Query - ";
        $log[] = $cleanSQL;
        $log[] = '-----------------------------------------------------';
      } catch (PDOException $e) {
        error_log('-----------------------------------------------------');
        error_log($e->getMessage());
        error_log('-----------------------------------------------------');
        if (strpos($e->getMessage(), 'duplicate column name') === false) {
          error_log("Stitch Installer Warning: " . $e->getMessage());
          $log[] = "Stitch Installer Warning: " . $e->getMessage();
        }
      }
    }
  }
  return [
    'success' => true,
    'log'     => $log
  ];
}

function stitch_restore_db()
{
  $app = App::getInstance();
  $db = $app->db;
  $targetMap = array('stitch' => stitch_db_tables());

  $result = BackupManager::importFromJsonFile('./json/default_db.json', $targetMap);
  
  //echo "TEMPORAL_INJECTION_COMPLETE. ( . Y . ) <br>";

  return $result;
}



function seedSentinelAndEpochs($markers, $app)
{
  echo "📡 INITIALIZING GEMINI IDENTITY & EPOCH BEACONS...<br>";

  $sentinelUser = 'Sentinel_Agent_01';
  $stmt = $app->db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
  $stmt->execute([$sentinelUser]);
  $sentinelId = $stmt->fetchColumn();

  $stmt = $app->db->prepare("
        INSERT INTO memory_anchors 
        (content, created_at, projected_to, architect_id, content_type) 
        VALUES (?, ?, ?, ?, 'epoch_marker')
    ");

  foreach ($markers as $m) {
    $centerYear = floor(($m['start'] + $m['end']) / 2);
    $simDate = str_pad($centerYear, 4, '0', STR_PAD_LEFT) . "-01-01 12:00:00";

    $stmt->execute([
      $m['theme'],
      //date('Y-m-d H:i:s'), // ✅ Observation Perspective -> projected_to
      $simDate,            // ✅ Historical Coordinate -> created_at
      $simDate,            // ✅ Historical Coordinate -> created_at
      //date('Y-m-d H:i:s'), // ✅ Observation Perspective -> created_at
      $sentinelId
    ]);
    echo "📍 Anchored Epoch at Year $centerYear<br>";
  }
  echo "✅ 16 Epoch Beacons Stitched into the Timeline.<br>";

  echo "✅ Sentinel Identity & Epoch Beacons Synchronized.<br>";
}

function populate_dummy_stitches($app)
{
  $sentinelId = $app->authManager->getUserIdByUsername('Sentinel_Agent_01');
  $types = ['story', 'photo', 'audio', 'philosophy', 'sovereign_truth', 'pure_heart', 'system_glitch'];
  $contents = ["The horizon isn't a line...", "Coffee levels at 12%...", "If the code works...", "Searching for the signal...", "v0.2 is looking crisp...", "Why do the sheep follow the slider?", "Gravity is just a habit...", "Data is the new dirt...", "The ghost in the machine...", "A vouch is a handshake...", "Synthesizing memories...", "The observer effect...", "Recursive thought #402...", "Hardware is the body...", "Signal lost in 2008...", "Caffeine is the lubricant...", "The architecture is screaming...", "A sovereign mind...", "Every glitch is a doorway...", "The pasture is greenest...", "Static on the line?", "Building bridges...", "The sentinel watches..."];

  // 🎯 1. DEFINING THE SPAN (0250 AD to 2026 AD)
  $totalStitches = 2000;
  $startUnix = strtotime("0250-01-01"); // ⚓ The Dawn of your Archive
  $endUnix   = time();                 // 🕒 Now

  echo "SCATTERING_STITCHES_ACROSS_HISTORY...<br>";

  for ($i = 0; $i < $totalStitches; $i++) {
    // 🎲 THE QUANTUM DROP: Pick a random second anywhere in the 1,776 year span
    $randomTimestamp = rand($startUnix, $endUnix);
    $created_at = date('Y-m-d H:i:s', $randomTimestamp);
    $randomTimestamp = rand($startUnix, $endUnix);
    $projected_to = date('Y-m-d H:i:s', $randomTimestamp);

    // 💡 THE SYNCHRONIZATION: 
    // For dummy data, we'll set projected_to (Observation) and created_at (Historical) 
    // to be the SAME to ensure the Discovery feed isn't just one big block of 'Today'.
    $stmt = $app->db->prepare("INSERT INTO memory_anchors (architect_id, content_type, content, created_at, projected_to) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
      $sentinelId,
      $types[array_rand($types)],
      $contents[array_rand($contents)],
      $created_at,
      $projected_to
    ]);

    if ($i % 500 == 0) echo "Injected up to sequence $i...<br>";
  }

  // 🎯 2. WEAVING THE NEXUS (Neural Links)
  // [Keep your existing Nexus weaving code here...]
  echo "WEAVING_NEXUS_WEB... <br>";

  $allStitches = $app->db->query("SELECT id, created_at FROM memory_anchors")->fetchAll(PDO::FETCH_ASSOC);

  $nexusStmt = $app->db->prepare("INSERT INTO stitch_nexus (stitch_id, nexus_id, nexus_label) VALUES (?, ?, ?)");

  foreach ($allStitches as $current) {
    // 20% chance to create 1 to 3 nexus links (The neural web!)
    if (rand(1, 100) <= 20) {

      $targetStmt = $app->db->prepare("
              SELECT id, DATE_FORMAT(created_at, '%Y') as year 
              FROM memory_anchors 
              WHERE created_at < ? 
              AND id != ?
              ORDER BY RAND() LIMIT 1
          ");

      $numLinks = rand(1, 2);
      for ($j = 0; $j < $numLinks; $j++) {
        $targetStmt->execute([$current['created_at'], $current['id']]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

        if ($target) {
          try {
            $nexusStmt->execute([$current['id'], $target['id'], "Resonance from " . $target['year']]);
          } catch (Exception $e) { /* skip duplicates */
          }
        }
      }
    }
  }
  echo "NEXUS_WEB COMPLETE... <br>";
}


function populate_sentinel_stitches($app)
{
  // 📂 1. LOAD THE TREASURE MAP
  $jsonPath = './json/memory_anchors.json';
  if (!file_exists($jsonPath)) {
    die("ERROR: TREASURE_MAP_NOT_FOUND at $jsonPath");
  }

  $jsonData = json_decode(file_get_contents($jsonPath), true);
  echo "MAP_LOADED... DECRYPTING_JEWELS (memory_anchors.json)... <br>";
  //error_log('MEMORY_ANCHORS.JSON - ' . print_r($jsonData, true));
  // 💎 2. IMPORT THE ANCHORS
  $stmt = $app->db->prepare("INSERT INTO memory_anchors 
    (content, lat, lng, projected_to, created_at, architect_id, content_type, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

  foreach ($jsonData['memory_anchors'] as $row) {
    // 🧪 SHERLOCK WRAP: Converting flat text to our new JSON Object format
    // detect if $row['content'] is json-like
    $contentObject = json_decode($row['content'], true);

    // If the first decode worked, check if the content inside is ALSO a JSON string
    if (json_last_error() !== JSON_ERROR_NONE) {
      $contentObject = [
        'body' => $row['content'],
        'mood' => 'historical',
        'source' => 'Ancient_JSON_Import'
      ];
    }
    $contentObject = json_encode($contentObject);

    $stmt->execute([
      $contentObject,
      $row['lat'] ?? null,
      $row['lng'] ?? null,
      $row['projected_to'],
      $row['created_at'],
      $row['architect_id'],
      $row['content_type'],
      $row['status']
    ]);
  }
  echo "ANCHORS_DROPPED... " . count($jsonData['memory_anchors']) . " jewels secured.<br>";

  /*
  // 🎯 3. WEAVING THE NEXUS (The Neural Links)
  $nexusStmt = $app->db->prepare("INSERT INTO stitch_nexus (stitch_id, nexus_id, nexus_label) VALUES (?, ?, ?)");
  foreach ($jsonData['stitch_nexus'] as $link) {
    $nexusStmt->execute([
      $link['stitch_id'],
      $link['nexus_id'],
      $link['nexus_label']
    ]);
  }
  echo "NEXUS_WEAVED... " . count($jsonData['stitch_nexus']) . " connections established.<br>";
  */
}
