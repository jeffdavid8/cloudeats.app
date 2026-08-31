<?php
/**
 * Phase 3 Cleanup Script - Development and Debug File Cleanup
 * This script identifies and optionally removes development artifacts from the codebase
 */

echo "MediaBrain Codebase Cleanup Analysis\n";
echo "====================================\n\n";

$projectRoot = __DIR__;
$htmlRoot = $projectRoot . '/html';

// Define cleanup categories
$cleanupCategories = [
    'test_files' => [
        'pattern' => 'test_*.php',
        'description' => 'Development test files',
        'action' => 'move_to_dev'
    ],
    'debug_files' => [
        'pattern' => 'debug_*.php',
        'description' => 'Debug diagnostic files',
        'action' => 'move_to_dev'
    ],
    'admin_test_files' => [
        'pattern' => '*_test.php',
        'description' => 'Admin and component test files',
        'action' => 'move_to_dev'
    ],
    'reset_files' => [
        'pattern' => 'reset_*.php',
        'description' => 'Administrative reset utilities',
        'action' => 'review'  // Keep but document
    ]
];

// Scan for files
echo "Scanning for cleanup candidates:\n";
echo "--------------------------------\n";

$foundFiles = [];
foreach ($cleanupCategories as $category => $config) {
    $foundFiles[$category] = [];
    
    // Scan root directory
    $files = glob($projectRoot . '/' . $config['pattern']);
    foreach ($files as $file) {
        $foundFiles[$category][] = str_replace($projectRoot . '/', '', $file);
    }
    
    // Scan html directory
    $files = glob($htmlRoot . '/' . $config['pattern']);
    foreach ($files as $file) {
        $foundFiles[$category][] = str_replace($projectRoot . '/', '', $file);
    }
    
    // Report findings
    echo "{$config['description']}: " . count($foundFiles[$category]) . " files\n";
    foreach ($foundFiles[$category] as $file) {
        echo "  - $file\n";
    }
    echo "\n";
}

// Check for commented code blocks in important files
echo "Checking for commented code blocks:\n";
echo "----------------------------------\n";

$importantFiles = [
    'html/includes/app.php',
    'html/includes/AuthManager.php',
    'html/includes/AppController.php',
    'html/index.php',
    'html/api.php'
];

foreach ($importantFiles as $file) {
    if (file_exists($projectRoot . '/' . $file)) {
        $content = file_get_contents($projectRoot . '/' . $file);
        $lines = explode("\n", $content);
        $commentBlocks = 0;
        
        foreach ($lines as $lineNum => $line) {
            $trimmed = trim($line);
            // Look for commented out code (not documentation)
            if (preg_match('/^\/\/\s*(require|include|function|\$|if|for|while)/', $trimmed)) {
                $commentBlocks++;
            }
        }
        
        if ($commentBlocks > 0) {
            echo "$file: $commentBlocks commented code lines\n";
        }
    }
}

echo "\nCleanup Summary:\n";
echo "================\n";
$totalFiles = array_sum(array_map('count', $foundFiles));
echo "Total files identified for cleanup: $totalFiles\n";

echo "\nRecommended Actions:\n";
echo "- Move development files to /dev/ directory\n";
echo "- Remove commented code blocks from production files\n";
echo "- Document administrative utilities in README\n";
echo "- Add .gitignore patterns for future development files\n";
?>