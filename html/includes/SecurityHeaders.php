<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Security Headers Manager
 * Implements comprehensive HTTP security headers for MediaBrain
 */

class SecurityHeaders
{

    private static $cspDirectives = [
        'default-src' => "'self'",
        
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://apis.google.com https://accounts.google.com https://www.youtube.com https://s.ytimg.com https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://www.paypalobjects.com blob:",
        'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com https://googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
        'font-src' => "'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net data:",
        'worker-src' => "'self' blob:",
        
        // 🖼️ UPDATED: Kept your rules, and validated data: blob: and openstreetmap are supported
        'img-src' => "'self' data: blob: https://i.ytimg.com https://storage.googleapis.com https://api.weather.gov https://radar.weather.gov https://*.googleusercontent.com https://*.openstreetmap.org https://images.unsplash.com https://plus.unsplash.com", 
        
        // 🔌 UPDATED: Appended the Nominatim geocoding engine to prevent fetch() breaks
        'connect-src' => "'self' https://www.youtube.com https://apis.google.com https://www.facebook.com https://api.weather.gov https://ipapi.co https://www.google-analytics.com https://stats.g.doubleclick.net https://nominatim.openstreetmap.org",
        
        'media-src' => "'self' data: blob: https://www.youtube.com https://*.googlevideo.com",
        'frame-src' => "'self' https://www.youtube.com https://youtube.com",
        'object-src' => "'none'",
        'base-uri' => "'self'",
        'form-action' => "'self' https://www.paypal.com https://www.sandbox.paypal.com",
        'frame-ancestors' => "'self'"
    ];

    /**
     * Set all security headers
     */
    public static function setHeaders($options = [])
    {
        if (headers_sent()) {
            return false; // Headers already sent
        }

        // Cache busting headers for development
        if (($options['development'] ?? false) || ($_ENV['APP_ENV'] ?? 'production') === 'development') {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('ETag: "' . md5(time()) . '"');
        }

        // Basic security headers
        self::setBasicHeaders($options);

        // Content Security Policy
        self::setCSPHeader($options);

        // Additional security headers
        self::setAdvancedHeaders($options);

        return true;
    }

    /**
     * Set basic security headers
     */
    private static function setBasicHeaders($options = [])
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Enable XSS filtering
        header('X-XSS-Protection: 1; mode=block');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Feature policy / Permissions policy
        $permissions = [
            'camera=()' => 'camera=()',
            'microphone=()' => 'microphone=()',
            'geolocation=(self)' => 'geolocation=(self)',
            'payment=()' => 'payment=()'
        ];
        header('Permissions-Policy: ' . implode(', ', $permissions));
    }

    /**
     * Set Content Security Policy header
     */
    private static function setCSPHeader($options = [])
    {
        $csp = self::$cspDirectives;

        // Allow custom CSP directives
        if (isset($options['csp']) && is_array($options['csp'])) {
            $csp = array_merge($csp, $options['csp']);
        }

        // Development mode adjustments
        if (isset($options['development']) && $options['development'] === true) {
            // Allow unsafe-eval for development tools
            $csp['script-src'] .= " 'unsafe-eval'";
        }

        $cspString = '';
        foreach ($csp as $directive => $value) {
            $cspString .= $directive . ' ' . $value . '; ';
        }

        header('Content-Security-Policy: ' . trim($cspString));
    }

    /**
     * Set HTTPS/SSL security headers
     */
    private static function setAdvancedHeaders($options = [])
    {
        // HTTPS enforcement (only if we're on HTTPS)
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if ($isHttps) {
            // Strict Transport Security - 1 year
            $hstsAge = $options['hsts_max_age'] ?? 31536000; // 1 year
            $hstsDirectives = 'max-age=' . $hstsAge;

            if ($options['hsts_include_subdomains'] ?? true) {
                $hstsDirectives .= '; includeSubDomains';
            }

            if ($options['hsts_preload'] ?? false) {
                $hstsDirectives .= '; preload';
            }

            header('Strict-Transport-Security: ' . $hstsDirectives);
        }

        // Expect-CT (Certificate Transparency)
        if ($isHttps && ($options['expect_ct'] ?? true)) {
            header('Expect-CT: max-age=86400, enforce');
        }
    }

    /**
     * Set headers for API endpoints
     */
    public static function setAPIHeaders($options = [])
    {
        // API-specific security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        // CORS headers if needed
        if (isset($options['cors']) && $options['cors'] === true) {
            $allowedOrigins = $options['allowed_origins'] ?? ["'self'"];
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            if (in_array($origin, $allowedOrigins) || in_array("'self'", $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
                header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }
    }

    /**
     * Remove potentially dangerous headers
     */
    public static function removeDangerousHeaders()
    {
        // Remove server signature
        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
            header_remove('Server');
        }
    }

    /**
     * Get current CSP directives
     */
    public static function getCSPDirectives()
    {
        return self::$cspDirectives;
    }

    /**
     * Update CSP directives
     */
    public static function updateCSPDirectives($directives)
    {
        self::$cspDirectives = array_merge(self::$cspDirectives, $directives);
    }

    /**
     * Generate CSP nonce for inline scripts/styles
     */
    public static function generateNonce()
    {
        if (!isset($_SESSION['csp_nonce'])) {
            $_SESSION['csp_nonce'] = base64_encode(random_bytes(16));
        }
        return $_SESSION['csp_nonce'];
    }

    /**
     * Check if headers have been set
     */
    public static function headersAlreadySent()
    {
        return headers_sent();
    }
}
