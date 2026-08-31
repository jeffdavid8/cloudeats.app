<?php
define('MB_RUNNING', true);
define('ROOT_PATH', __DIR__);

/*
* Base System API 
*  - This file should be included from the index.php file and not directly accessed via url
*/
// Route all api calls through index.php
/*
if (!defined('MB_RUNNING')) {
    // uncomment this line once all of the /api.php calls are converted to /?api=mediabrain
    //die('No direct access.  Please use index.php?api=system.');
}
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/mb.bootstrap.php';

$app = get_var('app');
if ($app) {
  $api_file = __DIR__ . '/apps/' . $app . '/' . $app . '.api.php';
  if (file_exists($api_file)) {
    require_once $api_file;
  }
}

use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;

setJsonHeader();

// Robust input handling for Cloud Run - prefer JSON body
$rawInput = file_get_contents('php://input');
$request = json_decode($rawInput, true); // `true` for an associative array
$action = (isset($request['action'])) ? $request['action'] : '';
$data = (isset($request['data'])) ? $request['data'] : array();

// Handle form-encoded data (fallback when JSON decode fails)
// This is especially important for Cloud Run containers
if (empty($action) && !empty($_REQUEST['action'])) {
  $action = $_REQUEST['action'];
  $data = $_REQUEST['data'] ?? array();
}

// Additional fallback for Cloud Run: check POST directly
if (empty($action) && !empty($_POST['action'])) {
  $action = $_POST['action'];
  $data = $_POST['data'] ?? array();
}

// Call api_logout for logout action
if ($action === 'logout') {
  // Collect all params from POST, GET, REQUEST
  $params = array_merge($_GET, $_POST, $_REQUEST);
  api_logout($params);
}

// Production: Debug logging removed for performance


if ((!empty($action)) && (function_exists('api_' . $action))) {
  // For text_to_speech, pass the entire REQUEST as params since data is in top-level POST
  if ($action === 'text_to_speech') {
    call_user_func_array('api_' . $action, array($_REQUEST));
  } else {
    call_user_func_array('api_' . $action, array($data));
  }
  exit;
} else {
  // No valid action found - silently fail for security
  http_response_code(400);
  header('Content-Type: application/json');
  echo json_encode(array('success' => false, 'error' => 'Invalid action'));
  exit;
}


/*
*  Text To Speech
*/
function api_text_to_speech($params)
{
  header('Access-Control-Allow-Origin: *');
  header('Content-Type: application/json');
  // Restore session from PHPSESSID if provided in params or cookies
  $sessionId = $params['PHPSESSID'] ?? App::getInstance()->getCookie('PHPSESSID') ?? null;
  if ($sessionId && !empty($sessionId)) {
    session_id($sessionId);
  }

  // Validate CSRF token - robust extraction for Cloud Run
  require_once(__DIR__ . '/includes/app.php');
  $appInstance = App::getInstance();
  $auth = $appInstance->getAuthManager();
  $csrf_token = $params['data']['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';

  // Use AuthManager for CSRF validation
  if ($auth->validateCsrf($csrf_token)) {
    // CSRF token is valid
  } else {
  error_log("TTS CSRF Warning - Token validation failed: received='" . $csrf_token . "', session='" . ($_SESSION['csrf_token'] ?? 'NULL') . "'");
    if (empty($csrf_token)) {
      http_response_code(403);
      echo json_encode(array('success' => false, 'error' => 'CSRF token required'));
      return;
    }
  }

  $text = $params['words'] ?? $params['data']['words'] ?? '';
  if (empty($text)) {
    echo json_encode(array('success' => false, 'error' => 'No text provided for synthesis'));
    return;
  }

  $errors = [];
  $result = '';
  // Secret Manager client
  $client = new Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient();
  if (!$client) {
    $errors[] = 'Secret Manager client not initialized.';
    echo json_encode(array('success' => false, 'errors' => $errors));
    return;
  }

  $projectId = getenv('GOOGLE_CLOUD_PROJECT') ?: 'mediabrain';
  $secretName = 'tts-sa-key';
  $name = "projects/$projectId/secrets/$secretName/versions/latest";
  $request = new Google\Cloud\SecretManager\V1\AccessSecretVersionRequest();
  $request->setName($name);
  $response = $client->accessSecretVersion($request);
  if (!$response || !$response->getPayload()) {
    $errors[] = 'Secret Manager error (TTS): No payload returned.';
    echo json_encode(array('success' => false, 'errors' => $errors));
    return;
  }
  $ttsCreds = $response->getPayload()->getData();
  if (!$ttsCreds) {
    $errors[] = 'Could not load TTS credentials from Secret Manager';
    echo json_encode(array('success' => false, 'errors' => $errors));
    return;
  }
  $tts_json = json_decode($ttsCreds, true);
  if (!$tts_json) {
    $errors[] = 'TTS credentials not valid JSON';
    echo json_encode(array('success' => false, 'errors' => $errors));
    return;
  }

  $ttsClient = new Google\Cloud\TextToSpeech\V1\TextToSpeechClient([
    'credentials' => $tts_json
  ]);
  if (!$ttsClient) {
    $errors[] = 'TextToSpeechClient not initialized.';
    echo json_encode(array('success' => false, 'errors' => $errors));
    return;
  }

  $inputText = $text;

  $synthesisInput = new Google\Cloud\TextToSpeech\V1\SynthesisInput();
  $synthesisInput->setText($inputText);
  $voice = new Google\Cloud\TextToSpeech\V1\VoiceSelectionParams();
  $voice->setLanguageCode('en-US');
  $voice->setSsmlGender(Google\Cloud\TextToSpeech\V1\SsmlVoiceGender::NEUTRAL);
  $audioConfig = new Google\Cloud\TextToSpeech\V1\AudioConfig();
  $audioConfig->setAudioEncoding(Google\Cloud\TextToSpeech\V1\AudioEncoding::MP3);
  $ttsResponse = $ttsClient->synthesizeSpeech($synthesisInput, $voice, $audioConfig);
  $audioContent = $ttsResponse ? $ttsResponse->getAudioContent() : null;
  $ttsClient->close();
  if (!empty($audioContent)) {
    echo json_encode(array(
      'success' => true,
      'audioContent' => base64_encode($audioContent),
    ));
    return;
  } else {
    $errors[] = 'TTS synthesis returned empty audio content for ' . $text;
    $errors[] = 'TTS response object: ' . print_r($ttsResponse, true);
    $errors[] = 'TTS credentials: ' . substr($ttsCreds, 0, 100) . '...';
    echo json_encode(array('success' => false, 'errors' => $errors));
  }
  return;
}



// --- LOGOUT ENDPOINT ---
function api_logout($params)
{
  // Start session if not already started
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  // Validate CSRF token
  require_once(__DIR__ . '/includes/app.php');
  $appInstance = App::getInstance();
  $auth = $appInstance->getAuthManager();
  $csrf_token = $params['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_REQUEST['csrf_token'] ?? '';
  if (!$auth->validateCsrf($csrf_token)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'CSRF token required']);
    exit();
  }
  $returnUrl = $params['return_url'] ?? '?p=login';
  // Clear all session data
  $_SESSION = array();
  // Destroy session cookie if it exists
  if (ini_get("session.use_cookies")) {
    $cookie_params = session_get_cookie_params();
    App::getInstance()->setCookie(session_name(), '');
  }
  // Destroy the session
  session_destroy();
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'redirect' => $returnUrl]);
  exit();
}

