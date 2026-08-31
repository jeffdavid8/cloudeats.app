<?php
echo "<h1>App Constructor Test</h1>";

try {
    require('../vendor/autoload.php');
    require_once('includes/app.php');
    
    echo "1. About to call App::getInstance()...<br>";
    $app = App::getInstance('admin');
    echo "2. App instance created successfully<br>";
    
    echo "3. App name: " . $app->app . "<br>";
    echo "4. EventLogger available: " . (($app->getEventLogger() !== null) ? 'YES' : 'NO') . "<br>";
    
    echo "5. Testing render function...<br>";
    ob_start();
    render('components/head.php');
    $head = ob_get_clean();
    echo "6. Render function works: " . (!empty($head) ? 'YES' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>