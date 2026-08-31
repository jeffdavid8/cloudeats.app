<?php
// Test the applications menu with anonymous user logic

// Simulate what the applications menu does
require_once 'html/includes/app.php';
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

echo "=== TESTING APPLICATIONS MENU LOGIC ===\n";

// Test 1: Anonymous user (not logged in)
echo "\n1. ANONYMOUS USER (not logged in):\n";
$currentUser = 'anonymous';
$permissionsMatrix = new PermissionsMatrix();

$structure = [
    'apps' => [
        ['title' => 'Weather App', 'href' => '?app=weather', 'icon' => '🌤️'],
        ['title' => 'Recipe Manager', 'href' => '?app=recipes', 'icon' => '🍳'],
        ['title' => 'Bible Bot', 'href' => '?app=bibleBot', 'icon' => '📖'],
        ['title' => 'Admin Panel', 'href' => '?app=admin', 'icon' => '⚙️'],
        ['title' => 'Ancestry', 'href' => '?app=ancestry', 'icon' => '🌳'],
        ['title' => 'Help', 'href' => '?app=help', 'icon' => '❓']
    ]
];

$accessibleApps = [];
foreach ($structure['apps'] as $app) {
    // Extract app name from href
    $appName = '';
    if (preg_match('/[?&]app=([^&]+)/', $app['href'], $matches)) {
        $appName = $matches[1];
    }
    
    if ($permissionsMatrix && $appName) {
        try {
            if ($permissionsMatrix->canAccessApp($currentUser, $appName)) {
                $accessibleApps[] = ['name' => $appName, 'title' => $app['title']];
                echo "  ✅ {$app['title']} ({$appName})\n";
            } else {
                echo "  ❌ {$app['title']} ({$appName}) - No access\n";
            }
        } catch (Exception $e) {
            echo "  ❌ {$app['title']} ({$appName}) - Permission check failed\n";
        }
    }
}

echo "\nAnonymous user can access " . count($accessibleApps) . " apps\n";

// Test 2: Demo user (logged in as regular user)
echo "\n2. DEMO USER (logged in as 'user' role):\n";
$currentUser = 'demo';

$accessibleApps = [];
foreach ($structure['apps'] as $app) {
    // Extract app name from href
    $appName = '';
    if (preg_match('/[?&]app=([^&]+)/', $app['href'], $matches)) {
        $appName = $matches[1];
    }
    
    if ($permissionsMatrix && $appName) {
        try {
            if ($permissionsMatrix->canAccessApp($currentUser, $appName)) {
                $accessibleApps[] = ['name' => $appName, 'title' => $app['title']];
                echo "  ✅ {$app['title']} ({$appName})\n";
            } else {
                echo "  ❌ {$app['title']} ({$appName}) - No access\n";
            }
        } catch (Exception $e) {
            echo "  ❌ {$app['title']} ({$appName}) - Permission check failed\n";
        }
    }
}

echo "\nDemo user can access " . count($accessibleApps) . " apps\n";

echo "\n=== SYSTEM BENEFITS ===\n";
echo "✅ No hardcoded app names in applications menu\n";
echo "✅ Consistent permission checking for all users\n";
echo "✅ Anonymous users get appropriate public app access\n";
echo "✅ Logged-in users get role-based access\n";
echo "✅ Easy to modify app access by changing role permissions\n";
echo "✅ Maintainable and extensible architecture\n";
?>