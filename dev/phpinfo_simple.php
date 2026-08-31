<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Default Socket Timeout: " . ini_get('default_socket_timeout') . "\n";

echo "\nLoaded Extensions:\n";
$extensions = get_loaded_extensions();
foreach (['curl', 'json', 'pdo', 'mysqli'] as $ext) {
    echo "$ext: " . (in_array($ext, $extensions) ? "✓" : "✗") . "\n";
}

echo "\nPHP-FPM Status:\n";
echo "Process ID: " . getmypid() . "\n";
echo "Server API: " . php_sapi_name() . "\n";
?>