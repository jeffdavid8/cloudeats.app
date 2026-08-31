<?php

use Ramsey\Uuid\Uuid;

// Secure the entry point
if (!defined('MB_RUNNING')) exit;
//error_log(print_r($_REQUEST, true));
$input = file_get_contents('php://input');
// Decode it into an associative array
$request = json_decode($input, true) ?? [];
//error_log(print_r($request, true));

$action = $_REQUEST['action'] ?? $request['action'] ?? null;
$app = App::getInstance('stitch');
$app->includeModel('stitch');
$app->includeModel('nexus');
$app->includeModel('vouch');

//$app->authManager->requireAdmin();

switch ($action) {


  case 'anchor_treasure':
    // 1. Decode the Treasure Chest
    // Since JS sent JSON.stringify, we get the 'data' field which is Base64
    $encodedData = $request['data'] ?? '';

    if (empty($encodedData)) {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Treasure chest was empty!']);
      exit;
    }

    // Convert Base64 back into a PHP Array
    $treasure = json_decode(base64_decode($encodedData), true);

    if (!$treasure) {
      header('Content-Type: application/json');
      echo json_encode(['status' => 'error', 'message' => 'Could not decode treasure.']);
      exit;
    }

    // 2. Prepare the Data for the Ledger
    error_log(print_r($treasure, true));
    // We use the content from the treasure, but we give it a NEW timestamp and NEW user_id
    // so it belongs to the person who just "Anchored" it.
    $content = $treasure['content'] ?? 'Unnamed Treasure';
    $lat = $treasure['latitude'] ?? null;
    $lng = $treasure['longitude'] ?? null;
    $userId = $_SESSION['user_id']; // The current Architect
    $timestamp = time();
    $uuid = bin2hex(random_bytes(16)); // Give it a fresh UUID for your local nexus

    /*
    // 3. Anchor it to the Database
    $stmt = $app->db->prepare("INSERT INTO memory_anchors (uuid, user_id, content, latitude, longitude, timestamp, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    //$stmt->execute([$uuid, $userId, $content, $lat, $lng, $timestamp, 'anchored']);
    
    $anchorId = $app->db->lastInsertId();

    // 4. Fetch the new object to render the card
    $stmt = $app->db->prepare("SELECT * FROM memory_anchors WHERE id = ?");
    $stmt->execute([$anchorId]);
    $newAnchor = $stmt->fetch(PDO::FETCH_ASSOC);
    */

    // 5. Success Broadcast
    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'id' => $anchorId,
      'data' => [
        'html' => Stitch::render_card($newAnchor, [], true),
      ]
    ]);

    exit;
    break;

  /* NO DELETES ALLOWED */
  // --- THE SOVEREIGN HANDSHAKE (Signaling) ---

  case 'post_offer':
    // Jeff drops the 'Invitation' in the hollow tree
    $sid = $_POST['session_id'] ?? $request['session_id'];
    $offer = $_POST['offer'] ?? $request['offer'];

    $sql = "INSERT INTO pasture_handshakes (session_id, offer) VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE offer = ?";
    $app->db->prepare($sql)->execute([$sid, $offer, $offer]);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
    break;

  case 'post_answer':
    // Dad's phone drops the 'Boomerang' here automatically
    $sid = $_POST['session_id'] ?? $request['session_id'];
    $answer = $_POST['answer'] ?? $request['answer'];

    $sql = "UPDATE pasture_handshakes SET answer = ? WHERE session_id = ?";
    $app->db->prepare($sql)->execute([$answer, $sid]);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
    break;

  case 'get_answer':
    // Jeff's phone checks the tree every 3 seconds
    $sid = $_GET['session_id'] ?? $request['session_id'];

    $stmt = $app->db->prepare("SELECT answer FROM pasture_handshakes WHERE session_id = ?");
    $stmt->execute([$sid]);
    $row = $stmt->fetch();

    header('Content-Type: application/json');
    echo json_encode(['answer' => $row['answer'] ?? null]);
    exit;
    break;

  case 'generate_observation':
    api_require_admin();
    $gemini = new AIService();
    $agentID = $app->authManager->getUserIdByUsername('Sentinel_Agent_01');
    //error_log($agentID);
    // 📅 2. TOUGH DATE PARSING
    $startStr = $_GET['start'] ?? rand(0001, 2026);
    $endStr = $_GET['end'] ?? null;
    $startStr = $_GET['start'] ?? date('Y');
    $location = $_GET['location'] ?? 'a random significant city';

    $targetDate = null;

    // Check if it's a full date MM-DD-YYYY
    if (strpos($startStr, '-') !== false && strlen($startStr) > 4) {
      $targetDate = DateTime::createFromFormat('m-d-Y', $startStr);
    }

    // If it's just a Year (4 digits), randomize the Month and Day for variety
    if (!$targetDate && preg_match('/^\d{4}$/', $startStr)) {
      $year = $startStr;
      $m = str_pad(rand(1, 12), 2, "0", STR_PAD_LEFT);
      $d = str_pad(rand(1, 28), 2, "0", STR_PAD_LEFT);
      $targetDate = new DateTime("$year-$m-$d " . date("H:i:s"));
    }

    // Fallback
    if (!$targetDate) {
      $targetDate = new DateTime();
    }

    // Now use ONE source of truth for both the DB and the Prompt
    $timestamp = $targetDate->format('Y-m-d H:i:s');

    // 🔬 THE GEO-TEMPORAL PROMPT (Requesting JSON)
    $prompt = "You are a localized observer on {$timestamp}. 
               Location: $location.
               CRITICAL: You must return ONLY a valid JSON object.

               Follow this exact structure:
               {
                 \"observation\": \"(A high-fidelity observation (2-3 sentences) of what is happening RIGHT NOW - landmarks, weather, and atmosphere)\",
                 \"lat\": (Float value),
                 \"lng\": (Float value),
                 \"context\": \"(One sentence on historical/emotional significance)\"
               }
               
               Perspective: First-person. Tone: MediaBrain Sentinel.
               Return ONLY the JSON object.";
    error_log("Prompt: " . print_r($prompt, true));

    // 🔬 2. The Prompt
    /*
    $prompt = "You are a localized observer in the year $randomYear. Write a short, high-fidelity observation (2-3 sentences) of a specific event or atmosphere happening RIGHT NOW. Perspective: First-person. Tone: MediaBrain Sentinel.";

    /generate_observation August 1700 @ Upper Merion, Montgomery, Pennsylvania
    /generate_observation August 1350 @ Indiana
    */

    $response = $gemini->ask($prompt);

    // 1. Decode what the AI gave us
    $aiData = json_decode($response, true);

    // 2. If the AI failed to give valid JSON, handle the fallback
    if (json_last_error() !== JSON_ERROR_NONE) {
      $aiData = [
        'body' => $response,
        'lat' => null,
        'lng' => null,
        'context' => "A raw signal captured by the Sentinel."
      ];
    }

    // 3. 💎 THE CLEAN INJECTION (Flattening the layers!)
    // We combine AI data with our UI needs into ONE object.
    $finalJewel = json_encode([
      'body'             => $aiData['observation'] ?? '',
      'location_context' => $aiData['context'] ?? '',
      'mood'             => 'ai_synthetic_discovery',
      'lat'           => $aiData['lat'] ?? null,
      'lng'           => $aiData['lng'] ?? null,
    ]);

    // ⚓ 4. THE INSERTION
    // Notice we use $aiData['lat'] directly for the REAL columns too!
    $stmt = $app->db->prepare("
        INSERT INTO memory_anchors 
        (content, content_type, lat, lng, created_at, projected_to, architect_id, status) 
        VALUES (?, 'historical_snapshot', ?, ?, ?, ?, ?, 'active')
    ");

    $stmt->execute([
      $finalJewel,
      $aiData['lat'] ?? null,
      $aiData['lng'] ?? null,
      date('Y-m-d H:i:s'),
      //$timestamp,
      $timestamp,
      $agentID,
    ]);

    // 🎯 NEW: Grab the ID we just created!
    $newId = $app->db->lastInsertId();

    // 🛰️ FETCH THE FRESH ANCHOR FOR THE CARD
    $anchor = Stitch::getById($newId);

    if (!$anchor) {
      throw new Exception("FAILED_TO_RECOVER_STITCH_ID: " . $newId);
    }

    // 🎨 RENDER THE COMPONENT
    $html = render('components/stitch_card.php', [
      'anchor'    => $anchor,
      'isBranch'  => false,
      'glowClass' => 'high-fidelity-glow'
    ], true);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => [
        'id'      => $newId,
        'year'    => $randomYear,
        'timestamp'    => $timestamp,
        'html'    => $html,
        'content' => $observation,
        'anchor' => $anchor,
      ]
    ]);
    exit;
    break;

  case 'import_file':

    $app->includeModel('importer_engine');
    $engine = new ImporterEngine($app);

    if (isset($input)) {
      $result = $engine->analyze($input, $_GET['file_name']);

      if ($result['status'] === 'success') {
        // Use ob_start to capture the component HTML
        $html = render('components/import_preview.php', ['data' => $result['data']], true);

        echo json_encode([
          'status' => 'success',
          'html' => $html,
          'raw' => $result['data'] // Keep this for the "Commit" step later
        ]);
      } else {
        header('Content-Type: application/json');
        echo json_encode($result);
      }
      exit;
    }
    break;

  case 'commit_import':
    $people = $request['people'] ?? [];
    $families = $request['families'] ?? [];
    $filename = $request['filename'] ?? 'GEDCOM Import';
    $architectId = $app->user->id;

    // 📂 STEP 1: Create the "Container" Anchor (The Folder)
    $containerUuid = Uuid::uuid4()->toString(); // Simple UUID generation
    $containerContent = json_encode([
      'body' => "Collection: " . $filename,
      'mood' => 'organized',
      'source' => 'GEDCOM_System_Import'
    ]);

    $folderStmt = $app->db->prepare("INSERT INTO memory_anchors (uuid, content, content_type, architect_id) VALUES (?, ?, ?, ?)");
    $folderStmt->execute([$containerUuid, $containerContent, 'collection', $architectId]);
    $folderDbId = $app->db->lastInsertId();

    // 🧠 STEP 2: Save People & Map IDs
    $idMap = [];
    $stmt = $app->db->prepare("INSERT INTO memory_anchors (uuid, content, created_at, projected_to, content_type, architect_id) VALUES (?, ?, ?, ?, ?, ?)");
    $containmentStmt = $app->db->prepare("INSERT INTO stitch_nexus (stitch_id, nexus_id, nexus_label) VALUES (?, ?, ?)");

    foreach ($people as $person) {
      $personUuid = Uuid::uuid4()->toString();
      $content = json_encode([
        'gedcom_id' => $person['gedcom_id'],
        'body'      => $person['name'],
        'location_context' => $person['location'],
        'events'    => $person['all_events'],
        'mood'      => 'historical_import',
        'source'    => 'GEDCOM_Precision_Import'
      ]);

      $stmt->execute([
        $personUuid,
        $content,
        date('Y-m-d H:i:s'),
        $person['date'],
        'ancestry',
        $architectId
      ]);

      $newId = $app->db->lastInsertId();
      $idMap[$person['gedcom_id']] = $newId;

      // 🔗 STITCH: Put the person inside the Folder
      $containmentStmt->execute([$folderDbId, $newId, 'member']);
    }

    // 🎯 STEP 3: Weave the Biological Nexus
    $nexusStmt = $app->db->prepare("INSERT INTO stitch_nexus (stitch_id, nexus_id, nexus_label, weight) VALUES (?, ?, ?, ?)");

    foreach ($families as $fam) {
      $husbId = $idMap[$fam['HUSB'] ?? ''] ?? null;
      $wifeId = $idMap[$fam['WIFE'] ?? ''] ?? null;

      if ($husbId && $wifeId) {
        $nexusStmt->execute([$husbId, $wifeId, 'spouse', 1.0]);
      }

      foreach (($fam['CHIL'] ?? []) as $childGedId) {
        $childDbId = $idMap[$childGedId] ?? null;
        if ($childDbId) {
          if ($husbId) $nexusStmt->execute([$husbId, $childDbId, 'parent_child', 1.0]);
          if ($wifeId) $nexusStmt->execute([$wifeId, $childDbId, 'parent_child', 1.0]);
        }
      }
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'count' => count($people), 'folder_id' => $folderDbId]);
    exit;

  case 'get_form':
    $type = $_GET['type'] ?? 'default';
    $path = "apps/stitch/views/components/cards/{$type}.form.php";

    if (!file_exists($path)) {
      $path = "components/cards/default.form.php";
    } else {
      $path = "components/cards/{$type}.form.php";
    }

    // We use the app's existing render tool or a simple include/buffer
    $html = render($path, $request['default_values'] ?? [], true);

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'html' => $html]);
    exit;
    break;

  case 'add_stitch':
    // 🕵️ 1. Extract inputs
    $content = $_POST['content'] ?? $request['content'] ?? '';
    //$content = json_decode($content, true);
    $architectId = $_POST['architect_id'] ?? $request['architect_id'] ?? null;
    $type    = $_POST['data_type'] ?? $request['data_type'] ?? 'observation';


    // 🏗️ 3. Invoke the Model (The Sovereign Stand)
    // Stitch::create handles the UUID and JSON encoding automatically!
    $uuid = Stitch::create([
      'architect_id' => $architectId,
      'content_type' => $type,
      'content'      => $content,
      'projected_to' => $meta['created_at'] ?? date('Y-m-d H:i:s'),
      'lat'          => $_POST['lat'] ?? null,
      'lng'          => $_POST['lng'] ?? null
    ]);

    if ($uuid) {
      // 🎯 4. Fetch the newly created object to return to the view
      $stitch = Stitch::getByUuid($uuid);

      $attributes = array(
        'class' => 'high-fidelity-glow',
      );

      // This is where you'd render the HTML for the new card
      $html = Stitch::render_card($stitch, $attributes, true);

      header('Content-Type: application/json');
      echo json_encode([
        'status' => 'success',
        'data' => [
          'uuid' => $uuid,
          'content' => $contentPayload,
          'html' => $html 
        ]
      ]);
    } else {
      throw new Exception("The Ledger refused the Stitch.");
    }
    break;

  case 'get_matrix_data':
    $threshold = (int)get_var('threshold', $request['threshold'] ?? 1);
    $search = get_var('search', $request['search'] ?? '');

    // 🛡️ 1. GET NODES
    $nodeQuery = "SELECT * FROM memory_anchors WHERE 1=1";
    if ($search) {
      $nodeQuery .= " AND content LIKE :search";
    }

    $nodeStmt = $app->db->prepare($nodeQuery);
    if ($search) {
      $nodeStmt->bindValue(':search', '%' . $search . '%');
    }
    $nodeStmt->execute();
    $rows = $nodeStmt->fetchAll(PDO::FETCH_ASSOC);

    $nodes = [];
    $validIds = [];

    foreach ($rows as $row) {
      $sId = (int)$row['id'];

      // 🎯 PDO way to get a single value (The Weight)
      $weightStmt = $app->db->prepare("SELECT COUNT(*) FROM stitch_nexus WHERE stitch_id = :sid");
      $weightStmt->execute([':sid' => $sId]);
      $weight = (int)$weightStmt->fetchColumn();
      $data = json_decode($row['content'], true);

      // If the first decode worked, check if the content inside is ALSO a JSON string
      if (json_last_error() === JSON_ERROR_NONE) {
        if (is_string($data)) {
          $data = json_decode($data, true);
        }
      }

      if (is_array($data)) {
        // 💎 Handle the Sentinel's specific Packet structure
        // We look for 'observation' (AI) or 'body' (Manual)
        $displayBody = $data['observation'] ?? $data['body'] ?? $anchor->content;
        $displayLat  = $data['lat'] ?? $anchor->lat;
        $displayLng  = $data['lng'] ?? $anchor->lng;
        $displayContext = $data['context'] ?? $data['location_context'] ?? '';
        $displayTitle = $displayBody;
      }

      if ($weight >= $threshold) {
        $validIds[] = $sId;
        $nodes[] = [
          'id'    => $sId,
          'label' => mb_strimwidth($displayBody, 0, 20, "..."),
          'value' => $weight + 2,
          'title' => $displayTitle,
          'lat'   => $displayLat,
          'lng'   => $displayLng,
          'context' => $displayContext,
          'color' => '#6a1b9a',
          'data' => $data,
        ];
      }
    }

    // 🛡️ 2. GET EDGES
    $edges = [];
    if (!empty($validIds)) {
      // Find pairs of stitches that share the same nexus_label
      $idList = implode(',', $validIds);
      $edgeQuery = "
            SELECT n1.stitch_id AS from_id, n2.stitch_id AS to_id, n1.nexus_label
            FROM stitch_nexus n1
            JOIN stitch_nexus n2 ON n1.nexus_label = n2.nexus_label
            WHERE n1.stitch_id < n2.stitch_id
            AND n1.stitch_id IN ($idList)
            AND n2.stitch_id IN ($idList)
        ";

      $edgeStmt = $app->db->query($edgeQuery);
      $edgeRows = $edgeStmt->fetchAll(PDO::FETCH_ASSOC);

      foreach ($edgeRows as $row) {
        $edges[] = [
          'from'  => (int)$row['from_id'],
          'to'    => (int)$row['to_id'],
          'label' => $row['nexus_label'],
          'color' => 'rgba(255, 64, 129, 0.4)',
          'font'  => ['size' => 10, 'color' => '#ffffff']
        ];
      }
    }

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'nodes' => $nodes,
      'edges' => $edges,
      'count' => count($nodes)
    ]);
    exit;
    break;


  /* STITCH API: INFINITE HORIZON LOAD */
  case 'load_more':
  case 'search_string':
  case 'sentinel_load_more':
  case 'fetch_history':
  case 'chronos_dial':
  case 'list':
  case 'warp_to_nexus':

    $allowedTypes = $_GET['types'] ?? ['default', 'pure_heart', 'story', 'historical_snapshot', 'historical_import', 'observation', 'epoch_marker', 'nexus_link'];
    // If it's a string (from a single select), wrap it in an array
    if (!is_array($allowedTypes)) {
      $allowedTypes = [$allowedTypes];
    }
    $dimension = $_GET['dimension'] ?? 'projected_to';
    $startStr  = $_GET['start_date'] ?? '2006-01-01'; // ⚓ Set to your actual start
    $endStr    = $_GET['end_date'] ?? date('Y-m-d H:i:s'); // 🕒 Use H:i:s for "True Now"
    $search    = $_GET['search'] ?? '';
    $limit     = (int)($_GET['limit'] ?? 20);
    $target_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : null;

    $depth    = (int)($_GET['depth'] ?? 100);
    $beforeId = !empty($_GET['before_id']) ? (int)$_GET['before_id'] : null;
    $afterId  = !empty($_GET['after_id']) ? (int)$_GET['after_id'] : null;
    $isWarp   = (isset($_GET['warp']) && $_GET['warp'] === 'true');
    $horizon_reached = false;

    // 🎯 1. ROUTE THE LOGIC
    // --- 1. PREPARE THE FOUNDATION ---
    // 1. 🧹 CLEAN THE SLATE at the start of the action
    $where = "1=1";
    $params = [
      'where' => "1=1",
      'binds' => [],
      'order_by' => "a.$dimension DESC",
      'limit' => $limit
    ];
    $orderBy = "a.$dimension DESC";
    // Switch from strtotime to the more robust DateTime class
    $startDate = new DateTime($_GET['start_date'] ?? '0001-01-01');
    $endDate   = new DateTime($_GET['end_date'] ?? '2026-02-01');
    $interval  = $startDate->diff($endDate);
    $totalSeconds = ($interval->days * 24 * 60 * 60) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

    $targetUnix = $startDate->getTimestamp() + ($totalSeconds * ($depth / 100));
    $targetDate = date('Y-m-d H:i:s', $targetUnix);
    $totalSpan  = $endTime - $startTime;

    // Ensure $depth is treated as a float/int
    $depthPercent = (float)$depth / 100;

    switch ($action) {
      case 'search_string':
        $search = $_GET['search'] ?? null;
        $targetDate = $_GET['end_date'] ?? date('Y-m-d H:i:s');

        // 🛡️ 1. Start with a clean anchor
        $params['where'] = "1=1";

        // 🕰️ 3. Normal scroll-mode uses the date filter
        //$where .= " AND a.created_at <= :target_date";
        //$params['binds'][':target_date'] = $targetDate;
        break;

      case 'reset_horizon':
      case 'sentinel_load_more':
      case 'load_more':
      case 'fetch_history':
        if ($depth <= 1) {
          $id = $afterId ?: $beforeId;
          if ($id) {
            $params['where'] = "a.$dimension < (SELECT $dimension FROM memory_anchors WHERE id = $id)";
          }
          $params['order_by'] = "a.$dimension DESC";
        } else {
          $id = $beforeId ?: $afterId;
          if ($id) {
            $params['where'] = "a.$dimension < (SELECT $dimension FROM memory_anchors WHERE id = $id)";
          }
          $params['order_by'] = "a.$dimension DESC";
        }
        break;

      case 'chronos_dial':
        // 🛰️ UNIVERSAL CALCULATION
        // (The $targetDate calculation is already happening above)

        // 🎯 THE FIX: If we are dialing, we ignore the "Before/After" IDs 
        // because we are repositioning the entire viewport.
        if ($depth >= 99) {
          $params['where'] = "1=1"; // Present Day / Full View
          $params['order_by'] = "a.$dimension DESC";
        } else {
          // Look for everything from the target date and older
          $params['where'] = "a.$dimension <= '$targetDate'";
          $params['order_by'] = "a.$dimension DESC";
        }

        // 🛡️ CRITICAL: If search is active, we keep it, but we drop the before_id capping
        // so the 2026 records can actually "surface."
        break;

      case 'warp_to_nexus':
        // 🎯 THE SNIPER SHOT: We want exactly one specific record.
        if ($target_id) {
          $params['where'] = "a.id = " . $target_id;
          // Since it's a specific ID, the orderBy doesn't matter much, 
          // but we'll keep it consistent.
          $params['order_by'] = "a.$dimension DESC";
        }
        break;
      default:
        break;
    }

    if (!empty($allowedTypes)) {
      $placeholders = [];
      foreach ($allowedTypes as $i => $t) {
        $key = ":type_" . $i;
        $placeholders[] = $key;
        $params['binds'][$key] = $t;
      }
      // Result: a.content_type IN (:type_0, :type_1)
      $params['where'] .= " AND a.content_type IN (" . implode(', ', $placeholders) . ")";
    }

    if (!empty($search)) {
      // Wrap the OR in parentheses so the "AND" type filter isn't ignored
      $params['where'] .= " AND (LOWER(a.content) LIKE :search OR LOWER(a.content_type) LIKE :search)";
      $params['binds'][':search'] = '%' . strtolower($search) . '%';
    }
    //error_log("DEBUG: SQL: $query | PARAMS: " . print_r($params, true));

    $stitches = Stitch::query($params);

    if (!count($stitches)) {
      echo json_encode([
        'status' => 'success',
        'data' => [
          'horizon_reached' => true,
        ]
      ]);
      exit;
    }
    // 🎯 3. HYDRATE MODELS & RENDER
    $html = '';
    $stitchObjects = [];

    foreach ($stitches as $stitch) {
      // Create the model (the model constructor handles the nexus_json automatically!)
      //$stitch = new Stitch($row);

      // Pass the OBJECT to the view, not the array!
      $html .= Stitch::render_card($stitch, [], true);
    }

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => [
        'html' => $html,
        'anchors' => $stitches, // Only populated if type=data
        'count' => count($stitches),
        'era' => $targetDate,
        'horizon_reached' => $horizon_reached,
      ]
    ]);
    exit;
    break;

  case 'vouch':
    $id = $_POST['id'] ?? $request['id'] ?? null;
    if ($id) {
      // The table now has stitch_id, so the Goobers are locked out!
      $stmt = $app->db->prepare("INSERT INTO vouches (anchor_id, stitch_id, fidelity_score) VALUES (?, ?, ?)");
      $stmt->execute([$id, 'Architect', 1.0]);

      header('Content-Type: application/json');
      echo json_encode(['status' => 'success']);
      exit;
    }
    break;

  case 'observe_stitch_nexus':
    // 🧠 Increase weight by 0.1 per observation
    $id = $request['id'];
    $sql = "UPDATE stitch_nexus 
        SET weight = weight + 0.1, 
            last_accessed = CURRENT_TIMESTAMP 
        WHERE stitch_id = ? OR nexus_id = ?";
    $app->db->prepare($sql)->execute([$id, $id]);
    break;

  case 'help':
    $page = $_GET['page'] ?? 'index';
    $html = render('help/' . $page . '.php', [], true);
    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => [
        'html' => $html,
      ]
    ]);
    exit;
    break;

  default:
    json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
