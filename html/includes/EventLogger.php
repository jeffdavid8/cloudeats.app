<?php

class EventLogger {
    private static $instance = null;
    private $enabled = false;
    private $logFile = null;
    private $configFile = null;
    
    private function __construct() {
        try {
            // In Cloud Run, use /tmp for writable logs; locally use /var/www path
            $logDir = getenv('LOG_DIR') ?: '/tmp/mediabrain-logs';
            $this->logFile = $logDir . '/event.log';
            $this->configFile = $logDir . '/event_config.json';
            
            // Get or create session ID
            $sessionId = session_id();
            if (empty($sessionId)) {
                // Start session if not already started
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $sessionId = session_id();
            }
            
            // Always try to create logs directory
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            // Test if we can write to the directory (non-fatal if we can't)
            if (!is_writable($logDir)) {
                // In Cloud Run, /tmp should be writable; if not, we'll still use error_log as fallback
                error_log("EventLogger: Warning - log directory not writable: $logDir");
            }
            
            // Always enable logging - we'll use error_log as fallback
            $this->enabled = true;
            
            // Clear log file for this session if writable
            if (is_writable($logDir) && !isset($_SESSION['event_log_initialized'])) {
                @file_put_contents($this->logFile, '');
                $_SESSION['event_log_initialized'] = true;
            }
            
            // Load configuration if available
            if (is_writable($logDir)) {
                $this->loadConfig();
            }
        } catch (Exception $e) {
            // Even if file operations fail, we still write to error_log
            $this->enabled = true;
            error_log("EventLogger: Initialization warning - " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new EventLogger();
        }
        return self::$instance;
    }
    
    /**
     * Reset the singleton instance (for debugging/fixing path issues)
     */
    public static function resetInstance() {
        self::$instance = null;
        return self::getInstance();
    }
    
    /**
     * Clean up old session log files to prevent disk accumulation
     */
    private function cleanupOldSessionLogs() {
        try {
            $logDir = dirname($this->logFile);
            $files = glob($logDir . '/event_*.log');
            $now = time();
            $maxAge = 24 * 60 * 60; // 24 hours
            
            foreach ($files as $file) {
                if (file_exists($file) && (filemtime($file) + $maxAge) < $now) {
                    @unlink($file);
                }
            }
        } catch (Exception $e) {
            // Cleanup failure shouldn't break logging
        }
    }
    
    /**
     * Log an event
     * 
     * @param string $level Event level (INFO, WARNING, ERROR, DEBUG)
     * @param string $event Event name/type
     * @param string $message Event message
     * @param array $context Additional context data
     */
    public function log($level, $event, $message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $session_id = session_id() ?: 'no-session';
        $user = $this->getCurrentUser();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logData = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'event' => $event,
            'message' => $message,
            'user' => $user,
            'session_id' => $session_id,
            'ip' => $ip,
            'user_agent' => $user_agent,
            'request_uri' => $request_uri,
            'context' => $context
        ];
        
        $logLine = json_encode($logData) . "\n";
        
        // Always write to error log (captured by Cloud Run as stderr)
        error_log($logLine);
        
        // Also write to file if enabled and writable
        if ($this->enabled && $this->logFile) {
            try {
                $this->rotateLogIfNeeded();
                @file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
            } catch (Exception $e) {
                // If file writing fails, we already logged to error_log above
            }
        }
    }
    
    /**
     * Rotate log file if it exceeds size limit
     */
    private function rotateLogIfNeeded() {
        if (!file_exists($this->logFile)) {
            return;
        }
        
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (filesize($this->logFile) > $maxSize) {
            $rotatedFile = $this->logFile . '.' . date('Y-m-d-H-i-s');
            
            // Create a compressed backup of the old log
            if (function_exists('gzopen')) {
                try {
                    $source = fopen($this->logFile, 'r');
                    $dest = gzopen($rotatedFile . '.gz', 'wb');
                    
                    if ($source && $dest) {
                        while (!feof($source)) {
                            gzwrite($dest, fread($source, 8192));
                        }
                        fclose($source);
                        gzclose($dest);
                        
                        // Clear the current log
                        file_put_contents($this->logFile, '');
                        
                        // Log the rotation
                        $this->log('INFO', 'LOG_ROTATED', "Event log rotated to: $rotatedFile.gz");
                    }
                } catch (Exception $e) {
                    // If rotation fails, just truncate the log
                    file_put_contents($this->logFile, '');
                }
            } else {
                // Simple rotation without compression
                rename($this->logFile, $rotatedFile);
                touch($this->logFile);
                $this->log('INFO', 'LOG_ROTATED', "Event log rotated to: $rotatedFile");
            }
        }
    }
    
    /**
     * Log info level event
     */
    public function info($event, $message, $context = []) {
        $this->log('INFO', $event, $message, $context);
    }
    
    /**
     * Log warning level event
     */
    public function warning($event, $message, $context = []) {
        $this->log('WARNING', $event, $message, $context);
    }
    
