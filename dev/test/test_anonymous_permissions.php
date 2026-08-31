<?php
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== TESTING ANONYMOUS USER PERMISSIONS ===\n";

// Test anonymous user
$apps = ['admin', 'recipes', 'weather', 'bibleBot', 'ancestry', 'help'];

echo "\nTesting 'anonymous' user:\n";
foreach ($apps as $app) {
    $canAccess = $permissionsMatrix->canAccessApp('anonymous', $app);
    echo "  {$app}: " . ($canAccess ? '✅ CAN ACCESS' : '❌ NO ACCESS') . "\n";
}

echo "\nTesting 'demo' user:\n";
foreach ($apps as $app) {
    $canAccess = $permissionsMatrix->canAccessApp('demo', $app);
    echo "  {$app}: " . ($canAccess ? '✅ CAN ACCESS' : '❌ NO ACCESS') . "\n";
}

echo "\nAnonymous user permissions:\n";
$anonPerms = $permissionsMatrix->getUserPermissions('anonymous');
echo json_encode($anonPerms, JSON_PRETTY_PRINT) . "\n";

echo "\nAnonymous user apps:\n";
$anonApps = $permissionsMatrix->getUserApps('anonymous');
foreach ($anonApps as $appName => $appInfo) {
    echo "  - {$appName}: {$appInfo['name']}\n";
}
?>