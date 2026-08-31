<?php
/**
 * 💳 MEMBERSHIP API ENDPOINTS
 * 
 * Example integration showing how to use the new MembershipPurchaseController
 * and membership helper functions in ledger.api.php
 * 
 * Add these endpoints to the existing ledger.api.php file
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

if (!$userId) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(['error' => 'Authentication required']);
  exit;
}

// ============================================================================
// 💳 MEMBERSHIP PURCHASE ENDPOINTS
// ============================================================================

/**
 * GET_MEMBERSHIP_STATUS
 * Get current membership status for user
 * 
 * Usage: ?action=get_membership_status
 */
if ($action === 'get_membership_status') {
  try {
    $status = getMembershipStatus($userId);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => $status
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * GET_TIERS
 * Get all available membership tiers with pricing
 * 
 * Usage: ?action=get_tiers
 */
if ($action === 'get_tiers') {
  try {
    $tiers = [];
    foreach (getAllTiers() as $tier_key => $tier_data) {
      $tiers[$tier_key] = [
        'key' => $tier_key,
        'label' => getTierLabel($tier_key),
        'description' => getTierDescription($tier_key),
        'color' => getTierColor($tier_key),
        'pricing' => formatTierPricing($tier_key),
        'features' => getTierFeatures($tier_key)
      ];
    }

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => $tiers
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * PURCHASE_MEMBERSHIP
 * Purchase a new membership
 * 
 * Usage: POST ?action=purchase_membership
 * Data: {
 *   "tier": "silver",
 *   "billing_cycle": "monthly",
 *   "payment_method": "stripe"
 * }
 */
if ($action === 'purchase_membership') {
  try {
    $tier = $_REQUEST['tier'] ?? $request['tier'] ?? null;
    $billing_cycle = $_REQUEST['billing_cycle'] ?? $request['billing_cycle'] ?? 'monthly';
    $payment_method = $_REQUEST['payment_method'] ?? $request['payment_method'] ?? 'stripe';

    // Validate input
    $validation = validatePurchaseData([
      'tier' => $tier,
      'billing_cycle' => $billing_cycle
    ]);

    if (!$validation['valid']) {
      return [
        'status' => 'error',
        'errors' => $validation['errors']
      ];
    }

    // 🛒 Get controller and process purchase
    $controller = $app->getControllerClass('MembershipPurchaseController', [$app->db, $userId]);
    $controller->payment_provider = $payment_method;  // Set payment provider
    
    $result = $controller->purchaseMembership($tier, $billing_cycle, []);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => $result['success'] ? 'success' : 'error',
      'data' => $result
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * UPGRADE_MEMBERSHIP
 * Upgrade to higher tier
 * 
 * Usage: POST ?action=upgrade_membership
 * Data: {
 *   "new_tier": "gold"
 * }
 */
if ($action === 'upgrade_membership') {
  try {
    $new_tier = $_REQUEST['new_tier'] ?? $request['new_tier'] ?? null;

    if (!$new_tier) {
      throw new Exception('new_tier parameter required');
    }

    $controller = $app->getControllerClass('MembershipPurchaseController', [$app->db, $userId]);
    $result = $controller->upgradeMembership($new_tier);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => $result['success'] ? 'success' : 'error',
      'data' => $result
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * DOWNGRADE_MEMBERSHIP
 * Schedule downgrade for next renewal
 * 
 * Usage: POST ?action=downgrade_membership
 * Data: {
 *   "new_tier": "bronze"
 * }
 */
if ($action === 'downgrade_membership') {
  try {
    $new_tier = $_REQUEST['new_tier'] ?? $request['new_tier'] ?? null;

    if (!$new_tier) {
      throw new Exception('new_tier parameter required');
    }

    $controller = $app->getControllerClass('MembershipPurchaseController', [$app->db, $userId]);
    $result = $controller->downgradeMembership($new_tier);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => $result['success'] ? 'success' : 'error',
      'data' => $result
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * RENEW_MEMBERSHIP
 * Renew expiring membership
 * 
 * Usage: POST ?action=renew_membership
 */
if ($action === 'renew_membership') {
  try {
    $controller = $app->getControllerClass('MembershipPurchaseController', [$app->db, $userId]);
    $result = $controller->renewMembership();

    header('Content-Type: application/json');
    echo json_encode([
      'status' => $result['success'] ? 'success' : 'error',
      'data' => $result
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * CANCEL_MEMBERSHIP
 * Cancel active membership
 * 
 * Usage: POST ?action=cancel_membership
 */
if ($action === 'cancel_membership') {
  try {
    $controller = $app->getControllerClass('MembershipPurchaseController', [$app->db, $userId]);
    $result = $controller->cancelMembership();

    header('Content-Type: application/json');
    echo json_encode([
      'status' => $result['success'] ? 'success' : 'error',
      'data' => $result
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * CHECK_FEATURE_ACCESS
 * Verify if user can access a feature
 * 
 * Usage: ?action=check_feature_access&feature=custom_domain
 */
if ($action === 'check_feature_access') {
  try {
    $feature = $_REQUEST['feature'] ?? $request['feature'] ?? null;

    if (!$feature) {
      throw new Exception('feature parameter required');
    }

    $can_access = userCanAccess($userId, $feature);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => [
        'feature' => $feature,
        'can_access' => $can_access,
        'message' => $can_access ? "Access granted" : "Feature requires higher tier"
      ]
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * GET_USAGE_SUMMARY
 * Get usage stats for user's tier
 * 
 * Usage: ?action=get_usage_summary
 */
if ($action === 'get_usage_summary') {
  try {
    $summary = getUsageSummary($userId);

    if (!$summary) {
      throw new Exception('No active membership');
    }

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => $summary
    ]);
  } catch (Exception $e) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * GET_MEMBERSHIP_STATISTICS (Admin only)
 * Get tier statistics
 * 
 * Usage: ?action=get_membership_statistics
 */
if ($action === 'get_membership_statistics') {
  try {
    // 🔒 Check admin
    if (!$app->isAdmin()) {
      throw new Exception('Admin access required');
    }

    $stats = getMembershipStatistics();

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => $stats
    ]);
  } catch (Exception $e) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
  }
  exit;
}

/**
 * GET_EXPIRING_MEMBERSHIPS (Admin only)
 * Get memberships expiring soon
 * 
 * Usage: ?action=get_expiring_memberships&days=30
 */
if ($action === 'get_expiring_memberships') {
  try {
    // 🔒 Check admin
    if (!$app->isAdmin()) {
      throw new Exception('Admin access required');
    }

    $days = (int)($_REQUEST['days'] ?? $request['days'] ?? 30);
    $report = getExpiringMembershipsReport($days);

    header('Content-Type: application/json');
    echo json_encode([
      'status' => 'success',
      'data' => $report
    ]);
  } catch (Exception $e) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
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
