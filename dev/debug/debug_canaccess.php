<?php
// Debug the canAccessApp method
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== DEBUG canAccessApp method ===\n";

$username = 'demo';
$appName = 'recipes';

echo "Testing access for user '{$username}' to app '{$appName}':\n";

// Test both parts of the OR condition
$hasPermissionWithAccess = $permissionsMatrix->hasPermission($username, "apps.{$appName}", 'access');
$hasPermissionWithoutAccess = $permissionsMatrix->hasPermission($username, "apps.{$appName}");

echo "hasPermission('{$username}', 'apps.{$appName}', 'access') = " . ($hasPermissionWithAccess ? 'TRUE' : 'FALSE') . "\n";
echo "hasPermission('{$username}', 'apps.{$appName}') = " . ($hasPermissionWithoutAccess ? 'TRUE' : 'FALSE') . "\n";

$canAccess = $permissionsMatrix->canAccessApp($username, $appName);
echo "canAccessApp('{$username}', '{$appName}') = " . ($canAccess ? 'TRUE' : 'FALSE') . "\n";

// Check the user's permissions data
$userPermissions = $permissionsMatrix->getUserPermissions($username);
echo "\nUser permissions data:\n";
echo "Role: " . ($userPermissions['role'] ?? 'unknown') . "\n";
echo "Custom permissions: " . json_encode($userPermissions['custom_permissions'] ?? []) . "\n";
echo "Denied permissions: " . json_encode($userPermissions['denied_permissions'] ?? []) . "\n";
?>