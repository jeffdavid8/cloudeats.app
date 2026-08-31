<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Database Backup and Migration Layer
 *
 * Manages atomic, non-destructive exports and structural cascading imports
 * for multiple sub-ecosystem tables using JSON transfer schemas.
 */
class BackupManager
{
    /**
     * Target application specific platform tables divided by ecosystem module mapping blocks
     */
    private static $defaultAppTableMap = [
        'stitch' => [
            'memory_anchors',
            'vouches',
            'stitch_nexus',
            'pasture_handshakes'
        ],
        'neighborhub' => [
            'neighborhub_merchants',
            'neighborhub_merchant_users',
            'neighborhub_products',
            'neighborhub_product_images',
            'neighborhub_orders',
            'neighborhub_order_items',
            'neighborhub_couriers',
            'neighborhub_delivery_tracking'
        ]
    ];

    /**
     * Generate a complete JSON Export payload of ecosystem environment tables and store it to disk
     *
     * @param array|null $appTableMap Optional target multi-dimensional array mapping ['app' => ['table1', 'table2']]
     * @return string|false Structured JSON string of application tables or false on failure
     */
    public static function exportToJson($appTableMap = null)
    {
        try {
            $db = App::getInstance()->db;

            // Fall back onto the standard global map if custom structural layers aren't supplied
            $targetMap = is_array($appTableMap) ? $appTableMap : self::$defaultAppTableMap;

            $exportData = [
                'metadata' => [
                    'export_date' => date('Y-m-d H:i:s'),
                    'version'     => '1.1.0',
                    'origin'      => 'Multi_Ecosystem_Backup_Engine'
                ],
                'tables' => []
            ];

            // Traverse down each module layer to fetch corresponding database tables
            foreach ($targetMap as $appName => $tables) {
                if (!is_array($tables)) continue;

                foreach ($tables as $table) {
                    $table = trim($table);
                    if (empty($table)) continue;

                    // Fetch rows from current table iteration
                    $stmt = $db->query("SELECT * FROM {$table}");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $exportData['tables'][$table] = $rows ? $rows : [];
                }
            }

            $jsonString = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Construct full system destination path string pointing to the targeted location
            $storageDir = '/var/www/storage/';
            if (!file_exists($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $destinationPath = $storageDir . '/default_data.json';
            $bytesWritten = file_put_contents($destinationPath, $jsonString);

            if ($bytesWritten === false) {
                error_log("BackupManager::exportToJson Error: Failed writing content to disk context path: " . $destinationPath);
            }

            return $jsonString;
        } catch (Exception $e) {
            error_log("BackupManager::exportToJson Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stream read a JSON backup file and parse entries cleanly back into database structures
     *
     * @param string $filePath Absolute file system pointer to the backup file
     * @param array|null $appTableMap Optional structural sync layout constraint matching the array configuration
     * @return array Status payload with success flag and transactional logs
     */
    public static function importFromJsonFile($filePath, $appTableMap = null)
    {
        $log = [];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'error' => "Backup file context is unreachable or missing read clearance."];
        }

        // Parse JSON file contents into memory safely
        $rawContent = file_get_contents($filePath);
        $data = json_decode($rawContent, true);

        if (!$data || !isset($data['tables'])) {
            return ['success' => false, 'error' => "Invalid transfer schema format. Missing active 'tables' block pointer."];
        }

        try {
            $db = App::getInstance()->db;
            $targetMap = is_array($appTableMap) ? $appTableMap : self::$defaultAppTableMap;

            // 1. DEACTIVATE CONSTRAINTS TO ALLOW OUT-OF-ORDER SEEDING
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");

            // 2. BEGIN ATOMIC ISOLATION TRANSACTION BLOCK
            $db->exec("START TRANSACTION;");

            foreach ($targetMap as $appName => $tables) {
                if (!is_array($tables)) continue;

                foreach ($tables as $table) {
                    $table = trim($table);
                    if (empty($table)) continue;

                    // Clear existing table content to prevent UNIQUE key/PKey constraint collisions
                    $db->exec("DELETE FROM {$table};");

                    if (!isset($data['tables'][$table]) || empty($data['tables'][$table])) {
                        $log[] = "Table [{$table}] cleared, no backup dataset rows to ingest.";
                        continue;
                    }

                    $rows = $data['tables'][$table];

                    // Grab array columns dynamically from the first payload chunk element
                    $sampleRow = $rows[0];
                    $columns = array_keys($sampleRow);

                    // Construct statement strings mapping query variables safely
                    $columnList = implode(', ', $columns);
                    $placeholderList = ':' . implode(', :', $columns);

                    $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$placeholderList})";
                    $stmt = $db->prepare($sql);

                    $rowCount = 0;
                    foreach ($rows as $row) {
                        $bindArray = [];
                        foreach ($row as $columnName => $value) {
                            $bindArray[':' . $columnName] = $value;
                        }
                        $stmt->execute($bindArray);
                        $rowCount++;
                    }

                    $log[] = "Successfully restored {$rowCount} record segments into [{$table}].";
                }
            }

            // 3. SECURELY COMMIT TRANSFERS DOCKING CHUNKS TO PERMANENT DISK STORAGE
            $db->exec("COMMIT;");

            // 4. REACTIVATE INTEGRITY LAYER ENFORCEMENT RULES
            $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            return [
                'success' => true,
                'log'     => $log
            ];
        } catch (Exception $e) {
            // Revert changes on emergency operational faults
            if (isset($db) && $db->inTransaction()) {
                $db->exec("ROLLBACK;");
            }
            if (isset($db)) {
                $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            }

            error_log("BackupManager::importFromJsonFile Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'log'     => $log
            ];
        }
    }
}
