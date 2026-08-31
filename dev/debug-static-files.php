<?php
/**
 * Debug file to check what's happening with static file requests
 */

// Log all requests to debug MIME type issues
error_log("DEBUG: Request URI: " . $_SERVER['REQUEST_URI']);
error_log("DEBUG: Script Name: " . $_SERVER['SCRIPT_NAME']);
error_log("DEBUG: PHP Self: " . $_SERVER['PHP_SELF']);

// Check if this is a static file request
$requestUri = $_SERVER['REQUEST_URI'];
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];

foreach ($staticExtensions as $ext) {
    if (preg_match("/\\.{$ext}(\\?.*)?$/i", $requestUri)) {
        error_log("STATIC FILE REQUEST DETECTED: " . $requestUri);
        
        // Check if file exists
        $filePath = $_SERVER['DOCUMENT_ROOT'] . parse_url($requestUri, PHP_URL_PATH);
        if (file_exists($filePath)) {
            error_log("STATIC FILE EXISTS: " . $filePath);
            
            // Set proper MIME type and serve file
            switch ($ext) {
                case 'css':
                    header('Content-Type: text/css');
                    break;
                case 'js':
                    header('Content-Type: application/javascript');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'gif':
                    header('Content-Type: image/gif');
                    break;
                case 'svg':
                    header('Content-Type: image/svg+xml');
                    break;
                case 'woff':
                case 'woff2':
                    header('Content-Type: font/woff');
                    break;
                case 'ttf':
                    header('Content-Type: font/ttf');
                    break;
                case 'ico':
                    header('Content-Type: image/x-icon');
                    break;
            }
            
            // Serve the file directly
            readfile($filePath);
            exit;
        } else {
            error_log("STATIC FILE NOT FOUND: " . $filePath);
            header('HTTP/1.0 404 Not Found');
            exit;
        }
    }
}

// Continue with normal PHP processing
error_log("CONTINUING WITH NORMAL PHP PROCESSING");
?>