<?php

/**
 * 💳 MEMBERSHIP HELPER FUNCTIONS
 * 
 * Utility functions for membership operations, access control, and data formatting.
 * These are global helpers for common membership checks and tasks.
 */

// ============================================================================
// 🔑 ACCESS & AUTHORIZATION HELPERS
// ============================================================================

/**
 * Check if user has an active membership
 * 
 * @param int $architect_id
 * @return bool
 */
function userHasMembership($architect_id)
{
  $membership = getActiveMembership($architect_id);
  return $membership !== null;
}

/**
 * Get active membership for user (or null if none)
 * 
 * @param int $architect_id
 * @return Membership|null
 */
function getActiveMembership($architect_id)
{
  return Membership::getActive($architect_id);
}

/**
 * Check if user can access a specific feature
 * 
 * @param int $architect_id
 * @param string $required_feature
 * @return bool
 */
function userCanAccess($architect_id, $required_feature)
{
  $membership = getActiveMembership($architect_id);
  
  if (!$membership) {
    return false;
  }

  return $membership->hasFeature($required_feature);
}

/**
 * Check if user has access to specific tier(s)
 * 
 * @param int $architect_id
 * @param string|array $required_tier - Single tier or array of tiers
 * @return bool
 */
function userHasTier($architect_id, $required_tier)
{
  $membership = getActiveMembership($architect_id);
  
  if (!$membership) {
    return false;
  }

  if (is_array($required_tier)) {
    return in_array($membership->membership_tier, $required_tier);
  }

  return $membership->membership_tier === $required_tier;
}

/**
 * Enforce feature access or redirect/exit
 * Terminates execution if user lacks required feature
 * 
 * @param int $architect_id
 * @param string $required_feature
 * @param string $redirect_url - Redirect on denied access
 * @return void
 */
function enforceFeatureAccess($architect_id, $required_feature, $redirect_url = '/upgrade')
{
  $membership = getActiveMembership($architect_id);

  if (!$membership || !$membership->hasFeature($required_feature)) {
    if ($redirect_url) {
      header("Location: {$redirect_url}");
      exit;
    } else {
      http_response_code(403);
      die("Access denied: Feature requires {$required_feature}");
    }
  }
}

/**
 * Enforce tier access or redirect/exit
 * Terminates execution if user doesn't have required tier
 * 
 * @param int $architect_id
 * @param string $required_tier
 * @param string $redirect_url - Redirect on denied access
 * @return void
 */
function enforceTierAccess($architect_id, $required_tier, $redirect_url = '/upgrade')
{
  if (!userHasTier($architect_id, $required_tier)) {
    if ($redirect_url) {
      header("Location: {$redirect_url}");
      exit;
    } else {
      http_response_code(403);
      die("Access denied: Requires {$required_tier} tier");
    }
  }
}

/**
 * Get user's current membership status
 * 
 * @param int $architect_id
 * @return array
 */
function getMembershipStatus($architect_id)
{
  $membership = getActiveMembership($architect_id);

  if (!$membership) {
    return [
      'has_membership' => false,
      'status' => 'none',
      'message' => 'No active membership'
    ];
  }

  return [
    'has_membership' => true,
    'status' => $membership->status,
    'tier' => $membership->membership_tier,
    'expires_at' => $membership->expires_at,
    'days_remaining' => $membership->days_remaining,
    'is_trial' => $membership->is_trial,
    'auto_renew' => $membership->auto_renew,
    'message' => $membership->membership_tier . ' tier - ' . $membership->days_remaining . ' days remaining'
  ];
}

// ============================================================================
// 📊 TIER & FEATURE HELPERS
// ============================================================================

/**
 * Get human-readable tier label
 * 
 * @param string $tier
 * @return string
 */
function getTierLabel($tier)
{
  $defs = Membership::getTierDefinitions();
  return $defs[$tier]['display_name'] ?? ucfirst($tier) . ' Tier';
}

/**
 * Get tier description
 * 
 * @param string $tier
 * @return string
 */
function getTierDescription($tier)
{
  $defs = Membership::getTierDefinitions();
  return $defs[$tier]['description'] ?? '';
}

