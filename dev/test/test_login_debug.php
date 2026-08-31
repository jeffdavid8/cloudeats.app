<?php
// test_login_debug.php
// Diagnose login issues with Google Cloud Storage and users.json

ini_set('display_errors', 1);
error_reporting(E_ALL);



require_once '../vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\SecretManager\V1\client\SecretManagerServiceClient;

echo "<h2>Google Secret Manager: Testing access</h2>";
try {
    $secretClient = new SecretManagerServiceClient();
    echo "<h3>✓ SecretManagerServiceClient loaded</h3>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>✗ Failed to load SecretManagerServiceClient: " . $e->getMessage() . "</h3>";
    exit;
}

function fetchSecret($secretName, $version = 'latest') {
    global $secretClient;
    try {
        $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: 'mediabrain';
        $name = "projects/$projectId/secrets/$secretName/versions/$version";
        $request = new Google\Cloud\SecretManager\V1\AccessSecretVersionRequest();
        $request->setName($name);
        $response = $secretClient->accessSecretVersion($request);
        return $response->getPayload()->getData();
    } catch (Exception $e) {
        echo "<h3 style='color:red'>✗ Failed to fetch secret $secretName: " . $e->getMessage() . "</h3>";
        return false;
    }
}

// --- Cloud Storage Test ---
echo "<h2>Cloud Storage: Testing access</h2>";
$storageCreds = fetchSecret('cloud-storage-key');
if ($storageCreds) {
    $cred_json = json_decode($storageCreds, true);
    if (!$cred_json) {
        echo "<h3 style='color:red'>✗ Cloud Storage credentials not valid JSON</h3>";
    } else {
        echo "<h3>✓ Cloud Storage credentials loaded</h3>";
        $projectId = $cred_json['project_id'] ?? 'mediabrain';
        $bucketName = 'mediabrain-system-data';
        $objectPaths = [
            'users.json',
            'system_data/users.json',
            'storage/google/cloud-storage/users.json'
        ];
        try {
            $storage = new StorageClient([
                'projectId' => $projectId,
                'keyFile' => $cred_json
            ]);
            echo "<h3>✓ StorageClient created</h3>";
            echo "<h4>Buckets in project:</h4><ul>";
            foreach ($storage->buckets() as $b) {
                echo "<li>" . htmlspecialchars($b->name()) . "</li>";
            }
            echo "</ul>";
            $bucket = $storage->bucket($bucketName);
            echo "<h4>Bucket reference: $bucketName</h4>";
            if ($bucket->exists()) {
                echo "<h4>✓ Bucket exists</h4>";
                echo "<h4>Files in bucket:</h4><ul>";
                foreach ($bucket->objects() as $obj) {
                    echo "<li>" . htmlspecialchars($obj->name()) . "</li>";
                }
                echo "</ul>";
                $found = false;
                foreach ($objectPaths as $objectPath) {
                    $object = $bucket->object($objectPath);
                    if ($object->exists()) {
                        echo "<h4 style='color:green'>✓ Found: $objectPath</h4>";
                        $content = $object->downloadAsString();
                        echo "<h4>✓ Content downloaded (" . strlen($content) . " bytes)</h4>";
                        $data = json_decode($content, true);
                        if (is_array($data)) {
                            echo "<h4>✓ JSON decoded successfully</h4>";
                            echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
                        } else {
                            echo "<h4 style='color:red'>✗ JSON decode failed</h4>";
                            echo "<pre>" . htmlspecialchars($content) . "</pre>";
                        }
                        $found = true;
                        break;
                    } else {
                        echo "<h4 style='color:red'>✗ Not found: $objectPath</h4>";
                    }
                }
                if (!$found) {
                    echo "<h4 style='color:red'>✗ No users.json found in any expected location</h4>";
                }
            } else {
                echo "<h4 style='color:red'>✗ Bucket $bucketName does not exist</h4>";
            }
        } catch (Exception $e) {
            echo "<h4 style='color:red'>✗ Storage error: " . $e->getMessage() . "</h4>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
} else {
    echo "<h3 style='color:red'>✗ Could not load Cloud Storage credentials from Secret Manager</h3>";
}

// --- TTS Test ---
echo "<h2>Text-to-Speech: Testing access</h2>";
$ttsCreds = fetchSecret('tts-key');
if ($ttsCreds) {
    $tts_json = json_decode($ttsCreds, true);
    if (!$tts_json) {
        echo "<h3 style='color:red'>✗ TTS credentials not valid JSON</h3>";
    } else {
        echo "<h3>✓ TTS credentials loaded</h3>";
        try {
            $ttsClient = new TextToSpeechClient([
                'credentials' => $tts_json
            ]);
            $inputText = 'Hello, this is a test of Google Cloud Text-to-Speech.';
            $synthesisInput = new SynthesisInput();
            $synthesisInput->setText($inputText);
            $voice = new VoiceSelectionParams();
            $voice->setLanguageCode('en-US');
            $voice->setSsmlGender(SsmlVoiceGender::NEUTRAL);
            $audioConfig = new AudioConfig();
            $audioConfig->setAudioEncoding(AudioEncoding::MP3);
            $response = $ttsClient->synthesizeSpeech($synthesisInput, $voice, $audioConfig);
            $audioContent = $response->getAudioContent();
            if ($audioContent) {
                echo "<h3>✓ TTS synthesis succeeded</h3>";
                echo '<audio controls src="data:audio/mp3;base64,' . base64_encode($audioContent) . '"></audio>';
            } else {
                echo "<h3 style='color:red'>✗ TTS synthesis returned no audio</h3>";
            }
            $ttsClient->close();
        } catch (Exception $e) {
            echo "<h3 style='color:red'>✗ TTS error: " . $e->getMessage() . "</h3>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
} else {
    echo "<h3 style='color:red'>✗ Could not load TTS credentials from Secret Manager</h3>";
}
?>
