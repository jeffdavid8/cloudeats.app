<?php
// Debug the hasPermission method in detail
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

class DebugPermissionsMatrix extends PermissionsMatrix {
    public function debugHasPermission($username, $resource, $action = null) {
        echo "\n=== DEBUG hasPermission('{$username}', '{$resource}', '" . ($action ?? 'null') . "') ===\n";
        
        $userPermissions = $this->getUserPermissions($username);
        $allPermissions = $this->getPermissionsStructure();
        
        // Build the permission key
        $permissionKey = $resource;
        if ($action) {
            $permissionKey .= '.' . $action;
        }
        echo "Permission key: '{$permissionKey}'\n";
        
        // Check for explicit denials first
        echo "Checking denied permissions...\n";
        if (isset($userPermissions['denied_permissions'][$permissionKey])) {
            $deniedActions = $userPermissions['denied_permissions'][$permissionKey];
            echo "Found denied actions for '{$permissionKey}': " . json_encode($deniedActions) . "\n";
            if ($action === null || in_array($action, $deniedActions) || in_array('*', $deniedActions)) {
                echo "DENIED: Action is in denied list\n";
                return false;
            }
        } else {
            echo "No denied permissions found for '{$permissionKey}'\n";
        }
        
        // Also check the base resource for denials
        if ($action && isset($userPermissions['denied_permissions'][$resource])) {
            $deniedActions = $userPermissions['denied_permissions'][$resource];
            echo "Found denied actions for base resource '{$resource}': " . json_encode($deniedActions) . "\n";
            if (in_array($action, $deniedActions) || in_array('*', $deniedActions)) {
                echo "DENIED: Action is in denied list for base resource\n";
                return false;
            }
        }
        
        // Check custom permissions
        echo "Checking custom permissions...\n";
        if (isset($userPermissions['custom_permissions'][$permissionKey])) {
            echo "Found custom permissions for '{$permissionKey}': " . json_encode($userPermissions['custom_permissions'][$permissionKey]) . "\n";
            $result = in_array($action, $userPermissions['custom_permissions'][$permissionKey]);
            echo "CUSTOM: " . ($result ? 'GRANTED' : 'NOT GRANTED') . "\n";
            return $result;
        } else {
            echo "No custom permissions found for '{$permissionKey}'\n";
        }
        
        // Check role-based permissions
        echo "Checking role-based permissions...\n";
        $userRole = $userPermissions['role'] ?? 'guest';
        echo "User role: '{$userRole}'\n";
        $rolePermissions = $this->getRolePermissions($userRole, $allPermissions);
        
        $result = $this->checkPermissionInList($permissionKey, $action, $rolePermissions);
        echo "ROLE: " . ($result ? 'GRANTED' : 'NOT GRANTED') . "\n";
        return $result;
    }
    
    public function getRolePermissions($role, $allPermissions) {
        return parent::getRolePermissions($role, $allPermissions);
    }
    
    public function checkPermissionInList($permissionKey, $action, $rolePermissions) {
        return parent::checkPermissionInList($permissionKey, $action, $rolePermissions);
    }
}

$permissionsMatrix = new DebugPermissionsMatrix();

// Test the specific case that's failing
$permissionsMatrix->debugHasPermission('demo', 'apps.recipes', 'access');
$permissionsMatrix->debugHasPermission('demo', 'apps.recipes');
?>