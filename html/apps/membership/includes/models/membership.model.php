<?php

/**
 * 💳 MEMBERSHIP MODEL
 * 
 * Handles user subscription tiers, access control, and feature entitlements.
 * Extends Storage base class and uses the memory_anchors table for persistence.
 * 
 * Each membership tier has features, limits, and pricing.
 * Tracks expiration, renewal, and feature usage.
 */
class Membership extends Storage
{
  // 🎫 ADDITIONAL PROPERTIES
  public $membership_tier;      // 'bronze', 'silver', 'gold', 'platinum', 'enterprise'
  public $started_at;           // When membership began
  public $renewal_date;         // Next billing date
  public $expires_at;           // When membership ends
  public $payment_method;       // 'stripe', 'paypal', 'bank_transfer'
  public $payment_id;           // External payment provider ID
  public $auto_renew = true;    // Auto-renew on expiration
  public $updated_at;           // Last update timestamp

  // 📊 COMPUTED PROPERTIES
  public $days_remaining;
  public $is_trial;
  public $is_active_status;

  /**
   * 🏗️ CONSTRUCTOR: Hydrate membership from DB data
   */
  public function __construct($data = [])
  {
    parent::__construct($data);

    // 🔧 Extract additional fields
    $this->membership_tier = $data['membership_tier'] ?? 'bronze';
    $this->started_at = $data['started_at'] ?? null;
    $this->renewal_date = $data['renewal_date'] ?? null;
    $this->expires_at = $data['expires_at'] ?? null;
    $this->payment_method = $data['payment_method'] ?? null;
    $this->payment_id = $data['payment_id'] ?? null;
    $this->auto_renew = $data['auto_renew'] ?? true;
    $this->updated_at = $data['updated_at'] ?? date('Y-m-d H:i:s');

    // 🧠 Auto JSON Decode content
    if (is_string($this->content)) {
      $this->content = json_decode($this->content, true) ?? [];
    }

    // ⚡ Compute runtime properties
    $this->days_remaining = $this->calculateDaysRemaining();
    $this->is_trial = $this->calculateIsTrial();
    $this->is_active_status = $this->isActive();
  }

  /**
   * 📋 CONFIGURATION: Database table name
   */
  protected static function getTableName()
  {
    return 'memory_anchors';
  }

  /**
   * 🎯 STATUS CONFIGURATION: Allowed membership statuses
   */
  protected static function getStatusValues()
  {
    return [
      'pending'    => 'Pending Payment',
      'active'     => 'Active',
      'expired'    => 'Expired',
      'suspended'  => 'Suspended',
      'cancelled'  => 'Cancelled',
    ];
  }

  protected static function getDefaultStatus()
  {
    return 'pending';
  }

