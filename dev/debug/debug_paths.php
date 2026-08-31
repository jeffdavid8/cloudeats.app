<?php
echo "Path Debug Test\n";
echo "===============\n\n";

echo "Current __DIR__: " . __DIR__ . "\n";
echo "Parent dir: " . dirname(__DIR__) . "\n";
echo "Grandparent dir: " . dirname(__DIR__, 2) . "\n";
echo "Expected log file: " . dirname(__DIR__, 2) . '/logs/event.log' . "\n";
echo "Expected log dir: " . dirname(dirname(__DIR__, 2) . '/logs/event.log') . "\n";

echo "\nChecking if paths exist:\n";
echo "Log file exists: " . (file_exists(dirname(__DIR__, 2) . '/logs/event.log') ? 'YES' : 'NO') . "\n";
echo "Log dir exists: " . (is_dir(dirname(__DIR__, 2) . '/logs') ? 'YES' : 'NO') . "\n";

echo "\nChecking alternative paths:\n";
$altPath1 = '/var/www/mediabrain.app.local/logs/event.log';
$altPath2 = '/var/www/html/../logs/event.log';
$altPath3 = realpath(__DIR__ . '/../../logs/event.log');

echo "Alt path 1 ($altPath1): " . (file_exists($altPath1) ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "Alt path 2 ($altPath2): " . (file_exists($altPath2) ? 'EXISTS' : 'NOT FOUND') . "\n";  
echo "Alt path 3 ($altPath3): " . ($altPath3 ? 'RESOLVED TO: ' . $altPath3 : 'NOT RESOLVED') . "\n";

if ($altPath3) {
    echo "Alt path 3 exists: " . (file_exists($altPath3) ? 'YES' : 'NO') . "\n";
}

echo "\nListing contents of possible log directories:\n";
$dirs = [
    dirname(__DIR__, 2) . '/logs',
    '/var/www/mediabrain.app.local/logs',
    '/var/www/html/../logs'
];

foreach ($dirs as $dir) {
    echo "\nDirectory: $dir\n";
    if (is_dir($dir)) {
        echo "Exists - Contents:\n";
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    } else {
        echo "Does not exist\n";
    }
}
?>