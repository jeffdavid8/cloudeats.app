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

      $app->includeClass('BackupManager');

      $tables = array();
      $tables['neighborhub'] = app_invoke('neighborhub', 'db_tables');
      $tables['stitch'] = app_invoke('stitch', 'db_tables');
      $tables['admin'] = app_invoke('admin', 'db_tables');

      $table_index = array();
      $exportData = array();

      /**
       * Returns a SELECT column list that converts spatial/binary columns to
       * human-readable text so json_encode never chokes on raw WKB bytes.
       * MySQL POINT, GEOMETRY, etc. are returned as ST_AsText(col) AS col.
       */
      $safe_select = function(string $table) use ($db): string {
        $cols = [];
        $meta = $db->query("
          SELECT COLUMN_NAME, DATA_TYPE
          FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME   = '{$table}'
          ORDER BY ORDINAL_POSITION
        ");
        foreach ($meta->fetchAll(PDO::FETCH_ASSOC) as $col) {
          $name     = $col['COLUMN_NAME'];
          $dataType = strtolower($col['DATA_TYPE']);
          // Spatial and binary types that break json_encode
          if (in_array($dataType, ['point','geometry','linestring','polygon',
                                   'multipoint','multilinestring','multipolygon',
                                   'geometrycollection','blob','mediumblob','longblob'])) {
            $cols[] = "ST_AsText(`{$name}`) AS `{$name}`";
          } else {
            $cols[] = "`{$name}`";
          }
        }
        return empty($cols) ? '*' : implode(', ', $cols);
      };

      // Traverse down each module layer to fetch corresponding database tables
      foreach ($tables as $appName => $tables) {
        if (!is_array($tables)) continue;

        foreach ($tables as $table) {
          $table = trim($table);
          if (empty($table)) continue;

          // Check if the table exists in the database
          $stmt = $db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}' LIMIT 1");
          if ($stmt->fetchColumn() === false) {
            error_log("Warning: Table '{$table}' does not exist in the database. Skipping.");
            continue;
          }

          $table_index[] = $table;
          error_log("Exporting table: {$table}");

          // Use spatial-safe column list to avoid WKB binary data in json_encode
          $selectCols = $safe_select($table);
          $stmt = $db->query("SELECT {$selectCols} FROM `{$table}`");
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

          error_log("Fetched " . count($rows) . " rows from table: {$table}");
          $exportData[$table] = $rows ?: [];
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
      // Build JSON first so we can set an accurate Content-Length before
      // any output reaches the buffer — prevents "headers already sent" errors.
      $filename = "cloudeats".((is_development())?'_dev':'_prod')."_full_export_" . date('Ymd_His') . ".json";

      // Primary encode; fallback substitutes any remaining bad bytes rather
      // than silently returning false and producing a 0-byte download.
      $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
      if ($json === false) {
        error_log("json_encode primary FAILED (error " . json_last_error() . ": " . json_last_error_msg() . ") — retrying with UTF-8 substitution");
        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
      }
      if ($json === false) {
        die("❌ EXPORT_CRITICAL_FAILURE: json_encode failed — " . json_last_error_msg());
      }

      header('Content-Type: application/json');
      header('Content-Disposition: attachment; filename="' . $filename . '"');
      header('Content-Length: ' . strlen($json));
      header('Cache-Control: no-store, no-cache, must-revalidate');
      header('Pragma: no-cache');
      header('Expires: 0');

      echo $json;

      exit;
    } catch (Exception $e) {
      die("❌ EXPORT_CRITICAL_FAILURE: " . $e->getMessage());
    }
    break;

  default:
    break;
}
