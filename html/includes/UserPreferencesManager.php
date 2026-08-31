<?php

/**
 * User Preferences Manager
 * 
 * Handles saving and loading user-specific preferences including TTS settings,
 * UI preferences, and other personalization options.
 * 
 * @version 1.0
 */
class UserPreferencesManager
{
    private $storageManager;
    private $logger;
    
    // Default TTS preferences
    const DEFAULT_TTS_PREFERENCES = [
        'voice' => 'en-US-Neural2-A',
        'language' => 'en-US',
        'gender' => 'NEUTRAL',
        'audio_format' => 'MP3',
        'speech_rate' => 1.0,
        'volume' => 80,
        'enable_ssml' => true
    ];
    
    public function __construct()
    {
        require_once __DIR__ . '/storage/FileStorageManager.php';
        $this->storageManager = FileStorageManager::getInstance();
        $this->logger = App::getInstance()->logger ?? null;
    }
    
    /**
     * Get user preferences for a specific user and category
     * 
     * @param string $username
     * @param string $category (e.g., 'tts', 'ui', 'general')
     * @return array Preferences array or defaults
     */
    public function getUserPreferences(string $username, string $category = 'tts'): array
    {
        try {
            $preferencesPath = "user_data/{$username}/preferences/{$category}.json";
            
            if ($this->storageManager->fileExists($preferencesPath)) {
                $content = $this->storageManager->readFile($preferencesPath);
                $preferences = json_decode($content, true);
                
                if ($preferences !== null) {
                    // Merge with defaults to ensure all required keys exist
                    return array_merge($this->getDefaultPreferences($category), $preferences);
                }
            }
            
            // Return defaults if no preferences found
            return $this->getDefaultPreferences($category);
            
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to load user preferences', [
                    'username' => $username,
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
            
            return $this->getDefaultPreferences($category);
        }
    }
    
