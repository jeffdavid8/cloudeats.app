<?php
/**
 * Permission Management Helper Script
 * 
 * This script provides easy methods to modify user permissions and roles.
 * Run this script from the command line or through a web interface.
 * 
 * Usage examples:
 * php manage_permissions.php --user=demo --add-app=help
 * php manage_permissions.php --user=demo --remove-app=recipes
 * php manage_permissions.php --user=demo --role=editor
 * php manage_permissions.php --list-permissions
 */

require_once 'html/apps/admin/includes/PermissionsMatrix.php';

class PermissionManager {
    private $permissionsMatrix;
    
    public function __construct() {
        $this->permissionsMatrix = new PermissionsMatrix();
    }
    
    /**
     * Add app access to a user
     */
    public function addAppAccess($username, $appName) {
        try {
            $this->permissionsMatrix->setUserPermission($username, "apps.{$appName}", ['access']);
            echo "✅ Added {$appName} access for user '{$username}'\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error adding app access: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Remove app access from a user
     */
    public function removeAppAccess($username, $appName) {
        try {
            // Check if the app is granted by role
            $userPermissions = $this->permissionsMatrix->getUserPermissions($username);
            $allPermissions = $this->permissionsMatrix->getPermissionsSummary();
            $rolePermissions = $allPermissions['roles'][$userPermissions['role']]['permissions'] ?? [];
            
            if (isset($rolePermissions["apps.{$appName}"])) {
                // App is granted by role, need to deny it
                $this->permissionsMatrix->denyUserPermission($username, "apps.{$appName}", ['access']);
                echo "✅ Denied {$appName} access for user '{$username}' (was granted by role)\n";
            } else {
                // App is custom permission, just remove it
                $this->permissionsMatrix->removeUserPermission($username, "apps.{$appName}");
                echo "✅ Removed {$appName} access for user '{$username}'\n";
            }
            return true;
        } catch (Exception $e) {
            echo "❌ Error removing app access: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Change user role
     */
    public function changeUserRole($username, $role) {
        try {
            $this->permissionsMatrix->setUserRole($username, $role);
            echo "✅ Changed role for user '{$username}' to '{$role}'\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Error changing user role: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * List all user permissions
     */
    public function listUserPermissions($username = null) {
        try {
            $summary = $this->permissionsMatrix->getPermissionsSummary();
            
            if ($username) {
                if (!isset($summary['users'][$username])) {
                    echo "❌ User '{$username}' not found\n";
                    return false;
                }
                
                echo "=== PERMISSIONS FOR USER: {$username} ===\n";
                $userPerms = $summary['users'][$username];
                echo "Role: {$userPerms['role']}\n";
                
                $userApps = $this->permissionsMatrix->getUserApps($username);
                echo "Accessible Apps: " . implode(', ', array_keys($userApps)) . "\n";
                
                if (!empty($userPerms['custom_permissions'])) {
                    echo "Custom Permissions:\n";
                    foreach ($userPerms['custom_permissions'] as $resource => $actions) {
                        echo "  - {$resource}: " . implode(', ', $actions) . "\n";
                    }
                }
                
                if (!empty($userPerms['denied_permissions'])) {
                    echo "Denied Permissions:\n";
                    foreach ($userPerms['denied_permissions'] as $resource => $actions) {
                        echo "  - {$resource}: " . implode(', ', $actions) . "\n";
                    }
                }
            } else {
                echo "=== ALL USER PERMISSIONS ===\n";
                foreach ($summary['users'] as $user => $perms) {
                    $userApps = $this->permissionsMatrix->getUserApps($user);
                    echo "{$user} ({$perms['role']}): " . implode(', ', array_keys($userApps)) . "\n";
                }
            }
            return true;
        } catch (Exception $e) {
            echo "❌ Error listing permissions: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * List all available roles
     */
    public function listRoles() {
        try {
            $summary = $this->permissionsMatrix->getPermissionsSummary();
            echo "=== AVAILABLE ROLES ===\n";
            foreach ($summary['roles'] as $roleName => $roleConfig) {
                echo "{$roleName}: {$roleConfig['name']} - {$roleConfig['description']}\n";
                
                // Show app access for this role
                $appAccess = [];
                foreach ($roleConfig['permissions'] as $perm => $actions) {
                    if (strpos($perm, 'apps.') === 0 && strpos($perm, '.features') === false) {
                        $appName = substr($perm, 5);
                        $appAccess[] = $appName;
                    }
                }
                echo "  Apps: " . implode(', ', $appAccess) . "\n\n";
            }
            return true;
        } catch (Exception $e) {
            echo "❌ Error listing roles: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test anonymous user permissions
     */
    public function testAnonymousAccess() {
        echo "=== ANONYMOUS USER ACCESS ===\n";
        $apps = ['admin', 'recipes', 'weather', 'bibleBot', 'ancestry', 'help'];
        foreach ($apps as $app) {
            $canAccess = $this->permissionsMatrix->canAccessApp('anonymous', $app);
            echo "  {$app}: " . ($canAccess ? '✅ CAN ACCESS' : '❌ NO ACCESS') . "\n";
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $manager = new PermissionManager();
    $options = getopt('', ['user:', 'add-app:', 'remove-app:', 'role:', 'list-permissions', 'list-roles', 'test-anonymous', 'help']);
    
    if (isset($options['help']) || empty($options)) {
        echo "MediaBrain Permission Manager\n";
        echo "=============================\n\n";
        echo "Usage:\n";
        echo "  --user=USERNAME           Target user for operations\n";
        echo "  --add-app=APPNAME         Add app access to user\n";
        echo "  --remove-app=APPNAME      Remove app access from user\n";
        echo "  --role=ROLENAME           Change user role\n";
        echo "  --list-permissions        List all user permissions\n";
        echo "  --list-roles              List all available roles\n";
        echo "  --test-anonymous          Test anonymous user access\n";
        echo "  --help                    Show this help message\n\n";
        echo "Examples:\n";
        echo "  php manage_permissions.php --user=demo --add-app=help\n";
        echo "  php manage_permissions.php --user=demo --remove-app=recipes\n";
        echo "  php manage_permissions.php --user=demo --role=editor\n";
        echo "  php manage_permissions.php --list-permissions\n";
        echo "  php manage_permissions.php --test-anonymous\n";
        exit(0);
    }
    
    if (isset($options['list-permissions'])) {
        $user = $options['user'] ?? null;
        $manager->listUserPermissions($user);
    }
    
    if (isset($options['list-roles'])) {
        $manager->listRoles();
    }
    
    if (isset($options['test-anonymous'])) {
        $manager->testAnonymousAccess();
    }
    
    if (isset($options['user'])) {
        $username = $options['user'];
        
        if (isset($options['add-app'])) {
            $manager->addAppAccess($username, $options['add-app']);
        }
        
        if (isset($options['remove-app'])) {
            $manager->removeAppAccess($username, $options['remove-app']);
        }
        
        if (isset($options['role'])) {
            $manager->changeUserRole($username, $options['role']);
        }
    }
} else {
    // Web interface (basic)
    echo "<h1>Permission Manager</h1>";
    echo "<p>This script is designed to be run from command line.</p>";
    echo "<p>For web-based permission management, use the Admin panel at <a href='?app=admin'>?app=admin</a></p>";
}
?>