<?php
// Test the role management system
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$permissionsMatrix = new PermissionsMatrix();

echo "=== TESTING ROLE MANAGEMENT SYSTEM ===\n\n";

// Test 1: Create a new custom role
echo "1. Creating a new 'moderator' role...\n";
$result = $permissionsMatrix->createRole(
    'moderator', 
    'Moderator', 
    'Can moderate content and manage some users',
    ['user'], // inherit from user role
    ['recipes', 'bibleBot'], // app access
    [
        'recipes' => [
            'recipes' => ['view', 'create', 'update', 'delete'],
            'voice_control' => ['use']
        ],
        'bibleBot' => [
            'search' => ['use'],
            'bookmarks' => ['create', 'view', 'delete']
        ]
    ], // feature permissions
    [] // no system permissions
);

if ($result['success']) {
    echo "✅ Moderator role created successfully\n";
} else {
    echo "❌ Failed to create moderator role: " . $result['error'] . "\n";
}

// Test 2: List all roles
echo "\n2. Listing all roles:\n";
$summary = $permissionsMatrix->getPermissionsSummary();
foreach ($summary['roles'] as $roleKey => $roleData) {
    echo "  - {$roleKey}: {$roleData['name']}\n";
}

// Test 3: Update the new role
echo "\n3. Updating moderator role to add weather app access...\n";
$result = $permissionsMatrix->updateRole(
    'moderator', 
    'moderator', 
    'Moderator', 
    'Can moderate content, manage some users, and access weather',
    ['user'], // inherit from user role
    ['recipes', 'bibleBot', 'weather'], // added weather app access
    [
        'recipes' => [
            'recipes' => ['view', 'create', 'update', 'delete'],
            'voice_control' => ['use']
        ],
        'bibleBot' => [
            'search' => ['use'],
            'bookmarks' => ['create', 'view', 'delete']
        ],
        'weather' => [
            'current_weather' => ['view'],
            'forecasts' => ['view']
        ]
    ], // feature permissions
    [] // no system permissions
);

if ($result['success']) {
    echo "✅ Moderator role updated successfully\n";
} else {
    echo "❌ Failed to update moderator role: " . $result['error'] . "\n";
}

// Test 4: Try to delete a system role (should fail)
echo "\n4. Attempting to delete 'admin' role (should fail)...\n";
$result = $permissionsMatrix->deleteRole('admin');
if (!$result['success']) {
    echo "✅ Correctly prevented deletion of system role: " . $result['error'] . "\n";
} else {
    echo "❌ System role deletion should have been prevented!\n";
}

// Test 5: Delete the custom role
echo "\n5. Deleting the custom moderator role...\n";
$result = $permissionsMatrix->deleteRole('moderator');
if ($result['success']) {
    echo "✅ Moderator role deleted successfully\n";
} else {
    echo "❌ Failed to delete moderator role: " . $result['error'] . "\n";
}

// Test 6: Verify role was deleted
echo "\n6. Verifying role deletion:\n";
$summary = $permissionsMatrix->getPermissionsSummary();
if (isset($summary['roles']['moderator'])) {
    echo "❌ Moderator role still exists!\n";
} else {
    echo "✅ Moderator role successfully removed\n";
}

echo "\n=== ROLE MANAGEMENT SYSTEM TESTS COMPLETE ===\n";
?>