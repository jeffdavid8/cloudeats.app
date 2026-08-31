<?php
// Super minimal test to see if there's actually duplicate inclusion
echo "=== INCLUSION TEST ===\n";

// Track inclusions manually
$GLOBALS['inclusion_counter'] = ($GLOBALS['inclusion_counter'] ?? 0) + 1;
echo "This is inclusion attempt #" . $GLOBALS['inclusion_counter'] . "\n";

// Show current included files
$included = get_included_files();
echo "Currently included files: " . count($included) . "\n";
foreach ($included as $i => $file) {
    echo "  [$i] " . basename($file) . "\n";
}

echo "\nAbout to include app.php...\n";
require_once __DIR__ . '/includes/app.php';
echo "\nApp.php included successfully!\n";
echo "Test complete!\n";
?>