<?php
// Simple admin logs test
require_once 'includes/app.php';
require_once 'includes/EventLogger.php';

$app = App::getInstance();
$eventLogger = EventLogger::resetInstance();

header('Content-Type: application/json');

$entries = $eventLogger->getRecentEntries(5);
$response = [
    'status' => 'success',
    'enabled' => $eventLogger->isEnabled(),
    'entries_count' => count($entries),
    'entries' => $entries
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>