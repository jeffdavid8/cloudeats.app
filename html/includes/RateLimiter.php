<?php
/**
 * Rate Limiter for MediaBrain
 * Prevents brute force attacks and abuse
 */

class RateLimiter {
    
    private static $limits = [
        'login' => ['attempts' => 5, 'window' => 900], // 5 attempts per 15 minutes
        'api' => ['attempts' => 100, 'window' => 60],   // 100 requests per minute
        'upload' => ['attempts' => 10, 'window' => 3600], // 10 uploads per hour
        'password_reset' => ['attempts' => 3, 'window' => 3600] // 3 resets per hour
    ];
    
    private static $storage = null;
    
    /**
     * Initialize rate limiter storage
     */
    private static function initStorage() {
        if (self::$storage === null) {
            // Use session storage for simplicity, can be extended to Redis/Database
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            if (!isset($_SESSION['rate_limits'])) {
                $_SESSION['rate_limits'] = [];
            }
            
            self::$storage = &$_SESSION['rate_limits'];
        }
    }
    
    /**
     * Check if action is allowed for given identifier
     */
    public static function isAllowed($action, $identifier = null) {
        self::initStorage();
        
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        if (!isset(self::$limits[$action])) {
            return true; // No limit configured for this action
        }
        
        $limit = self::$limits[$action];
        $key = $action . '_' . md5($identifier);
        $now = time();
        
        // Clean up old entries
        if (isset(self::$storage[$key])) {
            self::$storage[$key] = array_filter(
                self::$storage[$key],
                function($timestamp) use ($now, $limit) {
                    return ($now - $timestamp) < $limit['window'];
                }
            );
        }
        
        // Check current count
        $currentCount = isset(self::$storage[$key]) ? count(self::$storage[$key]) : 0;
        
        if ($currentCount >= $limit['attempts']) {
            // Log rate limit violation
            if (class_exists('App')) {
                $app = App::getInstance();
                if ($app->getEventLogger()) {
                    $app->getEventLogger()->log('security', 'rate_limit_exceeded', [
                        'action' => $action,
                        'identifier' => $identifier,
                        'current_count' => $currentCount,
                        'limit' => $limit['attempts'],
                        'window' => $limit['window']
                    ]);
                }
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Record an attempt for the given action and identifier
     */
    public static function recordAttempt($action, $identifier = null) {
        self::initStorage();
        
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        $key = $action . '_' . md5($identifier);
        $now = time();
        
        if (!isset(self::$storage[$key])) {
            self::$storage[$key] = [];
        }
        
        self::$storage[$key][] = $now;
        
        // Log the attempt
        if (class_exists('App')) {
            $app = App::getInstance();
            if ($app->getEventLogger()) {
                $app->getEventLogger()->log('security', 'rate_limit_attempt', [
                    'action' => $action,
                    'identifier' => $identifier,
                    'attempt_count' => count(self::$storage[$key])
                ]);
            }
        }
    }
    
    /**
     * Get remaining attempts for action/identifier
     */
    public static function getRemainingAttempts($action, $identifier = null) {
        self::initStorage();
        
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        if (!isset(self::$limits[$action])) {
            return -1; // No limit
        }
        
        $limit = self::$limits[$action];
        $key = $action . '_' . md5($identifier);
        $now = time();
        
        // Clean up old entries
        if (isset(self::$storage[$key])) {
            self::$storage[$key] = array_filter(
                self::$storage[$key],
                function($timestamp) use ($now, $limit) {
                    return ($now - $timestamp) < $limit['window'];
                }
            );
        }
        
        $currentCount = isset(self::$storage[$key]) ? count(self::$storage[$key]) : 0;
        return max(0, $limit['attempts'] - $currentCount);
    }
    
    /**
     * Get time until next attempt is allowed
     */
    public static function getTimeUntilReset($action, $identifier = null) {
        self::initStorage();
        
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        if (!isset(self::$limits[$action])) {
            return 0; // No limit
        }
        
        $limit = self::$limits[$action];
        $key = $action . '_' . md5($identifier);
        $now = time();
        
        if (!isset(self::$storage[$key]) || empty(self::$storage[$key])) {
            return 0; // No attempts recorded
        }
        
        // Get oldest attempt in current window
        $oldestAttempt = min(self::$storage[$key]);
        $timeUntilReset = $limit['window'] - ($now - $oldestAttempt);
        
        return max(0, $timeUntilReset);
    }
    
    /**
     * Clear attempts for action/identifier
     */
    public static function clearAttempts($action, $identifier = null) {
        self::initStorage();
        
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        $key = $action . '_' . md5($identifier);
        unset(self::$storage[$key]);
    }
    
    /**
     * Get client identifier (IP + User Agent)
     */
    private static function getClientIdentifier() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Handle proxy headers
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwardedIps[0]);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $ip . '|' . substr(md5($userAgent), 0, 8);
    }
    
    /**
     * Configure limits for actions
     */
    public static function setLimit($action, $attempts, $windowSeconds) {
        self::$limits[$action] = [
            'attempts' => $attempts,
            'window' => $windowSeconds
        ];
    }
    
    /**
     * Get configured limits
     */
    public static function getLimits() {
        return self::$limits;
    }
    
    /**
     * Check rate limit with automatic recording
     */
    public static function checkAndRecord($action, $identifier = null) {
        $allowed = self::isAllowed($action, $identifier);
        
        if ($allowed) {
            self::recordAttempt($action, $identifier);
        }
        
        return $allowed;
    }
    
    /**
     * Get rate limit status for debugging
     */
    public static function getStatus($action, $identifier = null) {
        if ($identifier === null) {
            $identifier = self::getClientIdentifier();
        }
        
        return [
            'action' => $action,
            'identifier' => $identifier,
            'is_allowed' => self::isAllowed($action, $identifier),
            'remaining_attempts' => self::getRemainingAttempts($action, $identifier),
            'time_until_reset' => self::getTimeUntilReset($action, $identifier),
            'limit' => self::$limits[$action] ?? null
        ];
    }
}