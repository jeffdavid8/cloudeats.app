<?php

define('MB_RUNNING', true);
date_default_timezone_set('America/Indiana/Indianapolis');
require __DIR__ . '/../../html/apps/neighborhub/includes/classes/VendorHoursChecker.class.php';
require __DIR__ . '/../../html/apps/neighborhub/includes/models/merchant.model.php';

class FixtureStatement
{
    public function execute($values): void {}
    public function fetchAll($mode): array
    {
        $today = strtolower(date('l'));
        $tomorrow = strtolower(date('l', strtotime('+1 day')));
        $open = ['weekly' => [$today => [['open' => '00:00', 'close' => '00:00']]]];
        $closed = ['weekly' => [$tomorrow => [['open' => '00:00', 'close' => '00:00']]]];
        $holiday = ['weekly' => [$today => [['open' => '00:00', 'close' => '00:00']]], 'holidays' => [date('Y-m-d') => []]];
        $base = ['status' => 'active', 'latitude' => 40.0, 'longitude' => -85.0, 'address' => 'Main St', 'city' => 'Marion', 'state' => 'IN', 'distance_miles' => 1.0];
        return [
            $base + ['id' => 1, 'business_name' => 'Open', 'store_hours' => json_encode($open)],
            $base + ['id' => 2, 'business_name' => 'Closed', 'store_hours' => json_encode($closed)],
            $base + ['id' => 3, 'business_name' => 'Holiday', 'store_hours' => json_encode($holiday)],
            $base + ['id' => 4, 'business_name' => 'Missing', 'store_hours' => ''],
            $base + ['id' => 5, 'business_name' => 'Legacy', 'store_hours' => 'Mon-Fri 9AM - 5PM'],
        ];
    }
}

class FixtureDatabase
{
    public function prepare($sql): FixtureStatement { return new FixtureStatement(); }
}

class App
{
    public $db;
    public static function getInstance($name = '')
    {
        static $app;
        if (!$app) {
            $app = new self();
            $app->db = new FixtureDatabase();
        }
        return $app;
    }
    public function includeModel($name): void {}
    public function includeClass($name): void {}
}

class Order
{
    public static function calculateDeliveryFee($merchantLat, $merchantLng, $lat, $lng): array
    {
        return ['fee' => 4.5];
    }
}

class NeighborhubReporting
{
    public function __construct($db) {}
    public function recordEvent(...$args): void {}
}

$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET = ['action' => 'search_merchants'];
$_REQUEST = $_GET;
$_POST = ['lat' => '40', 'lng' => '-85'];
session_start();
$_SESSION = [];
register_shutdown_function(static function (): void {
    fwrite(STDERR, (string)(http_response_code() ?: 200));
});
require __DIR__ . '/../../html/apps/neighborhub/neighborhub.api.php';
