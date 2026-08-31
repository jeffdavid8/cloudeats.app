<?
// Secure the entry point
if (!defined('MB_RUNNING')) exit;
$app = App::getInstance();
$db = $app->db;

$type = get_var('type', 'json');

// 🛡️ The Sovereign Move: 
// We tell SQLite to create a clean, consistent copy of itself 
// into a temp file that isn't locked by the Docker process.
switch ($type) {
  case 'sqlite':
    //
    $dbPath = $app->config['db_path'] ?? 'db/mediabrain_dev.sqlite';
    $backupFile = '/tmp/stitch_safe_backup.sqlite';
    $type = get_var('type', 'json');
    try {
      $app->db->query("VACUUM INTO '$backupFile'");
    } catch (Exception $e) {
      // If VACUUM INTO isn't supported (older SQLite), use a simple copy
      // but the VACUUM is the gold standard for Docker.
      copy($dbPath, $backupFile);
    }
    if (file_exists($backupFile)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/x-sqlite3');
      header('Content-Disposition: attachment; filename="stitch_backup_' . date('Y-m-d_H-i') . '.sqlite"');
      header('Content-Length: ' . filesize($backupFile));

      readfile($backupFile);
      unlink($backupFile); // Clean up the temp file
      exit;
    }
    break;

  case 'json':
    /**
     * 🛰️ MISSION: Total Data Sovereignty
     * This script pulls every record from the core tables and 
     * streams it as a JSON download.
     */
    try {

      $data = readfile('/var/www/storage/default_db.json');
      $app->includeClass('BackupManager');

      $tables = array();
      $tables['stitch'] = app_invoke('stitch', 'db_tables');
      $tables['neighborhub'] = app_invoke('neighborhub', 'db_tables');
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

      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      header('Pragma: no-cache');
      header('Expires: 0');

      echo json_encode($export, JSON_PRETTY_PRINT);
      exit;
    } catch (Exception $e) {
      die("❌ EXPORT_CRITICAL_FAILURE: " . $e->getMessage());
    }
    break;

  default:
    break;
}
