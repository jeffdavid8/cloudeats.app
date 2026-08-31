<?php
// Clear OPcache and enable timestamp validation for development
echo "<h1>OPcache Management</h1>";

echo "<h3>Current OPcache Status:</h3>";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "<pre>";
    echo "OPcache Enabled: " . ($status ? "Yes" : "No") . "\n";
    echo "Cache Full: " . ($status['cache_full'] ? "Yes" : "No") . "\n";
    echo "Used Memory: " . number_format($status['memory_usage']['used_memory']) . " bytes\n";
    echo "Free Memory: " . number_format($status['memory_usage']['free_memory']) . " bytes\n";
    echo "Cached Scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "</pre>";
} else {
    echo "<p>OPcache not available</p>";
}

echo "<h3>Actions:</h3>";

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'reset':
            if (function_exists('opcache_reset')) {
                opcache_reset();
                echo "<p style='color: green;'>✅ OPcache reset successfully!</p>";
            } else {
                echo "<p style='color: red;'>❌ OPcache reset function not available</p>";
            }
            break;
        case 'invalidate':
            // Invalidate specific files
            $files = [
                '/var/www/html/includes/util.php',
                '/var/www/html/includes/app.php',
                '/var/www/html/includes/Services/TextToSpeechService.php'
            ];
            
            foreach ($files as $file) {
                if (function_exists('opcache_invalidate')) {
                    $result = opcache_invalidate($file, true);
                    echo "<p style='color: " . ($result ? "green" : "red") . ";'>" . 
                         ($result ? "✅" : "❌") . " " . basename($file) . "</p>";
                }
            }
            break;
    }
}

echo "<a href='?action=reset' style='background: #007cba; color: white; padding: 10px; text-decoration: none; margin-right: 10px;'>Reset OPcache</a>";
echo "<a href='?action=invalidate' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; margin-right: 10px;'>Invalidate Key Files</a>";
echo "<a href='?' style='background: #28a745; color: white; padding: 10px; text-decoration: none;'>Refresh Status</a>";

echo "<h3>Configuration Info:</h3>";
echo "<pre>";
echo "validate_timestamps: " . (ini_get('opcache.validate_timestamps') ? 'On' : 'Off') . "\n";
echo "revalidate_freq: " . ini_get('opcache.revalidate_freq') . " seconds\n";
echo "enable: " . (ini_get('opcache.enable') ? 'On' : 'Off') . "\n";
echo "</pre>";

echo "<hr>";
echo "<h3>Development Note:</h3>";
echo "<p>For development, opcache.validate_timestamps should be ON so PHP checks for file changes.</p>";
echo "<p>Current setting is OFF, which means files are cached permanently until manually cleared.</p>";
?>