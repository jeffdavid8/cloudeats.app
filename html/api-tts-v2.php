<?php
/**
 * Modern Text-to-Speech API Endpoint v2.0
 * 
 * Provides enhanced TTS capabilities including:
 * - Multiple voice options (Standard, WaveNet, Neural2)
 * - SSML support
 * - Audio caching
 * - Voice preview
 * - Enhanced error handling
 */

require_once __DIR__ . '/includes/app.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

use MediaBrain\Services\TextToSpeechService;

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize app and authentication
$app = App::getInstance();
$auth = $app->getAuthManager();

// Get request data
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$data = $_POST['data'] ?? $_POST ?? [];

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $data['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (!$auth->validateCsrf($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF token required']);
        exit();
    }
}

try {
    // Initialize TTS service
    $ttsService = new TextToSpeechService();
    
    switch ($action) {
        case 'synthesize':
            handleSynthesize($ttsService, $data);
            break;
            
        case 'get_voices':
            handleGetVoices($ttsService, $data);
            break;
            
        case 'preview_voice':
            handlePreviewVoice($ttsService, $data);
            break;
            
        case 'text_to_speech': // Legacy compatibility
            handleLegacySynthesize($ttsService, $data);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (MediaBrain\Services\TTSException $e) {
    error_log("TTS API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    error_log("TTS API Unexpected Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

/**
 * Handle text synthesis with modern options
 */
function handleSynthesize(TextToSpeechService $ttsService, array $data): void
{
    $text = $data['text'] ?? $data['words'] ?? '';
    
    if (empty($text)) {
        throw new MediaBrain\Services\TTSException('Text is required for synthesis');
    }
    
    $options = [
        'voice' => $data['voice'] ?? null,
        'language' => $data['language'] ?? null,
        'gender' => $data['gender'] ?? null,
        'format' => $data['format'] ?? null,
        'sample_rate' => $data['sample_rate'] ?? null
    ];
    
    // Remove null values
    $options = array_filter($options, function($value) {
        return $value !== null;
    });
    
    $result = $ttsService->synthesize($text, $options);
    
    echo json_encode([
        'success' => true,
        'audioContent' => $result->getBase64Audio(),
        'dataUrl' => $result->getDataUrl(),
        'metadata' => $result->getMetadata()
    ]);
}

/**
 * Handle getting available voices
 */
function handleGetVoices(TextToSpeechService $ttsService, array $data): void
{
    $languageCode = $data['language'] ?? null;
    $voices = $ttsService->getVoices($languageCode);
    
    echo json_encode([
        'success' => true,
        'voices' => $voices
    ]);
}

/**
 * Handle voice preview
 */
function handlePreviewVoice(TextToSpeechService $ttsService, array $data): void
{
    $voiceId = $data['voice'] ?? '';
    $sampleText = $data['sample_text'] ?? 'Hello! This is a preview of my voice.';
    
    if (empty($voiceId)) {
        throw new MediaBrain\Services\TTSException('Voice ID is required for preview');
    }
    
    $result = $ttsService->previewVoice($voiceId, $sampleText);
    
    echo json_encode([
        'success' => true,
        'audioContent' => $result->getBase64Audio(),
        'dataUrl' => $result->getDataUrl(),
        'metadata' => $result->getMetadata()
    ]);
}

/**
 * Handle legacy synthesis for backward compatibility
 */
function handleLegacySynthesize(TextToSpeechService $ttsService, array $data): void
{
    // Map legacy request format to new format
    $text = $data['words'] ?? $data['text'] ?? '';
    
    if (empty($text)) {
        // Use the old fallback behavior
        echo json_encode(['success' => false, 'error' => 'No text provided for synthesis']);
        return;
    }
    
    try {
        $result = $ttsService->synthesize($text);
        
        // Return in legacy format
        echo json_encode([
            'success' => true,
            'audioContent' => $result->getBase64Audio()
        ]);
    } catch (Exception $e) {
        // Legacy fallback behavior
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}