/**
 * Get all features available in a tier
 * 
 * @param string $tier
 * @return array
 */
function getTierFeatures($tier)
{
  $defs = Membership::getTierDefinitions();
  return $defs[$tier]['features'] ?? [];
}

/**
 * Get price for tier
 * 
 * @param string $tier
 * @param string $billing_cycle - 'monthly' or 'yearly'
 * @return float
 */
function getTierPrice($tier, $billing_cycle = 'monthly')
{
  $defs = Membership::getTierDefinitions();
  if (!isset($defs[$tier])) {
    return 0;
  }

  $price_key = ($billing_cycle === 'yearly') ? 'price_yearly' : 'price_monthly';
  return $defs[$tier][$price_key] ?? 0;
}

/**
 * Get color/branding for tier
 * 
 * @param string $tier
 * @return string - Hex color code
 */
function getTierColor($tier)
{
  $defs = Membership::getTierDefinitions();
  return $defs[$tier]['color'] ?? '#999999';
}

/**
 * Compare two tiers - return feature differences
 * 
 * @param string $tier1
 * @param string $tier2
 * @return array - Features unique to each tier
 */
function compareTiers($tier1, $tier2)
{
  $features1 = getTierFeatures($tier1);
  $features2 = getTierFeatures($tier2);

  $unique_to_tier1 = array_diff_key($features1, $features2);
  $unique_to_tier2 = array_diff_key($features2, $features1);
  $different_values = [];

  foreach ($features1 as $key => $val1) {
    if (isset($features2[$key]) && $features2[$key] !== $val1) {
      $different_values[$key] = [
        'tier1' => $val1,
        'tier2' => $features2[$key]
      ];
    }
  }

  return [
    'tier1' => $tier1,
    'tier2' => $tier2,
    'unique_to_tier1' => $unique_to_tier1,
    'unique_to_tier2' => $unique_to_tier2,
    'different_values' => $different_values
  ];
}

/**
 * Get all available tiers for display
 * 
 * @return array
 */
function getAllTiers()
{
  return Membership::getTierDefinitions();
}

// ============================================================================
// 💰 PRICING & FORMATTING HELPERS
// ============================================================================

/**
 * Format price for display
 * 
 * @param float $price
 * @param string $currency - Currency code (default: 'USD')
 * @return string
 */
function formatPrice($price, $currency = 'USD')
{
  $symbols = [
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'JPY' => '¥',
  ];

  $symbol = $symbols[$currency] ?? $currency;
  return $symbol . number_format($price, 2);
}

/**
 * Format pricing table for tier
 * 
 * @param string $tier
 * @return array
 */
function formatTierPricing($tier)
{
  $monthly = getTierPrice($tier, 'monthly');
  $yearly = getTierPrice($tier, 'yearly');

  // Calculate monthly equivalent for yearly
  $yearly_monthly = $yearly / 12;
  $monthly_savings = (($monthly * 12) - $yearly) / ($monthly * 12) * 100;

  return [
    'tier' => $tier,
    'label' => getTierLabel($tier),
    'monthly' => [
      'price' => $monthly,
      'formatted' => formatPrice($monthly)
    ],
    'yearly' => [
      'price' => $yearly,
      'formatted' => formatPrice($yearly),
      'monthly_equivalent' => round($yearly_monthly, 2),
      'savings_percent' => round($monthly_savings, 1)
    ]
  ];
}

/**
 * Get discount for yearly billing
 * 
 * @param string $tier
 * @return float - Savings percentage
 */
function getYearlyDiscount($tier)
{
  $monthly_total = getTierPrice($tier, 'monthly') * 12;
  $yearly = getTierPrice($tier, 'yearly');
  $savings = (($monthly_total - $yearly) / $monthly_total) * 100;
  return round($savings, 1);
}

// ============================================================================
// 📅 DATE & EXPIRATION HELPERS
// ============================================================================

/**
 * Calculate days until expiration
 * 
 * @param string $expires_at - Timestamp string
 * @return int - Days remaining (negative if expired)
 */
function daysUntilExpiry($expires_at)
{
  if (!$expires_at) {
    return 0;
  }

  $now = new DateTime();
  $expiry = new DateTime($expires_at);
  $diff = $expiry->diff($now);

  return $diff->invert ? $diff->days : -$diff->days;
}

