<?php
echo "<h1>Event Logging Control</h1>";

$eventLogger = EventLogger::getInstance();

echo "<h3>Current Status:</h3>";
$enabled = $eventLogger->isEnabled();
echo "<p><strong>Event Logging:</strong> " . ($enabled ? '<span style="color: green;">ENABLED</span>' : '<span style="color: red;">DISABLED</span>') . "</p>";
echo "<p><strong>Log File:</strong> " . $eventLogger->getLogFile() . "</p>";

if (file_exists($eventLogger->getLogFile())) {
    $fileSize = filesize($eventLogger->getLogFile());
    $lastModified = date('Y-m-d H:i:s', filemtime($eventLogger->getLogFile()));
    echo "<p><strong>Log File Size:</strong> " . number_format($fileSize) . " bytes</p>";
    echo "<p><strong>Last Modified:</strong> $lastModified</p>";
}

echo "<hr>";

// Handle toggle actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'enable':
            $eventLogger->enable();
            echo "<p style='color: green;'>✅ Event logging has been ENABLED</p>";
            break;
        case 'disable':
            $eventLogger->disable();
            echo "<p style='color: red;'>❌ Event logging has been DISABLED</p>";
            break;
    }
    echo "<script>setTimeout(function() { window.location.reload(); }, 1000);</script>";
}

?>

<h3>Quick Toggle:</h3>
<form method="post" style="display: inline;">
    <input type="hidden" name="action" value="<?= $enabled ? 'disable' : 'enable' ?>">
    <button type="submit" style="padding: 10px 20px; font-size: 16px; <?= $enabled ? 'background: red; color: white;' : 'background: green; color: white;' ?> border: none; cursor: pointer;">
        <?= $enabled ? '🛑 Disable Event Logging' : '✅ Enable Event Logging' ?>
    </button>
</form>

<hr>

<h3>Alternative Control Methods:</h3>

<h4>1. Admin Interface:</h4>
<p>Visit <a href="/?app=admin&page=logs" target="_blank">Admin → Logs Page</a> and use the toggle button.</p>

<h4>2. Configuration File:</h4>
<p>Edit <code>/logs/event_config.json</code> and set <code>"enabled": true/false</code></p>

<h4>3. Programmatic Control:</h4>
<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">
// In PHP code:
$eventLogger = EventLogger::getInstance();
$eventLogger->disable();  // Turn off
$eventLogger->enable();   // Turn on
$eventLogger->isEnabled(); // Check status
</pre>

<h4>4. API Control:</h4>
<pre style="background: #f5f5f5; padding: 10px; border-radius: 4px;">
// Toggle via API:
fetch('/api.php?app=admin', {
    method: 'POST',
    body: 'action=toggle_event_logging'
})
</pre>

<p><a href="/">← Back to Home</a> | <a href="/?app=admin&page=logs">Go to Logs Page →</a></p>