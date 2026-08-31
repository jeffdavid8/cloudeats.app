<?php
/**
 * Theme API Handler - Admin Theme Management
 * 
 * Handles API requests for theme switching and theme data
 */

require_once __DIR__ . '/../../includes/theme/ThemeManager.php';

class ThemeAPI {
    
    private $themeManager;
    
    public function __construct() {
        $this->themeManager = new ThemeManager();
    }
    
    /**
     * Handle theme API requests
     */
    public function handleRequest() {
        $action = $_GET['action'] ?? $_POST['action'] ?? 'get_themes';
        
        // Check for action in request body
        if ($action === 'get_themes') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (isset($input['action'])) {
                $action = $input['action'];
            }
        }
        
        header('Content-Type: application/json');
        
        switch ($action) {
            case 'get_themes':
                $this->getThemes();
                break;
                
            case 'switch_theme':
                $this->switchTheme();
                break;
                
            case 'get_current_theme':
                $this->getCurrentTheme();
                break;
                
            case 'reset_theme':
                $this->resetTheme();
                break;
                
            case 'set_system_default':
                $this->setSystemDefault();
                break;
                
            default:
                $this->errorResponse('Invalid action', 400);
        }
    }
    
    /**
     * Get available themes
     */
    private function getThemes() {
        try {
            $themes = $this->themeManager->getThemePreviewData();
            $currentTheme = $this->themeManager->getCurrentTheme();
            
            $this->successResponse([
                'themes' => $themes,
                'current_theme' => $currentTheme
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to get themes: ' . $e->getMessage());
        }
    }
    
    /**
     * Switch user theme
     */
    private function switchTheme() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $themeName = $input['theme'] ?? $_POST['theme'] ?? null;
            
            if (!$themeName) {
                $this->errorResponse('Theme name required', 400);
                return;
            }
            
            $persistent = $input['persistent'] ?? $_POST['persistent'] ?? true;
            
            $success = $this->themeManager->setUserTheme($themeName, $persistent);
            
            if ($success) {
                $this->successResponse([
                    'message' => 'Theme switched successfully',
                    'new_theme' => $themeName
                ]);
            } else {
                $this->errorResponse('Invalid theme name');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to switch theme: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current theme
     */
    private function getCurrentTheme() {
        try {
            $currentTheme = $this->themeManager->getCurrentTheme();
            $themeConfig = $this->themeManager->getThemeConfig($currentTheme);
            
            $this->successResponse([
                'current_theme' => $currentTheme,
                'theme_config' => $themeConfig
            ]);
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to get current theme: ' . $e->getMessage());
        }
    }
    
    /**
     * Reset theme to default
     */
    private function resetTheme() {
        try {
            $success = $this->themeManager->resetUserTheme();
            
            if ($success) {
                $newTheme = $this->themeManager->getCurrentTheme();
                $this->successResponse([
                    'message' => 'Theme reset successfully',
                    'new_theme' => $newTheme
                ]);
            } else {
                $this->errorResponse('Failed to reset theme');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to reset theme: ' . $e->getMessage());
        }
    }
    
    /**
     * Set system default theme
     */
    private function setSystemDefault() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $themeName = $input['theme'] ?? $_POST['theme'] ?? null;
            
            if (!$themeName) {
                $this->errorResponse('Theme name required', 400);
                return;
            }
            
            $success = $this->themeManager->setSystemDefaultTheme($themeName);
            
            if ($success) {
                $this->successResponse([
                    'message' => 'System default theme updated successfully',
                    'default_theme' => $themeName
                ]);
            } else {
                $this->errorResponse('Invalid theme name');
            }
            
        } catch (Exception $e) {
            $this->errorResponse('Failed to set system default theme: ' . $e->getMessage());
        }
    }
    
    /**
     * Send success response
     */
    private function successResponse($data = []) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ] + $data);
        exit;
    }
    
    /**
     * Send error response
     */
    private function errorResponse($message, $code = 500) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}

// Handle the request if called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'theme-api.php') {
    // Check admin permission
    require_once __DIR__ . '/../../includes/AuthManager.php';
    
    session_start();
    if (!isset($_SESSION['user']) || !AuthManager::userIsAdmin($_SESSION['user'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Admin access required']);
        exit;
    }
    
    $api = new ThemeAPI();
    $api->handleRequest();
}