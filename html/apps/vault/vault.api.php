<?php
if (!defined('MB_RUNNING')) exit;

use Ramsey\Uuid\Uuid;

// Secure the entry point
$input = file_get_contents('php://input');
$request = json_decode($input, true) ?? [];

$action = $_REQUEST['action'] ?? $request['action'] ?? null;
$app = App::getInstance();
$app->includeModel('vault');

// Include the Model so we can use Vault::mint_provision if we want, 
// or we can keep the logic local here. Let's fix the local logic for now:

switch ($action) {
  /*
  case 'mint_provision':

    $results = Vault::mint_provision();

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'minted' => $results['minted']]);
    break;
    */

  case 'transfer':
    // If you're calling 'transfer' from vault.js, we add that logic here too
    $from_id = $app->user->id; // Sovereign sender
    $to_id = $request['to_id'] ?? null;
    $amount = $request['amount'] ?? 0;
    $note = $request['note'] ?? 'No note';

    if ($to_id && $amount > 0) {
      // We use the Model we built earlier to keep it clean
      $success = Vault::transfer($from_id, $to_id, $amount, $note);
      if (!$success) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Transfer failed.']);
        break;
      }

      header('Content-Type: application/json');
      echo json_encode(['status' => $success ? 'success' : 'error']);
    }
    break;

  case 'request_vault_payout':
    $userId = $_SESSION['user']['id'];
    $amountRequested = floatval($request['amount'] ?? 0);

    // 1. Check if user has sufficient virtual credits inside your native Vault table
    $currentBalance = Vault::get_balance($userId);
    if ($amountRequested > $currentBalance || $amountRequested <= 0) {
      exit(json_encode(['success' => false, 'error' => 'Insufficient Vault balance allocation available.']));
    }

    // 2. Grab their Stripe destination credentials out of your local database profile row
    $stmt = $app->db->prepare("SELECT stripe_connect_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $stripeConnectId = $stmt->fetchColumn();

    if (!$stripeConnectId) {
      exit(json_encode(['success' => false, 'error' => 'Please set up your bank deposit link via Stripe Connect first.']));
    }

    // 3. Initiate atomic data operations wrapper block
    $app->db->beginTransaction();
    try {
      // Debit their local vault ledger cleanly using your existing Memory Anchors schema structure
      $uuid = bin2hex(random_bytes(16));
      $timestamp = date('Y-m-d H:i:s');
      $payoutContent = json_encode([
        'description' => "Withdrew funds via Stripe Connect payout transfer",
        'amount' => (float)$amountRequested
      ]);

      $ins = $app->db->prepare("INSERT INTO memory_anchors (uuid, architect_id, content, content_type, created_at, projected_to, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $ins->execute([$uuid . '-payout', $userId, $payoutContent, 'token_debit', $timestamp, $timestamp, 'active']);

      // 4. Hit Stripe instantly to push the real funds from your primary ledger balance straight to them
      $stripeTxId = StripeModel::executePayoutTransfer(
        $stripeConnectId,
        $amountRequested,
        "MediaBrain Neighborhub Vault Payout Settlement for User #{$userId}"
      );

      // Everything passed without structural anomalies! Commit execution state tracking rows securely
      $app->db->commit();
      echo json_encode(['success' => true, 'message' => 'Payout completed successfully! Funds are on the way to your bank.']);
    } catch (Exception $e) {
      $app->db->rollBack();
      error_log("Vault Stripe Payout Abort Exception: " . $e->getMessage());
      exit(json_encode(['success' => false, 'error' => 'Payment processor rejected transfer clearing authorization bounds.']));
    }
    break;
}