function getGoogleAccessToken()
{
  // Try Cloud Run metadata service first (production with attached service account)
  try {
    return getAccessTokenFromMetadata();
  } catch (Exception $e) {
    log_error("Metadata service failed: " . $e->getMessage());
  }

  /*
    // Try ADC first in local development (more reliable than service account key files)
    $adcPath = '/tmp/adc_credentials.json';
    if (file_exists($adcPath)) {
        try {
            return getAccessTokenFromADC($adcPath);
    } catch (Exception $e) {
      log_error("ADC failed: " . $e->getMessage());
    }
    }
    */

  // Try service account key file (alternative production setup)
  $serviceAccountPath = '/tmp/tts-sa-key.json';
  if (file_exists($serviceAccountPath)) {
    return getAccessTokenFromServiceAccount($serviceAccountPath);
  }

  throw new Exception("No credentials found - tried metadata service, ADC, and service account key");
}

function getAccessTokenFromADC($adcPath)
{
  $creds = json_decode(file_get_contents($adcPath), true);

  // The ADC file should contain a refresh token we can use
  if (!isset($creds['refresh_token'])) {
    throw new Exception("No refresh token found in ADC credentials");
  }

  $postData = [
    'client_id' => $creds['client_id'],
    'client_secret' => $creds['client_secret'],
    'refresh_token' => $creds['refresh_token'],
    'grant_type' => 'refresh_token'
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
  ]);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode === 200) {
    $tokenData = json_decode($response, true);
    return $tokenData['access_token'];
  } else {
    throw new Exception("ADC token refresh failed: " . $response);
  }
}

