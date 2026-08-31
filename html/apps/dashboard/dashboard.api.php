<?php

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$rawInput = file_get_contents('php://input');
if ($rawInput) {
    $request = json_decode($rawInput, true);
    if ($request && isset($request['action'])) {
        $action = $request['action'];
        $data = $request['data'] ?? array();
    }
}

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
    exit;
}

$csrf_protected_actions = [
    'add_card',
    'move_card',
    'update_card_text',
];

if (in_array($action, $csrf_protected_actions)) {
    $csrf_token = $request['_csrf'] ?? $_GET['_csrf'] ?? $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!AuthManager::validateCsrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token', 'status' => 'error']);
        exit;
    }
}

$app = App::getInstance('dashboard');

switch ($action) {
    case 'get_board':
        api_get_board();
        break;
    
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action not found']);
        break;
}

function api_get_board() {
    $boardData = json_read_file('apps/dashboard/json/board.json');
    echo json_encode(['status' => 'success', 'board' => $boardData]);
}
