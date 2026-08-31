<?php
require_once __DIR__ . '/includes/app.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>PHP Environment Check</title></head><body>";
echo "<h1>PHP Environment & TTS Service Check</h1>";

echo "<h2>Basic PHP Info</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";
echo "<p>Include Path: " . get_include_path() . "</p>";

echo "<h2>Composer Autoloader</h2>";
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "<p>✓ Composer autoload file exists</p>";
    require_once $autoloadFile;
    echo "<p>✓ Composer autoload included</p>";
} else {
    echo "<p>❌ Composer autoload file not found: $autoloadFile</p>";
}

echo "<h2>TTS Service Files</h2>";
$servicesDir = __DIR__ . '/includes/Services';
echo "<p>Services directory: $servicesDir</p>";
if (is_dir($servicesDir)) {
    echo "<p>✓ Services directory exists</p>";
    $files = scandir($servicesDir);
    echo "<p>Files in Services directory:</p><ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Services directory not found</p>";
}

echo "<h2>Class Availability</h2>";
$classes = [
    'App',
    'EventLogger',
    'AuthManager',
    'Google\\Cloud\\TextToSpeech\\V1\\TextToSpeechClient',
    'MediaBrain\\Services\\TextToSpeechService'
];

foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "<p>✓ $class - Available</p>";
    } else {
        echo "<p>❌ $class - Not found</p>";
    }
}

echo "<h2>File Permissions</h2>";
$testFiles = [
    __DIR__ . '/tts-v2-test.php',
    __DIR__ . '/api-tts-v2.php',
    __DIR__ . '/includes/Services/TextToSpeechService.php',
    __DIR__ . '/storage/cache/tts'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p>✓ $file - Permissions: $perms</p>";
    } else {
        echo "<p>❌ $file - Not found</p>";
    }
}

echo "<h2>Error Testing</h2>";
try {
    $app = App::getInstance();
    echo "<p>✓ App instance created successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ App instance error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo '<p><a href="/tts-v2-test.php">Try TTS v2 Test Page</a></p>';
echo '<p><a href="/">Back to MediaBrain</a></p>';

echo "</body></html>";
?>