/**
 * Format expiration date for display
 * 
 * @param string $expires_at
 * @param string $format - PHP date format
 * @return string
 */
function formatExpirationDate($expires_at, $format = 'M j, Y')
{
  if (!$expires_at) {
    return "No expiration";
  }

  try {
    return date($format, strtotime($expires_at));
  } catch (Exception $e) {
    return "Invalid date";
  }
}

/**
 * Get human-readable time until expiration
 * 
 * @param string $expires_at
 * @return string
 */
function getTimeUntilExpiry($expires_at)
{
  $days = daysUntilExpiry($expires_at);

  if ($days < 0) {
    return "Expired " . abs($days) . " days ago";
  }

  if ($days === 0) {
    return "Expires today";
  }

  if ($days === 1) {
    return "Expires tomorrow";
  }

  if ($days <= 7) {
    return "Expires in " . $days . " days";
  }

  $weeks = floor($days / 7);
  return "Expires in " . $weeks . " weeks";
}

/**
 * Check if membership needs immediate renewal
 * 
 * @param Membership $membership
 * @param int $days_threshold - Days threshold to consider "immediate"
 * @return bool
 */
function needsImmediateRenewal($membership, $days_threshold = 7)
{
  return $membership && $membership->isExpiringSoon($days_threshold);
}

/**
 * Get renewal reminder message
 * 
 * @param Membership $membership
 * @return string
 */
function getRenewalReminderMessage($membership)
{
  if (!$membership) {
    return "No active membership";
  }

  if ($membership->isExpired()) {
    return "Your membership has expired. Renew now to regain access.";
  }

  $days = $membership->days_remaining;

  if ($days <= 3) {
    return "⚠️ Your membership expires in {$days} days!";
  }

  if ($days <= 7) {
    return "Your membership expires in {$days} days.";
  }

  if ($days <= 30) {
    return "Your membership expires in {$days} days. Plan ahead for renewal.";
  }

  return "Your membership is active and won't expire for {$days} days.";
}

// ============================================================================
// ✅ QUOTA & USAGE HELPERS
// ============================================================================

/**
 * Check if user has quota available for feature
 * 
 * @param int $architect_id
 * @param string $feature_name
 * @param int $requested - Amount requested (default: 1)
 * @return array - ['available' => bool, 'remaining' => int, 'limit' => int]
 */
function checkFeatureQuota($architect_id, $feature_name, $requested = 1)
{
  $membership = getActiveMembership($architect_id);

  if (!$membership) {
    return [
      'available' => false,
      'remaining' => 0,
      'limit' => 0,
      'reason' => 'No active membership'
    ];
  }

  $current = $membership->getCurrentUsage($feature_name);
  $limit = $membership->getFeatureLimit($feature_name);
  $remaining = $membership->getRemainingQuota($feature_name);

  // Check if unlimited (-1)
  if ($limit === -1 || $limit < 0) {
    return [
      'available' => true,
      'remaining' => -1,  // Unlimited
      'limit' => -1,
      'current_usage' => $current
    ];
  }

  $has_quota = $remaining >= $requested;

  return [
    'available' => $has_quota,
    'remaining' => $remaining,
    'limit' => $limit,
    'current_usage' => $current,
    'requested' => $requested,
    'message' => $has_quota ? "OK: {$remaining} remaining" : "Quota exceeded: {$remaining} remaining"
  ];
}

/**
 * Increment feature usage for user
 * 
 * @param int $architect_id
 * @param string $feature_name
 * @param int $amount
 * @return bool
 */
function incrementFeatureUsage($architect_id, $feature_name, $amount = 1)
{
  $membership = getActiveMembership($architect_id);

  if (!$membership) {
    return false;
  }

  return $membership->incrementFeatureUsage($feature_name, $amount);
}

/**
 * Get usage summary for user
 * 
 * @param int $architect_id
 * @return array
 */
