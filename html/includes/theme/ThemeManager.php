<?php

/**
 * ThemeManager - Centralized theme management system for MediaBrain
 * 
 * This class handles theme selection, loading, and application across the entire
 * MediaBrain application. It supports user preferences, admin overrides, and
 * dynamic theme switching.
 * 
 * Features:
 * - Theme loading and validation
 * - User preference storage
 * - Admin theme management
 * - CSS compilation and caching
 * - Theme inheritance
 * 
 * @author MediaBrain Development Team
 * @version 2.0
 */

class ThemeManager {
    
    private $themeBasePath;
    private $cacheEnabled;
    private $availableThemes = [];
    private $currentTheme = 'default';
    private $userPreferences = [];
    
    /**
     * Constructor - Initialize theme manager
     */
    public function __construct() {
        $this->themeBasePath = __DIR__ . '/../../css/themes/';
        $this->cacheEnabled = true;
        $this->loadAvailableThemes();
        $this->loadUserPreferences();
    }
    
    /**
     * Get all available themes
     * 
     * @return array Array of theme configurations
     */
    public function getAvailableThemes() {
        return $this->availableThemes;
    }
    
    /**
     * Get current active theme for user
     * 
     * @param string|null $userId User ID (null for current session)
     * @return string Theme name
     */
    public function getCurrentTheme($userId = null) {
        if ($userId === null && isset($_SESSION['user'])) {
            $userId = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
        }
        
        // Check user preference
        if ($userId && isset($this->userPreferences[$userId]['theme'])) {
            return $this->userPreferences[$userId]['theme'];
        }
        
        // Check session override
        if (isset($_SESSION['theme_override'])) {
            return $_SESSION['theme_override'];
        }
        
        // Check admin default
        if (isset($this->userPreferences['_system']['default_theme'])) {
            return $this->userPreferences['_system']['default_theme'];
        }
        
        return $this->currentTheme;
    }
    
    /**
     * Set theme for current user
     * 
     * @param string $themeName Theme to activate
     * @param bool $persistent Save to user preferences
     * @return bool Success status
     */
    public function setUserTheme($themeName, $persistent = true) {
        if (!$this->isValidTheme($themeName)) {
            return false;
        }
        
        $userId = null;
        if (isset($_SESSION['user'])) {
            $userId = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
        }
        
        if ($persistent && $userId) {
            $this->userPreferences[$userId]['theme'] = $themeName;
            $this->saveUserPreferences();
        } else {
            $_SESSION['theme_override'] = $themeName;
        }
        
        return true;
    }
    
    /**
     * Get theme configuration
     * 
     * @param string $themeName Theme name
     * @return array|null Theme configuration
     */
    public function getThemeConfig($themeName) {
        return $this->availableThemes[$themeName] ?? null;
    }
    
    /**
     * Generate theme CSS for inclusion
     * 
     * @param string|null $themeName Theme name (null for current)
     * @return string CSS content or link tags
     */
    public function getThemeCSS($themeName = null) {
        if ($themeName === null) {
            $themeName = $this->getCurrentTheme();
        }
        
        $config = $this->getThemeConfig($themeName);
        if (!$config) {
            $config = $this->getThemeConfig('default');
        }
        
        $cssOutput = '';
        
        // Always include utilities framework first
        $cssOutput .= '<link rel="stylesheet" type="text/css" href="/css/themes/utilities.css">' . "\n";
        
        // Include theme-specific CSS files
        if (!empty($config['css_files'])) {
            foreach ($config['css_files'] as $cssFile) {
                $cssPath = "/css/themes/{$themeName}/{$cssFile}";
                $cssOutput .= '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($cssPath) . '">' . "\n";
            }
        }
        
        // Include inline CSS
        if (!empty($config['inline_css'])) {
            $cssOutput .= '<style type="text/css">' . "\n";
            $cssOutput .= $config['inline_css'] . "\n";
            $cssOutput .= '</style>' . "\n";
        }
        
        return $cssOutput;
    }
    
