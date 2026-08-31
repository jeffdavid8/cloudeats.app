<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Database Backup and Migration Layer
 * * Manages atomic, non-destructive exports and structural cascading imports
 * for all Neighborhub sub-ecosystem tables using JSON transfer schemas.
 */
class BackupManager
{
    /**
     * Target application specific platform tables to cycle through
     */
    private static $targetTables = [
        'neighborhub_merchants',
        'neighborhub_merchant_users',
        'neighborhub_products',
        'neighborhub_product_images',
        'neighborhub_orders',
        'neighborhub_order_items',
        'neighborhub_couriers',
        'neighborhub_delivery_tracking'
    ];

    /**
     * Generate a complete JSON Export payload of Neighborhub environment tables
     * * @return string|false Structured JSON string of application tables or false on failure
     */
    public static function exportToJson()
    {
        try {
            $db = App::getInstance()->db;
            $exportData = [
                'metadata' => [
                    'export_date' => date('Y-m-d H:i:s'),
                    'version'     => '1.0.0',
                    'origin'      => 'Neighborhub_Backup_Engine'
                ],
                'tables' => []
            ];

            foreach (self::$targetTables as $table) {
                // Fetch rows from current table iteration
                $stmt = $db->query("SELECT * FROM {$table}");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $exportData['tables'][$table] = $rows ? $rows : [];
            }

            return json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            error_log("NeighborhubBackupManager::exportToJson Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stream read a JSON backup file and parse entries cleanly back into SQLite tables
     * * @param string $filePath Absolute file system pointer to the backup file
     * @return array Status payload with success flag and transactional logs
     */
    public static function importFromJsonFile($filePath)
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

            // 1. DEACTIVATE CONSTRAINTS TO ALLOW OUT-OF-ORDER SEEDING
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // 2. BEGIN ATOMIC ISOLATION TRANSACTION BLOCK
            $db->exec("START TRANSACTION;");

            foreach (self::$targetTables as $table) {
                // Clear existing table content to prevent UNIQUE key/PKey constraint collisons
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
            
            error_log("NeighborhubBackupManager::importFromJsonFile Exception: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'log'     => $log
            ];
        }
    }
}