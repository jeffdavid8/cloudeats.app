<?php
require_once __DIR__ . '/includes/app.php';
$app = App::getInstance();
$auth = $app->getAuthManager();

// Check admin access for this test page
if (!$auth->userIsAdmin($_SESSION['user'] ?? null)) {
    header('Location: /?app=admin&p=login&return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS v2 Test & Demo - MediaBrain</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- Bootstrap CSS for quick styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .demo-section {
            margin: 2rem 0;
            padding: 2rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .status-display {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            font-family: monospace;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .playing-indicator {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: none;
            z-index: 1000;
        }
        .tts-playing .playing-indicator {
            display: block;
        }
        .cache-stats {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="playing-indicator">
        <i class="fas fa-volume-up"></i> TTS Playing...
    </div>

    <div class="container mt-4">
        <header class="mb-4">
            <h1><i class="fas fa-microphone text-primary"></i> Text-to-Speech v2.0 Test & Demo</h1>
            <p class="lead">Testing the modernized TTS system with enhanced features</p>
            <nav>
                <a href="/" class="btn btn-outline-secondary">← Back to MediaBrain</a>
                <a href="/?app=admin" class="btn btn-outline-primary">Admin Panel</a>
            </nav>
        </header>

        <!-- Basic TTS Test -->
        <section class="demo-section">
            <h3><i class="fas fa-play-circle text-success"></i> Basic TTS Test</h3>
            <p>Test basic text-to-speech functionality with the new system.</p>
            
            <div class="row">
                <div class="col-md-8">
                    <textarea id="test-text" class="form-control" rows="3" 
                              placeholder="Enter text to synthesize...">Hello! This is a test of the new MediaBrain Text-to-Speech system version 2.0. It features enhanced voice options, improved caching, and better error handling.</textarea>
                </div>
                <div class="col-md-4">
                    <button id="speak-btn" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-play"></i> Speak Text
                    </button>
                    <button id="stop-btn" class="btn btn-danger w-100 mt-2">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>
            </div>
            
            <div id="basic-status" class="status-display">Ready to test TTS...</div>
        </section>

        <!-- Voice Selection -->
        <section class="demo-section">
            <h3><i class="fas fa-users text-info"></i> Voice Selection</h3>
            <p>Select and preview different TTS voices.</p>
            
            <div id="voice-selector-container">
                <!-- Voice selector component will be inserted here -->
            </div>
        </section>

        <!-- Advanced Controls -->
        <section class="demo-section">
            <h3><i class="fas fa-sliders-h text-warning"></i> Advanced Controls</h3>
            <p>Test advanced TTS features like speed, volume, and queue management.</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h5>Playback Controls</h5>
                    <div class="mb-3">
                        <label for="speed-control" class="form-label">Speed: <span id="speed-value">1.0x</span></label>
                        <input type="range" class="form-range" id="speed-control" min="0.25" max="2.0" step="0.25" value="1.0">
                    </div>
                    <div class="mb-3">
                        <label for="volume-control" class="form-label">Volume: <span id="volume-value">100%</span></label>
                        <input type="range" class="form-range" id="volume-control" min="0" max="1" step="0.1" value="1.0">
                    </div>
                    
                    <div class="btn-group w-100" role="group">
                        <button id="pause-btn" class="btn btn-outline-warning">
                            <i class="fas fa-pause"></i> Pause
                        </button>
                        <button id="resume-btn" class="btn btn-outline-success">
                            <i class="fas fa-play"></i> Resume
                        </button>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5>Queue Management</h5>
                    <div class="mb-3">
                        <input type="text" id="queue-text" class="form-control" placeholder="Text to add to queue...">
                        <button id="add-queue-btn" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-plus"></i> Add to Queue
                        </button>
                        <button id="clear-queue-btn" class="btn btn-outline-danger mt-2">
                            <i class="fas fa-trash"></i> Clear Queue
                        </button>
                    </div>
                    
                    <div id="queue-status" class="alert alert-info">
                        Queue: 0 items
                    </div>
                </div>
            </div>
        </section>

        <!-- System Status -->
        <section class="demo-section">
            <h3><i class="fas fa-info-circle text-secondary"></i> System Status</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>TTS Client Status</h5>
                    <div id="client-status" class="status-display">Initializing...</div>
                </div>
                <div class="col-md-6">
                    <h5>Cache Statistics</h5>
                    <div id="cache-stats" class="cache-stats">
                        <div>Cache Size: <span id="cache-size">0</span> items</div>
                        <div>Max Cache Size: <span id="max-cache-size">100</span> items</div>
                        <button id="clear-cache-btn" class="btn btn-sm btn-outline-danger mt-2">
                            <i class="fas fa-trash"></i> Clear Cache
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- SSML Testing -->
        <section class="demo-section">
            <h3><i class="fas fa-code text-dark"></i> SSML Testing</h3>
            <p>Test Speech Synthesis Markup Language features for enhanced speech quality.</p>
            
            <div class="mb-3">
                <label for="ssml-text" class="form-label">SSML Content:</label>
                <textarea id="ssml-text" class="form-control" rows="4"><speak>
  <emphasis level="moderate">Welcome to MediaBrain</emphasis>
  <break time="0.5s"/>
  This is an example of <emphasis level="strong">SSML markup</emphasis> for enhanced speech synthesis.
  <break time="1s"/>
  <prosody rate="0.8" pitch="+2st">
    You can control the rate and pitch of the speech.
  </prosody>
</speak></textarea>
            </div>
            <button id="speak-ssml-btn" class="btn btn-success">
                <i class="fas fa-magic"></i> Speak SSML
            </button>
        </section>
    </div>

    <!-- Include JavaScript dependencies -->
    <script src="/js/jquery-ready.js"></script>
    <script>
        // Initialize mb object with CSRF token
        window.mb = window.mb || {};
        mb.csrf_token = document.querySelector('meta[name="csrf-token"]').content;
    </script>
    <script src="/js/modern-tts-client.js"></script>
    <script src="/js/voice-selector.js"></script>
    
    <script>
        // Global TTS client instance
        let ttsClient = null;
        let voiceSelector = null;

        // Initialize everything when DOM is ready
        document.addEventListener('DOMContentLoaded', async function() {
            try {
                // Initialize modern TTS client
                ttsClient = initModernTTS({
                    csrfToken: mb.csrf_token
                });

                // Set up event listeners
                setupEventListeners();
                setupTTSEventListeners();
                
                // Wait for client to be ready
                ttsClient.on('ready', function() {
                    updateStatus('basic-status', 'TTS client ready! ✓', 'success');
                    updateClientStatus();
                    
                    // Initialize voice selector
                    try {
                        voiceSelector = new VoiceSelector('voice-selector-container', {
                            onVoiceChange: function(voice) {
                                if (voice) {
                                    updateStatus('basic-status', `Voice changed to: ${voice.name}`, 'info');
                                }
                            }
                        });
                    } catch (error) {
                        console.error('Voice selector initialization failed:', error);
                        updateStatus('basic-status', 'Voice selector failed to load', 'error');
                    }
                });

                updateStatus('basic-status', 'Initializing TTS client...', 'info');

            } catch (error) {
                console.error('TTS initialization error:', error);
                updateStatus('basic-status', 'TTS initialization failed: ' + error.message, 'error');
            }
        });

        function setupEventListeners() {
            // Basic speak button
            document.getElementById('speak-btn').addEventListener('click', async function() {
                const text = document.getElementById('test-text').value.trim();
                if (!text) {
                    updateStatus('basic-status', 'Please enter some text', 'warning');
                    return;
                }

                try {
                    updateStatus('basic-status', 'Synthesizing speech...', 'info');
                    await ttsClient.speak(text);
                    updateStatus('basic-status', 'Speech synthesis completed', 'success');
                } catch (error) {
                    updateStatus('basic-status', 'Speech failed: ' + error.message, 'error');
                }
            });

            // Stop button
            document.getElementById('stop-btn').addEventListener('click', function() {
                ttsClient.stop();
                updateStatus('basic-status', 'Speech stopped', 'info');
            });

            // Speed control
            document.getElementById('speed-control').addEventListener('input', function() {
                const speed = parseFloat(this.value);
                document.getElementById('speed-value').textContent = speed + 'x';
                ttsClient.setSpeed(speed);
            });

            // Volume control
            document.getElementById('volume-control').addEventListener('input', function() {
                const volume = parseFloat(this.value);
                document.getElementById('volume-value').textContent = Math.round(volume * 100) + '%';
                ttsClient.setVolume(volume);
            });

            // Pause/Resume
            document.getElementById('pause-btn').addEventListener('click', () => ttsClient.pause());
            document.getElementById('resume-btn').addEventListener('click', () => ttsClient.resume());

            // Queue management
            document.getElementById('add-queue-btn').addEventListener('click', function() {
                const text = document.getElementById('queue-text').value.trim();
                if (text) {
                    ttsClient.queueText(text);
                    document.getElementById('queue-text').value = '';
                    updateQueueStatus();
                }
            });

            document.getElementById('clear-queue-btn').addEventListener('click', function() {
                ttsClient.clearQueue();
                updateQueueStatus();
            });

            // Clear cache
            document.getElementById('clear-cache-btn').addEventListener('click', function() {
                ttsClient.clearCache();
                updateCacheStats();
            });

            // SSML testing
            document.getElementById('speak-ssml-btn').addEventListener('click', async function() {
                const ssmlText = document.getElementById('ssml-text').value.trim();
                if (!ssmlText) return;

                try {
                    updateStatus('basic-status', 'Synthesizing SSML...', 'info');
                    await ttsClient.speak(ssmlText);
                    updateStatus('basic-status', 'SSML synthesis completed', 'success');
                } catch (error) {
                    updateStatus('basic-status', 'SSML failed: ' + error.message, 'error');
                }
            });
        }

        function setupTTSEventListeners() {
            if (!ttsClient) return;

            ttsClient.on('synthesisBegan', function(data) {
                updateStatus('basic-status', 'Synthesis began...', 'info');
            });

            ttsClient.on('synthesisComplete', function(data) {
                updateStatus('basic-status', 'Synthesis completed ✓', 'success');
                updateCacheStats();
            });

            ttsClient.on('playbackStarted', function(data) {
                updateStatus('basic-status', 'Playback started 🔊', 'info');
                updateClientStatus();
            });

            ttsClient.on('playbackEnded', function(data) {
                updateStatus('basic-status', 'Playback ended', 'info');
                updateClientStatus();
                updateQueueStatus();
            });

            ttsClient.on('playbackPaused', function(data) {
                updateStatus('basic-status', 'Playback paused ⏸️', 'info');
            });

            ttsClient.on('error', function(data) {
                updateStatus('basic-status', 'Error: ' + data.error.message, 'error');
            });

            ttsClient.on('cacheCleared', function(data) {
                updateStatus('basic-status', 'Cache cleared', 'info');
                updateCacheStats();
            });
        }

        function updateStatus(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            if (!element) return;

            const timestamp = new Date().toLocaleTimeString();
            element.innerHTML = `[${timestamp}] ${message}`;
            
            // Update styling based on type
            element.className = 'status-display';
            if (type === 'success') element.style.background = '#d4edda';
            else if (type === 'error') element.style.background = '#f8d7da';
            else if (type === 'warning') element.style.background = '#fff3cd';
            else element.style.background = '#f8f9fa';
        }

        function updateClientStatus() {
            if (!ttsClient) return;

            const state = ttsClient.getCurrentState();
            const statusText = `
                Playing: ${state.isPlaying ? 'Yes' : 'No'} |
                Paused: ${state.isPaused ? 'Yes' : 'No'} |
                Queue: ${state.queueLength} items |
                Voice: ${state.currentVoice} |
                Speed: ${state.currentSpeed}x |
                Volume: ${Math.round(state.currentVolume * 100)}%
            `;
            
            document.getElementById('client-status').textContent = statusText;
        }

        function updateQueueStatus() {
            if (!ttsClient) return;

            const state = ttsClient.getCurrentState();
            const queueElement = document.getElementById('queue-status');
            queueElement.textContent = `Queue: ${state.queueLength} items`;
            queueElement.className = state.queueLength > 0 ? 'alert alert-warning' : 'alert alert-info';
        }

        function updateCacheStats() {
            if (!ttsClient) return;

            const stats = ttsClient.getCacheStats();
            document.getElementById('cache-size').textContent = stats.size;
            document.getElementById('max-cache-size').textContent = stats.maxSize;
        }

        // Update displays periodically
        setInterval(function() {
            updateClientStatus();
            updateCacheStats();
        }, 1000);
    </script>
</body>
</html>