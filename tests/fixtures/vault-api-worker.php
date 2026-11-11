<?php
define('MB_RUNNING', true);

class App
{
    public $user;

    public static function getInstance()
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->user = isset($GLOBALS['argv'][3])
                ? (object)['id' => (int)($GLOBALS['argv'][4] ?? $GLOBALS['argv'][3])]
                : null;
        }
        return $app;
    }

    public function includeModel($model)
    {
        throw new RuntimeException('Unexpected model access: ' . $model);
    }
}

$_REQUEST['action'] = $argv[1];
$_SERVER['REQUEST_METHOD'] = $argv[2];
$_SESSION['user'] = isset($argv[3]) ? ['id' => (int)$argv[3]] : [];
ob_start();
require __DIR__ . '/../../html/apps/vault/vault.api.php';
$body = ob_get_clean();
echo json_encode(['status_code' => http_response_code(), 'body' => json_decode($body, true)]);