  /**
   * 🛍️ TIER DEFINITIONS
   * Master definition of all membership tiers
   */
  public static function getTierDefinitions()
  {
    return [
      'bronze' => [
        'display_name' => 'Bronze Tier',
        'description' => 'Perfect for getting started',
        'price_monthly' => 9.99,
        'price_yearly' => 99.99,
        'color' => '#CD7F32',
        'features' => [
          'max_anchors' => 100,
          'max_connections' => 500,
          'api_calls_monthly' => 1000,
          'file_storage_gb' => 5,
          'custom_domain' => false,
          'priority_support' => false,
          'team_members' => 1,
        ]
      ],
      'silver' => [
        'display_name' => 'Silver Tier',
        'description' => 'For growing collections',
        'price_monthly' => 19.99,
        'price_yearly' => 199.99,
        'color' => '#C0C0C0',
        'features' => [
          'max_anchors' => 1000,
          'max_connections' => 5000,
          'api_calls_monthly' => 10000,
          'file_storage_gb' => 50,
          'custom_domain' => true,
          'priority_support' => true,
          'team_members' => 3,
        ]
      ],
      'gold' => [
        'display_name' => 'Gold Tier',
        'description' => 'For serious genealogists',
        'price_monthly' => 49.99,
        'price_yearly' => 499.99,
        'color' => '#FFD700',
        'features' => [
          'max_anchors' => 10000,
          'max_connections' => 50000,
          'api_calls_monthly' => 100000,
          'file_storage_gb' => 500,
          'custom_domain' => true,
          'priority_support' => true,
          'team_members' => 10,
          'advanced_analytics' => true,
        ]
      ],
      'platinum' => [
        'display_name' => 'Platinum Tier',
        'description' => 'Unlimited access for organizations',
        'price_monthly' => 99.99,
        'price_yearly' => 999.99,
        'color' => '#E5E4E2',
        'features' => [
          'max_anchors' => -1,          // Unlimited
          'max_connections' => -1,
          'api_calls_monthly' => -1,
          'file_storage_gb' => 2000,
          'custom_domain' => true,
          'priority_support' => true,
          'team_members' => 50,
          'advanced_analytics' => true,
          'dedicated_account_manager' => true,
        ]
      ],
      'enterprise' => [
        'display_name' => 'Enterprise',
        'description' => 'Custom solutions and support',
        'price_monthly' => 0,           // Custom pricing
        'price_yearly' => 0,
        'color' => '#000000',
        'features' => [
          'max_anchors' => -1,
          'max_connections' => -1,
          'api_calls_monthly' => -1,
          'file_storage_gb' => -1,
          'custom_domain' => true,
          'priority_support' => true,
          'team_members' => -1,
          'advanced_analytics' => true,
          'dedicated_account_manager' => true,
          'sso' => true,
          'compliance_support' => true,
        ]
      ],
    ];
  }

  /**
   * ✨ THE CREATION ENGINE
   * Create a new membership for a user
   */
  public static function create($data)
  {
    $db = self::getDb();
    $uuid = self::generateUuid();
    $tier = $data['membership_tier'] ?? 'bronze';
    $billing_cycle = $data['billing_cycle'] ?? 'monthly';
    $now = new DateTime('now');
    $started_at = $now->format('Y-m-d H:i:s');

    // Calculate expiration date
    $expires_at = clone $now;
    if ($billing_cycle === 'yearly') {
      $expires_at->add(new DateInterval('P1Y'));
    } else {
      $expires_at->add(new DateInterval('P1M'));
    }
    $renewal_date = $expires_at->format('Y-m-d H:i:s');
    $expires_at_str = $expires_at->format('Y-m-d H:i:s');

    // Initialize content with tier features and usage tracking
    // All membership-specific data stored in JSON content
    $tier_defs = self::getTierDefinitions();
    $tier_features = $tier_defs[$tier]['features'] ?? [];
    $content = [
      'membership_tier' => $tier,
      'billing_cycle' => $billing_cycle,
      'started_at' => $started_at,
      'renewal_date' => $renewal_date,
      'expires_at' => $expires_at_str,
      'payment_method' => $data['payment_method'] ?? null,
      'payment_id' => $data['payment_id'] ?? null,
      'auto_renew' => $data['auto_renew'] ?? true,
      'updated_at' => $started_at,
      'features_enabled' => $tier_features,
      'usage_tracking' => [
        'api_calls_this_month' => 0,
        'file_storage_gb_used' => 0,
        'anchors_created' => 0,
        'connections_created' => 0,
      ],
      'upgrade_history' => [],
      'notes' => $data['notes'] ?? '',
    ];

    $stmt = $db->prepare("
      INSERT INTO memory_anchors 
      (uuid, architect_id, content_type, content, status, created_at)
      VALUES (?, ?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
      $uuid,
      $data['architect_id'] ?? null,
      'membership',
      json_encode($content),
      $data['status'] ?? 'pending',
      $started_at
    ]);

    return $success ? $uuid : false;
  }

