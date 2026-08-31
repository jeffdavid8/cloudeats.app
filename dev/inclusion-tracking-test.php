<?php
// Modified minimal test to track ALL file inclusions step by step
echo "=== INCLUSION TRACKING TEST ===\n";

// Track what gets included at each step
function print_included_files($step) {
    $files = get_included_files();
    echo "STEP $step - Files included so far: " . count($files) . "\n";
    foreach ($files as $i => $file) {
        echo "  [$i] $file\n";
    }
    echo "\n";
}

print_included_files("1 - Before app.php");

// Use a slightly different inclusion method to see if it makes a difference
$app_path = __DIR__ . '/includes/app.php';
echo "About to include: $app_path\n";
echo "Real path of app.php: " . realpath($app_path) . "\n\n";

require_once $app_path;

print_included_files("2 - After app.php");

echo "Test complete!\n";
?>