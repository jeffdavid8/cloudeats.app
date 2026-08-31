<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache cleared successfully\n";
} else {
    echo "OPcache reset function not available\n";
}

if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "OPcache enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "Cache full: " . ($status['cache_full'] ? 'YES' : 'NO') . "\n";
} else {
    echo "OPcache status not available\n";
}
?>