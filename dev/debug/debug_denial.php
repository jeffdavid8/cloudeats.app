<?php
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== TESTING DENIAL LOGIC ===\n";

$userPermissions = $permissionsMatrix->getUserPermissions('demo');
echo "User permissions:\n";
echo json_encode($userPermissions, JSON_PRETTY_PRINT) . "\n";

echo "\n=== Manual denial check ===\n";
$resource = 'apps.recipes';
$action = 'access';
$permissionKey = $resource . '.' . $action; // 'apps.recipes.access'

echo "Looking for denied permission '{$permissionKey}'\n";
if (isset($userPermissions['denied_permissions'][$permissionKey])) {
    echo "Found: " . json_encode($userPermissions['denied_permissions'][$permissionKey]) . "\n";
} else {
    echo "Not found\n";
}

echo "Looking for denied permission '{$resource}'\n";
if (isset($userPermissions['denied_permissions'][$resource])) {
    $deniedActions = $userPermissions['denied_permissions'][$resource];
    echo "Found: " . json_encode($deniedActions) . "\n";
    echo "Action '{$action}' in denied list: " . (in_array($action, $deniedActions) ? 'YES' : 'NO') . "\n";
    echo "'*' in denied list: " . (in_array('*', $deniedActions) ? 'YES' : 'NO') . "\n";
} else {
    echo "Not found\n";
}
?>