function getAccessTokenFromMetadata()
{
  // Use Google Cloud metadata service to get access token from attached service account
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/token');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Metadata-Flavor: Google']);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode === 200) {
    $tokenData = json_decode($response, true);
    if (isset($tokenData['access_token'])) {
      return $tokenData['access_token'];
    } else {
      throw new Exception("No access token in metadata response");
    }
  } else {
    throw new Exception("Metadata service request failed with HTTP " . $httpCode . ": " . $response);
  }
}

function getAccessTokenFromServiceAccount($keyPath)
{
  $serviceAccount = json_decode(file_get_contents($keyPath), true);

  // Create JWT for service account authentication
  $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
  $now = time();
  $payload = json_encode([
    'iss' => $serviceAccount['client_email'],
    'scope' => 'https://www.googleapis.com/auth/cloud-platform',
    'aud' => 'https://oauth2.googleapis.com/token',
    'exp' => $now + 3600,
    'iat' => $now
  ]);

  $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
  $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

  $signature = '';
  openssl_sign($base64Header . '.' . $base64Payload, $signature, $serviceAccount['private_key'], 'SHA256');
  $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

  $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;

  // Exchange JWT for access token
  $postData = [
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode === 200) {
    $tokenData = json_decode($response, true);
    return $tokenData['access_token'];
  } else {
    throw new Exception("Service account token request failed: " . $response);
  }
}

function synthesizeSpeechDirect($text)
{
  // Use Secret Manager for API key in Cloud Run
  $apiKey = null;
  if (isCloudRun()) {
    // Use Google Secret Manager client
    try {
      $client = new Google\Cloud\SecretManager\V1\client\SecretManagerServiceClient();
      $projectId = getenv('GOOGLE_CLOUD_PROJECT');
      $secretName = 'tts-sa-key'; // Change to your actual secret name
      $name = "projects/$projectId/secrets/$secretName/versions/latest";
      $request = new Google\Cloud\SecretManager\V1\AccessSecretVersionRequest();
      $request->setName($name);
      $response = $client->accessSecretVersion($request);
      $apiKey = $response->getPayload()->getData();
    } catch (Exception $e) {
      log_error('Secret Manager error: ' . $e->getMessage());
    }
  }
  $accessToken = getGoogleAccessToken();

  $requestData = [
    'input' => ['text' => $text],
    'voice' => [
      'languageCode' => 'en-US',
      'ssmlGender' => 'NEUTRAL'
    ],
    'audioConfig' => [
      'audioEncoding' => 'MP3'
    ]
  ];

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://texttospeech.googleapis.com/v1/text:synthesize' . ($apiKey ? '?key=' . $apiKey : ''));
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $headers = [
    'Content-Type: application/json',
    'X-Goog-User-Project: mediabrain'
  ];
  if (!$apiKey) {
    $headers[] = 'Authorization: Bearer ' . $accessToken;
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

  $response = curl_exec($ch);
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    return $responseData['audioContent'];
  } else {
    throw new Exception("TTS API call failed (HTTP $httpCode): " . $response);
  }
}

/*
*  API Info
*/
function api_info($data)
{
  $result = '';

  switch ($data['type']) {
    case 'php-version':
      $result = PHP_VERSION;
  }

  header('Content-Type: application/json');
  echo json_encode(array(
    'status' => 'Ok',
    'info' => $result,
  ));
}


/*
*  API Set Background
*/
function api_set_background($params)
{
  if (isset($params['image_name'])) {
    App::getInstance()->setCookie('bg_image', $params['image_name']);

    header('Content-Type: application/json');
    echo json_encode(array(
      'status' => 'Ok',
      'bg_image' => $params['image_name'],
    ));
  }
}


