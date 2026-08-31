<?php

namespace MediaBrain\Services;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;

/**
 * Modern Text-to-Speech Service for MediaBrain
 * 
 * Provides advanced TTS capabilities including:
 * - Multiple voice options (Standard, WaveNet, Neural2)
 * - SSML support for natural speech
 * - Audio caching for performance
 * - Streaming synthesis options
 * - Enhanced error handling with fallbacks
 * 
 * @version 2.0
 */
class TextToSpeechService
{
    private $client;
    private $config;
    private $cache;
    private $logger;

    // Default configuration
    const DEFAULT_CONFIG = [
        'default_voice' => 'en-US-Neural2-A',
        'default_language' => 'en-US',
        'default_gender' => 'NEUTRAL',
        'audio_format' => 'MP3',
        'sample_rate' => 24000,
        'enable_caching' => true,
        'cache_duration' => 86400, // 24 hours
        'enable_ssml' => true,
        'streaming_enabled' => false,
        'max_text_length' => 5000,
        'rate_limit_per_minute' => 60
    ];

    // Voice type priority for selection
    const VOICE_TYPE_PRIORITY = ['Neural2', 'WaveNet', 'Standard'];

    public function __construct(array $config = [])
    {
        // Build waterfall configuration: System defaults → Admin settings → Manual overrides
        $this->config = $this->buildWaterfallConfig($config);
        $this->initializeLogger();
        $this->initializeCache();
    }
    
    /**
     * Build configuration using waterfall pattern
     * 
     * @param array $overrides Manual configuration overrides
     * @return array Final configuration
     */
    private function buildWaterfallConfig(array $overrides = []): array
    {
        // 1. Start with system defaults
        $config = self::DEFAULT_CONFIG;
        
        // 2. Apply admin site-wide configuration (if exists)
        $adminConfig = $this->getAdminSiteWideConfig();
        if ($adminConfig) {
            $config = array_merge($config, $adminConfig);
        }
        
        // 3. Apply manual overrides (highest priority)
        $config = array_merge($config, $overrides);
        
        return $config;
    }
    