  /**
   * 📦 GET BY ARCHITECT ID
   * Retrieve all memberships for a user
   */
  public static function getByArchitectId($architect_id, $active_only = true)
  {
    $db = self::getDb();

    $where = "architect_id = ? AND content_type = 'membership'";
    $binds = [$architect_id];

    if ($active_only) {
      $where .= " AND status = 'active'";
    }

    return self::query([
      'where' => $where,
      'binds' => $binds,
      'order_by' => 'expires_at DESC',
      'limit' => 0  // No limit
    ]);
  }

  /**
   * 🟢 GET ACTIVE MEMBERSHIP
   * Get current active membership for user (or null)
   */
  public static function getActive($architect_id = 0)
  {
    $row = self::query([
      'where' => 'architect_id = ? AND content_type = ? AND status = ?',
      'binds' => [$architect_id, 'membership', 'active'],
      'order_by' => 'created_at DESC',
      'limit' => 1
    ]);

    if ($row) {
      $membership = new self($row);
      // Verify not expired (check in content)
      if ($membership->expires_at && strtotime($membership->expires_at) > time()) {
        return $membership;
      }
    }

    return $row;
  }

  /**
   * ✅ CHECK: Is membership active?
   * Verify status is 'active' AND not expired
   */
  public function isActive()
  {
    return $this->status === 'active' && 
           $this->expires_at && 
           strtotime($this->expires_at) > time();
  }

  /**
   * ⏰ CHECK: Is membership expired?
   * Verify expires_at is in the past or status is 'expired'
   */
  public function isExpired()
  {
    return $this->status === 'expired' || 
           (!is_null($this->expires_at) && strtotime($this->expires_at) <= time());
  }

  /**
   * ⚠️ CHECK: Can renew?
   * Membership is expired or expiring soon
   */
  public function canRenew($days_threshold = 30)
  {
    if ($this->status === 'cancelled') {
      return false;
    }

    if ($this->status === 'expired') {
      return true;
    }

    return $this->days_remaining <= $days_threshold;
  }

  /**
   * ⏳ CHECK: Is expiring soon?
   * Will expire within N days
   */
  public function isExpiringSoon($days_threshold = 14)
  {
    return $this->days_remaining > 0 && $this->days_remaining <= $days_threshold;
  }

  /**
   * 🔍 CHECK: Has feature available?
   * Verify feature is enabled in tier
   */
  public function hasFeature($feature_name)
  {
    $features = $this->content['features_enabled'] ?? [];
    return isset($features[$feature_name]) && $features[$feature_name] !== false;
  }

  /**
   * 📊 GET: Feature limit for tier
   * Return numeric limit (or -1 for unlimited)
   */
  public function getFeatureLimit($feature_name)
  {
    $features = $this->content['features_enabled'] ?? [];
    return $features[$feature_name] ?? 0;
  }

  /**
   * ✅ CHECK: Has quota available?
   * Compare usage against tier limit
   */
  public function checkFeatureQuota($feature_name, $current_usage)
  {
    $limit = $this->getFeatureLimit($feature_name);

    // -1 means unlimited
    if ($limit === -1 || $limit < 0) {
      return true;
    }

    return $current_usage < $limit;
  }

  /**
   * 🎯 ACTION: Can user perform action?
   * Validates tier access + quota
   */
  public function canPerformAction($action, $required_feature = null)
  {
    // Must be active
    if (!$this->isActive()) {
      return ['allowed' => false, 'reason' => 'Membership expired or inactive'];
    }

    // Check feature if specified
    if ($required_feature && !$this->hasFeature($required_feature)) {
      return ['allowed' => false, 'reason' => "Feature '{$required_feature}' not available in {$this->membership_tier} tier"];
    }

    return ['allowed' => true];
  }

  /**
   * 📈 USAGE: Track feature usage
   * Increment usage counter for a feature
   */
  public function incrementFeatureUsage($feature, $amount = 1)
  {
    if (!isset($this->content['usage_tracking'][$feature])) {
      $this->content['usage_tracking'][$feature] = 0;
    }

    $this->content['usage_tracking'][$feature] += $amount;

    return self::updateByUuid($this->uuid, [
      'content' => $this->content
    ]);
  }

