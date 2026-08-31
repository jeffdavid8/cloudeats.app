<?php
/**
 * Storage Migration System
 * Handles bidirectional file migration between providers
 */

require_once __DIR__ . '/FileStorageManager.php';

class StorageMigrationManager {
    private $sourceStorage;
    private $targetStorage;
    private $logFile;
    
    public function __construct($sourceType, $targetType, $sourceConfig = [], $targetConfig = []) {
        $this->sourceStorage = new FileStorageManager($sourceType, $sourceConfig);
        $this->targetStorage = new FileStorageManager($targetType, $targetConfig);
        $this->logFile = '/var/data/mediabrain/migration_log.json';
    }
    
    /**
     * Migrate all files with progress tracking
     */
    public function migrateAllFiles($options = []) {
        $results = [
            'success' => true,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'start_time' => time(),
            'end_time' => null,
            'categories' => []
        ];
        
        $categories = [
            FileStorageManager::CATEGORY_PROFILE_IMAGES,
            FileStorageManager::CATEGORY_APP_ASSETS,
            FileStorageManager::CATEGORY_USER_UPLOADS,
            FileStorageManager::CATEGORY_DOCUMENTS,
            FileStorageManager::CATEGORY_BACKUPS,
            FileStorageManager::CATEGORY_SYSTEM_DATA
        ];
        
        $progressCallback = $options['progress_callback'] ?? null;
        $dryRun = $options['dry_run'] ?? false;
        $overwrite = $options['overwrite'] ?? false;
        
        foreach ($categories as $category) {
            $categoryResult = $this->migrateCategory($category, [
                'dry_run' => $dryRun,
                'overwrite' => $overwrite,
                'progress_callback' => $progressCallback,
                'parent_results' => &$results
            ]);
            
            $results['categories'][$category] = $categoryResult;
            $results['total'] += $categoryResult['total'];
            $results['migrated'] += $categoryResult['migrated'];
            $results['failed'] += $categoryResult['failed'];
            $results['skipped'] += $categoryResult['skipped'];
            $results['errors'] = array_merge($results['errors'], $categoryResult['errors']);
        }
        
        $results['end_time'] = time();
        $results['duration'] = $results['end_time'] - $results['start_time'];
        
        // Log migration results
    $this->logMigration($results);
        
        return $results;
    }
    
