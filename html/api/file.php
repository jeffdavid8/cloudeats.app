<?php
/**
 * File serving endpoint for local storage
 * Serves files with proper headers and security checks
 */

require_once __DIR__ . '/../includes/storage/FileStorageManager.php';

// Security check - only serve files if user is authenticated
session_start();
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    // Allow public access for certain file types
    $allowedPublicTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file = $_GET['f'] ?? '';
    
    if (empty($file)) {
        http_response_code(403);
        exit('Access denied');
    }
    
    // Check if it's a public file type
    $isPublic = false;
    foreach ($allowedPublicTypes as $type) {
        if (strpos($file, 'profile_images/') === 0) {
            $isPublic = true;
            break;
        }
    }
    
    if (!$isPublic) {
        http_response_code(403);
        exit('Access denied');
    }
}

try {
    $file = $_GET['f'] ?? '';
    
    if (empty($file)) {
        http_response_code(400);
        exit('File parameter required');
    }
    
    // Security: prevent directory traversal
    if (strpos($file, '..') !== false || strpos($file, '\\') !== false) {
        http_response_code(400);
        exit('Invalid file path');
    }
    
    // Initialize storage manager with local provider
    $storage = new FileStorageManager(FileStorageManager::STORAGE_LOCAL);
    
    // Extract category and filename from path
    $pathParts = explode('/', $file);
    if (count($pathParts) < 2) {
        http_response_code(400);
        exit('Invalid file path');
    }
    
    $category = $pathParts[0];
    $filename = implode('/', array_slice($pathParts, 1));
    
    // Get file data
    $result = $storage->getFile($category, $filename);
    
    if (!$result['success']) {
        http_response_code(404);
        exit('File not found');
    }
    
    // Determine MIME type
    $mimeType = 'application/octet-stream';
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json'
    ];
    
    if (isset($mimeTypes[$extension])) {
        $mimeType = $mimeTypes[$extension];
    }
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . $result['size']);
    header('Cache-Control: public, max-age=3600');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $result['modified']) . ' GMT');
    
    // Set filename for downloads
    if (isset($_GET['download'])) {
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    }
    
    // Output file data
    echo $result['data'];
    
} catch (Exception $e) {
            log_error('File serving error: ' . $e->getMessage());
    http_response_code(500);
    exit('Internal server error');
}