  /**
   * 📊 USAGE: Get current usage
   * Return current usage for specific feature
   */
  public function getCurrentUsage($feature)
  {
    $usage = $this->content['usage_tracking'] ?? [];
    return $usage[$feature] ?? 0;
  }

  /**
   * 🔢 USAGE: Get remaining quota
   * Calculate remaining quota for feature
   */
  public function getRemainingQuota($feature)
  {
    $limit = $this->getFeatureLimit($feature);
    $current = $this->getCurrentUsage($feature);

    // Unlimited
    if ($limit === -1 || $limit < 0) {
      return -1;
    }

    return max(0, $limit - $current);
  }

  /**
   * 🔄 ACTION: Renew membership
   * Extend renewal_date and expires_at
   */
  public function renew($payment_data = null)
  {
    $billing_cycle = $this->content['billing_cycle'] ?? 'monthly';
    $new_renewal = new DateTime($this->renewal_date);

    if ($billing_cycle === 'yearly') {
      $new_renewal->add(new DateInterval('P1Y'));
    } else {
      $new_renewal->add(new DateInterval('P1M'));
    }

    $new_renewal_str = $new_renewal->format('Y-m-d H:i:s');

    // Reset usage counters for new billing period
    $this->content['usage_tracking'] = [
      'api_calls_this_month' => 0,
      'file_storage_gb_used' => 0,
      'anchors_created' => 0,
      'connections_created' => 0,
    ];
    $this->content['renewal_date'] = $new_renewal_str;
    $this->content['expires_at'] = $new_renewal_str;
    $this->content['updated_at'] = date('Y-m-d H:i:s');

    return self::updateByUuid($this->uuid, [
      'status' => 'active',
      'content' => $this->content
    ]);
  }

  /**
   * ❌ ACTION: Cancel membership
   * Set status to 'cancelled' (terminal state)
   */
  public function cancel()
  {
    $this->content['renewal_date'] = null;
    $this->content['updated_at'] = date('Y-m-d H:i:s');

    return self::updateByUuid($this->uuid, [
      'status' => 'cancelled',
      'content' => $this->content
    ]);
  }

  /**
   * ⏸️ ACTION: Suspend membership
   * Set status to 'suspended' (temporary hold)
   */
  public function suspend()
  {
    $this->content['updated_at'] = date('Y-m-d H:i:s');

    return self::updateByUuid($this->uuid, [
      'status' => 'suspended',
      'content' => $this->content
    ]);
  }

  /**
   * ▶️ ACTION: Reactivate suspended membership
   * Set status back to 'active'
   */
  public function reactivate()
  {
    if ($this->status !== 'suspended') {
      return false;
    }

    $this->content['updated_at'] = date('Y-m-d H:i:s');

    return self::updateByUuid($this->uuid, [
      'status' => 'active',
      'content' => $this->content
    ]);
  }

  /**
   * 📊 TIER: Get current tier object
   * Return full tier definition with all features/pricing
   */
  public function getCurrentTier()
  {
    $defs = self::getTierDefinitions();
    return $defs[$this->membership_tier] ?? $defs['bronze'];
  }

  /**
   * 📈 TIER: Get available upgrades
   * Return array of tiers higher than current
   */
  public function getAvailableUpgrades()
  {
    $all_tiers = ['bronze', 'silver', 'gold', 'platinum', 'enterprise'];
    $current_index = array_search($this->membership_tier, $all_tiers);

    if ($current_index === false) {
      return [];
    }

    $upgrades = [];
    $tier_defs = self::getTierDefinitions();

    for ($i = $current_index + 1; $i < count($all_tiers); $i++) {
      $tier_name = $all_tiers[$i];
      $upgrades[$tier_name] = $tier_defs[$tier_name];
    }

    return $upgrades;
  }