    /**
     * Get admin-configured site-wide TTS settings
     * 
     * @return array|null Admin TTS configuration or null if not set
     */
    private function getAdminSiteWideConfig(): ?array
    {
        try {
            require_once __DIR__ . '/../storage/FileStorageManager.php';
            $storage = \FileStorageManager::getInstance();
            
            if ($storage->fileExists('system_data/tts_admin_config.json')) {
                $configData = $storage->readFile('system_data/tts_admin_config.json');
                $adminConfig = json_decode($configData, true);
                
                if ($adminConfig && is_array($adminConfig)) {
                    return $adminConfig;
                }
            }
        } catch (\Exception $e) {
            // Log but don't fail - admin config is optional
            error_log("Failed to load admin TTS config: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Create a TTS service instance with full waterfall configuration
     * 
     * @param string $username The user to load preferences for
     * @param array $overrides Optional config overrides (highest priority)
     * @return static TTS service with waterfall config applied
     */
    public static function withUserPreferences(string $username, array $overrides = []): self
    {
        try {
            require_once __DIR__ . '/../UserPreferencesManager.php';
            $preferencesManager = new \UserPreferencesManager();
            
            $userPrefs = $preferencesManager->getTTSPreferences($username);
            
            // Map user preferences to TTS config (middle priority)
            $userConfig = [
                'default_voice' => $userPrefs['voice'] ?? null,
                'default_language' => $userPrefs['language'] ?? null,
                'default_gender' => $userPrefs['gender'] ?? null,
                'audio_format' => $userPrefs['audio_format'] ?? null,
                'enable_ssml' => $userPrefs['enable_ssml'] ?? null
            ];
            
            // Remove null values to let waterfall work properly
            $userConfig = array_filter($userConfig, function($value) {
                return $value !== null;
            });
            
            // Create instance - constructor will apply waterfall: system → admin → user → overrides
            $instance = new self($overrides);
            
            // Apply user config as secondary override
            $instance->config = array_merge($instance->config, $userConfig);
            
            return $instance;
            
        } catch (\Exception $e) {
            // If user preferences can't be loaded, fall back to admin + system defaults
            error_log("Failed to load user TTS preferences for {$username}: " . $e->getMessage());
            return new self($overrides);
        }
    }
    
    /**
     * Update default options with full waterfall configuration
     * 
     * @param string $username
     * @param array $options Synthesis options to enhance with waterfall config
     * @return array Enhanced options with waterfall priority
     */
    public static function enhanceWithUserPreferences(string $username, array $options = []): array
    {
        try {
            // 1. Get system defaults
            $enhanced = self::DEFAULT_CONFIG;
            
            // 2. Apply admin site-wide config
            $instance = new self(); // This applies system + admin config
            $adminConfig = [
                'voice' => $instance->config['default_voice'],
                'language' => $instance->config['default_language'], 
                'gender' => $instance->config['default_gender'],
                'format' => $instance->config['audio_format'],
                'enable_ssml' => $instance->config['enable_ssml']
            ];
            $enhanced = array_merge($enhanced, $adminConfig);
            
            // 3. Apply user preferences
            require_once __DIR__ . '/../UserPreferencesManager.php';
            $preferencesManager = new \UserPreferencesManager();
            $userPrefs = $preferencesManager->getTTSPreferences($username);
            
            $userConfig = [
                'voice' => $userPrefs['voice'] ?? null,
                'language' => $userPrefs['language'] ?? null,
                'gender' => $userPrefs['gender'] ?? null,
                'format' => $userPrefs['audio_format'] ?? null,
                'speech_rate' => $userPrefs['speech_rate'] ?? null,
                'enable_ssml' => $userPrefs['enable_ssml'] ?? null
            ];
            
            // Apply non-null user preferences
            foreach ($userConfig as $key => $value) {
                if ($value !== null) {
                    $enhanced[$key] = $value;
                }
            }
            
            // 4. Apply method options (highest priority)
            $enhanced = array_merge($enhanced, $options);
            
            return $enhanced;
            
        } catch (\Exception $e) {
            // If preferences can't be loaded, return options with system defaults
            error_log("Failed to build waterfall TTS preferences: " . $e->getMessage());
            return array_merge(self::DEFAULT_CONFIG, $options);
        }
    }
    
    /**
     * Save admin site-wide TTS configuration
     * 
     * @param array $config Admin TTS configuration
     * @return bool Success status
     */
    public static function saveAdminConfig(array $config): bool
    {
        try {
            // Validate admin config against known settings
            $validKeys = [
                'default_voice', 'default_language', 'default_gender', 'audio_format', 
                'sample_rate', 'enable_ssml', 'enable_caching', 'cache_duration',
                'max_text_length', 'rate_limit_per_minute'
            ];
            
            $filteredConfig = [];
            foreach ($config as $key => $value) {
                if (in_array($key, $validKeys)) {
                    $filteredConfig[$key] = $value;
                }
            }
            
            require_once __DIR__ . '/../storage/FileStorageManager.php';
            $storage = \FileStorageManager::getInstance();
            
            $success = $storage->writeFile(
                'system_data/tts_admin_config.json', 
                json_encode($filteredConfig, JSON_PRETTY_PRINT)
            );
            
            if ($success) {
                error_log("Admin TTS configuration saved successfully");
            }
            
            return $success;
            
        } catch (\Exception $e) {
            error_log("Failed to save admin TTS config: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get admin site-wide TTS configuration for editing
     * 
     * @return array Current admin configuration
     */
    public static function getAdminConfig(): array
    {
        try {
            require_once __DIR__ . '/../storage/FileStorageManager.php';
            $storage = \FileStorageManager::getInstance();
            
            if ($storage->fileExists('system_data/tts_admin_config.json')) {
                $configData = $storage->readFile('system_data/tts_admin_config.json');
                $adminConfig = json_decode($configData, true);
                
                if ($adminConfig && is_array($adminConfig)) {
                    return $adminConfig;
                }
            }
        } catch (\Exception $e) {
            error_log("Failed to load admin TTS config for editing: " . $e->getMessage());
        }
        
        // Return empty array if no admin config exists
        return [];
    }

    /**
     * Initialize the Google Cloud TTS client with authentication
     */
    private function initializeClient(): void
    {
        if ($this->client !== null) {
            return; // Already initialized
        }

        try {
            $credentials = $this->getCloudCredentials();
            $this->client = new TextToSpeechClient(['credentials' => $credentials]);
            $this->log('TTS client initialized successfully');
        } catch (\Exception $e) {
            $this->log('Failed to initialize TTS client: ' . $e->getMessage(), 'error');
            throw new TTSException('TTS service initialization failed', 0, $e);
        }
    }

    /**
     * Get Google Cloud credentials from Secret Manager
     */
    private function getCloudCredentials(): array
    {
        $secretClient = new SecretManagerServiceClient();
        $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: 'mediabrain';
        $secretName = 'tts-sa-key';
        $name = "projects/$projectId/secrets/$secretName/versions/latest";
        
        $request = new AccessSecretVersionRequest();
        $request->setName($name);
        
        $response = $secretClient->accessSecretVersion($request);
        
        if (!$response || !$response->getPayload()) {
            throw new TTSException('Could not access TTS credentials from Secret Manager');
        }
        
        $credentialsJson = $response->getPayload()->getData();
        $credentials = json_decode($credentialsJson, true);
        
        if (!$credentials) {
            throw new TTSException('Invalid TTS credentials format');
        }
        
        return $credentials;
    }

    /**
     * Synthesize text to speech with advanced options
     * 
     * @param string $text The text to synthesize
     * @param array $options Synthesis options
     * @return TTSResult The synthesis result
     */
    public function synthesize(string $text, array $options = []): TTSResult
    {
        try {
            // Validate input
            $this->validateInput($text, $options);
            
            // Check cache first
            $cacheKey = $this->generateCacheKey($text, $options);
            if ($this->config['enable_caching'] && ($cached = $this->getCached($cacheKey))) {
                $this->log("TTS cache hit for key: $cacheKey");
                return $cached;
            }

            // Initialize client if needed
            $this->initializeClient();
            
            // Prepare synthesis parameters
            $voice = $this->prepareVoiceSelection($options);
            $audioConfig = $this->prepareAudioConfig($options);
            $synthesisInput = $this->prepareSynthesisInput($text, $options);

            // Perform synthesis
            $response = $this->client->synthesizeSpeech($synthesisInput, $voice, $audioConfig);
            
            if (!$response || !$response->getAudioContent()) {
                throw new TTSException('TTS synthesis returned empty audio content');
            }

            // Create result object
            $result = new TTSResult([
                'audio_content' => $response->getAudioContent(),
                'text' => $text,
                'voice' => $options['voice'] ?? $this->config['default_voice'],
                'format' => $options['format'] ?? $this->config['audio_format'],
                'sample_rate' => $options['sample_rate'] ?? $this->config['sample_rate'],
                'synthesis_time' => time(),
                'cache_key' => $cacheKey
            ]);

            // Cache the result
            if ($this->config['enable_caching']) {
                $this->setCached($cacheKey, $result);
                $this->log("TTS result cached with key: $cacheKey");
            }

            $this->log("TTS synthesis completed successfully for " . strlen($text) . " characters");
            return $result;

        } catch (TTSException $e) {
            $this->log("TTS error: " . $e->getMessage(), 'error');
            throw $e;
        } catch (\Exception $e) {
            $this->log("Unexpected TTS error: " . $e->getMessage(), 'error');
            throw new TTSException('TTS synthesis failed', 0, $e);
        }
    }

    /**
     * Get available voices from Google Cloud TTS
     * 
     * @param string $languageCode Filter by language code (optional)
     * @return array Available voices
     */
    public function getVoices(string $languageCode = null): array
    {
        try {
            $this->initializeClient();
            
            $response = $this->client->listVoices(['language_code' => $languageCode]);
            $voices = [];
            
            foreach ($response->getVoices() as $voice) {
                $voiceData = [
                    'name' => $voice->getName(),
                    'language_codes' => iterator_to_array($voice->getLanguageCodes()),
                    'ssml_gender' => $voice->getSsmlGender(),
                    'natural_sample_rate' => $voice->getNaturalSampleRateHertz()
                ];
                
                // Determine voice type from name
                $voiceData['type'] = $this->getVoiceType($voice->getName());
                $voiceData['quality_score'] = $this->getVoiceQualityScore($voiceData['type']);
                
                $voices[] = $voiceData;
            }
            
            // Sort by quality (Neural2 first, then WaveNet, then Standard)
            usort($voices, function($a, $b) {
                return $b['quality_score'] <=> $a['quality_score'];
            });
            
            return $voices;
            
        } catch (\Exception $e) {
            $this->log("Failed to get voices: " . $e->getMessage(), 'error');
            throw new TTSException('Could not retrieve available voices', 0, $e);
        }
    }

    /**
     * Preview a voice with sample text
     * 
     * @param string $voiceId The voice ID to preview
     * @param string $sampleText Sample text to synthesize
     * @return TTSResult Preview result
     */
    public function previewVoice(string $voiceId, string $sampleText = 'Hello! This is a preview of my voice.'): TTSResult
    {
        return $this->synthesize($sampleText, [
            'voice' => $voiceId,
            'cache' => false // Don't cache preview audio
        ]);
    }

    /**
     * Validate synthesis input
     */
    private function validateInput(string $text, array $options): void
    {
        if (empty($text)) {
            throw new TTSException('Text cannot be empty');
        }
        
        if (strlen($text) > $this->config['max_text_length']) {
            throw new TTSException("Text length exceeds maximum of {$this->config['max_text_length']} characters");
        }
        
        // Validate voice if specified
        if (!empty($options['voice']) && !$this->isValidVoice($options['voice'])) {
            throw new TTSException("Invalid voice specified: {$options['voice']}");
        }
    }

    /**
     * Prepare voice selection parameters
     */
    private function prepareVoiceSelection(array $options): VoiceSelectionParams
    {
        $voice = new VoiceSelectionParams();
        
        // Set language code
        $languageCode = $options['language'] ?? $this->config['default_language'];
        $voice->setLanguageCode($languageCode);
        
        // Set voice name if specified
        if (!empty($options['voice'])) {
            $voice->setName($options['voice']);
        }
        
        // Set gender
        $gender = $options['gender'] ?? $this->config['default_gender'];
        switch (strtoupper($gender)) {
            case 'MALE':
                $voice->setSsmlGender(SsmlVoiceGender::MALE);
                break;
            case 'FEMALE':
                $voice->setSsmlGender(SsmlVoiceGender::FEMALE);
                break;
            default:
                $voice->setSsmlGender(SsmlVoiceGender::NEUTRAL);
                break;
        }
        
        return $voice;
    }

    /**
     * Prepare audio configuration
     */
    private function prepareAudioConfig(array $options): AudioConfig
    {
        $audioConfig = new AudioConfig();
        
        // Set audio encoding
        $format = $options['format'] ?? $this->config['audio_format'];
        switch (strtoupper($format)) {
            case 'WAV':
                $audioConfig->setAudioEncoding(AudioEncoding::LINEAR16);
                break;
            case 'OGG':
                $audioConfig->setAudioEncoding(AudioEncoding::OGG_OPUS);
                break;
            default:
                $audioConfig->setAudioEncoding(AudioEncoding::MP3);
                break;
        }
        
        // Set sample rate
        $sampleRate = $options['sample_rate'] ?? $this->config['sample_rate'];
        $audioConfig->setSampleRateHertz($sampleRate);
        
        return $audioConfig;
    }

    /**
     * Prepare synthesis input (text or SSML)
     */
    private function prepareSynthesisInput(string $text, array $options): SynthesisInput
    {
        $synthesisInput = new SynthesisInput();
        
        // Check if text contains SSML markup
        if ($this->config['enable_ssml'] && $this->isSSML($text)) {
            $synthesisInput->setSsml($text);
            $this->log("Using SSML synthesis for input");
        } else {
            $synthesisInput->setText($text);
        }
        
        return $synthesisInput;
    }

    /**
     * Check if text contains SSML markup
     */
    private function isSSML(string $text): bool
    {
        return strpos($text, '<speak>') !== false || strpos($text, '<') !== false && strpos($text, '>') !== false;
    }

    /**
     * Generate cache key for text and options
     */
    private function generateCacheKey(string $text, array $options): string
    {
        $keyData = [
            'text' => $text,
            'voice' => $options['voice'] ?? $this->config['default_voice'],
            'language' => $options['language'] ?? $this->config['default_language'],
            'format' => $options['format'] ?? $this->config['audio_format'],
            'sample_rate' => $options['sample_rate'] ?? $this->config['sample_rate']
        ];
        
        return 'tts_' . md5(json_encode($keyData));
    }

    /**
     * Get voice type from voice name
     */
    private function getVoiceType(string $voiceName): string
    {
        if (strpos($voiceName, 'Neural2') !== false) return 'Neural2';
        if (strpos($voiceName, 'Wavenet') !== false) return 'WaveNet';
        return 'Standard';
    }

    /**
     * Get quality score for voice type
     */
    private function getVoiceQualityScore(string $type): int
    {
        switch ($type) {
            case 'Neural2': return 3;
            case 'WaveNet': return 2;
            case 'Standard': return 1;
            default: return 0;
        }
    }

    /**
     * Validate if voice is available
     */
    private function isValidVoice(string $voiceId): bool
    {
        // This could be enhanced to check against actual available voices
        // For now, basic validation of voice format
        return preg_match('/^[a-zA-Z]{2}-[A-Z]{2}-(Standard|Wavenet|Neural2)-[A-Z]$/', $voiceId);
    }

    /**
     * Initialize caching system
     */
    private function initializeCache(): void
    {
        // Simple file-based cache for now
        // Could be enhanced with Redis, Memcache, etc.
        $this->cache = new SimpleFileCache(__DIR__ . '/../../storage/cache/tts/');
    }

    /**
     * Get cached result
     */
    private function getCached(string $key): ?TTSResult
    {
        if (!$this->cache) return null;
        
        try {
            $data = $this->cache->get($key);
            return $data ? TTSResult::fromArray($data) : null;
        } catch (\Exception $e) {
            $this->log("Cache read error: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Cache result
     */
    private function setCached(string $key, TTSResult $result): void
    {
        if (!$this->cache) return;
        
        try {
            $this->cache->set($key, $result->toArray(), $this->config['cache_duration']);
        } catch (\Exception $e) {
            $this->log("Cache write error: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Initialize logger
     */
    private function initializeLogger(): void
    {
        try {
            if (class_exists('\EventLogger')) {
                $this->logger = \EventLogger::getInstance();
            }
        } catch (\Exception $e) {
            // Fallback to error_log if EventLogger unavailable
            $this->logger = null;
        }
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info'): void
    {
        if ($this->logger) {
            $this->logger->log('tts', $message, ['level' => $level]);
        } else {
            error_log("TTS [$level]: $message");
        }
    }

    /**
     * Clean up resources
     */
    public function __destruct()
    {
        if ($this->client) {
            $this->client->close();
        }
    }
}

/**
 * TTS Result container
 */
class TTSResult
{
    private $audioContent;
    private $metadata;

    public function __construct(array $data)
    {
        $this->audioContent = $data['audio_content'];
        $this->metadata = [
            'text' => $data['text'] ?? '',
            'voice' => $data['voice'] ?? '',
            'format' => $data['format'] ?? 'MP3',
            'sample_rate' => $data['sample_rate'] ?? 24000,
            'synthesis_time' => $data['synthesis_time'] ?? time(),
            'cache_key' => $data['cache_key'] ?? '',
            'size' => strlen($this->audioContent)
        ];
    }

    public function getAudioContent(): string
    {
        return $this->audioContent;
    }

    public function getBase64Audio(): string
    {
        return base64_encode($this->audioContent);
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getDataUrl(): string
    {
        $mimeType = $this->getMimeType();
        return "data:$mimeType;base64," . $this->getBase64Audio();
    }

    private function getMimeType(): string
    {
        switch (strtoupper($this->metadata['format'])) {
            case 'WAV': return 'audio/wav';
            case 'OGG': return 'audio/ogg';
            default: return 'audio/mp3';
        }
    }

    public function toArray(): array
    {
        return [
            'audio_content' => $this->audioContent,
            'metadata' => $this->metadata
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self([
            'audio_content' => $data['audio_content'],
            'text' => $data['metadata']['text'] ?? '',
            'voice' => $data['metadata']['voice'] ?? '',
            'format' => $data['metadata']['format'] ?? 'MP3',
            'sample_rate' => $data['metadata']['sample_rate'] ?? 24000,
            'synthesis_time' => $data['metadata']['synthesis_time'] ?? time(),
            'cache_key' => $data['metadata']['cache_key'] ?? ''
        ]);
    }
}

/**
 * TTS Exception for error handling
 */
class TTSException extends \Exception
{
    //
}

/**
 * Simple file-based cache implementation
 */
class SimpleFileCache
{
    private $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key): ?array
    {
        $file = $this->cacheDir . $key . '.cache';
        
        if (!file_exists($file)) {
            return null;
        }

        $data = unserialize(file_get_contents($file));
        
        // Check expiration
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['content'];
    }

    public function set(string $key, array $content, int $ttl): void
    {
        $file = $this->cacheDir . $key . '.cache';
        $data = [
            'content' => $content,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data), LOCK_EX);
    }

    public function invalidate(string $pattern = '*'): int
    {
        $files = glob($this->cacheDir . $pattern . '.cache');
        $count = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
}