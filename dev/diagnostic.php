<?php
// Minimal diagnostic script to identify timeout source
echo "=== MediaBrain Diagnostic ===<br>";
flush();
set_time_limit(60); // Give it 60 seconds max

try {
    echo "1. Basic PHP execution: OK<br>";
    flush();
    sleep(1);
    
    echo "2. Starting session...";
    session_start();
    echo " OK<br>";
    flush();
    sleep(1);
    
    echo "3. Loading vendor autoload...";
    if (file_exists('../vendor/autoload.php')) {
        require_once('../vendor/autoload.php');
        echo " OK<br>";
    } else {
        echo " FILE NOT FOUND<br>";
    }
    flush();
    sleep(1);
    
    echo "4. Loading app.php (includes util.php and database)...";
    if (file_exists('includes/app.php')) {
        require_once('includes/app.php');
        echo " OK<br>";
    } else {
        echo " FILE NOT FOUND<br>";
    }
    flush();
    sleep(1);

    echo "5. Testing App class instantiation...";
    try {
        $app = App::getInstance();
        echo " OK<br>";
    } catch (Exception $e) {
        echo " ERROR: " . $e->getMessage() . "<br>";
    }
    flush();
    sleep(1);

    echo "6. Testing utility functions...";
    flush();
    sleep(1);
    
    echo "6. Creating App instance (this might take time)...";
    $start = microtime(true);
    $app = App::getInstance('splash');
    $end = microtime(true);
    echo " OK (took " . round($end - $start, 2) . " seconds)<br>";
    flush();
    
    echo "7. All checks completed successfully!<br>";
    echo "Current time: " . date('Y-m-d H:i:s') . "<br>";
    echo "Memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB<br>";
    
} catch (Exception $e) {
    echo "<br>ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<br>FATAL ERROR: " . $e->getMessage() . "<br>";
    echo "Stack trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
}
?>