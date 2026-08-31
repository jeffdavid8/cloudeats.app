<?php
if (!defined('MB_RUNNING')) exit;

/**
 * 💳 STRIPE CONNECT & MARKETPLACE MODEL
 * Bridges Stripe API operations with MediaBrain Internal Vault Ledger systems.
 */
class StripeModel
{
    /**
     * Initialize Stripe SDK with system environment secret key
     */
    private static function init()
    {
        $stripe_key = getenv('STRIPE_SECRET_KEY');
        if (!$stripe_key) {
            throw new Exception("Stripe runtime configuration error: STRIPE_SECRET_KEY missing from server environment.");
        }
        \Stripe\Stripe::setApiKey($stripe_key);
    }

    /**
     * 1. CREATE EXPRESS CONNECTED ACCOUNT
     * Provisions a brand new custom routing destination account container for a Driver or Merchant user.
     * Save the returned 'id' (acct_xxxxxxxx) into your local user or merchant DB profile table under `stripe_connect_id`.
     * * @param int $userId Local MediaBrain user primary ID mapping
     * @param string $email Account holder primary contact communication path
     * @param string $type Role context ('merchant' or 'driver')
     * @return string Stripe Connected Account ID (acct_...)
     */
    public static function createExpressAccount($userId, $email, $type)
    {
        self::init();

        $account = \Stripe\Account::create([
            'type' => 'express',
            'country' => 'US',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'mediabrain_user_id' => $userId,
                'role_context' => $type
            ]
        ]);

        return $account->id;
    }

    /**
     * 2. GENERATE ONBOARDING ROUTE LINK
     * Generates a safe, temporary secure redirect link to send to the user 
     * so Stripe can gather their banking, debit card, routing routing parameters securely.
     * * @param string $stripeAccountId The acct_xxxx identifier generated from createExpressAccount
     * @return string Secure verification entry URL redirect path
     */
    public static function getOnboardingLink($stripeAccountId)
    {
        self::init();

        $baseUrl = config('base_url');

        $link = \Stripe\AccountLink::create([
            'account' => $stripeAccountId,
            'refresh_url' => $baseUrl . '/?app=vault&view=lobby&status=onboard_failed',
            'return_url' => $baseUrl . '/?app=vault&view=lobby&status=onboard_success',
            'type' => 'account_onboarding',
        ]);

        return $link->url;
    }

    /**
     * 3. TRIGGER CONNECT ROUTING TRANSFER (THE VAULT PAYOUT CLEARING ENGINE)
     * Takes funds currently sitting inside MediaBrain's main Stripe platform account balance 
     * and drops it instantly onto the target merchant or driver's real bank routing setup.
     * Fires automatically right after a local Vault transfer debit occurs!
     * * @param string $stripeAccountId Destination acct_xxxx value
     * @param float $amount Precise dollar representation to clear
     * @param string $description Concise statement showing matching database history
     * @return string Stripe Transfer Record Transaction Reference Token String
     */
    public static function executePayoutTransfer($stripeAccountId, $amount, $description)
    {
        self::init();

        // Convert pristine dollar floats to absolute cents safely for Stripe API parsing
        $amountInCents = (int)round($amount * 100);

        if ($amountInCents <= 0) {
            throw new Exception("Payout allocation amount must resolve to greater than zero cents.");
        }

        $transfer = \Stripe\Transfer::create([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'destination' => $stripeAccountId,
            'description' => $description,
        ]);

        return $transfer->id;
    }
}