  /**
   * 🚀 TIER: Upgrade to higher tier
   * Change membership tier and handle proration
   */
  public function upgradeTier($new_tier, $payment_data = null)
  {
    // Verify upgrade is valid
    $available = $this->getAvailableUpgrades();
    if (!isset($available[$new_tier])) {
      return ['success' => false, 'reason' => 'Invalid upgrade tier'];
    }

    // Record upgrade in history
    if (!isset($this->content['upgrade_history'])) {
      $this->content['upgrade_history'] = [];
    }

    $this->content['upgrade_history'][] = [
      'from_tier' => $this->membership_tier,
      'to_tier' => $new_tier,
      'date' => date('Y-m-d'),
      'proration_credit' => 0  // Calculate as needed
    ];

    $this->content['membership_tier'] = $new_tier;
    $this->content['updated_at'] = date('Y-m-d H:i:s');
    $this->membership_tier = $new_tier;

    $success = self::updateByUuid($this->uuid, [
      'content' => $this->content
    ]);

    return ['success' => $success, 'new_tier' => $new_tier];
  }

  /**
   * 📉 TIER: Downgrade to lower tier
   * Change membership tier on renewal or immediately
   */
  public function downgradeTier($new_tier, $effective_date = 'renewal')
  {
    $all_tiers = ['bronze', 'silver', 'gold', 'platinum', 'enterprise'];
    $current_index = array_search($this->membership_tier, $all_tiers);
    $new_index = array_search($new_tier, $all_tiers);

    if ($current_index === false || $new_index === false || $new_index >= $current_index) {
      return ['success' => false, 'reason' => 'Invalid downgrade'];
    }

    $this->content['updated_at'] = date('Y-m-d H:i:s');

    if ($effective_date === 'renewal') {
      // Apply at next renewal
      $this->content['pending_downgrade'] = $new_tier;
    } else {
      // Apply immediately
      $this->content['membership_tier'] = $new_tier;
      $this->membership_tier = $new_tier;
    }

    $success = self::updateByUuid($this->uuid, [
      'content' => $this->content
    ]);

    return ['success' => $success, 'effective' => $effective_date];
  }

  /**
   * 📊 QUERY: Get expiring memberships
   * Find memberships expiring within N days
   */
  public static function getExpiringMemberships($days = 30)
  {
    $db = self::getDb();
    $future_date = (new DateTime())
      ->add(new DateInterval("P{$days}D"))
      ->format('Y-m-d H:i:s');
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
      SELECT * FROM memory_anchors 
      WHERE content_type = 'membership' 
      AND status = 'active'
      ORDER BY created_at DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
      $membership = new self($row);
      // Filter by expiration date in JSON content
      if ($membership->expires_at && $membership->expires_at <= $future_date && $membership->expires_at > $now) {
        $results[] = $membership;
      }
    }

