<?php
/**
 * Simple TTS Service Test
 * Check if the new TTS classes can be loaded properly
 */

require_once __DIR__ . '/includes/app.php';

echo "<!DOCTYPE html><html><head><title>TTS Service Load Test</title></head><body>";
echo "<h1>TTS Service Load Test</h1>";

try {
    echo "<p>✓ App instance loaded successfully</p>";
    
    // Test autoloader
    echo "<p>Testing autoloader...</p>";
    
    // Check if our TTS service file exists
    $serviceFile = __DIR__ . '/includes/Services/TextToSpeechService.php';
    if (file_exists($serviceFile)) {
        echo "<p>✓ TextToSpeechService.php file exists</p>";
    } else {
        echo "<p>❌ TextToSpeechService.php file not found</p>";
    }
    
    // Test class loading
    echo "<p>Testing class loading...</p>";
    
    if (class_exists('MediaBrain\\Services\\TextToSpeechService')) {
        echo "<p>✓ TextToSpeechService class can be loaded</p>";
    } else {
        echo "<p>❌ TextToSpeechService class not found</p>";
        echo "<p>Checking composer autoload...</p>";
        
        // Try to manually include the file
        if (file_exists($serviceFile)) {
            require_once $serviceFile;
            if (class_exists('MediaBrain\\Services\\TextToSpeechService')) {
                echo "<p>✓ TextToSpeechService loaded manually</p>";
            } else {
                echo "<p>❌ TextToSpeechService still not available after manual include</p>";
            }
        }
    }
    
    // Check Google Cloud TTS dependencies
    echo "<p>Testing Google Cloud TTS dependencies...</p>";
    if (class_exists('Google\\Cloud\\TextToSpeech\\V1\\TextToSpeechClient')) {
        echo "<p>✓ Google Cloud TTS client available</p>";
    } else {
        echo "<p>❌ Google Cloud TTS client not found</p>";
    }
    
    echo "<p>Test completed successfully!</p>";
    echo '<p><a href="/tts-v2-test.php">Try TTS v2 Test Page</a></p>';
    echo '<p><a href="/">Back to MediaBrain</a></p>';
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>