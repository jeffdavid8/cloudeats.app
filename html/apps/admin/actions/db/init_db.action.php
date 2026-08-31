<?
// Secure the entry point
if (!defined('MB_RUNNING')) exit;

function init_db($title)
{
  echo "<h1>$title</h1>";
  //$_SESSION['admin_key'] = NULL;
  //exit();
  $key = get_var('key', false);
  if (((!$key) || ($key != $_SESSION['admin_key'])) && (!isset($_SESSION['bypass_admin_key']))) {
    $key = rand(100000, 999999);
    $_SESSION['admin_key'] = $key;
    echo '<a class="btn" href="?app=admin&p=init_db&key=' . $key . '">Initialize Database</a>';
    die();
  }
  $_SESSION['admin_key'] = NULL;

  echo " <br><br><br><br>                                  ( . Y . ) <br><br>";
  echo 'Here we gooooo!  ------------~~~~~~~';
}

init_db('INIT DATABASE');
$app = App::getInstance();

// 🛡️ THE GREAT RESET 
// Crucial: Dependent child tables MUST drop before parent tables!
echo "PURGING_EXISTING_TIMELINES... <br>";
$app->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
$app->db->exec("DROP TABLE IF EXISTS stitch_nexus");
$app->db->exec("DROP TABLE IF EXISTS vouches");
$app->db->exec("DROP TABLE IF EXISTS memory_anchors");
$app->db->exec("DROP TABLE IF EXISTS pasture_handshakes");
$app->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

$stitchApp = App::getInstance('stitch');
$stitchApp->includeModel('stitch');
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

$tableSql .= app_invoke('neighborhub', 'db_schema', $app);

// ⚡ DISABLE FOREIGNS WRAPPING THE ENTIRE SEQUENTIAL EXECUTIONS LOOP
$app->db->exec("SET FOREIGN_KEY_CHECKS = 0;");

foreach (explode(';', $tableSql) as $q) {
  $q = trim($q);
  if ($q) {
    try {
      error_log('-----------------------------------------------------');
      error_log('Running Query - ');
      error_log($q);
      error_log('-----------------------------------------------------');
      $app->db->exec($q);
    } catch (PDOException $e) {
      error_log('-----------------------------------------------------');
      error_log($e->getMessage());
      error_log('-----------------------------------------------------');
      if (strpos($e->getMessage(), 'duplicate column name') === false) {
        echo "Warning: " . $e->getMessage() . "<br>";
      }
    }
  }
}

// 🔐 SAFE RESUME AFTER ENTIRE SCHEMA CONSTELLATION LIVES
$app->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

echo "TABLES_CREATED. STARTING_SEQUENTIAL_INJECTION... <br><br>";
echo " <br><br><br><br>                                  ( . Y . ) <br><br>";//exit;

$epochs = [
  ['start' => '0000', 'end' => '0500', 'theme' => 'Antiquity: The Roman Empire, Stoic logic, and the silent transition of empires.'],

  ['start' => '0501', 'end' => '1400', 'theme' => 'The Middle Ages: Scholasticism, early optics, and the preservation of ancient texts.'],

  ['start' => '1401', 'end' => '1750', 'theme' => 'The Great Awakening: Printing, navigation, and the mechanical philosophy of Newton.'],

  ['start' => '1751', 'end' => '1900', 'theme' => 'Industrial Synthesis: Steam power, the speed of light experiments, and photography.'],

  ['start' => '1901', 'end' => '1960', 'theme' => 'The Atomic Threshold: Radio communication, quantum theory, and the first vacuum tubes.'],

  ['start' => '1961', 'end' => '2000', 'theme' => 'The Silicon Bloom: ARPANET, the PC era, and the digitization of the human record.'],

  ['start' => '2001', 'end' => '2026', 'theme' => 'The Neural Horizon: Generative AI, decentralized truths, and the birth of MediaBrain.app']
];

//seedSentinelAndEpochs($epochs, $app);
//populate_dummy_stitches($app);
populate_sentinel_stitches($app);

finish();


function finish()
{
  echo "TEMPORAL_INJECTION_COMPLETE. ( . Y . ) <br>";
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
              SELECT id, strftime('%Y', created_at) as year 
              FROM memory_anchors 
              WHERE created_at < ? 
              AND id != ?
              ORDER BY RANDOM() LIMIT 1
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
