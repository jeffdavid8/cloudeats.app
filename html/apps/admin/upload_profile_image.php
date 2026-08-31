<?php
/**
 * Profile Image Upload API
 */

require_once __DIR__ . '/../../includes/AuthManager.php';
require_once __DIR__ . '/includes/ProfileImageManager.php';

// Session already started in index.php

// Check admin authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Set JSON content type
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'upload_profile_image') {
    $username = $_POST['username'] ?? '';
    
    if (empty($username)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username required']);
        exit;
    }
    
    if (!isset($_FILES['profileImageFile']) || $_FILES['profileImageFile']['error'] === UPLOAD_ERR_NO_FILE) {
        http_response_code(400);
        echo json_encode(['error' => 'No image file provided']);
        exit;
    }
    
    $imageManager = new ProfileImageManager();
    $result = $imageManager->uploadProfileImage($_FILES['profileImageFile'], $username);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'url' => $result['url'],
            'filename' => $result['filename']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['error']]);
    }
    
} elseif ($action === 'delete_profile_image') {
    $filename = $_POST['filename'] ?? '';
    
    if (empty($filename)) {
        echo json_encode(['success' => true]); // Nothing to delete
        exit;
    }
    
    $imageManager = new ProfileImageManager();
    $result = $imageManager->deleteProfileImage($filename);
    
    echo json_encode($result);
    
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
}
?>