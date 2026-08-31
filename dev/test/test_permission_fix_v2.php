<?php
// Test the improved permission update fix with denials
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== BEFORE UPDATE ===\n";
$userApps = $permissionsMatrix->getUserApps('demo');
echo "Demo user has access to: " . implode(', ', array_keys($userApps)) . "\n";

// Check what the user's role grants
$permissionsSummary = $permissionsMatrix->getPermissionsSummary();
$userRole = 'user';
$rolePermissions = $permissionsSummary['roles'][$userRole]['permissions'] ?? [];
echo "User role '{$userRole}' grants access to: ";
$roleApps = [];
foreach ($rolePermissions as $perm => $actions) {
    if (strpos($perm, 'apps.') === 0 && strpos($perm, '.features') === false) {
        $appName = substr($perm, 5); // Remove 'apps.' prefix
        $roleApps[] = $appName;
    }
}
echo implode(', ', $roleApps) . "\n\n";

echo "=== Simulating removal of Recipe Manager app ===\n";
// Simulate what the frontend does when you uncheck Recipe Manager but keep Weather and BibleBot
$username = 'demo';
$role = 'user';
$apps = ['weather', 'bibleBot']; // Recipe Manager is NOT in this list

$allApps = array_keys($permissionsSummary['apps'] ?? []);
echo "All available apps: " . implode(', ', $allApps) . "\n";
echo "Apps to keep: " . implode(', ', $apps) . "\n";

// Clear all existing custom app permissions and denials first
foreach ($allApps as $appName) {
    $permissionsMatrix->removeUserPermission($username, "apps.{$appName}");
    $permissionsMatrix->removeDeniedPermission($username, "apps.{$appName}");
}

// For each app, determine what to do based on selection and role permissions
foreach ($allApps as $appName) {
    $isSelected = in_array($appName, $apps);
    $grantedByRole = isset($rolePermissions["apps.{$appName}"]);
    
    echo "App: {$appName} | Selected: " . ($isSelected ? 'YES' : 'NO') . " | Granted by role: " . ($grantedByRole ? 'YES' : 'NO') . " → ";
    
    if ($isSelected && !$grantedByRole) {
        // App is selected but not granted by role → add custom permission
        $permissionsMatrix->setUserPermission($username, "apps.{$appName}", ['access']);
        echo "Added custom permission\n";
    } elseif (!$isSelected && $grantedByRole) {
        // App is not selected but granted by role → add denial
        $permissionsMatrix->denyUserPermission($username, "apps.{$appName}", ['access']);
        echo "Added denial\n";
    } else {
        echo "No action needed\n";
    }
}

echo "\n=== AFTER UPDATE ===\n";
$userApps = $permissionsMatrix->getUserApps('demo');
echo "Demo user now has access to: " . implode(', ', array_keys($userApps)) . "\n";

if (!isset($userApps['recipes'])) {
    echo "✅ SUCCESS: Recipe Manager app access has been removed!\n";
} else {
    echo "❌ FAILED: Recipe Manager app access is still present\n";
}

// Show the denied permissions
$userPermissions = $permissionsMatrix->getUserPermissions('demo');
if (isset($userPermissions['denied_permissions']) && !empty($userPermissions['denied_permissions'])) {
    echo "\nDenied permissions:\n";
    foreach ($userPermissions['denied_permissions'] as $resource => $actions) {
        echo "  - {$resource}: " . implode(', ', $actions) . "\n";
    }
}
?>