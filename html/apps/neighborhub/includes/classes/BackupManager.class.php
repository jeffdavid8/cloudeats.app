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

            // Helper for spatial/binary columns in SELECT
            $safe_select = function(string $table) use ($db): string {
                $cols = [];
                try {
                    $meta = $db->query("
                        SELECT COLUMN_NAME, LOWER(DATA_TYPE) as data_type
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'
                        ORDER BY ORDINAL_POSITION
                    ");
                    while ($col = $meta->fetch(PDO::FETCH_ASSOC)) {
                        $name = $col['COLUMN_NAME'];
                        if (in_array($col['data_type'], ['point','geometry','linestring','polygon','multipoint','multilinestring','multipolygon','geometrycollection','blob','mediumblob','longblob'])) {
                            $cols[] = "ST_AsText(`{$name}`) AS `{$name}`";
                        } else {
                            $cols[] = "`{$name}`";
                        }
                    }
                } catch (Exception $e) {
                    return '*';
                }
                return empty($cols) ? '*' : implode(', ', $cols);
            };

            foreach (self::$targetTables as $table) {
                // Fetch rows from current table iteration with spatial conversion
                $selectCols = $safe_select($table);
                $stmt = $db->query("SELECT {$selectCols} FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $exportData['tables'][$table] = $rows ? $rows : [];
            }

            $jsonString = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($jsonString === false) {
                $jsonString = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }

            return $jsonString;

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

        if (!$data || !isset($data['tables']) || !is_array($data['tables'])) {
            return ['success' => false, 'error' => "Invalid transfer schema format. Missing active 'tables' block pointer."];
        }

        try {
            $db = App::getInstance()->db;

            // 1. DEACTIVATE CONSTRAINTS TO ALLOW OUT-OF-ORDER SEEDING
            $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // 2. BEGIN ATOMIC ISOLATION TRANSACTION BLOCK
            $db->beginTransaction();

            foreach (self::$targetTables as $table) {
                // Check if table exists in DB
                $tableCheck = $db->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$table}' LIMIT 1");
                if ($tableCheck->fetchColumn() === false) {
                    $log[] = "Table [{$table}] does not exist in database. Skipping.";
                    continue;
                }

                if (!isset($data['tables'][$table]) || empty($data['tables'][$table])) {
                    $log[] = "Table [{$table}] cleared, no backup dataset rows to ingest.";
                    $db->exec("DELETE FROM `{$table}`;");
                    continue;
                }

                $rows = $data['tables'][$table];

                // Detect spatial columns
                $spatialColumns = [];
                try {
                    $colStmt = $db->query("
                        SELECT COLUMN_NAME, LOWER(DATA_TYPE) as data_type
                        FROM information_schema.COLUMNS
                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'
                    ");
                    while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                        if (in_array($col['data_type'], ['point','geometry','linestring','polygon','multipoint','multilinestring','multipolygon','geometrycollection'])) {
                            $spatialColumns[] = $col['COLUMN_NAME'];
                        }
                    }
                } catch (Exception $e) {
                    $spatialColumns = [];
                }

                // Clear existing table content to prevent UNIQUE key/PKey constraint collisions
                $db->exec("DELETE FROM `{$table}`;");

                // Grab array columns dynamically from the first payload chunk element
                $sampleRow = $rows[0];
                $columns = array_keys($sampleRow);

                // Construct statement strings mapping query variables safely
                $columnList = '`' . implode('`, `', $columns) . '`';
                $placeholders = [];
                foreach ($columns as $col) {
                    if (in_array($col, $spatialColumns)) {
                        $placeholders[] = "ST_PointFromText(:{$col})";
                    } else {
                        $placeholders[] = ":{$col}";
                    }
                }
                $placeholderList = implode(', ', $placeholders);

                $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholderList})";
                $stmt = $db->prepare($sql);

                $rowCount = 0;
                foreach ($rows as $row) {
                    $bindArray = [];
                    foreach ($row as $columnName => $value) {
                        if (in_array($columnName, $spatialColumns)) {
                            if (empty($value) || !is_string($value) || !preg_match('/^point\s*\(/i', trim($value))) {
                                $bindArray[':' . $columnName] = null;
                            } else {
                                $bindArray[':' . $columnName] = trim($value);
                            }
                        } elseif (is_array($value) || is_object($value)) {
                            $bindArray[':' . $columnName] = json_encode($value, JSON_UNESCAPED_UNICODE);
                        } else {
                            $bindArray[':' . $columnName] = $value;
                        }
                    }
                    $stmt->execute($bindArray);
                    $rowCount++;
                }

                $log[] = "Successfully restored {$rowCount} record segments into [{$table}].";
            }

            // 3. SECURELY COMMIT TRANSFERS DOCKING CHUNKS TO PERMANENT DISK STORAGE
            $db->commit();
            
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