function getUsageSummary($architect_id)
{
  $membership = getActiveMembership($architect_id);

  if (!$membership) {
    return null;
  }

  $usage = $membership->content['usage_tracking'] ?? [];
  $features = $membership->content['features_enabled'] ?? [];

  $summary = [];
  foreach ($usage as $feature => $current) {
    $limit = $features[$feature] ?? -1;
    $remaining = ($limit === -1 || $limit < 0) ? -1 : max(0, $limit - $current);

    $summary[$feature] = [
      'current' => $current,
      'limit' => $limit,
      'remaining' => $remaining,
      'unlimited' => ($limit === -1 || $limit < 0),
      'percent_used' => ($limit > 0) ? round(($current / $limit) * 100, 1) : 0
    ];
  }

  return $summary;
}

// ============================================================================
// 🔧 VALIDATION HELPERS
// ============================================================================

/**
 * Validate purchase data
 * 
 * @param array $data - Purchase data to validate
 * @return array - ['valid' => bool, 'errors' => array]
 */
function validatePurchaseData($data)
{
  $errors = [];

  // Validate tier
  if (empty($data['tier'])) {
    $errors[] = "Tier is required";
  } elseif (!isset(Membership::getTierDefinitions()[$data['tier']])) {
    $errors[] = "Invalid tier specified";
  }

  // Validate billing cycle
  if (empty($data['billing_cycle'])) {
    $errors[] = "Billing cycle is required";
  } elseif (!in_array($data['billing_cycle'], ['monthly', 'yearly'])) {
    $errors[] = "Billing cycle must be 'monthly' or 'yearly'";
  }

  return [
    'valid' => empty($errors),
    'errors' => $errors
  ];
}

/**
 * Validate membership data structure
 * 
 * @param array $content - Membership content JSON
 * @return array - ['valid' => bool, 'errors' => array]
 */
function validateMembershipContent($content)
{
  $errors = [];
  $required_fields = ['membership_tier', 'billing_cycle', 'expires_at', 'features_enabled'];

  foreach ($required_fields as $field) {
    if (!isset($content[$field])) {
      $errors[] = "Missing required field: {$field}";
    }
  }

  return [
    'valid' => empty($errors),
    'errors' => $errors
  ];
}

// ============================================================================
// 📊 REPORTING HELPERS
// ============================================================================

/**
 * Get membership statistics
 * 
 * @return array
 */
function getMembershipStatistics()
{
  $stats = Membership::getTierStatistics();
  $total = 0;
  $formatted = [];

  foreach ($stats as $tier_stat) {
    $tier = $tier_stat['membership_tier'];
    $count = $tier_stat['count'];
    $total += $count;

    $formatted[$tier] = [
      'tier' => $tier,
      'label' => getTierLabel($tier),
      'count' => $count,
      'unique_users' => $tier_stat['unique_users'],
      'percent' => 0  // Will calculate after
    ];
  }

  // Calculate percentages
  foreach ($formatted as &$stat) {
    $stat['percent'] = $total > 0 ? round(($stat['count'] / $total) * 100, 1) : 0;
  }

  return [
    'by_tier' => $formatted,
    'total_memberships' => $total,
    'total_users' => array_sum(array_column($formatted, 'unique_users'))
  ];
}

/**
 * Get expiring memberships summary
 * 
 * @param int $days - Days threshold
 * @return array
 */
function getExpiringMembershipsReport($days = 30)
{
  $expiring = Membership::getExpiringMemberships($days);
  $by_tier = [];
  $by_urgency = [
    'critical' => [],    // 0-3 days
    'soon' => [],         // 4-7 days
    'upcoming' => []      // 8+ days
  ];

  foreach ($expiring as $membership) {
    $tier = $membership->membership_tier;
    if (!isset($by_tier[$tier])) {
      $by_tier[$tier] = [];
    }
    $by_tier[$tier][] = $membership;

    // Categorize by urgency
    $days_left = $membership->days_remaining;
    if ($days_left <= 3) {
      $by_urgency['critical'][] = $membership;
    } elseif ($days_left <= 7) {
      $by_urgency['soon'][] = $membership;
    } else {
      $by_urgency['upcoming'][] = $membership;
    }
  }

  return [
    'total' => count($expiring),
    'by_tier' => $by_tier,
    'by_urgency' => $by_urgency,
    'memberships' => $expiring
  ];
}

