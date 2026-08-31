<?php
/**
 * Input Validation and Sanitization Framework
 * Centralized validation for MediaBrain application
 */

class InputValidator {
    
    const MAX_STRING_LENGTH = 255;
    const MAX_TEXT_LENGTH = 10000;
    const MAX_EMAIL_LENGTH = 254;
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $maxLength = self::MAX_STRING_LENGTH) {
        if (!is_string($input)) {
            return '';
        }
        
        // Remove null bytes and trim
        $input = str_replace("\0", '', trim($input));
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Sanitize text input (allows newlines)
     */
    public static function sanitizeText($input, $maxLength = self::MAX_TEXT_LENGTH) {
        if (!is_string($input)) {
            return '';
        }
        
        // Remove null bytes but preserve newlines
        $input = str_replace("\0", '', trim($input));
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Validate and sanitize email
     */
    public static function validateEmail($email) {
        $email = self::sanitizeString($email, self::MAX_EMAIL_LENGTH);
        
        if (empty($email)) {
            return ['valid' => false, 'error' => 'Email is required', 'value' => ''];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Invalid email format', 'value' => $email];
        }
        
        return ['valid' => true, 'value' => $email];
    }
    
    /**
     * Validate username
     */
    public static function validateUsername($username, $minLength = 3, $maxLength = 50) {
        $username = self::sanitizeString($username, $maxLength);
        
        if (empty($username)) {
            return ['valid' => false, 'error' => 'Username is required', 'value' => ''];
        }
        
        if (strlen($username) < $minLength) {
            return ['valid' => false, 'error' => "Username must be at least {$minLength} characters", 'value' => $username];
        }
        
        // Allow alphanumeric, underscore, hyphen, and dot
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            return ['valid' => false, 'error' => 'Username can only contain letters, numbers, dots, underscores, and hyphens', 'value' => $username];
        }
        
        return ['valid' => true, 'value' => $username];
    }
    
    /**
     * Validate password
     */
    public static function validatePassword($password, $minLength = 8) {
        if (!is_string($password)) {
            return ['valid' => false, 'error' => 'Password must be a string', 'value' => ''];
        }
        
        if (empty($password)) {
            return ['valid' => false, 'error' => 'Password is required', 'value' => ''];
        }
        
        if (strlen($password) < $minLength) {
            return ['valid' => false, 'error' => "Password must be at least {$minLength} characters", 'value' => ''];
        }
        
        // Check for common weak patterns
        $weakPatterns = ['password', '123456', 'admin', 'qwerty'];
        foreach ($weakPatterns as $pattern) {
            if (stripos($password, $pattern) !== false) {
                return ['valid' => false, 'error' => 'Password is too weak', 'value' => ''];
            }
        }
        
        return ['valid' => true, 'value' => $password];
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($input, $min = null, $max = null) {
        $value = filter_var($input, FILTER_VALIDATE_INT);
        
        if ($value === false) {
            return ['valid' => false, 'error' => 'Invalid integer', 'value' => null];
        }
        
        if ($min !== null && $value < $min) {
            return ['valid' => false, 'error' => "Value must be at least {$min}", 'value' => $value];
        }
        
        if ($max !== null && $value > $max) {
            return ['valid' => false, 'error' => "Value must be at most {$max}", 'value' => $value];
        }
        
        return ['valid' => true, 'value' => $value];
    }
    
    /**
     * Validate URL
     */
    public static function validateUrl($url, $allowedSchemes = ['http', 'https']) {
        $url = self::sanitizeString($url, 2048); // URLs can be longer
        
        if (empty($url)) {
            return ['valid' => false, 'error' => 'URL is required', 'value' => ''];
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format', 'value' => $url];
        }
        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, $allowedSchemes)) {
            return ['valid' => false, 'error' => 'Invalid URL scheme. Allowed: ' . implode(', ', $allowedSchemes), 'value' => $url];
        }
        
        return ['valid' => true, 'value' => $url];
    }
    
    /**
     * Validate JSON
     */
    public static function validateJson($input, $maxLength = 100000) {
        if (!is_string($input)) {
            return ['valid' => false, 'error' => 'JSON must be a string', 'value' => null];
        }
        
        if (strlen($input) > $maxLength) {
            return ['valid' => false, 'error' => "JSON too large (max {$maxLength} bytes)", 'value' => null];
        }
        
        json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg(), 'value' => null];
        }
        
        return ['valid' => true, 'value' => json_decode($input, true)];
    }
    
    /**
     * Sanitize HTML output
     */
    public static function escapeHtml($input, $encoding = 'UTF-8') {
        return htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, $encoding);
    }
    
    /**
     * Sanitize for JavaScript output
     */
    public static function escapeJs($input) {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $options = []) {
        $maxSize = $options['max_size'] ?? 5242880; // 5MB default
        $allowedTypes = $options['allowed_types'] ?? [];
        $allowedExtensions = $options['allowed_extensions'] ?? [];
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File upload incomplete',
                UPLOAD_ERR_NO_FILE => 'No file uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error',
                UPLOAD_ERR_CANT_WRITE => 'Server write error',
                UPLOAD_ERR_EXTENSION => 'File upload blocked by extension'
            ];
            
            $error = $errorMessages[$file['error']] ?? 'Unknown upload error';
            return ['valid' => false, 'error' => $error];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            return ['valid' => false, 'error' => "File too large (max {$maxSizeMB}MB)"];
        }
        
        // Check MIME type
        if (!empty($allowedTypes) && !in_array($file['type'], $allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
        }
        
        // Check file extension
        if (!empty($allowedExtensions)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions)) {
                return ['valid' => false, 'error' => 'Invalid file extension. Allowed: ' . implode(', ', $allowedExtensions)];
            }
        }
        
        return ['valid' => true, 'value' => $file];
    }
    
    /**
     * Validate array of inputs
     */
    public static function validateArray($inputs, $rules) {
        $results = [];
        $allValid = true;
        
        foreach ($rules as $field => $rule) {
            $value = $inputs[$field] ?? null;
            $method = $rule['method'] ?? 'sanitizeString';
            $params = $rule['params'] ?? [];
            
            if (method_exists(self::class, $method)) {
                $result = call_user_func_array([self::class, $method], array_merge([$value], $params));
                
                if (is_array($result) && isset($result['valid'])) {
                    $results[$field] = $result;
                    if (!$result['valid']) {
                        $allValid = false;
                    }
                } else {
                    $results[$field] = ['valid' => true, 'value' => $result];
                }
            } else {
                $results[$field] = ['valid' => false, 'error' => 'Invalid validation method: ' . $method, 'value' => $value];
                $allValid = false;
            }
        }
        
        return ['valid' => $allValid, 'results' => $results];
    }
}