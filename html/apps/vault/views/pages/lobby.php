<?php

$status_text = ($balance >= 0) ? "Sovereign Surplus" : "Community Provision";
$status_class = ($balance >= 0) ? "green-text" : "amber-text text-darken-2";
//error_log(print_r($this->user, true)); // Debugging: Log the user object to verify stripe_connect_id
// Fetch the user's stripe connect status directly out of the local profile snapshot
// Make sure $user_profile or your user object exposes this column!
$stripeConnectId = User::getUserStripeConnectId($this->user->id);
$hasStripeLinked = !empty($stripeConnectId); 
?>
<div class="container vault-container">

    <div class="card-panel z-depth-2 center-align main-balance-card">
        <span class="grey-text text-darken-1">@<?= $user->username ?></span>
        <h2 class="<?= $status_class ?>">$<?= number_format($balance, 2) ?></h2>
        <?php
        // Get the impact (total amount shared with others)
        $impact = Vault::get_impact($user->id, App::getInstance()->db);
        ?>
        <div class="hero-status" style="margin-top: -10px; margin-bottom: 20px;">
            <span class="chip gold-btn white-text z-depth-1">
                <i class="material-icons left">favorite</i>
                Town Impact: $<?= number_format($impact, 2) ?>
            </span>
        </div>
        <p class="italic grey-text">"<?= $status_text ?>"</p>
    </div>

    <div class="row action-grid" style="margin-bottom: 10px;">
        <div class="col s4">
            <button class="btn-large waves-effect waves-light full-width gold-btn" onclick="Vault.mintProvision()" style="padding: 0 4px; font-size: 13px;">
                <i class="material-icons left" style="margin-right: 4px;">cloud_download</i> MINT
            </button>
        </div>
        <div class="col s4">
            <button class="btn-large waves-effect waves-light full-width blue-btn" onclick="Vault.openTransferModal()" style="padding: 0 4px; font-size: 13px;">
                <i class="material-icons left" style="margin-right: 4px;">send</i> SHARE
            </button>
        </div>
        <div class="col s4">
            <?php if ($hasStripeLinked): ?>
                <button class="btn-large waves-effect waves-light full-width green" onclick="Vault.openPayoutModal()" style="padding: 0 4px; font-size: 13px;">
                    <i class="material-icons left" style="margin-right: 4px;">account_balance</i> BANK OUT
                </button>
            <?php else: ?>
                <button class="btn-large waves-effect waves-light full-width deep-orange pulse" onclick="Vault.linkStripeAccount()" style="padding: 0 4px; font-size: 11px;">
                    <i class="material-icons left" style="margin-right: 2px;">credit_card</i> LINK BANK
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="ledger-wrapper card z-depth-1">
        <div class="card-content">
            <span class="card-title">Town Ledger</span>
            <ul class="collection">
                <?php if (empty($history)): ?>
                    <li class="collection-item center-align grey-text italic">No ledger activity recorded yet.</li>
                <?php endif; ?>
                <?php foreach ($history as $entry): 
                    $isCredit = (in_array($entry['content_type'], array('token_credit', 'daily_provision')));
                    ?>
                    <li class="collection-item">
                        <span class="title">
                            <strong><?= date('m/d', strtotime($entry['created_at'])) ?></strong> -
                            <?= htmlspecialchars($entry['data']['description']) ?>
                        </span>
                        <span class="secondary-content <?= $isCredit ? 'green-text' : 'red-text' ?>">
                            <?= $isCredit ? '+' : '-' ?>
                            $<?= number_format($entry['data']['amount'], 2) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<div id="transferModal" class="modal mb-modal-fixed">
    <div class="modal-content">
        <h4>Share Treasure</h4>
        <div class="row">
            <div class="input-field col s12">
                <select id="transferRecipient">
                    <option value="" disabled selected>Choose a Recipient</option>
                    <?php
                    $residents = App::getInstance()->db->query("SELECT id, username FROM users")->fetchAll();
                    foreach ($residents as $res): if ($res['id'] == $user->id) continue; ?>
                        <option value="<?= $res['id'] ?>"><?= $res['username'] ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Who are you sending to?</label>
            </div>
            <div class="input-field col s12">
                <input id="transferAmount" type="number" step="0.01" placeholder="0.00">
                <label for="transferAmount">Amount (US)</label>
            </div>
            <div class="input-field col s12">
                <input id="transferNote" type="text" placeholder="For the Diner / Groceries / Love">
                <label for="transferNote">Note</label>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
        <button class="waves-effect waves-green btn gold-btn" onclick="Vault.sendTransfer()">SEND TREASURE</button>
    </div>
