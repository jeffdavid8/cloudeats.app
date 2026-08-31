<?
// Secure the entry point
if (!defined('MB_RUNNING')) exit;

function init_db($title)
{
  echo "<h1>$title</h1>";
  //$_SESSION['admin_key'] = NULL;
  //exit();
  $key = get_var('key', false);
  if (((!$key) || ($key != $_SESSION['admin_key'])) && (!isset($_SESSION['bypass_admin_key']))) {
    $key = rand(100000, 999999);
    $_SESSION['admin_key'] = $key;
    echo '<a class="btn" href="?app=admin&p=backup_db&key=' . $key . '">Backup Database</a>';
    die();
  }
  $_SESSION['admin_key'] = NULL;

  echo " <br><br><br><br>                                  ( . Y . ) <br><br>";
  echo 'Here we gooooo!  ------------~~~~~~~';
}

init_db('Backup DATABASE');
$app = App::getInstance();
$db = $app->db;

$app->includeClass('BackupManager');

$tables = array();
$tables['neighborhub'] = app_invoke('neighborhub', 'db_tables');
$tables['stitch'] = app_invoke('stitch', 'db_tables');
$tables['admin'] = app_invoke('admin', 'db_tables');

$table_index = array();
$exportData = array();

// Traverse down each module layer to fetch corresponding database tables
foreach ($tables as $appName => $tables) {
  if (!is_array($tables)) continue;

  foreach ($tables as $table) {
    $table = trim($table);
    $table_index[] = $table;
    if (empty($table)) continue;

    // Fetch rows from current table iteration
    $stmt = $db->query("SELECT * FROM {$table}");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $exportData[$table] = $rows ? $rows : [];
  }
}

// 🎯 PACKAGE THE MANIFEST
$export = [
  'metadata' => [
    'export_date' => date('Y-m-d H:i:s'),
    'version'     => '1.1.0',
    'origin'      => 'Multi_Ecosystem_Backup_Engine',
    'table_index' => $table_index,
  ],
  'tables' => $exportData,
];
// 🚀 STREAM THE DOWNLOAD
$filename = "mediabrain_full_export_" . date('Ymd_His') . ".json";

$contents = json_encode($export, JSON_PRETTY_PRINT);
$destination = (is_development()) ? FileStorageManager::CATEGORY_BACKUPS : 'google_cloud';
$storage = FileStorageManager::getInstance();
$result = $storage->storeJsonData(
  $destination,
  $filename,
  $export
);


echo "TABLES_EXTRACTED TO FILE...".$result['url']." <br><br>";
echo " <br><br><br><br>                                  ( . Y . ) <br><br>";//exit;
