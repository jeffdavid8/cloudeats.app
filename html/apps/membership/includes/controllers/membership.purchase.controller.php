<?php

/**
 * 💳 MEMBERSHIP PURCHASE CONTROLLER
 * 
 * Handles membership purchases, upgrades, renewals, and payment processing.
 * Integrates with payment gateways (Stripe, PayPal) and manages subscription lifecycle.
 */
class MembershipPurchaseController
{
  private $db;
  private $user_id;
  private $payment_provider = 'stripe';  // Default: can be 'stripe', 'paypal', 'bank_transfer'

  public function __construct($db, $user_id)
  {
    $this->db = $db;
    $this->user_id = $user_id;
  }

  /**
   * 🛒 PURCHASE: New membership purchase
   * Handles full purchase flow: validation, payment, membership creation
   */
  public function purchaseMembership($tier, $billing_cycle = 'monthly', $payment_data = [])
  {
    try {
      // ✅ Validate purchase request
      $validation = $this->validatePurchase($tier, $billing_cycle);
      if (!$validation['valid']) {
        return $validation;
      }

      // 💰 Process payment
      $payment_result = $this->processPayment($tier, $billing_cycle, $payment_data);
      if (!$payment_result['success']) {
        return $payment_result;
      }

      // 🎫 Create membership record
      $membership_uuid = Membership::create([
        'architect_id' => $this->user_id,
        'membership_tier' => $tier,
        'billing_cycle' => $billing_cycle,
        'status' => 'active',
        'payment_method' => $this->payment_provider,
        'payment_id' => $payment_result['payment_id'],
        'auto_renew' => true,
        'notes' => "Purchase via {$this->payment_provider}"
      ]);

      if (!$membership_uuid) {
        return [
          'success' => false,
          'error' => 'Failed to create membership record',
          'payment_id' => $payment_result['payment_id']
        ];
      }

      // 📧 Send confirmation email
      $this->sendConfirmationEmail($membership_uuid, $tier, $billing_cycle);

      return [
        'success' => true,
        'membership_uuid' => $membership_uuid,
        'payment_id' => $payment_result['payment_id'],
        'tier' => $tier,
        'message' => "Welcome to {$tier} tier! Your membership is now active."
      ];
    } catch (Exception $e) {
      error_log("Purchase Error: " . $e->getMessage());
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * 📈 UPGRADE: Upgrade to higher tier
   * Handles tier upgrade with prorated pricing
   */
  public function upgradeMembership($new_tier)
  {
    try {
      // 🔍 Get current membership
      $current = Membership::getActive($this->user_id);
      if (!$current) {
        return ['success' => false, 'error' => 'No active membership found'];
      }

      // ✅ Validate upgrade
      $validation = $this->validateUpgrade($current, $new_tier);
      if (!$validation['valid']) {
        return $validation;
      }

      // 💰 Calculate prorated cost
      $proration = $this->calculateProration($current, $new_tier);

      // 💳 Process payment for difference
      if ($proration['amount_due'] > 0) {
        $payment_result = $this->processPayment($new_tier, $current->content['billing_cycle'], [
          'amount' => $proration['amount_due'],
          'description' => "Upgrade from {$current->membership_tier} to {$new_tier}"
        ]);

        if (!$payment_result['success']) {
          return $payment_result;
        }
      }

      // 🚀 Apply upgrade
      $upgrade_result = $current->upgradeTier($new_tier, [
        'payment_id' => $payment_result['payment_id'] ?? null,
        'proration_credit' => $proration['credit']
      ]);

      if (!$upgrade_result['success']) {
        return ['success' => false, 'error' => 'Failed to upgrade membership'];
      }

      // 📧 Send upgrade confirmation
      $this->sendUpgradeConfirmationEmail($current->uuid, $new_tier, $proration);

      return [
        'success' => true,
        'from_tier' => $current->membership_tier,
        'to_tier' => $new_tier,
        'amount_charged' => $proration['amount_due'],
        'credit_applied' => $proration['credit'],
        'message' => "Successfully upgraded to {$new_tier} tier!"
      ];
    } catch (Exception $e) {
      error_log("Upgrade Error: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * 📉 DOWNGRADE: Schedule downgrade for next renewal
   * Downgrades to lower tier on next renewal date
   */
  public function downgradeMembership($new_tier)
  {
    try {
      // 🔍 Get current membership
      $current = Membership::getActive($this->user_id);
      if (!$current) {
        return ['success' => false, 'error' => 'No active membership found'];
      }

      // ✅ Validate downgrade
      $validation = $this->validateDowngrade($current, $new_tier);
      if (!$validation['valid']) {
        return $validation;
      }

      // 📉 Schedule downgrade for next renewal
      $result = $current->downgradeTier($new_tier, 'renewal');

      if (!$result['success']) {
        return ['success' => false, 'error' => 'Failed to schedule downgrade'];
      }

      // 📧 Send downgrade confirmation
      $this->sendDowngradeConfirmationEmail($current->uuid, $new_tier, $current->renewal_date);

      return [
        'success' => true,
        'current_tier' => $current->membership_tier,
        'downgrade_tier' => $new_tier,
        'effective_date' => $current->renewal_date,
        'message' => "Downgrade scheduled for {$current->renewal_date}. You'll be switched to {$new_tier} tier on renewal."
      ];
    } catch (Exception $e) {
      error_log("Downgrade Error: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * 🔄 RENEW: Renew expiring membership
   * Process renewal payment and extend membership
   */
  public function renewMembership($membership_uuid = null)
  {
    try {
      // 🔍 Get membership to renew
      if ($membership_uuid) {
        $membership = Membership::getByUuid($membership_uuid);
      } else {
        $membership = Membership::getActive($this->user_id);
      }

      if (!$membership) {
        return ['success' => false, 'error' => 'Membership not found'];
      }

      // ✅ Verify eligible for renewal
      if (!$membership->canRenew()) {
        return ['success' => false, 'error' => 'Membership not eligible for renewal yet'];
      }

      // 💰 Calculate renewal cost
      $tier_defs = Membership::getTierDefinitions();
      $tier_info = $tier_defs[$membership->membership_tier];
      $billing_cycle = $membership->content['billing_cycle'] ?? 'monthly';
      $amount = ($billing_cycle === 'yearly') ? $tier_info['price_yearly'] : $tier_info['price_monthly'];

      // 💳 Process renewal payment
      $payment_result = $this->processPayment($membership->membership_tier, $billing_cycle, [
        'amount' => $amount,
        'description' => "Renewal for {$membership->membership_tier} tier"
      ]);

      if (!$payment_result['success']) {
        return $payment_result;
      }

      // 🔄 Apply renewal
      $membership->content['payment_id'] = $payment_result['payment_id'];
      $renewal_result = $membership->renew();

      if (!$renewal_result) {
        return ['success' => false, 'error' => 'Failed to renew membership'];
      }

      // 📧 Send renewal confirmation
      $this->sendRenewalConfirmationEmail($membership->uuid);

      return [
        'success' => true,
        'tier' => $membership->membership_tier,
        'amount_charged' => $amount,
        'expires_at' => $membership->renewal_date,
        'payment_id' => $payment_result['payment_id'],
        'message' => 'Membership renewed successfully!'
      ];
    } catch (Exception $e) {
      error_log("Renewal Error: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * ❌ CANCEL: Cancel membership
   * Immediately cancels membership
   */
  public function cancelMembership()
  {
    try {
      // 🔍 Get current membership
      $membership = Membership::getActive($this->user_id);
      if (!$membership) {
        return ['success' => false, 'error' => 'No active membership found'];
      }

      // ❌ Cancel membership
      $result = $membership->cancel();

      if (!$result) {
        return ['success' => false, 'error' => 'Failed to cancel membership'];
      }

      // 📧 Send cancellation confirmation
      $this->sendCancellationConfirmationEmail($membership->uuid);

      return [
        'success' => true,
        'tier' => $membership->membership_tier,
        'message' => 'Membership cancelled. You can rejoin anytime.'
      ];
    } catch (Exception $e) {
      error_log("Cancellation Error: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * ✅ VALIDATE: Purchase validation
   * Checks if purchase is valid
   */
  private function validatePurchase($tier, $billing_cycle)
  {
    // Check tier is valid
    $tier_defs = Membership::getTierDefinitions();
    if (!isset($tier_defs[$tier])) {
      return ['valid' => false, 'error' => 'Invalid membership tier'];
    }

    // Check billing cycle
    if (!in_array($billing_cycle, ['monthly', 'yearly'])) {
      return ['valid' => false, 'error' => 'Invalid billing cycle'];
    }

    // Check for existing active membership
    $existing = Membership::getActive($this->user_id);
    if ($existing) {
      return ['valid' => false, 'error' => 'User already has active membership'];
    }

    return ['valid' => true];
  }

  /**
   * ✅ VALIDATE: Upgrade validation
   */
  private function validateUpgrade($current, $new_tier)
  {
    // Check tier is valid
    $tier_defs = Membership::getTierDefinitions();
    if (!isset($tier_defs[$new_tier])) {
      return ['valid' => false, 'error' => 'Invalid tier'];
    }

    // Verify upgrade is actually an upgrade
    $all_tiers = ['bronze', 'silver', 'gold', 'platinum', 'enterprise'];
    $current_idx = array_search($current->membership_tier, $all_tiers);
    $new_idx = array_search($new_tier, $all_tiers);

    if ($new_idx <= $current_idx) {
      return ['valid' => false, 'error' => 'Can only upgrade to higher tier'];
    }

    return ['valid' => true];
  }

  /**
   * ✅ VALIDATE: Downgrade validation
   */
  private function validateDowngrade($current, $new_tier)
  {
    // Check tier is valid
    $tier_defs = Membership::getTierDefinitions();
    if (!isset($tier_defs[$new_tier])) {
      return ['valid' => false, 'error' => 'Invalid tier'];
    }

    // Verify downgrade is actually a downgrade
    $all_tiers = ['bronze', 'silver', 'gold', 'platinum', 'enterprise'];
    $current_idx = array_search($current->membership_tier, $all_tiers);
    $new_idx = array_search($new_tier, $all_tiers);

    if ($new_idx >= $current_idx) {
      return ['valid' => false, 'error' => 'Can only downgrade to lower tier'];
    }

    return ['valid' => true];
  }

  /**
   * 💰 PAYMENT: Process payment with payment provider
   * Integrates with Stripe, PayPal, or bank transfer
   */
  private function processPayment($tier, $billing_cycle, $payment_data)
  {
    try {
      $tier_defs = Membership::getTierDefinitions();
      $tier_info = $tier_defs[$tier];
      $amount = $payment_data['amount'] ?? (($billing_cycle === 'yearly') ? $tier_info['price_yearly'] : $tier_info['price_monthly']);

      switch ($this->payment_provider) {
        case 'stripe':
          return $this->processStripePayment($tier, $amount, $payment_data);
        case 'paypal':
          return $this->processPayPalPayment($tier, $amount, $payment_data);
        case 'bank_transfer':
          return $this->processBankTransfer($tier, $amount, $payment_data);
        default:
          return ['success' => false, 'error' => 'Unsupported payment provider'];
      }
    } catch (Exception $e) {
      error_log("Payment Processing Error: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * 💳 STRIPE: Process Stripe payment
   * Integrate with Stripe API
   */
  private function processStripePayment($tier, $amount, $payment_data)
  {
    // TODO: Implement Stripe integration
    // For now, simulate successful payment
    $payment_id = 'pi_' . bin2hex(random_bytes(12));

    return [
      'success' => true,
      'payment_id' => $payment_id,
      'provider' => 'stripe',
      'amount' => $amount
    ];
  }

  /**
   * 💳 PAYPAL: Process PayPal payment
   * Integrate with PayPal API
   */
  private function processPayPalPayment($tier, $amount, $payment_data)
  {
    // TODO: Implement PayPal integration
    $payment_id = 'pp_' . bin2hex(random_bytes(12));

    return [
      'success' => true,
      'payment_id' => $payment_id,
      'provider' => 'paypal',
      'amount' => $amount
    ];
  }

  /**
   * 🏦 BANK TRANSFER: Process bank transfer
   * Create pending transfer record
   */
  private function processBankTransfer($tier, $amount, $payment_data)
  {
    // Create pending bank transfer record
    $payment_id = 'bank_' . bin2hex(random_bytes(12));

    return [
      'success' => true,
      'payment_id' => $payment_id,
      'provider' => 'bank_transfer',
      'amount' => $amount,
      'status' => 'pending'
    ];
  }

  /**
   * 📊 CALCULATE: Proration for upgrades
   * Calculate prorated pricing difference
   */
  private function calculateProration($current_membership, $new_tier)
  {
    $tier_defs = Membership::getTierDefinitions();
    $current_tier_info = $tier_defs[$current_membership->membership_tier];
    $new_tier_info = $tier_defs[$new_tier];
    $billing_cycle = $current_membership->content['billing_cycle'] ?? 'monthly';

    // Get daily rates
    $current_price = ($billing_cycle === 'yearly') ? $current_tier_info['price_yearly'] / 365 : $current_tier_info['price_monthly'] / 30;
    $new_price = ($billing_cycle === 'yearly') ? $new_tier_info['price_yearly'] / 365 : $new_tier_info['price_monthly'] / 30;

    // Calculate remaining days in cycle
    $now = new DateTime();
    $renewal = new DateTime($current_membership->renewal_date);
    $days_remaining = (int)$renewal->diff($now)->format('%a');

    // Calculate credit and new charge
    $credit = $current_price * $days_remaining;
    $charge = $new_price * $days_remaining;
    $amount_due = max(0, $charge - $credit);

    return [
      'credit' => round($credit, 2),
      'charge' => round($charge, 2),
      'amount_due' => round($amount_due, 2),
      'days_remaining' => $days_remaining
    ];
  }

  /**
   * 📧 EMAIL: Send confirmation email
   */
  private function sendConfirmationEmail($membership_uuid, $tier, $billing_cycle)
  {
    // TODO: Implement email notification
    // $user = User::getById($this->user_id);
    // send_email($user->email, 'membership-purchase', [
    //   'tier' => $tier,
    //   'billing_cycle' => $billing_cycle,
    //   'membership_uuid' => $membership_uuid
    // ]);
  }

  /**
   * 📧 EMAIL: Send upgrade confirmation
   */
  private function sendUpgradeConfirmationEmail($membership_uuid, $new_tier, $proration)
  {
    // TODO: Implement email notification
  }

  /**
   * 📧 EMAIL: Send downgrade confirmation
   */
  private function sendDowngradeConfirmationEmail($membership_uuid, $new_tier, $effective_date)
  {
    // TODO: Implement email notification
  }

  /**
   * 📧 EMAIL: Send renewal confirmation
   */
  private function sendRenewalConfirmationEmail($membership_uuid)
  {
    // TODO: Implement email notification
  }

  /**
   * 📧 EMAIL: Send cancellation confirmation
   */
  private function sendCancellationConfirmationEmail($membership_uuid)
  {
    // TODO: Implement email notification
  }

  /**
   * 🔄 AUTO-RENEW: Process auto-renewals
   * Batch job to renew memberships with auto_renew enabled
   */
  public static function processAutoRenewals($db)
  {
    $renewed_count = 0;
    $failed_count = 0;

    // Get all memberships expiring in next 5 days
    $expiring = Membership::getExpiringMemberships(5);

    foreach ($expiring as $membership) {
      if ($membership->content['auto_renew'] ?? true) {
        $controller = new self($db, $membership->architect_id);
        $result = $controller->renewMembership($membership->uuid);

        if ($result['success']) {
          $renewed_count++;
        } else {
          $failed_count++;
          error_log("Auto-renew failed for membership {$membership->uuid}: " . $result['error']);
        }
      }
    }

    return [
      'renewed' => $renewed_count,
      'failed' => $failed_count,
      'total' => count($expiring)
    ];
  }
}
