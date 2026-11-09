<?php
if (!defined('MB_RUNNING')) exit;

header('Content-Type: application/json');
$request = json_decode(file_get_contents('php://input'), true);
$request = is_array($request) ? $request : [];
$action = $_REQUEST['action'] ?? $request['action'] ?? null;
$app = App::getInstance();

if (empty($_SESSION['user']['id']) || empty($app->user->id)
    || (string)$_SESSION['user']['id'] !== (string)$app->user->id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Authentication required.']);
    return;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'POST required.']);
    return;
}

switch ($action) {
  case 'transfer':
    $app->includeModel('vault');
    try {
      Vault::transfer($app->user->id, $request['to_id'] ?? null, $request['amount'] ?? null, $request['note'] ?? '');
      echo json_encode(['status' => 'success']);
    } catch (InvalidArgumentException | DomainException $e) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
      error_log('Vault transfer failed: ' . $e->getMessage());
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'Transfer failed. Please try again or contact support.']);
    }
    break;

  case 'request_vault_payout':
    http_response_code(503);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Vault withdrawals are temporarily unavailable.', 'error' => 'Vault withdrawals are temporarily unavailable.']);
    break;

  default:
    http_response_code(400);
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Unknown Vault action.']);
}