    /**
     * Log error level event
     */
    public function error($event, $message, $context = []) {
        $this->log('ERROR', $event, $message, $context);
    }
    
    /**
     * Log debug level event
     */
    public function debug($event, $message, $context = []) {
        $this->log('DEBUG', $event, $message, $context);
    }
    
    /**
     * Enable event logging
     */
    public function enable() {
        $this->enabled = true;
        $this->saveConfig();
        $this->log('INFO', 'LOGGING_ENABLED', 'Event logging has been enabled');
    }
    
    /**
     * Disable event logging
     */
    public function disable() {
        $this->log('INFO', 'LOGGING_DISABLED', 'Event logging has been disabled');
        $this->enabled = false;
        $this->saveConfig();
    }
    
    /**
     * Check if logging is enabled
     */
    public function isEnabled() {
        return $this->enabled;
    }
    
    /**
     * Get log file path
     */
    public function getLogFile() {
        return $this->logFile;
    }
    
    /**
     * Get recent log entries
     * 
     * @param int $lines Number of lines to retrieve
     * @return array Array of log entries
     */
    public function getRecentEntries($lines = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        try {
            // Limit lines to prevent memory issues
            $lines = min($lines, 1000);
            
            // Check file size and use appropriate method
            $fileSize = filesize($this->logFile);
            
            // If file is small (< 1MB), use simple method
            if ($fileSize < 1048576) {
                $logLines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($logLines === false) {
                    return [];
                }
                $logLines = array_slice($logLines, -$lines);
            } else {
                // Use system tail command if available (faster for large files)
                if (function_exists('exec') && !stristr(PHP_OS, 'WIN')) {
                    $escapedFile = escapeshellarg($this->logFile);
                    $escapedLines = escapeshellarg($lines);
                    $output = [];
                    exec("tail -n $escapedLines $escapedFile", $output);
                    $logLines = $output;
                } else {
                    // Fallback to PHP implementation
                    $logLines = $this->getLastLines($this->logFile, $lines);
                }
            }
            
            // Parse JSON entries
            $entries = [];
            foreach ($logLines as $line) {
                if (!empty(trim($line))) {
                    $entry = json_decode(trim($line), true);
                    if ($entry) {
                        // Ensure required fields exist
                        $entry['timestamp'] = $entry['timestamp'] ?? 'Unknown';
                        $entry['level'] = $entry['level'] ?? 'INFO';
                        $entry['event'] = $entry['event'] ?? 'Unknown';
                        $entry['message'] = $entry['message'] ?? '';
                        $entry['user'] = $entry['user'] ?? 'Unknown';
                        $entry['ip'] = $entry['ip'] ?? 'Unknown';
                        $entry['context'] = $entry['context'] ?? [];
                        
                        $entries[] = $entry;
                    }
                }
            }
            
            return array_reverse($entries); // Most recent first
        } catch (Exception $e) {
            error_log("EventLogger::getRecentEntries error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Efficiently read the last N lines from a file without loading the entire file
     * 
     * @param string $file Path to the file
     * @param int $lines Number of lines to read from the end
     * @return array Array of lines
     */
    private function getLastLines($file, $lines) {
        if (!file_exists($file)) {
            return [];
        }
        
        try {
            $file = new SplFileObject($file, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();
            
            $startLine = max(0, $totalLines - $lines);
            $file->seek($startLine);
            
            $result = [];
            while (!$file->eof() && count($result) < $lines) {
                $line = $file->current();
                if (!empty(trim($line))) {
                    $result[] = trim($line);
                }
                $file->next();
            }
            
            return $result;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Clear the event log
     */
    public function clearLog() {
        if (file_exists($this->logFile)) {
            @file_put_contents($this->logFile, '');
        }
        $this->log('INFO', 'LOG_CLEARED', 'Event log has been cleared');
    }
    
    /**
     * Get current user for logging
     */
    private function getCurrentUser() {
        // Get current user from unified session
        if (isset($_SESSION['user'])) {
            $user = $_SESSION['user'];
            if (is_array($user)) {
                $username = $user['username'] ?? 'unknown';
                $isAdmin = AuthManager::isAdmin();
                return $username . ($isAdmin ? ' (admin)' : '');
            } else {
                // Backward compatibility for string format
                return $user . (AuthManager::isAdmin() ? ' (admin)' : '');
            }
        }
        return 'anonymous';
    }
    
    /**
     * Load configuration from file
     */
    private function loadConfig() {
        try {
            if (file_exists($this->configFile)) {
                $config = json_decode(file_get_contents($this->configFile), true);
                if ($config && isset($config['enabled'])) {
                    $this->enabled = (bool)$config['enabled'];
                }
            }
        } catch (Exception $e) {
            // If config loading fails, use default (enabled = true)
        }
    }
    
    /**
     * Save configuration to file
     */
    private function saveConfig() {
        $config = [
            'enabled' => $this->enabled,
            'updated' => date('Y-m-d H:i:s')
        ];
        @file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
}