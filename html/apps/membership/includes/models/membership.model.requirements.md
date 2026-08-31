# 💳 MEMBERSHIP MODEL REQUIREMENTS

## 🎯 Overview
The Membership model handles user subscription tiers, access control, and feature entitlements. Built on the Storage base class pattern, it manages membership purchases, renewals, expiration, and access verification.

---

## 📊 DATABASE SCHEMA

### Uses `memory_anchors` Table
The Membership model stores all data in the existing `memory_anchors` table:

- **content_type**: Always `'membership'`
- **content**: JSON object containing all membership-specific data (see Content JSON Structure)
- **architect_id**: User ID (inherited from Storage)
- **status**: 'active', 'expired', 'suspended', 'cancelled', 'pending'
- **created_at**: When membership record was created (inherited from Storage)
- **uuid**: Unique identifier (inherited from Storage)

All membership-specific fields are stored in the JSON `content` column to maintain compatibility with the existing table structure.

---

## 🏛️ MEMBERSHIP TIERS STRUCTURE

Each tier has features, limits, and pricing:

```php
TIER_DEFINITIONS = [
  'bronze' => [
    'display_name' => 'Bronze Tier',
    'price_monthly' => 9.99,
    'price_yearly' => 99.99,
    'features' => [
      'max_anchors' => 100,
      'max_connections' => 500,
      'api_calls_monthly' => 1000,
      'file_storage_gb' => 5,
      'custom_domain' => false,
      'priority_support' => false,
    ]
  ],
  'silver' => [
    'display_name' => 'Silver Tier',
    'price_monthly' => 19.99,
    'price_yearly' => 199.99,
    'features' => [
      'max_anchors' => 1000,
      'max_connections' => 5000,
      'api_calls_monthly' => 10000,
      'file_storage_gb' => 50,
      'custom_domain' => true,
      'priority_support' => true,
    ]
  ],
  // ... gold, platinum, enterprise
]
```

---

## 🔑 CLASS PROPERTIES

```php
public $id;                    // Primary key
public $uuid;                  // Unique identifier
public $architect_id;          // User who owns membership
public $membership_tier;       // Current tier level
public $content_type;          // Always 'membership'
public $content;               // JSON: tier features, custom data
public $started_at;            // When membership began
public $renewal_date;          // Next billing date
public $expires_at;            // Expiration timestamp
public $status;                // 'active', 'expired', 'suspended', 'cancelled', 'pending'
public $created_at;            // When record created
public $updated_at;            // When record last updated
public $payment_method;        // Payment provider
public $payment_id;            // External transaction ID
public $auto_renew;            // Boolean: auto-renew on expiration
public $days_remaining;        // Computed property (calculated)
public $is_trial;              // Computed property (if started < 14 days ago)
```

---

## ✨ CORE METHODS

### 🏗️ CRUD Operations (Inherited from Storage)
- `create($data)` - Create new membership
- `getById($id)` - Fetch by primary key
- `getByUuid($uuid)` - Fetch by UUID
- `update($id, $data)` - Update membership
- `delete($id)` - Soft delete (archive)
- `hardDelete($id)` - Permanent deletion

### 🎫 MEMBERSHIP-SPECIFIC METHODS

#### Purchase & Subscription
```php
public static function purchase($architect_id, $tier, $billing_cycle = 'monthly', $payment_data)
  // Create new membership; handle payment integration
  
public function renew($payment_data = null)
  // Extend membership renewal_date and expires_at
  
public function cancel()
  // Set status to 'cancelled' and clear renewal_date
  
public function suspend()
  // Set status to 'suspended' (temporary hold)
  
public function reactivate()
  // Set status to 'active' if previously suspended
```

#### Access Verification
```php
public function isActive()
  // Check if status is 'active' AND expires_at > NOW
  
public function isExpired()
  // Check if expires_at <= NOW or status is 'expired'
  
public function canRenew()
  // Check if status is 'expired' or expires_at < 30 days away
  
public function hasFeature($feature_name)
  // Check if tier includes specific feature (e.g., 'custom_domain')
  
public function canPerformAction($action, $required_feature = null)
  // Validate user can perform action based on tier & features
  
public function getFeatureLimit($feature_name)
  // Get numeric limit for feature (e.g., max_anchors: 100)
  
public function checkFeatureQuota($feature_name, $current_usage)
  // Compare current_usage against tier limit; return boolean
```

#### Tier & Upgrade Management
```php
public function getCurrentTier()
  // Return tier object with all features/pricing
  
public function getAvailableUpgrades()
  // Return array of tiers higher than current
  
public function upgradeTier($new_tier, $payment_data)
  // Upgrade to higher tier; handle prorated pricing
  
public function downgradeTier($new_tier, $effective_date = 'renewal')
  // Downgrade on next renewal or immediately
  
public static function getTierComparison($tier1, $tier2)
  // Compare two tiers; return feature differences
```

#### Expiration & Renewal Logic
```php
public function getDaysRemaining()
  // Return int: days until expires_at
  
public function getExpirationDate($format = 'M j, Y')
  // Return formatted expiration date
  
public function isExpiringSoon($days_threshold = 14)
  // Check if expires_at < NOW + $days_threshold
  
public function autoRenewIfEligible()
  // Check auto_renew flag; process renewal if eligible
  
public static function processExpirations()
  // Batch job: expire all memberships past expires_at
  
public static function sendRenewalReminders($days_before = 7)
  // Send emails to users expiring soon
```

