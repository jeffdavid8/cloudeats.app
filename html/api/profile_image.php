<?php
/**
 * Profile Image Server
 * Serves profile images from secure storage
 */

require_once __DIR__ . '/apps/admin/includes/ProfileImageManager.php';

// Get filename from query parameter
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Validate filename (security check)
if (!preg_match('/^profile_[a-zA-Z0-9_]+_[0-9]+\.(jpg|jpeg|png|gif|webp)$/', $filename)) {
    http_response_code(400);
    echo 'Invalid filename';
    exit;
}

// Serve the image
$imageManager = new ProfileImageManager();
if (!$imageManager->serveProfileImage($filename)) {
    http_response_code(404);
    echo 'Image not found';
}
?>