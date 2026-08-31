<?php
// Script to automatically wrap all functions in util.php with function_exists() checks

$utilPath = __DIR__ . '/html/includes/util.php';
$content = file_get_contents($utilPath);

// Pattern to find function definitions
$pattern = '/^(function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*(?:\s*:\s*[^{]*)?)\s*\{/m';

$newContent = preg_replace_callback($pattern, function($matches) {
    $functionDeclaration = $matches[1];
    $functionName = $matches[2];
    
    // Skip if already wrapped with function_exists
    if (strpos($matches[0], 'function_exists') !== false) {
        return $matches[0];
    }
    
    return "if (!function_exists('$functionName')) {\n$functionDeclaration {";
}, $content);

// Also need to add closing braces for each wrapped function
// This is trickier, so let's just do the critical functions manually
// Save the processed content
file_put_contents($utilPath . '.auto', $newContent);

echo "Auto-wrapped functions saved to util.php.auto\n";
echo "Please review and manually apply changes.\n";
?>