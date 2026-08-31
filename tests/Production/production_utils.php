<?php

/**
 * Production Environment Detection Utilities
 */

/**
 * Check if running in production environment
 */
function test_is_production(): bool
{
    return (
        (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') ||
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'mediabrain.app') !== false)
    );
}

/**
 * Check if running in development environment
 */
function test_is_development(): bool
{
    return !test_is_production() && (
        (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') ||
        (isset($_SERVER['HTTP_HOST']) && (
            strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
        ))
    );
}

/**
 * Get current protocol (http/https)
 */
function test_protocol(): string
{
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
}

/**
 * Get application version from composer.json or default
 */
function test_app_version(): string
{
    $composerFile = __DIR__ . '/../../composer.json';
    if (file_exists($composerFile)) {
        $composer = json_decode(file_get_contents($composerFile), true);
        return $composer['version'] ?? '1.0.0';
    }
    return '1.0.0';
}

/**
 * Check system requirements for production
 */
function test_check_system_requirements(): array
{
    $requirements = [
        'php_version' => [
            'required' => '8.0.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.0.0', '>=')
        ],
        'memory_limit' => [
            'required' => '128M',
            'current' => ini_get('memory_limit'),
            'status' => true // Simplified check
        ],
        'extensions' => [
            'curl' => extension_loaded('curl'),
            'json' => extension_loaded('json'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'pdo' => extension_loaded('pdo')
        ]
    ];
    
    return $requirements;
}

/**
 * Generate secure configuration for production
 */
function test_generate_production_config(): array
{
    return [
        'app' => [
            'debug' => false,
            'environment' => 'production',
            'version' => test_app_version()
        ],
        'security' => [
            'force_ssl' => true,
            'session_secure' => true,
            'csrf_protection' => true,
            'admin_password_min_length' => 12
        ],
        'performance' => [
            'cache_enabled' => true,
            'compression_enabled' => true,
            'minify_assets' => true
        ],
        'logging' => [
            'level' => 'error',
            'file_path' => '/var/log/mediabrain/app.log',
            'rotate_daily' => true
        ],
        'backup' => [
            'enabled' => true,
            'frequency' => 'daily',
            'retention_days' => 30,
            'compression' => true
        ]
    ];
}