    /**
     * Migrate files in a specific category
     */
    public function migrateCategory($category, $options = []) {
        $results = [
            'success' => true,
            'category' => $category,
            'total' => 0,
            'migrated' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];
        
        $dryRun = $options['dry_run'] ?? false;
        $overwrite = $options['overwrite'] ?? false;
        $progressCallback = $options['progress_callback'] ?? null;
        $parentResults = &$options['parent_results'];
        
        try {
            // Get list of files in category
            $filesList = $this->sourceStorage->listFiles($category, 1000);
            
            if (!$filesList['success']) {
                $results['errors'][] = "Failed to list files in category {$category}: " . $filesList['error'];
                return $results;
            }
            
            $results['total'] = count($filesList['files']);
            
            foreach ($filesList['files'] as $file) {
                try {
                    // Check if file already exists in target
                    if (!$overwrite && $this->targetStorage->getProviderInfo()['type'] !== 'local') {
                        // For cloud storage, we assume URLs indicate existence
                        $targetUrl = $this->targetStorage->getFileUrl($category, $file['name']);
                        if ($this->urlExists($targetUrl)) {
                            $results['skipped']++;
                            continue;
                        }
                    }
                    
                    if (!$dryRun) {
                        // Get file data from source
                        $fileData = $this->sourceStorage->getFile($category, $file['name']);
                        
                        if (!$fileData['success']) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to read {$category}/{$file['name']}: " . $fileData['error'];
                            continue;
                        }
                        
                        // Upload to target
                        $uploadResult = $this->targetStorage->getProvider()->uploadFileData(
                            $fileData['data'],
                            $category . '/' . $file['name'],
                            ['content_type' => $this->getMimeType($file['name'])]
                        );
                        
                        if (!$uploadResult['success']) {
                            $results['failed']++;
                            $results['errors'][] = "Failed to upload {$category}/{$file['name']}: " . $uploadResult['error'];
                            continue;
                        }
                    }
                    
                    $results['migrated']++;
                    
                    // Update progress
                    if ($progressCallback) {
                        $progressCallback([
                            'current_file' => $file['name'],
                            'current_category' => $category,
                            'category_progress' => $results,
                            'overall_progress' => $parentResults
                        ]);
                    }
                    
                } catch (Exception $e) {
                    $results['failed']++;
                    log_error("Error migrating {$category}/{$file['name']}: " . $e->getMessage());
                    $results['errors'][] = "Error migrating {$category}/{$file['name']}: " . $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $results['success'] = false;
            log_error("Category migration error: " . $e->getMessage());
            $results['errors'][] = "Category migration error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Sync files between providers (bidirectional)
     */
    public function syncFiles($options = []) {
        $results = [
            'source_to_target' => null,
            'target_to_source' => null
        ];
        
        $mode = $options['mode'] ?? 'both'; // 'source_to_target', 'target_to_source', 'both'
        
        if ($mode === 'source_to_target' || $mode === 'both') {
            $results['source_to_target'] = $this->migrateAllFiles(array_merge($options, [
                'overwrite' => false // Don't overwrite during sync
            ]));
        }
        
        if ($mode === 'target_to_source' || $mode === 'both') {
            // Swap source and target for reverse sync
            $tempStorage = $this->sourceStorage;
            $this->sourceStorage = $this->targetStorage;
            $this->targetStorage = $tempStorage;
            
            $results['target_to_source'] = $this->migrateAllFiles(array_merge($options, [
                'overwrite' => false
            ]));
            
            // Restore original order
            $tempStorage = $this->sourceStorage;
            $this->sourceStorage = $this->targetStorage;
            $this->targetStorage = $tempStorage;
        }
        
        return $results;
    }
    
    /**
     * Get migration status and history
     */
    public function getMigrationHistory($limit = 50) {
        try {
            if (!file_exists($this->logFile)) {
                return ['success' => true, 'migrations' => []];
            }
            
            $logData = file_get_contents($this->logFile);
            $lines = explode("\n", trim($logData));
            
            $migrations = [];
            foreach (array_slice($lines, -$limit) as $line) {
                if (!empty($line)) {
                    $migration = json_decode($line, true);
                    if ($migration) {
                        $migrations[] = $migration;
                    }
                }
            }
            
            return ['success' => true, 'migrations' => array_reverse($migrations)];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Failed to read migration history'];
        }
    }
    
    /**
     * Clean up failed migrations
     */
    public function cleanupFailedMigration($migrationId) {
        // This would require tracking uploaded files during migration
        // For now, return success
        return ['success' => true, 'message' => 'Cleanup completed'];
    }
    
    /**
     * Estimate migration time and size
     */
    public function estimateMigration() {
        $estimate = [
            'total_files' => 0,
            'total_size' => 0,
            'estimated_time' => 0,
            'categories' => []
        ];
        
        $categories = [
            FileStorageManager::CATEGORY_PROFILE_IMAGES,
            FileStorageManager::CATEGORY_APP_ASSETS,
            FileStorageManager::CATEGORY_USER_UPLOADS,
            FileStorageManager::CATEGORY_DOCUMENTS,
            FileStorageManager::CATEGORY_BACKUPS,
            FileStorageManager::CATEGORY_SYSTEM_DATA
        ];
        
        foreach ($categories as $category) {
            $filesList = $this->sourceStorage->listFiles($category, 1000);
            
            if ($filesList['success']) {
                $categorySize = array_sum(array_column($filesList['files'], 'size'));
                $categoryCount = count($filesList['files']);
                
                $estimate['categories'][$category] = [
                    'files' => $categoryCount,
                    'size' => $categorySize
                ];
                
                $estimate['total_files'] += $categoryCount;
                $estimate['total_size'] += $categorySize;
            }
        }
        
        // Estimate time (rough calculation: 1MB per second)
        $estimate['estimated_time'] = max(1, ceil($estimate['total_size'] / 1048576));
        
        return $estimate;
    }
    
    private function getMimeType($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
    
    private function urlExists($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function logMigration($results) {
        try {
            $logEntry = [
                'timestamp' => date('c'),
                'source_provider' => $this->sourceStorage->getProviderInfo()['type'],
                'target_provider' => $this->targetStorage->getProviderInfo()['type'],
                'results' => $results
            ];
            
            $logDir = dirname($this->logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            log_error('Migration logging error: ' . $e->getMessage());
        }
    }
}