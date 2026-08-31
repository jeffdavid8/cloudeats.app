<?php
// Debug admin app loading
echo "<h1>Admin App Debug</h1>";

// Set up basic environment
$_SESSION = $_SESSION ?? [];
$_SESSION['admin_user'] = 'admin';
$_SESSION['mb_user'] = 'admin';
$_SESSION['mb_user_data'] = ['is_admin' => true];

echo "<h2>1. Session Data</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Load required files
require_once 'html/includes/app.php';
require_once 'html/includes/util.php';

echo "<h2>2. App Instance</h2>";
try {
    $app = App::getInstance('admin');
    echo "App instance created successfully<br>";
    echo "App dir: " . $app->dir . "<br>";
    
    $appFile = $app->dir . '/apps/admin/admin.app.php';
    echo "Looking for app file: " . $appFile . "<br>";
    echo "File exists: " . (file_exists($appFile) ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Error creating app instance: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Direct File Check</h2>";
$directFile = __DIR__ . '/html/apps/admin/admin.app.php';
echo "Direct path: " . $directFile . "<br>";
echo "Direct file exists: " . (file_exists($directFile) ? 'YES' : 'NO') . "<br>";

echo "<h2>4. App Loading Test</h2>";
try {
    if (file_exists($directFile)) {
        require_once $directFile;
        echo "admin.app.php loaded successfully<br>";
        
        // Test admin_info function
        if (function_exists('admin_info')) {
            echo "admin_info function exists<br>";
            $info = admin_info();
            echo "Admin info: <pre>" . print_r($info, true) . "</pre>";
        } else {
            echo "admin_info function NOT found<br>";
        }
        
        // Test admin_render_body function
        if (function_exists('admin_render_body')) {
            echo "admin_render_body function exists<br>";
            echo "<h2>5. Admin App Output</h2>";
            echo "<div style='border: 1px solid #ccc; padding: 10px;'>";
            admin_render_body();
            echo "</div>";
        } else {
            echo "admin_render_body function NOT found<br>";
        }
        
    } else {
        echo "Cannot load admin.app.php - file not found<br>";
    }
} catch (Exception $e) {
    echo "Error loading admin app: " . $e->getMessage() . "<br>";
}
?>