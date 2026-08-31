<?php
/**
 * Debug Permissions Loading
 */

echo "<h1>Permissions Debug</h1>";

try {
    require_once 'apps/admin/includes/PermissionsMatrix.php';
    
    echo "<h2>PermissionsMatrix Test</h2>";
    $permissionsMatrix = new PermissionsMatrix();
    echo "<p>✓ PermissionsMatrix created</p>";
    
    // Test permissions structure loading
    echo "<h3>Permissions Structure</h3>";
    $reflection = new ReflectionClass($permissionsMatrix);
    $method = $reflection->getMethod('getPermissionsStructure');
    $method->setAccessible(true);
    $structure = $method->invoke($permissionsMatrix);
    
    echo "<p><strong>Structure type:</strong> " . gettype($structure) . "</p>";
    if (is_array($structure)) {
        echo "<p><strong>Apps count:</strong> " . (isset($structure['apps']) ? count($structure['apps']) : 'No apps key') . "</p>";
        echo "<p><strong>Roles count:</strong> " . (isset($structure['roles']) ? count($structure['roles']) : 'No roles key') . "</p>";
        
        if (isset($structure['apps'])) {
            echo "<h4>Available Apps:</h4>";
            foreach ($structure['apps'] as $appKey => $appData) {
                echo "<p>- " . htmlspecialchars($appKey) . ": " . htmlspecialchars($appData['name'] ?? 'No name') . "</p>";
            }
        }
        
        if (isset($structure['roles'])) {
            echo "<h4>Available Roles:</h4>";
            foreach ($structure['roles'] as $roleKey => $roleData) {
                echo "<p>- " . htmlspecialchars($roleKey) . ": " . htmlspecialchars($roleData['name'] ?? 'No name') . "</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Structure is not an array</p>";
        echo "<pre>" . var_export($structure, true) . "</pre>";
    }
    
    // Test user permissions loading
    echo "<h3>User Permissions Test</h3>";
    $userPermissions = $permissionsMatrix->getUserPermissions('admin');
    echo "<p><strong>Admin permissions type:</strong> " . gettype($userPermissions) . "</p>";
    echo "<p><strong>Admin role:</strong> " . ($userPermissions['role'] ?? 'No role') . "</p>";
    echo "<p><strong>Custom permissions:</strong> " . count($userPermissions['custom_permissions'] ?? []) . "</p>";
    
    // Test permissions summary
    echo "<h3>Permissions Summary</h3>";
    $summary = $permissionsMatrix->getPermissionsSummary();
    echo "<p><strong>Summary type:</strong> " . gettype($summary) . "</p>";
    if (is_array($summary)) {
        echo "<p><strong>Users in summary:</strong> " . (isset($summary['users']) ? count($summary['users']) : 'No users key') . "</p>";
        
        if (isset($summary['users'])) {
            echo "<h4>Users Found:</h4>";
            foreach ($summary['users'] as $username => $userData) {
                echo "<p>- " . htmlspecialchars($username) . " (role: " . htmlspecialchars($userData['role'] ?? 'unknown') . ")</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>Summary is not an array</p>";
        echo "<pre>" . var_export($summary, true) . "</pre>";
    }
    
    // Test direct storage access
    echo "<h3>Direct Storage Test</h3>";
    require_once 'includes/storage/FileStorageManager.php';
    $storage = FileStorageManager::getInstance();
    
    $permissionsResult = $storage->getJsonData('', 'permissions.json');
    echo "<p><strong>Permissions file success:</strong> " . ($permissionsResult['success'] ? 'YES' : 'NO') . "</p>";
    if ($permissionsResult['success']) {
        $permsData = $permissionsResult['data'];
        echo "<p><strong>Apps in file:</strong> " . (isset($permsData['apps']) ? count($permsData['apps']) : 'No apps') . "</p>";
        echo "<p><strong>Roles in file:</strong> " . (isset($permsData['roles']) ? count($permsData['roles']) : 'No roles') . "</p>";
    } else {
        echo "<p style='color: red;'>Error: " . ($permissionsResult['error'] ?? 'Unknown') . "</p>";
    }
    
    $userPermsResult = $storage->getJsonData('', 'user_permissions.json');
    echo "<p><strong>User permissions file success:</strong> " . ($userPermsResult['success'] ? 'YES' : 'NO') . "</p>";
    if ($userPermsResult['success']) {
        $userPermsData = $userPermsResult['data'];
        echo "<p><strong>Users in file:</strong> " . count($userPermsData) . "</p>";
        foreach ($userPermsData as $username => $userData) {
            echo "<p>- " . htmlspecialchars($username) . " (role: " . htmlspecialchars($userData['role'] ?? 'unknown') . ")</p>";
        }
    } else {
        echo "<p style='color: red;'>Error: " . ($userPermsResult['error'] ?? 'Unknown') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>