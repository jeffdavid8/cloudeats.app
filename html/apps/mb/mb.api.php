<?php
/**
 * 💳 MEDIABRAIN CORE ENDPOINTS
 * 
 * 
 * 
 * Add these endpoints to the existing mb.api.php file
 */

if (!defined('MB_RUNNING')) exit;

// 🛡️ Secure the entry point
$input = file_get_contents('php://input');
$request = json_decode($input, true) ?? [];

$action = $_REQUEST['action'] ?? $request['action'] ?? null;
$app = App::getInstance('ledger');

// 📊 Include membership model, controller, and helpers
$app->includeModel('membership');
$app->includeHelper('membership');  // Loads all 40+ helper functions

// 🔐 Require authentication
$userId = 0;
if (isset($_SESSION['user'])) {
  $userId = $_SESSION['user']['id'];
} else {
  $user = User::getByUsername('demo');
  $userId = $user ? $user->id : 0;
}

/*
if (!$userId) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Authentication required']);
  exit;
}
*/

// ============================================================================
// 💳 MEDIABRAIN CORE ENDPOINTS
// ============================================================================



/**
 * FETCH PAGE META DATA
 * 
 * 
 * Usage: ?api=mb&action=fetch_page_meta_data
 */
if ($action === 'fetch_page_meta_data') {
  try {
    $url = $request['url'] ?? $_REQUEST['url'] ?? null;
    
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
      throw new Exception("Invalid or missing URL for extraction.");
    }

    // 🕵️ EXTRACTION_OVERRIDE: Create a context to look like a real browser
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    // 🧪 Pull the HTML with the browser identity
    $html = @file_get_contents($url, false, $context);
    
    if (!$html) {
        throw new Exception("Could not reach the site. The signal is blocked.");
    }

    // 🏗️ INITIALIZE THE BUCKET
    $all_meta = [];
    $meta_results = [
        'title' => '',
        'description' => '',
        'image' => '',
        'type' => 'website',
        'created_at' => date('Y-m-d H:i:s'),
        'url' => $url
    ];

    // 🔍 1. PULL ALL META TAGS (The "Integrity" Sweep)
    // This regex catches property/name/content in any order
    preg_match_all('/<meta\s+[^>]*?(?:name|property)=["\']([^"\']+)["\']\s+[^>]*?content=["\']([^"\']+)["\']| <meta\s+[^>]*?content=["\']([^"\']+)["\']\s+[^>]*?(?:name|property)=["\']([^"\']+)["\'] /ix', $html, $out, PREG_SET_ORDER);

    foreach ($out as $m) {
        $key = !empty($m[1]) ? $m[1] : $m[4];
        $val = !empty($m[2]) ? $m[2] : $m[3];
        $all_meta[$key] = htmlspecialchars_decode($val);
    }

    // 🎯 2. MAP THE CRITICAL DATA
    $all_meta['title'] = $all_meta['og:title'] ?? $all_meta['twitter:title'] ?? '';
    if (!$all_meta['title'] && preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        $all_meta['title'] = $matches[1];
    }

    $all_meta['description'] = $all_meta['og:description'] ?? $all_meta['description'] ?? $all_meta['twitter:description'] ?? '';
    $all_meta['image'] = $all_meta['og:image'] ?? $all_meta['twitter:image'] ?? $all_meta['image'] ?? '';
    $all_meta['type'] = $all_meta['og:type'] ?? 'article';
    
    // 📅 3. THE STARDATE EXTRACTION
    $raw_date = $all_meta['article:published_time'] ?? $all_meta['published_at'] ?? $all_meta['date'] ?? null;
    if ($raw_date) {
        $all_meta['created_at'] = date('Y-m-d H:i:s', strtotime($raw_date));
    } elseif (preg_match('/"datePublished":\s*"(.*?)"/', $html, $matches)) {
        $all_meta['created_at'] = date('Y-m-d H:i:s', strtotime($matches[1]));
    }

    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'meta' => $all_meta,
    ]);

  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit;
}



// ============================================================================
// DEFAULT - Unknown action
// ============================================================================

header('Content-Type: application/json');
http_response_code(404);
echo json_encode([
  'status' => 'error',
  'message' => 'Unknown action: ' . htmlspecialchars($action)
]);
exit;