    /**
     * Get theme JavaScript for inclusion
     * 
     * @param string|null $themeName Theme name (null for current)
     * @return string JavaScript content or script tags
     */
    public function getThemeJS($themeName = null) {
        if ($themeName === null) {
            $themeName = $this->getCurrentTheme();
        }
        
        $config = $this->getThemeConfig($themeName);
        if (!$config) {
            return '';
        }
        
        $jsOutput = '';
        
        // Include JS files
        if (!empty($config['js_files'])) {
            foreach ($config['js_files'] as $jsFile) {
                $jsPath = "/js/themes/{$themeName}/{$jsFile}";
                $jsOutput .= '<script type="text/javascript" src="' . htmlspecialchars($jsPath) . '"></script>' . "\n";
            }
        }
        
        // Include inline JS
        if (!empty($config['inline_js'])) {
            $jsOutput .= '<script type="text/javascript">' . "\n";
            $jsOutput .= $config['inline_js'] . "\n";
            $jsOutput .= '</script>' . "\n";
        }
        
        return $jsOutput;
    }
    
    /**
     * Apply theme variables to template
     * 
     * @param string $content Template content
     * @param string|null $themeName Theme name
     * @return string Processed content
     */
    public function applyThemeVariables($content, $themeName = null) {
        if ($themeName === null) {
            $themeName = $this->getCurrentTheme();
        }
        
        $config = $this->getThemeConfig($themeName);
        if (!$config || empty($config['variables'])) {
            return $content;
        }
        
        foreach ($config['variables'] as $variable => $value) {
            $content = str_replace('{{theme.' . $variable . '}}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Load available themes from filesystem
     * 
     * @return void
     */
    private function loadAvailableThemes() {
        $themesPath = __DIR__ . '/../../css/themes/';
        
        if (!is_dir($themesPath)) {
            mkdir($themesPath, 0755, true);
        }
        
        $themes = scandir($themesPath);
        foreach ($themes as $themeDir) {
            if ($themeDir === '.' || $themeDir === '..') {
                continue;
            }
            
            $themeConfigPath = $themesPath . $themeDir . '/theme.json';
            if (file_exists($themeConfigPath)) {
                $config = json_decode(file_get_contents($themeConfigPath), true);
                if ($config) {
                    $this->availableThemes[$themeDir] = $config;
                }
            }
        }
    }
    
    /**
     * Load user preferences from storage
     * 
     * @return void
     */
    private function loadUserPreferences() {
        $prefsPath = __DIR__ . '/../../storage/user_preferences/theme_preferences.json';
        
        if (file_exists($prefsPath)) {
            $prefs = json_decode(file_get_contents($prefsPath), true);
            if ($prefs) {
                $this->userPreferences = $prefs;
            }
        }
    }
    
    /**
     * Save user preferences to storage
     * 
     * @return bool Success status
     */
    private function saveUserPreferences() {
        $prefsPath = __DIR__ . '/../../storage/user_preferences/theme_preferences.json';
        $prefsDir = dirname($prefsPath);
        
        if (!is_dir($prefsDir)) {
            mkdir($prefsDir, 0755, true);
        }
        
        return file_put_contents($prefsPath, json_encode($this->userPreferences, JSON_PRETTY_PRINT)) !== false;
    }
    
    /**
     * Check if theme is valid
     * 
     * @param string $themeName Theme name
     * @return bool Validity status
     */
    private function isValidTheme($themeName) {
        return isset($this->availableThemes[$themeName]);
    }
    
    /**
     * Get theme preview data for selection interface
     * 
     * @return array Theme preview data
     */
    public function getThemePreviewData() {
        $previewData = [];
        
        foreach ($this->availableThemes as $themeName => $config) {
            $previewData[$themeName] = [
                'name' => $config['name'] ?? ucfirst($themeName),
                'description' => $config['description'] ?? '',
                'preview_image' => $config['preview_image'] ?? "/css/themes/{$themeName}/preview.png",
                'author' => $config['author'] ?? 'Unknown',
                'version' => $config['version'] ?? '1.0',
                'category' => $config['category'] ?? 'General'
            ];
        }
        
        return $previewData;
    }
    
    /**
     * Set system default theme (admin function)
     * 
     * @param string $themeName Theme name
     * @return bool Success status
     */
    public function setSystemDefaultTheme($themeName) {
        if (!$this->isValidTheme($themeName)) {
            return false;
        }
        
        $this->userPreferences['_system']['default_theme'] = $themeName;
        return $this->saveUserPreferences();
    }
    
    /**
     * Reset user theme to default
     * 
     * @param string|null $userId User ID (null for current)
     * @return bool Success status
     */
    public function resetUserTheme($userId = null) {
        if ($userId === null && isset($_SESSION['user'])) {
            $userId = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
        }
        
        if ($userId && isset($this->userPreferences[$userId]['theme'])) {
            unset($this->userPreferences[$userId]['theme']);
            $this->saveUserPreferences();
        }
        
        unset($_SESSION['theme_override']);
        
        return true;
    }
}