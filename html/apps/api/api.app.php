<?php

/**
 * API App Configuration
 */
function api_info() {
    return [
        'title' => 'API Handler',
        'description' => 'Centralized API handler for all AJAX requests',
        'icon' => '<i class="material-icons">api</i>',
        'version' => '1.0.0',
        'requires_auth' => false,  // API endpoints handle their own auth
        'requires_admin' => false,
        'no_header' => true,       // APIs should not render headers
        'public_app' => true
    ];
}

class ApiApp extends App {

    protected $name = 'api';
    protected $title = 'API';
    protected $version = '1.0.0';
    protected $description = 'Centralized API handler for all AJAX requests';

    public function __construct() {
        // Set JSON headers early for all API requests
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Handle preflight OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        parent::__construct();
    }

    public function init() {
        // API app initialization - minimal setup needed
    }

    public function getMainContent() {
        // API app should never render HTML content
        // All requests should be handled by the routing system
        return $this->handleApiRequest();
    }

    private function handleApiRequest() {
        // Get the API action from various sources
        $action = $this->getApiAction();
        $data = $this->getApiData();
        
        if (empty($action)) {
            return $this->jsonError('No action specified', 400);
        }

        // Route to specific app APIs first
        $targetApp = get_var('target_app') ?: get_var('target');
        if ($targetApp && $targetApp !== 'api') {
            return $this->routeToAppApi($targetApp, $action, $data);
        }

        // Handle global API actions
        return $this->handleGlobalApiAction($action, $data);
    }

    private function getApiAction() {
        // Try to get action from various sources
        $rawInput = file_get_contents('php://input');
        $request = json_decode($rawInput, true);
        
        return $request['action'] 
            ?? $_REQUEST['action'] 
            ?? $_POST['action'] 
            ?? $_GET['action'] 
            ?? '';
    }

    private function getApiData() {
        // Get data from JSON body or form data
        $rawInput = file_get_contents('php://input');
        $request = json_decode($rawInput, true);
        
        return $request['data'] 
            ?? $_REQUEST['data'] 
            ?? $_REQUEST 
            ?? array();
    }

    private function routeToAppApi($targetApp, $action, $data) {
        $apiFile = __DIR__ . '/../' . $targetApp . '/' . $targetApp . '.api.php';
        
        if (!file_exists($apiFile)) {
            return $this->jsonError("API not found for app: $targetApp", 404);
        }

        // Set up environment for the app API
        $_REQUEST['action'] = $action;
        $_REQUEST['data'] = $data;
        
        // Capture output from the app API
        ob_start();
        include $apiFile;
        $output = ob_get_clean();
        
        // If the app API didn't output anything, return success
        if (empty($output)) {
            return json_encode(['success' => true]);
        }
        
        return $output;
    }

    private function handleGlobalApiAction($action, $data) {
        // Include the main API file for global actions
        if (file_exists(__DIR__ . '/../../api.php')) {
            // Set up the environment
            $_REQUEST['action'] = $action;
            $_REQUEST['data'] = $data;
            
            ob_start();
            include __DIR__ . '/../../api.php';
            $output = ob_get_clean();
            
            if (empty($output)) {
                return json_encode(['success' => true]);
            }
            
            return $output;
        }
        
        return $this->jsonError("Unknown action: $action", 400);
    }

    private function jsonError($message, $code = 400) {
        http_response_code($code);
        return json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ]);
    }
}

/**
 * API render functions for the app framework
 */
function api_render_body() {
    $app = App::getInstance('api');
    echo $app->getMainContent();
}

?>