#### Usage Tracking & Quotas
```php
public function incrementFeatureUsage($feature, $amount = 1)
  // Track cumulative feature usage (API calls, storage, etc)
  
public function getCurrentUsage($feature)
  // Get current usage for specific feature
  
public function getRemainingQuota($feature)
  // Get remaining quota for feature
  
public function hasQuotaAvailable($feature, $requested = 1)
  // Check if feature quota available before action
```

#### Query & Reporting
```php
public static function getByArchitectId($architect_id)
  // Get all memberships for user (filter active by default)
  
public static function getExpiringMemberships($days = 30)
  // Find memberships expiring within N days
  
public static function getExpiredMemberships()
  // Find all expired memberships
  
public static function getTierStatistics()
  // Return count of active members per tier
  
public static function getRevenueReport($start_date, $end_date)
  // Aggregate revenue by tier and period
```

---

## 🛡️ HELPER FUNCTIONS (Standalone Utilities)

```php
/**
 * Check if user has specific membership tier
 * @param int $architect_id
 * @param string|array $required_tier - Tier(s) required
 * @return bool
 */
function userHasMembership($architect_id, $required_tier = null) { }

/**
 * Get active membership for user (or null)
 * @param int $architect_id
 * @return Membership|null
 */
function getActiveMembership($architect_id) { }

/**
 * Check if user can perform action (tier check)
 * @param int $architect_id
 * @param string $required_feature
 * @return bool
 */
function userCanAccess($architect_id, $required_feature) { }

/**
 * Enforce membership access or redirect to upgrade page
 * @param int $architect_id
 * @param string $required_feature
 * @param string $redirect_url
 * @return void (exits if denied)
 */
function enforceFeatureAccess($architect_id, $required_feature, $redirect_url = '/upgrade') { }

/**
 * Get human-readable tier label
 * @param string $tier
 * @return string
 */
function getTierLabel($tier) { }

/**
 * Get all features available to tier
 * @param string $tier
 * @return array
 */
function getTierFeatures($tier) { }

/**
 * Format price for display
 * @param float $price
 * @param string $currency = 'USD'
 * @return string
 */
function formatPrice($price, $currency = 'USD') { }

/**
 * Calculate days until expiration
 * @param string $expires_at (timestamp)
 * @return int
 */
function daysUntilExpiry($expires_at) { }

/**
 * Check if membership requires immediate renewal
 * @param Membership $membership
 * @return bool
 */
function needsImmediateRenewal($membership) { }
```

---

## 🔄 STATUS TRANSITIONS

```
        ┌─────────┐
        │ Pending │  (Payment processing)
        └────┬────┘
             │
             ▼
        ┌─────────┐
   ┌───►│ Active  │◄───┐
   │    └────┬────┘    │
   │         │         │ (Reactivate)
   │         │         │
   │    ┌────▼────┐    │
   └────┤ Suspended├────┘
        └────┬────┘
             │
             ▼
        ┌──────────┐
        │ Expired  │  (Auto-archived after 90 days)
        └────┬─────┘
             │
             ▼
        ┌──────────┐
        │ Cancelled│  (Terminal state)
        └──────────┘
```

---

## 📝 CONTENT JSON STRUCTURE

All membership data is stored as JSON in the `content` column:

```json
{
  "membership_tier": "silver",
  "billing_cycle": "monthly",
  "started_at": "2026-05-02T10:30:00",
  "renewal_date": "2026-06-02T10:30:00",
  "expires_at": "2026-06-02T10:30:00",
  "payment_method": "stripe",
  "payment_id": "pi_1234567890",
  "auto_renew": true,
  "updated_at": "2026-05-02T10:30:00",
  "features_enabled": {
    "max_anchors": 1000,
    "max_connections": 5000,
    "api_calls_monthly": 10000,
    "file_storage_gb": 50,
    "custom_domain": true,
    "priority_support": true,
    "team_members": 3
  },
  "usage_tracking": {
    "api_calls_this_month": 5234,
    "file_storage_gb_used": 23.5,
    "anchors_created": 47,
    "connections_created": 12
  },
  "upgrade_history": [
    {
      "from_tier": "bronze",
      "to_tier": "silver",
      "date": "2026-05-01",
      "proration_credit": 12.50
    }
  ],
  "notes": "Premium customer, enterprise discount applied"
}
```

---

## 🔗 INTEGRATION POINTS

- **Payment Gateway**: Stripe, PayPal integration
- **Access Control**: Feature gate checks before allowing actions
- **Quotas**: Track API usage, storage, anchor creation limits
- **Notifications**: Email reminders, invoice delivery
- **Analytics**: Track tier adoption, churn rate, revenue trends

---

## 🧪 TESTING SCENARIOS

- [ ] Create membership for new user
- [ ] Verify access to tier-specific features
- [ ] Test tier upgrade with proration
- [ ] Test membership expiration and renewal
- [ ] Verify quota enforcement
- [ ] Test cancellation and reactivation
- [ ] Batch expiration processing
- [ ] Revenue reporting by tier
- [ ] Auto-renew payment failure handling
- [ ] Downgrade with warning period

