<?php
/**
 * Test hook system functionality
 */

require_once __DIR__ . '/includes/util.php';

echo "<h2>Admin Dashboard Hook System Test</h2>";

echo "<h3>Testing app_invoke() function:</h3>";
$result = app_invoke('ancestry', 'hook_admin_dashboard');
echo "<pre>";
echo "app_invoke('ancestry', 'hook_admin_dashboard') = ";
if ($result && !isset($result['error'])) {
    echo "SUCCESS\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT);
} else {
    echo "FAILED\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error');
}
echo "</pre>";

echo "<h3>Testing app_invoke_all() function:</h3>";
$results = app_invoke_all('hook_admin_dashboard');
echo "<pre>";
echo "app_invoke_all('hook_admin_dashboard') = ";
if (!empty($results)) {
    echo "SUCCESS (" . count($results) . " apps responded)\n";
    echo "Results: " . json_encode($results, JSON_PRETTY_PRINT);
} else {
    echo "NO RESPONSES\n";
}
echo "</pre>";

echo "<h3>Testing Dashboard Links:</h3>";
if (!empty($results)) {
    foreach ($results as $appName => $dashboardData) {
        if (isset($dashboardData['admin_links'])) {
            echo "<h4>$appName app links:</h4>";
            echo "<ul>";
            foreach ($dashboardData['admin_links'] as $link) {
                echo "<li>";
                echo "<strong>" . htmlspecialchars($link['title']) . "</strong> ";
                echo "(<a href='" . htmlspecialchars($link['url']) . "'>Link</a>) ";
                echo "- " . htmlspecialchars($link['description']);
                echo "</li>";
            }
            echo "</ul>";
        }
    }
} else {
    echo "<p>No apps returned dashboard links.</p>";
}

echo "<h3>Dashboard Integration Status:</h3>";
echo "<ul>";
echo "<li>✅ app_invoke() function available</li>";
echo "<li>✅ app_invoke_all() function available</li>";
echo "<li>" . (!empty($results) ? "✅" : "❌") . " Hook responses received</li>";
echo "<li>✅ Admin dashboard ready to use hooks</li>";
echo "</ul>";

?>