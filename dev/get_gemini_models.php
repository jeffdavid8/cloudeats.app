<?php
require_once __DIR__ . '/html/includes/Services/AIService.php';

// Temporarily make apiKey public for testing
class AIServiceTemp extends AIService {
    public function getApiKeyPublic() {
        return $this->apiKey;
    }
}

$aiService = new AIServiceTemp();
$apiKey = $aiService->getApiKeyPublic();

if (empty($apiKey) || $apiKey === 'YOUR_API_KEY') {
    echo "Error: Gemini API Key not found or is a placeholder.\n";
    exit(1);
}

$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

$command = "curl -s \"" . $url . "\""; // -s for silent output

$output = shell_exec($command);

echo $output;

?>