</div>

<div id="payoutModal" class="modal mb-modal-fixed">
    <div class="modal-content">
        <h4>Transfer Funds to Bank</h4>
        <p class="grey-text">Move money instantly from your MediaBrain virtual vault ledger straight onto your real-world bank account via Stripe Connect.</p>
        <div class="row" style="margin-top: 25px;">
            <div class="col s12 center-align">
                <span class="grey-text">Available Balance</span>
                <h3 class="green-text style-weight-600" style="margin: 5px 0 25px 0;">$<?= number_format($balance, 2) ?></h3>
            </div>
            <div class="input-field col s12">
                <i class="material-icons prefix">attach_money</i>
                <input id="payoutAmount" type="number" step="0.01" max="<?= $balance ?>" placeholder="0.00">
                <label for="payoutAmount">Withdrawal Amount</label>
                <span class="helper-text" data-error="Insufficient funds available" data-success="Looks great!">Enter how much you wish to transfer</span>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <a href="#!" class="modal-close waves-effect waves-red btn-flat">Cancel</a>
        <button class="waves-effect waves-green btn green" onclick="Vault.submitStripePayout()">CONFIRM WITHDRAWAL</button>
    </div>
</div>

<script>
$(document).ready(function(){
    // Initialize the new payout modal along with default initialization protocols
    $('#payoutModal').modal();
});

/**
 * Direct onboarding dispatch trigger
 */
Vault.linkStripeAccount = function() {
    M.toast({html: 'Preparing secure banking link...'});
    
    mb.ajax({
        url: '?api=neighborhub&action=setup_stripe_connect',
        method: 'POST',
        data: JSON.stringify({}),
        success: function(response) {
            if (response && response.success && response.onboarding_url) {
                // Hand the user off smoothly to Stripe's hosted banking enrollment verification portal
                window.location.href = response.onboarding_url;
            } else {
                M.toast({html: 'Error: ' + (response.error || 'Failed to initialize setup Link'), classes: 'red'});
            }
        },
        error: function() {
            M.toast({html: 'System connection error initializing onboarding payload.', classes: 'red'});
        }
    });
};

/**
 * Open the local payout form modal
 */
Vault.openPayoutModal = function() {
    $('#payoutAmount').val('');
    $('#payoutModal').modal('open');
};

/**
 * Dispatches the vault debit request to the server API
 */
Vault.submitStripePayout = function() {
    const amount = parseFloat($('#payoutAmount').val() || 0);
    const maxBalance = parseFloat(<?= json_encode($balance) ?>);

    if (amount <= 0) {
        M.toast({html: 'Please enter a valid transfer amount greater than $0.00', classes: 'red'});
        return;
    }
    if (amount > maxBalance) {
        M.toast({html: 'You cannot withdraw more than your current vault balance.', classes: 'red'});
        return;
    }

    // Double-check loading indicators
    $('#payoutModal .modal-footer button').attr('disabled', true).text('PROCESSING...');

    mb.ajax({
        url: '?api=neighborhub&action=request_vault_payout',
        method: 'POST',
        data: JSON.stringify({ amount: amount }),
        success: function(response) {
            $('#payoutModal').modal('close');
            if (response && response.success) {
                M.toast({html: '🎉 Funds transferred successfully! Check your bank account.', classes: 'green', displayLength: 4000});
                // Reload the page after a brief delay so they see the fresh balance ledger reduction immediately!
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                M.toast({html: 'Transfer Failed: ' + (response.error || 'Unknown error occurred'), classes: 'red'});
                $('#payoutModal .modal-footer button').attr('disabled', false).text('CONFIRM WITHDRAWAL');
            }
        },
        error: function() {
            M.toast({html: 'Critical gateway error processing transfer settlement parameters.', classes: 'red'});
            $('#payoutModal .modal-footer button').attr('disabled', false).text('CONFIRM WITHDRAWAL');
        }
    });
};
</script>

<style>
/* Clean layout helper tweaks for tiny screens */
.full-width { width: 100% !important; }
.action-grid .col { padding: 0 4px !important; }
.style-weight-600 { font-weight: 600; }
</style>