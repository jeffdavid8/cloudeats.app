<?php
require_once __DIR__ . '/includes/app.php';
$app = App::getInstance();
$auth = $app->getAuthManager();

// Check admin access for this test page
if (!$auth->userIsAdmin($_SESSION['user'] ?? null)) {
    header('Location: /?app=admin&p=login&return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Load composer autoloader
$autoloadPath = dirname(__DIR__, 1) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Manually include our TTS service for now since autoloader might not be updated in container
$ttsServicePath = __DIR__ . '/includes/Services/TextToSpeechService.php';
if (file_exists($ttsServicePath)) {
    require_once $ttsServicePath;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS v2 Simple Test - MediaBrain</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- Bootstrap CSS for quick styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .status-box {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-family: monospace;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <header class="mb-4">
            <h1><i class="fas fa-microphone text-primary"></i> TTS v2 Simple Test</h1>
            <p class="lead">Testing basic TTS functionality</p>
            <nav>
                <a href="/" class="btn btn-outline-secondary">← Back to MediaBrain</a>
            </nav>
        </header>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>System Status</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        echo '<div class="status-box">';
                        echo '<h5>Environment Check</h5>';
                        
                        // Check PHP version
                        echo '<p>✓ PHP Version: ' . PHP_VERSION . '</p>';
                        
                        // Check if autoloader works
                        if (class_exists('Google\\Cloud\\TextToSpeech\\V1\\TextToSpeechClient')) {
                            echo '<p class="success">✓ Google Cloud TTS Client available</p>';
                        } else {
                            echo '<p class="error">❌ Google Cloud TTS Client not available</p>';
                        }
                        
                        // Check if our TTS service can be loaded
                        if (class_exists('MediaBrain\\Services\\TextToSpeechService')) {
                            echo '<p class="success">✓ TTS Service class loaded</p>';
                            
                            try {
                                // Try to instantiate the service
                                $ttsService = new MediaBrain\Services\TextToSpeechService([
                                    'enable_caching' => false // Disable cache for testing
                                ]);
                                echo '<p class="success">✓ TTS Service instantiated successfully</p>';
                                
                            } catch (Exception $e) {
                                echo '<p class="error">❌ TTS Service instantiation failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
                            }
                        } else {
                            echo '<p class="warning">⚠ TTS Service class not found - checking manual include</p>';
                            
                            if (file_exists($ttsServicePath)) {
                                echo '<p class="success">✓ TTS Service file exists</p>';
                                echo '<p>File path: ' . $ttsServicePath . '</p>';
                            } else {
                                echo '<p class="error">❌ TTS Service file not found</p>';
                            }
                        }
                        
                        // Check composer autoload
                        if (file_exists($autoloadPath)) {
                            echo '<p class="success">✓ Composer autoload file exists</p>';
                        } else {
                            echo '<p class="error">❌ Composer autoload file not found</p>';
                            echo '<p>Expected path: ' . $autoloadPath . '</p>';
                        }
                        
                        // Test legacy TTS API
                        echo '<h6 class="mt-3">Legacy API Test</h6>';
                        if (function_exists('api_text_to_speech')) {
                            echo '<p class="success">✓ Legacy TTS function available</p>';
                        } else {
                            echo '<p class="warning">⚠ Legacy TTS function not in global scope (normal)</p>';
                        }
                        
                        echo '</div>';
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Quick TTS Test</h3>
                    </div>
                    <div class="card-body">
                        <textarea id="test-text" class="form-control mb-3" rows="3">Hello! This is a test of the TTS system.</textarea>
                        
                        <button id="test-legacy-btn" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-play"></i> Test Legacy TTS
                        </button>
                        
                        <?php if (class_exists('MediaBrain\\Services\\TextToSpeechService')): ?>
                        <button id="test-v2-btn" class="btn btn-success w-100">
                            <i class="fas fa-star"></i> Test TTS v2
                        </button>
                        <?php else: ?>
                        <button class="btn btn-secondary w-100" disabled>
                            <i class="fas fa-exclamation"></i> TTS v2 Not Available
                        </button>
                        <?php endif; ?>
                        
                        <div id="test-status" class="status-box mt-3">Ready to test...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        window.mb = window.mb || {};
        mb.csrf_token = document.querySelector('meta[name="csrf-token"]').content;
        
        function updateStatus(message, type = 'info') {
            const statusEl = document.getElementById('test-status');
            statusEl.textContent = '[' + new Date().toLocaleTimeString() + '] ' + message;
            statusEl.className = 'status-box ' + type;
        }
        
        // Test legacy TTS
        document.getElementById('test-legacy-btn').addEventListener('click', async function() {
            const text = document.getElementById('test-text').value.trim();
            if (!text) {
                updateStatus('Please enter some text', 'warning');
                return;
            }
            
            updateStatus('Testing legacy TTS API...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('action', 'text_to_speech');
                formData.append('csrf_token', mb.csrf_token);
                formData.append('words', text);
                
                const response = await fetch('/api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateStatus('Legacy TTS successful! Playing audio...', 'success');
                    
                    // Play the audio
                    const audioUrl = `data:audio/mp3;base64,${result.audioContent}`;
                    const audio = new Audio(audioUrl);
                    audio.play().catch(e => {
                        updateStatus('Audio playback failed: ' + e.message, 'error');
                    });
                } else {
                    updateStatus('Legacy TTS failed: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                updateStatus('Legacy TTS request failed: ' + error.message, 'error');
            }
        });
        
        // Test TTS v2 (if available)
        <?php if (class_exists('MediaBrain\\Services\\TextToSpeechService')): ?>
        document.getElementById('test-v2-btn').addEventListener('click', async function() {
            const text = document.getElementById('test-text').value.trim();
            if (!text) {
                updateStatus('Please enter some text', 'warning');
                return;
            }
            
            updateStatus('Testing TTS v2 API...', 'info');
            
            try {
                const formData = new FormData();
                formData.append('action', 'text_to_speech');
                formData.append('csrf_token', mb.csrf_token);
                formData.append('words', text);
                
                const response = await fetch('/api-tts-v2.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    updateStatus('TTS v2 successful! Playing audio...', 'success');
                    
                    // Play the audio
                    const audioUrl = `data:audio/mp3;base64,${result.audioContent}`;
                    const audio = new Audio(audioUrl);
                    audio.play().catch(e => {
                        updateStatus('Audio playback failed: ' + e.message, 'error');
                    });
                } else {
                    updateStatus('TTS v2 failed: ' + (result.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                updateStatus('TTS v2 request failed: ' + error.message, 'error');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>