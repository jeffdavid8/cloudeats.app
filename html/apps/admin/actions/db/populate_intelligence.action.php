<?
// Secure the entry point
if (!defined('MB_RUNNING')) exit;

$gemini = new AIService();
$app = App::getInstance();

echo " <br><br><br><br>                                  ( . Y . ) <br><br>";

$epochs = [
    ['start' => '0000', 'end' => '0500', 'theme' => 'Antiquity: The Roman Empire, Stoic logic, and the silent transition of empires.'],
    ['start' => '0501', 'end' => '1400', 'theme' => 'The Middle Ages: Scholasticism, early optics, and the preservation of ancient texts.'],
    ['start' => '1401', 'end' => '1750', 'theme' => 'The Great Awakening: Printing, navigation, and the mechanical philosophy of Newton.'],
    ['start' => '1751', 'end' => '1900', 'theme' => 'Industrial Synthesis: Steam power, the speed of light experiments, and photography.'],
    ['start' => '1901', 'end' => '1960', 'theme' => 'The Atomic Threshold: Radio communication, quantum theory, and the first vacuum tubes.'],
    ['start' => '1961', 'end' => '2000', 'theme' => 'The Silicon Bloom: ARPANET, the PC era, and the digitization of the human record.'],
    ['start' => '2001', 'end' => '2026', 'theme' => 'The Neural Horizon: Generative AI, decentralized truths, and the birth of MediaBrain.app']
];

echo "🚀 COMMENCING PURE_GENESIS: YEAR 0 -> YEAR 2026\n";

foreach ($epochs as $epoch) {
    echo "⏳ EPOCH: {$epoch['start']} - {$epoch['end']} | THEME: {$epoch['theme']}\n";

    // Adjust the number of batches for more or less density (currently 5 batches of 10 = 50 per epoch)
    for ($batch = 0; $batch < 5; $batch++) { 
        $prompt = "You are the primary Archivist of the MediaBrain. 
                   Generate a JSON array of 10 profoundly realistic and era-appropriate observations from the years {$epoch['start']} to {$epoch['end']}.
                   Theme: {$epoch['theme']}.
                   Format: JSON array of objects with keys: 'content', 'content_type' (story, philosophy, warning, log), and 'date' (Y-m-d H:i:s).
                   Each observation must feel like it was written by someone living in that exact year. 
                   Output ONLY the raw JSON array.";

        $response = $gemini->ask($prompt);

        // 🛡️ JSON extraction
        if (isset($response['error'])) {
            echo "  ⚠️ API_ERROR: " . $response['error'] . "<br><br>";
            // If it's a quota error, we should probably stop the script
            if ($response['error'] === 'QUOTA_EXCEEDED') {
                echo "🛑 QUOTA EXHAUSTED. TERMINATING GENESIS.<br><br>";
                break 2; 
            }
            continue;
        }

        $jsonStr = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        
        // DEBUG: If you want to see exactly what came back
        // echo "DEBUG RAW: " . $jsonStr . "\n"; 

        $jsonStr = preg_replace('/^```json|```$/m', '', $jsonStr);
        $data = json_decode(trim($jsonStr), true);

        if (is_array($data)) {
            foreach ($data as $entry) {
                // We use architect_id = 1 for the system-generated genesis
                $stmt = $app->db->prepare("INSERT INTO memory_anchors (architect_id, content, content_type, created_at) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    1,
                    $entry['content'],
                    $entry['type'],
                    $entry['date']
                ]);
                echo "  ✅ [" . date('Y', strtotime($entry['date'])) . "] " . substr($entry['content'], 0, 50) . "...<br><br>";
            }
        } else {
            echo "  ❌ BATCH_FAILURE: Invalid JSON response. Retrying next cycle.<br><br>";
        }
        
        sleep(1.5); // Maintain a steady AI heartbeat
    }
}

echo "<br><br>🏁 PURE_GENESIS_COMPLETE. The archive is now filled with 2,000 years of high-quality human observation. <3<br><br>";

echo " <br><br><br><br>                                  ( . Y . ) <br><br>";
