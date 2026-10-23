<?php

if (!defined('MB_RUNNING')) {
    exit;
}

class MaintenanceMode
{
    private const SETTINGS_TABLE = 'app_system_settings';
    private const MODE_KEY = 'maintenance_mode';
    private const MESSAGE_KEY = 'maintenance_message';
    private const ETA_KEY = 'maintenance_eta';
    private const DEFAULT_MESSAGE = 'We are performing scheduled maintenance. Please check back soon.';

    public static function getEnvironmentOverride()
    {
        $value = getenv('MAINTENANCE_MODE');
        if ($value === false || trim($value) === '') {
            return null;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw new RuntimeException('MAINTENANCE_MODE must be set to a valid boolean value.');
        }

        return $parsed;
    }

    public static function getStatus(PDO $database)
    {
        $settings = self::getStoredSettings($database);
        $override = self::getEnvironmentOverride();
        $environmentMessage = getenv('MAINTENANCE_MESSAGE');
        $environmentEta = getenv('MAINTENANCE_ETA');

        return [
            'enabled' => $override ?? filter_var($settings[self::MODE_KEY] ?? '0', FILTER_VALIDATE_BOOLEAN),
            'message' => $environmentMessage !== false && trim($environmentMessage) !== ''
                ? trim($environmentMessage)
                : ($settings[self::MESSAGE_KEY] ?? self::DEFAULT_MESSAGE),
            'eta' => $environmentEta !== false && trim($environmentEta) !== ''
                ? trim($environmentEta)
                : ($settings[self::ETA_KEY] ?? ''),
            'environment_override' => $override !== null,
        ];
    }

    public static function getStatusFromEnvironment()
    {
        $message = getenv('MAINTENANCE_MESSAGE');
        $eta = getenv('MAINTENANCE_ETA');

        return [
            'enabled' => self::getEnvironmentOverride() === true,
            'message' => $message !== false && trim($message) !== '' ? trim($message) : self::DEFAULT_MESSAGE,
            'eta' => $eta !== false ? trim($eta) : '',
            'environment_override' => true,
        ];
    }

    public static function enforce($jsonResponse = false)
    {
        if (self::isAllowedRequest()) {
            return;
        }

        try {
            $override = self::getEnvironmentOverride();
            $status = $override !== null
                ? self::getStatusFromEnvironment()
                : self::getStatus(App::getInstance('/')->db);

            if ($status['enabled'] && !self::isAdminSession()) {
                self::sendResponse($status, $jsonResponse);
            }
        } catch (Throwable $e) {
            error_log('Maintenance mode check failed: ' . $e->getMessage());
            self::setResponseSecurityHeaders();
            http_response_code(503);
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            if ($jsonResponse || !empty($_GET['api'])) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'service_unavailable',
                    'message' => 'Service temporarily unavailable.',
                ]);
            } else {
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Service temporarily unavailable.';
            }
            exit;
        }
    }

    public static function saveSettings(PDO $database, $enabled, $message, $eta, $updatedBy)
    {
        $statement = $database->prepare(
            'INSERT INTO ' . self::SETTINGS_TABLE . ' (setting_key, setting_value, updated_by)
             VALUES (:setting_key, :setting_value, :updated_by)
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP'
        );

        foreach ([
            self::MODE_KEY => $enabled ? '1' : '0',
            self::MESSAGE_KEY => $message,
            self::ETA_KEY => $eta,
        ] as $key => $value) {
            $statement->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
                ':updated_by' => $updatedBy,
            ]);
        }
    }

    public static function isAllowedRequest()
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (preg_match('#^/oauth/[^/]+\\.php$#i', $path)) {
            return true;
        }

        if (($_GET['p'] ?? '') === 'login') {
            return true;
        }

        $api = $_GET['api'] ?? '';
        $action = $_GET['action'] ?? '';
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        return ($api === 'neighborhub' && $action === 'verify_registration' && $method === 'GET')
            || ($api === 'neighborhub' && $action === 'stripe_webhook' && $method === 'POST')
            || ($api === 'auth' && in_array($action, ['login', 'logout'], true));
    }

    public static function sendResponse(array $status, $jsonResponse = false)
    {
        self::setResponseSecurityHeaders();
        http_response_code(503);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Retry-After: 300');

        if ($jsonResponse || !empty($_GET['api'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'maintenance',
                'message' => $status['message'],
                'eta' => $status['eta'] !== '' ? $status['eta'] : null,
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        $message = htmlspecialchars($status['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eta = $status['eta'] !== ''
            ? '<p class="eta">Estimated return: ' . htmlspecialchars($status['eta'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            : '';

        echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Temporarily unavailable</title><style>'
            . 'body{font:16px/1.5 system-ui,sans-serif;margin:0;background:#f4f6f8;color:#1f2937}'
            . 'main{max-width:36rem;margin:12vh auto;padding:2rem;background:#fff;border-radius:.75rem;'
            . 'box-shadow:0 8px 32px #0001}h1{margin-top:0;color:#315b7d}.eta{color:#52606d}'
            . '</style></head><body><main><h1>We will be back soon</h1><p>' . $message . '</p>'
            . $eta . '</main></body></html>';
        exit;
    }

    private static function getStoredSettings(PDO $database)
    {
        $statement = $database->query(
            'SELECT setting_key, setting_value FROM ' . self::SETTINGS_TABLE
            . ' WHERE setting_key IN (' . $database->quote(self::MODE_KEY) . ', '
            . $database->quote(self::MESSAGE_KEY) . ', ' . $database->quote(self::ETA_KEY) . ')'
        );
        $settings = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }
    private static function isAdminSession()
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user)
            && (!empty($user['is_admin']) || ($user['role'] ?? '') === 'admin');
    }

    private static function setResponseSecurityHeaders()
    {
        if (class_exists('SecurityHeaders')) {
            SecurityHeaders::setHeaders([
                'development' => function_exists('is_development') && is_development(),
                'hsts_max_age' => function_exists('is_development') && is_development() ? 0 : 31536000,
                'hsts_include_subdomains' => true,
            ]);
        }
    }
}
