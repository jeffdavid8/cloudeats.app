<?php
echo "Starting minimal util test...\n";
echo "Before requiring app.php\n";

require_once __DIR__ . '/includes/app.php';

echo "After requiring app.php\n";
echo "Test complete!\n";