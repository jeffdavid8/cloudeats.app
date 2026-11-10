<?php

define('MB_RUNNING', true);

class App
{
    public $db;
    public $user;

    public static function getInstance($name = '')
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->user = (object)['id' => 1, 'is_admin' => ($GLOBALS['testRole'] ?? 'admin') === 'admin'];
            $app->db = new PDO('sqlite::memory:');
            $app->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $app->db->exec('CREATE TABLE neighborhub_images (id INTEGER, parent_type TEXT, parent_id INTEGER, image_url TEXT)');
            $app->db->exec("INSERT INTO neighborhub_images VALUES (51, 'merchant', 8, '/own.png'), (52, 'merchant', 9, '/other.png')");
            $app->db->exec("CREATE TABLE neighborhub_merchant_users (merchant_id INTEGER, user_id INTEGER, staff_role TEXT, status TEXT)");
        }
        return $app;
    }

    public function includeModel($model) {}
    public function includeClass($class) {}
}

class Merchant
{
    public static function update($id, $data)
    {
        file_put_contents('php://stderr', json_encode($data));
        return ['success' => true];
    }

    public static function uploadGalleryImages($id, $file)
    {
        $db = App::getInstance()->db;
        $db->exec("INSERT INTO neighborhub_images VALUES (53, 'merchant', 8, '/uploaded.png'), (54, 'merchant', 8, '/concurrent.png')");
        return ['/uploaded.png'];
    }
}

$testRole = $argv[2] ?? 'admin';
session_start();
$_SESSION['user'] = ['id' => 1, 'is_admin' => $testRole === 'admin'];
$_GET = ['action' => ($argv[1] === 'upload' ? 'upload_storefront_asset' : 'update_merchant_settings')];
$_REQUEST = $_GET;
$_POST = ['merchant_id' => 8, 'business_name' => 'Example', 'profile_image_id' => $argv[1]];
$_FILES = ['asset' => ['name' => 'image.png']];
register_shutdown_function(static function (): void {
    file_put_contents('php://stderr', "\n" . http_response_code(), FILE_APPEND);
});
require __DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php';
