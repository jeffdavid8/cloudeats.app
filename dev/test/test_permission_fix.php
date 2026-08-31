<?php
// Test the permission update fix
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== BEFORE UPDATE ===\n";
$userApps = $permissionsMatrix->getUserApps('demo');
echo "Demo user has access to: " . implode(', ', array_keys($userApps)) . "\n\n";

echo "=== Simulating removal of Recipe Manager app ===\n";
// Simulate what the frontend does when you uncheck Recipe Manager but keep Weather and BibleBot
$username = 'demo';
$role = 'user';
$apps = ['weather', 'bibleBot']; // Recipe Manager is NOT in this list

// Get all available apps to properly clear old permissions
$permissionsSummary = $permissionsMatrix->getPermissionsSummary();
$allApps = array_keys($permissionsSummary['apps'] ?? []);

echo "All available apps: " . implode(', ', $allApps) . "\n";
echo "Apps to keep: " . implode(', ', $apps) . "\n";

// Clear all existing app permissions first
foreach ($allApps as $appName) {
    $permissionsMatrix->removeUserPermission($username, "apps.{$appName}");
    echo "Removed permission for apps.{$appName}\n";
}

// Set new app-specific permissions
foreach ($apps as $appName) {
    $permissionsMatrix->setUserPermission($username, "apps.{$appName}", ['access']);
    echo "Added permission for apps.{$appName}\n";
}

echo "\n=== AFTER UPDATE ===\n";
$userApps = $permissionsMatrix->getUserApps('demo');
echo "Demo user now has access to: " . implode(', ', array_keys($userApps)) . "\n";

if (!isset($userApps['recipes'])) {
    echo "✅ SUCCESS: Recipe Manager app access has been removed!\n";
} else {
    echo "❌ FAILED: Recipe Manager app access is still present\n";
}
?>