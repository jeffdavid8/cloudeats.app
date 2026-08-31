<?php
echo "Path Resolution Debug\n";
echo "=====================\n\n";

echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";
echo "dirname(__DIR__, 1): " . dirname(__DIR__, 1) . "\n";

echo "\nBuilding path step by step:\n";
$step1 = dirname(__DIR__, 1);
echo "Step 1 - Parent dir: $step1\n";

$step2 = $step1 . '/logs';  
echo "Step 2 - Add logs: $step2\n";

$step3 = $step2 . '/event.log';
echo "Step 3 - Add filename: $step3\n";

echo "Final path exists: " . (file_exists($step3) ? 'YES' : 'NO') . "\n";

echo "\nTrying alternative construction:\n";
$alt = realpath(__DIR__ . '/../logs/event.log');
echo "Alternative path: " . ($alt ?: 'NULL') . "\n";
if ($alt) {
    echo "Alternative exists: " . (file_exists($alt) ? 'YES' : 'NO') . "\n";
}
?>