/*
*  API Search
*/
function api_search($search_string)
{
  $endpoint = "http://vi/mediawiki/api.php";
  $params_array = [
    "action" => "query",
    "list" => "search",
    "srsearch" => $search_string,
    "format" => "json",
  ];

  $url = $endpoint . "?" . http_build_query($params_array);

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $output = curl_exec($ch);
  curl_close($ch);

  $result = json_decode($output, true);

  if ($result['query']['search'][0]['title'] == $search_string) {
  }
  header('Content-Type: application/json');
  echo json_encode(array(
    'status' => 'Ok',
    'search_results' => $result,
  ));
}


/*
*  Toggle Night Mode
*/
function api_toggle_night_mode($params)
{
  if (isset($params['day_night_mode'])) {
    App::getInstance()->setCookie('day_night_mode', $params['day_night_mode']);

    header('Content-Type: application/json');
    echo json_encode(array(
      'status' => 'Ok',
      'day_night_mode' => $params['day_night_mode'],
    ));
  }
}

/*
*  Add Bookmark to $_SESSION
*/
function api_add_bookmark($params)
{
  $_SESSION['bookmarks'][] = $params['key'];

  $bookmarks = array();
  foreach ($_SESSION['bookmarks'] as $bookmark) {
    $bookmarks[] = render('components/bookmark_link.php', array('bookmark' => array('reference' => $bookmark)), 1);
  }

  header('Content-Type: application/json');
  echo json_encode(array(
    'status' => 'Ok',
    'bookmarks' => $bookmarks,
  ));
}


/*
*  Clear all bookmarks from $_SESSION
*/
function api_clear_all_bookmarks($params)
{
  // Clear all bookmarks fromn session
  $_SESSION['bookmarks'] = array();

  header('Content-Type: application/json');
  echo json_encode(array(
    'status' => 'Ok',
    'bookmarks' => $_SESSION['bookmarks'],
  ));
}

function api_get_session_bookmarks($params)
{
  $type = get_var('type', 'json');
  if ($type == 'file') {
    header('Content-disposition: attachment; filename=bookmarks.json');
  }
  header('Content-Type: application/json');
  echo json_encode(array(
    'status' => 'Ok',
    'bookmarks' => $_SESSION['bookmarks'],
  ));
}

function api_upload_session_bookmarks($params)
{
  //debug($params);

  if (isset($params['imports']['bookmarks'])) {
    if ($params['merge']) {
      foreach ($params['imports']['bookmarks'] as $bookmark) {
        $_SESSION['bookmarks'][] = $bookmark;
      }
    } else {
      $_SESSION['bookmarks'] = $params['imports']['bookmarks'];
    }
  }

  $bookmarks = array();
  foreach ($_SESSION['bookmarks'] as $bookmark) {
    $bookmarks[] = render('components/bookmark_link.php', array('bookmark' => array('reference' => $bookmark)), 1);
  }

  echo json_encode(array(
    'status' => 'Ok',
    'bookmarks' => $bookmarks,
  ));
}


function file_get_contents_curl($url)
{
  //debug($url);//http://bibleBot.local/search.php?s=Proverbs+9%3A10
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

  $data = curl_exec($ch);
  curl_close($ch);
  //debug($data);

  return $data;
}

function api_download($params)
{
  $url = $params['url'];
  $to_path = $params['toPath'];
  if (file_put_contents($to_path, file_get_contents($url))) {
    echo json_encode(array(
      'status' => 'Ok',
    ));
  } else {
    echo json_encode(array(
      'status' => 'Ok',
      'url' =>  $url,
      'to_path' => $to_path,
      'error' => "File downloading failed.",
    ));
  }
}

function api_get_meta_tags($url)
{
  $html = file_get_contents_curl($url);

  //parsing begins here:
  $doc = new DOMDocument();
  @$doc->loadHTML($html);
  $nodes = $doc->getElementsByTagName('title');

  //get and display what you need:
  $title = $nodes->item(0)->nodeValue;

  $metas = $doc->getElementsByTagName('meta');
  $meta_tags = array();

  for ($i = 0; $i < $metas->length; $i++) {
    $meta = $metas->item($i);
    $name = '';
    if (!empty($meta->getAttribute('name'))) {
      $name = $meta->getAttribute('name');
    } else if (!empty($meta->getAttribute('property'))) {
      $name = $meta->getAttribute('property');
    }
    if (!empty($name)) {
      $meta_tags[$name] = $meta->getAttribute('content');
    }
  }


  echo json_encode(array(
    'status' => 'Ok',
    'meta_tags' => $meta_tags,
    'count' => count($meta_tags),
  ));
}