    /**
     * Save user preferences for a specific user and category
     * 
     * @param string $username
     * @param string $category
     * @param array $preferences
     * @return bool Success status
     */
    public function saveUserPreferences(string $username, string $category, array $preferences): bool
    {
        try {
            // Validate preferences against defaults
            $validatedPreferences = $this->validatePreferences($category, $preferences);
            
            $preferencesPath = "user_data/{$username}/preferences/{$category}.json";
            $content = json_encode($validatedPreferences, JSON_PRETTY_PRINT);
            
            $success = $this->storageManager->writeFile($preferencesPath, $content);
            
            if ($this->logger && $success) {
                $this->logger->info('User preferences saved', [
                    'username' => $username,
                    'category' => $category
                ]);
            }
            
            return $success;
            
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to save user preferences', [
                    'username' => $username,
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Get TTS preferences specifically (convenience method)
     * 
     * @param string $username
     * @return array TTS preferences
     */
    public function getTTSPreferences(string $username): array
    {
        return $this->getUserPreferences($username, 'tts');
    }
    
    /**
     * Save TTS preferences specifically (convenience method)
     * 
     * @param string $username
     * @param array $preferences
     * @return bool Success status
     */
    public function saveTTSPreferences(string $username, array $preferences): bool
    {
        return $this->saveUserPreferences($username, 'tts', $preferences);
    }
    
    /**
     * Get default preferences for a category
     * 
     * @param string $category
     * @return array Default preferences
     */
    private function getDefaultPreferences(string $category): array
    {
        switch ($category) {
            case 'tts':
                return self::DEFAULT_TTS_PREFERENCES;
            case 'ui':
                return [
                    'theme' => 'default',
                    'language' => 'en',
                    'timezone' => 'UTC'
                ];
            case 'general':
                return [
                    'notifications' => true,
                    'auto_save' => true
                ];
            default:
                return [];
        }
    }
    
    /**
     * Validate and sanitize preferences
     * 
     * @param string $category
     * @param array $preferences
     * @return array Validated preferences
     */
    private function validatePreferences(string $category, array $preferences): array
    {
        $defaults = $this->getDefaultPreferences($category);
        $validated = [];
        
        if ($category === 'tts') {
            // Validate TTS preferences
            $validated['voice'] = $this->validateVoice($preferences['voice'] ?? $defaults['voice']);
            $validated['language'] = $this->validateLanguage($preferences['language'] ?? $defaults['language']);
            $validated['gender'] = $this->validateGender($preferences['gender'] ?? $defaults['gender']);
            $validated['audio_format'] = $this->validateAudioFormat($preferences['audio_format'] ?? $defaults['audio_format']);
            $validated['speech_rate'] = $this->validateSpeechRate($preferences['speech_rate'] ?? $defaults['speech_rate']);
            $validated['volume'] = $this->validateVolume($preferences['volume'] ?? $defaults['volume']);
            $validated['enable_ssml'] = (bool)($preferences['enable_ssml'] ?? $defaults['enable_ssml']);
            
        } else {
            // For other categories, merge with defaults and preserve valid values
            foreach ($defaults as $key => $defaultValue) {
                $validated[$key] = $preferences[$key] ?? $defaultValue;
            }
        }
        
        return $validated;
    }
    
    /**
     * Validate voice selection
     */
    private function validateVoice(string $voice): string
    {
        $validVoices = [
            'en-US-Neural2-A', 'en-US-Neural2-C', 'en-US-Neural2-D', 'en-US-Neural2-E',
            'en-US-Neural2-F', 'en-US-Neural2-G', 'en-US-Neural2-H', 'en-US-Neural2-I',
            'en-US-Neural2-J', 'en-GB-Neural2-A', 'en-GB-Neural2-B', 'en-GB-Neural2-C',
            'en-GB-Neural2-D', 'en-AU-Neural2-A', 'en-AU-Neural2-B', 'en-AU-Neural2-C',
            'en-AU-Neural2-D'
        ];
        
        return in_array($voice, $validVoices) ? $voice : self::DEFAULT_TTS_PREFERENCES['voice'];
    }
    
    /**
     * Validate language selection
     */
    private function validateLanguage(string $language): string
    {
        $validLanguages = [
            'en-US', 'en-GB', 'en-AU', 'en-IN', 'es-ES', 'es-MX', 'fr-FR', 'fr-CA',
            'de-DE', 'it-IT', 'pt-BR', 'ja-JP', 'ko-KR', 'zh-CN'
        ];
        
        return in_array($language, $validLanguages) ? $language : self::DEFAULT_TTS_PREFERENCES['language'];
    }
    
    /**
     * Validate gender selection
     */
    private function validateGender(string $gender): string
    {
        $validGenders = ['NEUTRAL', 'FEMALE', 'MALE'];
        return in_array($gender, $validGenders) ? $gender : self::DEFAULT_TTS_PREFERENCES['gender'];
    }
    
    /**
     * Validate audio format
     */
    private function validateAudioFormat(string $format): string
    {
        $validFormats = ['MP3', 'WAV', 'OGG_OPUS'];
        return in_array($format, $validFormats) ? $format : self::DEFAULT_TTS_PREFERENCES['audio_format'];
    }
    
    /**
     * Validate speech rate (0.25 - 4.0)
     */
    private function validateSpeechRate($rate): float
    {
        $rate = (float)$rate;
        return max(0.25, min(4.0, $rate));
    }
    
    /**
     * Validate volume (0 - 100)
     */
    private function validateVolume($volume): int
    {
        $volume = (int)$volume;
        return max(0, min(100, $volume));
    }
    
    /**
     * Delete all preferences for a user
     * 
     * @param string $username
     * @return bool Success status
     */
    public function deleteUserPreferences(string $username): bool
    {
        try {
            $userPreferencesPath = "user_data/{$username}/preferences/";
            
            // List and delete all preference files for the user
            if ($this->storageManager->fileExists($userPreferencesPath)) {
                return $this->storageManager->deleteDirectory($userPreferencesPath);
            }
            
            return true;
            
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error('Failed to delete user preferences', [
                    'username' => $username,
                    'error' => $e->getMessage()
                ]);
            }
            
            return false;
        }
    }
    
    /**
     * Get all categories of preferences for a user
     * 
     * @param string $username
     * @return array Categories and their preferences
     */
    public function getAllUserPreferences(string $username): array
    {
        $categories = ['tts', 'ui', 'general'];
        $allPreferences = [];
        
        foreach ($categories as $category) {
            $allPreferences[$category] = $this->getUserPreferences($username, $category);
        }
        
        return $allPreferences;
    }
}