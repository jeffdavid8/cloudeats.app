<?php
// Absolutely minimal reproduction to isolate the issue
echo "Direct util.php inclusion test...\n";

// Directly include util.php to bypass app.php
require_once __DIR__ . '/includes/util.php';

echo "If you see this, util.php loaded successfully!\n";
?>