    return $results;
  }

  /**
   * 📊 QUERY: Get expired memberships
   * Find all memberships past expiration date
   */
  public static function getExpiredMemberships()
  {
    $db = self::getDb();
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
      SELECT * FROM memory_anchors 
      WHERE content_type = 'membership' 
      ORDER BY created_at DESC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $row) {
      $membership = new self($row);
      // Filter expired memberships from JSON content
      if ($membership->status === 'expired' || ($membership->expires_at && $membership->expires_at <= $now)) {
        $results[] = $membership;
      }
    }

    return $results;
  }

  /**
   * 📊 QUERY: Get tier statistics
   * Return count of active members per tier
   */
  public static function getTierStatistics()
  {
    $db = self::getDb();
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
      SELECT * FROM memory_anchors 
      WHERE content_type = 'membership' 
      AND status = 'active'
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [];
    $tiers = ['bronze', 'silver', 'gold', 'platinum', 'enterprise'];

    foreach ($tiers as $tier) {
      $stats[$tier] = ['count' => 0, 'unique_users' => []];
    }

    foreach ($rows as $row) {
      $membership = new self($row);
      // Check if not expired
      if ($membership->expires_at && $membership->expires_at > $now) {
        $tier = $membership->membership_tier ?? 'bronze';
        if (isset($stats[$tier])) {
          $stats[$tier]['count']++;
          $stats[$tier]['unique_users'][] = $membership->architect_id;
        }
      }
    }

    // Convert to result format
    $results = [];
    foreach ($stats as $tier => $data) {
      $results[] = [
        'membership_tier' => $tier,
        'count' => $data['count'],
        'unique_users' => count(array_unique($data['unique_users']))
      ];
    }

    return $results;
  }

  /**
   * 💰 BATCH: Process expirations
   * Expire all memberships past their expiration date
   */
  public static function processExpirations()
  {
    $db = self::getDb();
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
      SELECT * FROM memory_anchors 
      WHERE content_type = 'membership' 
      AND status = 'active'
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expired_count = 0;
    foreach ($rows as $row) {
      $membership = new self($row);
      // Check if expires_at is in the past
      if ($membership->expires_at && $membership->expires_at <= $now) {
        $membership->content['updated_at'] = $now;
        self::updateByUuid($membership->uuid, [
          'status' => 'expired',
          'content' => $membership->content
        ]);
        $expired_count++;
      }
    }

    return $expired_count;
  }

  /**
   * 📧 BATCH: Send renewal reminders
   * Notify users with memberships expiring soon
   */
  public static function sendRenewalReminders($days_before = 7)
  {
    $expiring = self::getExpiringMemberships($days_before);
    $count = 0;

    foreach ($expiring as $membership) {
      // 📧 Send email notification
      // This would integrate with your email service
      // $this->sendEmail($membership->architect_id, 'membership-expiring-soon');
      $count++;
    }

    return ['reminder_count' => $count];
  }

  /**
   * ⏳ HELPER: Calculate days remaining
   */
  private function calculateDaysRemaining()
  {
    if (!$this->expires_at) {
      return 0;
    }

    $now = new DateTime();
    $expiry = new DateTime($this->expires_at);
    $diff = $expiry->diff($now);

    return $diff->invert ? $diff->days : -$diff->days;
  }

  /**
   * 🎁 HELPER: Calculate if trial period
   */
  private function calculateIsTrial()
  {
    if (!$this->started_at) {
      return false;
    }

    $now = new DateTime();
    $started = new DateTime($this->started_at);
    $diff = $now->diff($started);

    return $diff->days < 14;
  }

  /**
   * 📅 HELPER: Get formatted date
   */
  public function getFormattedDate($format = 'M j, Y')
  {
    if (!$this->expires_at) {
      return "No expiration";
    }
    return date($format, strtotime($this->expires_at));
  }

  /**
   * 🔄 HELPER: Get days remaining
   */
  public function getDaysRemaining()
  {
    return $this->days_remaining;
  }

  /**
   * 📅 HELPER: Get expiration date
   */
  public function getExpirationDate($format = 'M j, Y')
  {
    if (!$this->expires_at) {
      return "Unknown";
    }
    return date($format, strtotime($this->expires_at));
  }

  /**
   * 🧪 DEBUG: Convert to array
   */
  public function toArray()
  {
    return [
      'id' => $this->id,
      'uuid' => $this->uuid,
      'architect_id' => $this->architect_id,
      'membership_tier' => $this->membership_tier,
      'content_type' => $this->content_type,
      'content' => $this->content,
      'started_at' => $this->started_at,
      'renewal_date' => $this->renewal_date,
      'expires_at' => $this->expires_at,
      'status' => $this->status,
      'payment_method' => $this->payment_method,
      'payment_id' => $this->payment_id,
      'auto_renew' => $this->auto_renew,
      'days_remaining' => $this->days_remaining,
      'is_active' => $this->is_active_status,
      'created_at' => $this->created_at,
    ];
  }
}
