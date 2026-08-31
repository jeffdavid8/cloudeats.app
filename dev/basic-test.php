<?php
// Simple test without TTS service dependencies
echo "<!DOCTYPE html><html><head><title>Basic Test</title></head><body>";
echo "<h1>Basic PHP Test</h1>";

echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test if basic includes work
try {
    require_once __DIR__ . '/includes/app.php';
    echo "<p>✓ App.php included successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ App.php error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test autoloader file
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo "<p>✓ Autoload file exists at: $autoloadFile</p>";
    try {
        require_once $autoloadFile;
        echo "<p>✓ Autoload included successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Autoload error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p>❌ Autoload file not found</p>";
}

// Test if Google Cloud classes exist
if (class_exists('Google\\Cloud\\TextToSpeech\\V1\\TextToSpeechClient')) {
    echo "<p>✓ Google Cloud TTS client available</p>";
} else {
    echo "<p>❌ Google Cloud TTS client not found</p>";
}

echo "</body></html>";
?>