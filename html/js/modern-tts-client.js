/**
 * Modern Text-to-Speech Client v2.0
 * 
 * Enhanced TTS client with features:
 * - Multiple voice support
 * - Audio caching
 * - Queue management
 * - Advanced controls (speed, pitch, volume)
 * - Better error handling
 * - Promise-based API
 */

class ModernTTSClient {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: options.apiEndpoint || '/api-tts-v2.php',
            fallbackToWebSpeech: options.fallbackToWebSpeech !== false,
            cacheAudio: options.cacheAudio !== false,
            maxCacheSize: options.maxCacheSize || 100, // Maximum cached items
            defaultVoice: options.defaultVoice || 'en-US-Neural2-A',
            defaultSpeed: options.defaultSpeed || 1.0,
            defaultVolume: options.defaultVolume || 1.0,
            csrfToken: options.csrfToken || mb?.csrf_token || '',
            ...options
        };

        // Initialize properties
        this.currentAudio = null;
        this.audioQueue = [];
        this.isPlaying = false;
        this.isPaused = false;
        this.cache = new Map();
        this.voices = [];
        this.eventListeners = new Map();
        
        // Initialize
        this.init();
    }

    async init() {
        try {
            // Load available voices
            await this.loadVoices();
            this.emit('ready', { client: this });
        } catch (error) {
            console.warn('TTS client initialization failed:', error.message);
            this.emit('error', { error, client: this });
        }
    }

    /**
     * Synthesize and play text
     */
    async speak(text, options = {}) {
        try {
            const config = { ...this.options, ...options };
            
            // Validate input
            if (!text || typeof text !== 'string') {
                throw new Error('Text is required for synthesis');
            }

            this.emit('synthesisBegan', { text, config });

            // Check cache first
            const cacheKey = this.generateCacheKey(text, config);
            let audioUrl = null;

            if (this.options.cacheAudio && this.cache.has(cacheKey)) {
                audioUrl = this.cache.get(cacheKey);
                console.log('TTS: Using cached audio');
            } else {
                // Synthesize new audio
                audioUrl = await this.synthesizeAudio(text, config);
                
                // Cache the result
                if (this.options.cacheAudio && audioUrl) {
                    this.addToCache(cacheKey, audioUrl);
                }
            }

            if (audioUrl) {
                await this.playAudio(audioUrl, config);
                this.emit('synthesisComplete', { text, audioUrl, config });
            }

        } catch (error) {
            console.error('TTS synthesis error:', error);
            this.emit('error', { error, text });
            
            if (this.options.fallbackToWebSpeech) {
                console.log('Falling back to Web Speech API');
                this.speakWithWebSpeech(text, options);
            } else {
                throw error;
            }
        }
    }

    /**
     * Synthesize audio via API
     */
    async synthesizeAudio(text, config) {
        const formData = new FormData();
        formData.append('action', 'synthesize');
        formData.append('text', text);
        formData.append('csrf_token', this.options.csrfToken);

        // Add voice options
        if (config.voice) formData.append('voice', config.voice);
        if (config.language) formData.append('language', config.language);
        if (config.gender) formData.append('gender', config.gender);
        if (config.format) formData.append('format', config.format);

        const response = await fetch(this.options.apiEndpoint, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`TTS API error: ${response.status} ${response.statusText}`);
        }

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || 'TTS synthesis failed');
        }

        return result.dataUrl || `data:audio/mp3;base64,${result.audioContent}`;
    }

    /**
     * Play audio with enhanced controls
     */
    async playAudio(audioUrl, config = {}) {
        // Stop current playback
        if (this.currentAudio) {
            this.stop();
        }

        return new Promise((resolve, reject) => {
            try {
                this.currentAudio = new Audio(audioUrl);
                
                // Set audio properties
                this.currentAudio.volume = config.volume || this.options.defaultVolume;
                this.currentAudio.playbackRate = config.speed || this.options.defaultSpeed;
                
                // Set up event handlers
                this.currentAudio.onloadstart = () => {
                    this.emit('audioLoading', { audio: this.currentAudio });
                };

                this.currentAudio.oncanplaythrough = () => {
                    this.emit('audioReady', { audio: this.currentAudio });
                };

                this.currentAudio.onplay = () => {
                    this.isPlaying = true;
                    this.isPaused = false;
                    this.emit('playbackStarted', { audio: this.currentAudio });
                };

                this.currentAudio.onpause = () => {
                    this.isPaused = true;
                    this.emit('playbackPaused', { audio: this.currentAudio });
                };

                this.currentAudio.onended = () => {
                    this.isPlaying = false;
                    this.isPaused = false;
                    this.emit('playbackEnded', { audio: this.currentAudio });
                    resolve();
                };

                this.currentAudio.onerror = (error) => {
                    this.emit('audioError', { error, audio: this.currentAudio });
                    reject(new Error('Audio playback failed'));
                };

                this.currentAudio.ontimeupdate = () => {
                    this.emit('timeUpdate', { 
                        currentTime: this.currentAudio.currentTime,
                        duration: this.currentAudio.duration,
                        audio: this.currentAudio 
                    });
                };

                // Start playback
                this.currentAudio.play();

            } catch (error) {
                reject(error);
            }
        });
    }

    /**
     * Fallback to Web Speech API
     */
    speakWithWebSpeech(text, options = {}) {
        if (!('speechSynthesis' in window)) {
            throw new Error('Web Speech API not supported');
        }

        const synth = window.speechSynthesis;
        
        // Cancel any ongoing speech
        if (synth.speaking) {
            synth.cancel();
        }

        const utterance = new SpeechSynthesisUtterance(text);
        
        // Configure utterance
        utterance.rate = options.speed || this.options.defaultSpeed || 1.0;
        utterance.volume = options.volume || this.options.defaultVolume || 1.0;
        utterance.pitch = options.pitch || 1.0;

        // Set voice if available
        if (options.voice) {
            const voices = synth.getVoices();
            const selectedVoice = voices.find(v => 
                v.name === options.voice || v.name.includes(options.voice)
            );
            if (selectedVoice) {
                utterance.voice = selectedVoice;
            }
        }

        // Set up events
        utterance.onstart = () => {
            this.isPlaying = true;
            this.emit('playbackStarted', { utterance, isWebSpeech: true });
        };

        utterance.onend = () => {
            this.isPlaying = false;
            this.emit('playbackEnded', { utterance, isWebSpeech: true });
        };

        utterance.onerror = (error) => {
            this.emit('error', { error, utterance, isWebSpeech: true });
        };

        // Start speech
        synth.speak(utterance);
        this.currentAudio = utterance; // Store for stop() compatibility
    }

    /**
     * Load available voices from API
     */
    async loadVoices() {
        try {
            const formData = new FormData();
            formData.append('action', 'get_voices');
            formData.append('csrf_token', this.options.csrfToken);

            const response = await fetch(this.options.apiEndpoint, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.voices = result.voices || [];
                    this.emit('voicesLoaded', { voices: this.voices });
                }
            }
        } catch (error) {
            console.warn('Failed to load TTS voices:', error);
        }
    }

    /**
     * Preview a voice with sample text
     */
    async previewVoice(voiceId, sampleText = 'Hello! This is a preview of my voice.') {
        try {
            const formData = new FormData();
            formData.append('action', 'preview_voice');
            formData.append('voice', voiceId);
            formData.append('sample_text', sampleText);
            formData.append('csrf_token', this.options.csrfToken);

            const response = await fetch(this.options.apiEndpoint, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Preview failed: ${response.status}`);
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Voice preview failed');
            }

            // Play preview audio
            await this.playAudio(result.dataUrl);
            
            return result;

        } catch (error) {
            console.error('Voice preview error:', error);
            throw error;
        }
    }

    /**
     * Playback control methods
     */
    pause() {
        if (this.currentAudio && this.currentAudio.pause) {
            this.currentAudio.pause();
        } else if ('speechSynthesis' in window) {
            window.speechSynthesis.pause();
        }
    }

    resume() {
        if (this.currentAudio && this.currentAudio.play && this.isPaused) {
            this.currentAudio.play();
        } else if ('speechSynthesis' in window) {
            window.speechSynthesis.resume();
        }
    }

    stop() {
        if (this.currentAudio) {
            if (this.currentAudio.pause) {
                this.currentAudio.pause();
                this.currentAudio.currentTime = 0;
            }
            this.currentAudio = null;
        }
        
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }

        this.isPlaying = false;
        this.isPaused = false;
        this.emit('playbackStopped');
    }

    /**
     * Set playback properties
     */
    setVolume(volume) {
        if (this.currentAudio && this.currentAudio.volume !== undefined) {
            this.currentAudio.volume = Math.max(0, Math.min(1, volume));
        }
        this.options.defaultVolume = volume;
    }

    setSpeed(speed) {
        if (this.currentAudio && this.currentAudio.playbackRate !== undefined) {
            this.currentAudio.playbackRate = Math.max(0.25, Math.min(4, speed));
        }
        this.options.defaultSpeed = speed;
    }

    setVoice(voiceId) {
        this.options.defaultVoice = voiceId;
    }

    /**
     * Queue management
     */
    queueText(text, options = {}) {
        this.audioQueue.push({ text, options });
        
        if (!this.isPlaying) {
            this.processQueue();
        }
    }

    async processQueue() {
        while (this.audioQueue.length > 0 && !this.isPlaying) {
            const { text, options } = this.audioQueue.shift();
            await this.speak(text, options);
        }
    }

    clearQueue() {
        this.audioQueue = [];
    }

    /**
     * Cache management
     */
    generateCacheKey(text, config) {
        const keyData = {
            text,
            voice: config.voice || this.options.defaultVoice,
            format: config.format || 'mp3'
        };
        return btoa(JSON.stringify(keyData)).replace(/[^a-zA-Z0-9]/g, '');
    }

    addToCache(key, audioUrl) {
        if (this.cache.size >= this.options.maxCacheSize) {
            // Remove oldest entry (simple LRU)
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        this.cache.set(key, audioUrl);
    }

    clearCache() {
        this.cache.clear();
        this.emit('cacheCleared');
    }

    getCacheStats() {
        return {
            size: this.cache.size,
            maxSize: this.options.maxCacheSize
        };
    }

    /**
     * Event system
     */
    on(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    off(event, callback) {
        if (this.eventListeners.has(event)) {
            const callbacks = this.eventListeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    emit(event, data = {}) {
        if (this.eventListeners.has(event)) {
            this.eventListeners.get(event).forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`TTS event handler error (${event}):`, error);
                }
            });
        }
    }

    /**
     * Utility methods
     */
    getVoices() {
        return [...this.voices];
    }

    getVoicesByLanguage(languageCode) {
        return this.voices.filter(voice => 
            voice.language_codes.includes(languageCode)
        );
    }

    getVoicesByType(type) {
        return this.voices.filter(voice => voice.type === type);
    }

    getCurrentState() {
        return {
            isPlaying: this.isPlaying,
            isPaused: this.isPaused,
            queueLength: this.audioQueue.length,
            cacheSize: this.cache.size,
            currentVoice: this.options.defaultVoice,
            currentSpeed: this.options.defaultSpeed,
            currentVolume: this.options.defaultVolume
        };
    }
}

// Global instance and backward compatibility
let modernTTSClient = null;

// Initialize modern TTS client
function initModernTTS(options = {}) {
    if (!modernTTSClient) {
        modernTTSClient = new ModernTTSClient(options);
        
        // Add global event handlers for UI updates
        modernTTSClient.on('playbackStarted', (data) => {
            document.body.classList.add('tts-playing');
        });
        
        modernTTSClient.on('playbackEnded', (data) => {
            document.body.classList.remove('tts-playing');
        });
        
        modernTTSClient.on('playbackStopped', (data) => {
            document.body.classList.remove('tts-playing');
        });
    }
    return modernTTSClient;
}

// Enhanced backward-compatible speak function
function speak(words, attachListeners) {
    // Initialize modern client if needed
    if (!modernTTSClient) {
        initModernTTS();
    }
    
    // Handle legacy attachListeners callback
    if (typeof attachListeners === 'function') {
        modernTTSClient.on('playbackStarted', (data) => {
            attachListeners(data.audio || data.utterance);
        });
    }
    
    // Use modern speak method
    return modernTTSClient.speak(words);
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ModernTTSClient, initModernTTS, speak };
}

// Auto-initialize on DOM ready
if (typeof mb !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        initModernTTS({
            csrfToken: mb.csrf_token
        });
    });
}

console.log('Modern TTS Client v2.0 loaded successfully');