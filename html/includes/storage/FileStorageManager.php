<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Universal File Storage Manager
 * Supports multiple storage providers (Local, Google Cloud Storage)
 * Scalable for any app file storage needs
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
// Note: util.php functions are available when FileStorageManager is instantiated through app.php

require_once __DIR__ . '/StorageProviderInterface.php';
require_once __DIR__ . '/LocalStorageProvider.php';
require_once __DIR__ . '/GoogleCloudStorageProvider.php';

class FileStorageManager
{
    private $logger;
    // Helper to fetch secrets from Google Secret Manager
    public function getSecret($secretName, $version = 'latest')
    {
        if (!isCloudRun()) return null;
        try {
            $client = new Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient();
            $projectId = getenv('GOOGLE_CLOUD_PROJECT');
            $name = "projects/$projectId/secrets/$secretName/versions/$version";
            $request = new \Google\Cloud\SecretManager\V1\AccessSecretVersionRequest();
            $request->setName($name);
            $response = $client->accessSecretVersion($request);
            return $response->getPayload()->getData();
        } catch (Exception $e) {
            error_log('Secret Manager error: ' . $e->getMessage());
            return null;
        }
    }
    private $provider;
    private $config;
    private static $instance = null;
    private $jsonDataCache = [];

    // Storage types
    const STORAGE_LOCAL = 'local';
    const STORAGE_GOOGLE_CLOUD = 'google_cloud';

    // File categories for organization
    const CATEGORY_PROFILE_IMAGES = 'profile_images';
    const CATEGORY_APP_ASSETS = 'app_assets';
    const CATEGORY_USER_UPLOADS = 'user_uploads';
    const CATEGORY_DOCUMENTS = 'documents';
    const CATEGORY_BACKUPS = 'backups';
    const CATEGORY_SYSTEM_DATA = 'system_data';

    public function __construct($storageType = null, $config = [])
    {
        $this->config = $this->loadConfig();

        // Use provided storage type or fall back to configured default
        $storageType = $storageType ?? $this->config['default_provider'] ?? self::STORAGE_LOCAL;

        $this->initializeProvider($storageType, $config);

        // Initialize logger
        $this->initLogger();
    }

    /**
     * Initialize Monolog logger for storage operations
     */
    private function initLogger()
    {
        // Disable logging to prevent memory bloat in Cloud Run
        // Use error_log() directly if needed instead of Monolog
        $this->logger = null;
        return;
    }

    /**
     * Singleton pattern for global access
     */
    public static function getInstance($storageType = null, $config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($storageType, $config);
        }
        return self::$instance;
    }

    /**
     * Initialize storage provider
     */
    private function initializeProvider($storageType, $config = [])
    {
        $providerConfig = array_merge($this->config['providers'][$storageType] ?? [], $config);

        switch ($storageType) {
            case self::STORAGE_GOOGLE_CLOUD:
                $this->provider = new GoogleCloudStorageProvider($providerConfig);
                break;
            case self::STORAGE_LOCAL:
            default:
                $this->provider = new LocalStorageProvider($providerConfig);
                break;
        }
    }

    public function writeFile($file, $category, $filename = null, $options = [])
    {
        return $this->provider->uploadFile($file, $this->getCategoryPath($category) . '/' . $filename, $options);
    }

    public function readFile($category, $filename)
    {
        return $this->getFile($category, $filename);
    }

    /**
     * Upload file with automatic organization
     */
    public function uploadFile($file, $category, $filename = null, $options = [])
    {
        try {
            // Validate file
            $validation = $this->validateFile($file, $category);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }

            // Generate filename if not provided
            if (!$filename) {
                $extension = $this->getFileExtension($file);
                $filename = $this->generateFilename($category, $extension, $options);
            }

            // Add category path
            $fullPath = $this->getCategoryPath($category) . '/' . $filename;
error_log("Uploading file to: {$fullPath} (category: {$category}, filename: {$filename})");
error_log("File details: " . print_r($file, true));
error_log("Provider type: " . $this->provider->getProviderType());
            // Upload file
            $result = $this->provider->uploadFile($file, $fullPath, $options);

            if ($result['success']) {
                $result['category'] = $category;
                $result['filename'] = $filename;
                $result['fullPath'] = $fullPath;

                // Log upload
                $this->logFileOperation('upload', $fullPath, $result);
            }

            return $result;
        } catch (Exception $e) {
            error_log('FileStorageManager upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    /**
     * Download/retrieve file
     */
    public function getFile($category, $filename)
    {
        //error_log('Getting file: ' . $category . '/' . $filename);

        $fullPath = $this->getCategoryPath($category) . '/' . $filename;
        return $this->provider->getFile($fullPath);
    }

    /**
     * Get file URL
     */
    public function getFileUrl($category, $filename)
    {
        $fullPath = $this->getCategoryPath($category) . '/' . $filename;
        return $this->provider->getFileUrl($fullPath);
    }

    /**
     * Delete file
     */
    public function deleteFile($category, $filename)
    {
        try {
            $fullPath = $this->getCategoryPath($category) . '/' . $filename;
            $result = $this->provider->deleteFile($fullPath);

            if ($result['success']) {
                $this->logFileOperation('delete', $fullPath, $result);
            }

            return $result;
        } catch (Exception $e) {
            error_log('FileStorageManager delete error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Delete failed'];
        }
    }

    /**
     * List files in category
     */
    public function listFiles($category, $limit = 100, $offset = 0)
    {
        $categoryPath = $this->getCategoryPath($category);
        return $this->provider->listFiles($categoryPath, $limit, $offset);
    }

    /**
     * Copy file from current provider to another provider
     */
    public function copyToProvider($category, $filename, $targetStorageType, $targetConfig = [])
    {
        try {
            // Get file from current provider
            $sourceFile = $this->getFile($category, $filename);
            if (!$sourceFile['success']) {
                return $sourceFile;
            }

            // Create target provider
            $targetProvider = $this->createProvider($targetStorageType, $targetConfig);
            $fullPath = $this->getCategoryPath($category) . '/' . $filename;

            // Upload to target provider
            $result = $targetProvider->uploadFileData($sourceFile['data'], $fullPath);

            if ($result['success']) {
                $this->logFileOperation('copy', $fullPath, [
                    'from' => $this->provider->getProviderType(),
                    'to' => $targetStorageType
                ]);
            }

            return $result;
        } catch (Exception $e) {
            error_log('FileStorageManager copy error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Copy failed'];
        }
    }

    /**
     * Migrate all files from current provider to another
     */
    public function migrateToProvider($targetStorageType, $targetConfig = [], $progressCallback = null)
    {
        try {
            $results = [
                'success' => true,
                'total' => 0,
                'migrated' => 0,
                'failed' => 0,
                'errors' => []
            ];

            // Get all categories
            $categories = [
                self::CATEGORY_PROFILE_IMAGES,
                self::CATEGORY_APP_ASSETS,
                self::CATEGORY_USER_UPLOADS,
                self::CATEGORY_DOCUMENTS,
                self::CATEGORY_BACKUPS,
                self::CATEGORY_SYSTEM_DATA
            ];

            foreach ($categories as $category) {
                $files = $this->listFiles($category, 1000);
                if (!$files['success']) continue;

                foreach ($files['files'] as $file) {
                    $results['total']++;

                    $copyResult = $this->copyToProvider($category, $file['name'], $targetStorageType, $targetConfig);

                    if ($copyResult['success']) {
                        $results['migrated']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to migrate {$category}/{$file['name']}: " . $copyResult['error'];
                    }

                    // Call progress callback if provided
                    if ($progressCallback) {
                        $progressCallback($results);
                    }
                }
            }

            return $results;
        } catch (Exception $e) {
            error_log('FileStorageManager migration error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Migration failed'];
        }
    }

    /**
     * Switch storage provider
     */
    public function switchProvider($storageType, $config = [])
    {
        $this->initializeProvider($storageType, $config);

        // Update config
        $this->config['default_provider'] = $storageType;
        $this->saveConfig();

        // Reset singleton instance to force all subsequent getInstance() calls to use new provider
        self::$instance = null;
        self::$instance = $this;

        return ['success' => true, 'provider' => $storageType];
    }

    /**
     * Get current provider info
     */
    public function getProviderInfo()
    {
        return [
            'type' => $this->provider->getProviderType(),
            'config' => $this->provider->getConfig(),
            'status' => $this->provider->getStatus()
        ];
    }

    /**
     * Get direct access to provider (for advanced operations)
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Store JSON data as a file
     */
    public function storeJsonData($category, $filename, $data)
    {
        try {
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT);
            $fullPath = $this->getCategoryPath($category) . '/' . $filename;
            // Use the provider's uploadFileData method directly
            $result = $this->provider->uploadFileData($jsonContent, $fullPath, [
                'content_type' => 'application/json'
            ]);

            if ($result['success']) {
                $result['category'] = $category;
                $result['filename'] = $filename;
                $result['fullPath'] = $fullPath;

                // Log upload
                $this->logFileOperation('store_json', $fullPath, ['size' => strlen($jsonContent)]);
            }

            return $result;
        } catch (Exception $e) {
            error_log('FileStorageManager JSON store error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to store JSON data: ' . $e->getMessage()];
        }
    }

    /**
     * Retrieve JSON data from a file
     */
    public function getJsonData($category, $filename)
    {
        $cacheKey = $category . '/' . $filename;
        if (array_key_exists($cacheKey, $this->jsonDataCache)) {
            return $this->jsonDataCache[$cacheKey];
        }
        try {
            $fileResult = $this->getFile($category, $filename);

            if (!$fileResult['success']) {
                $this->jsonDataCache[$cacheKey] = $fileResult;
                return $fileResult;
            }

            $data = json_decode($fileResult['data'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $result = ['success' => false, 'error' => 'Invalid JSON data'];
                $this->jsonDataCache[$cacheKey] = $result;
                return $result;
            }

            $result = ['success' => true, 'data' => $data];
            $this->jsonDataCache[$cacheKey] = $result;
            return $result;
        } catch (Exception $e) {
            error_log('FileStorageManager JSON get error: ' . $e->getMessage());
            $result = ['success' => false, 'error' => 'Failed to retrieve JSON data'];
            $this->jsonDataCache[$cacheKey] = $result;
            return $result;
        }
    }

    /**
     * Check if JSON data file exists
     */
    public function jsonDataExists($category, $filename)
    {
        $fullPath = $this->getCategoryPath($category) . '/' . $filename;
        return $this->provider->fileExists($fullPath);
    }

    /**
     * Create temporary file with content
     */
    private function createTempFile($content)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'fsm_');
        file_put_contents($tempFile, $content);
        return $tempFile;
    }

    /**
     * Validate file based on category rules
     */
    private function validateFile($file, $category)
    {
        $rules = $this->getCategoryRules($category);

        // Check file size
        if (isset($rules['max_size']) && $file['size'] > $rules['max_size']) {
            return ['valid' => false, 'error' => "File too large (max {$rules['max_size']} bytes)"];
        }

        // Check file type
        if (isset($rules['allowed_types']) && !in_array($file['type'], $rules['allowed_types'])) {
            return ['valid' => false, 'error' => 'Invalid file type'];
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error occurred'];
        }

        return ['valid' => true];
    }

    /**
     * Get category-specific validation rules
     */
    private function getCategoryRules($category)
    {
        $rules = [
            self::CATEGORY_PROFILE_IMAGES => [
                'max_size' => 512000, // 500KB
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
            ],
            self::CATEGORY_APP_ASSETS => [
                'max_size' => 2097152, // 2MB
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'text/css', 'application/javascript']
            ],
            self::CATEGORY_USER_UPLOADS => [
                'max_size' => 10485760, // 10MB
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain']
            ],
            self::CATEGORY_DOCUMENTS => [
                'max_size' => 52428800, // 50MB
                'allowed_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
            ],
            self::CATEGORY_BACKUPS => [
                'max_size' => 1073741824, // 1GB
                'allowed_types' => ['application/zip', 'application/gzip', 'application/x-tar']
            ],
            self::CATEGORY_SYSTEM_DATA => [
                'max_size' => 10485760, // 10MB
                'allowed_types' => ['application/json', 'text/plain']
            ],
            'default' => [
                'max_size' => 5242880, // 5MB
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4']
            ]
        ];

        return $rules[$category] ?? $rules['default']; // 2MB default
    }

    /**
     * Get category path for organization
     */
    private function getCategoryPath($category)
    {
        return $category;
    }

    /**
     * Generate unique filename
     */
    private function generateFilename($category, $extension, $options = [])
    {
        $prefix = $options['prefix'] ?? $category;
        $suffix = $options['suffix'] ?? time();
        return $prefix . '_' . $suffix . '.' . $extension;
    }

    /**
     * Get file extension
     */
    private function getFileExtension($file)
    {
        if (isset($file['name'])) {
            return pathinfo($file['name'], PATHINFO_EXTENSION);
        }

        // Map MIME types to extensions
        $mimeToExt = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt'
        ];

        return $mimeToExt[$file['type']] ?? 'bin';
    }

    /**
     * Create storage provider instance
     */
    private function createProvider($storageType, $config = [])
    {
        $providerConfig = array_merge($this->config['providers'][$storageType] ?? [], $config);

        switch ($storageType) {
            case self::STORAGE_GOOGLE_CLOUD:
                return new GoogleCloudStorageProvider($providerConfig);
            case self::STORAGE_LOCAL:
            default:
                return new LocalStorageProvider($providerConfig);
        }
    }

    /**
     * Load storage configuration
     * Note: Storage config must remain accessible without storage system (bootstrap problem)
     */
    private function loadConfig()
    {
        /*
        if (isCloudRun()) {
            $projectId = getenv('GOOGLE_CLOUD_PROJECT');
            $bucketPrefix = getenv('GCS_BUCKET_PREFIX') ?: 'mediabrain-';
            $defaultLocation = getenv('GCS_DEFAULT_LOCATION') ?: 'US';

            // Optionally fetch service account key from Secret Manager if needed
            // $keyFileJson = $this->getSecret('storage-sa-key');
            // if ($keyFileJson) file_put_contents('/tmp/storage-sa-key.json', $keyFileJson);

            return [
                'default_provider' => self::STORAGE_GOOGLE_CLOUD,
                'providers' => [
                    self::STORAGE_GOOGLE_CLOUD => [
                        'project_id' => $projectId,
                        // 'key_file' => '/tmp/storage-sa-key.json', // Uncomment if needed
                        'bucket_prefix' => $bucketPrefix,
                        'bucket_name' => 'mediabrain-system-data',
                        'default_location' => $defaultLocation
                    ]
                ]
            ];
        }

        // Local/Docker: Try to load from file system, with fallback
        $configFile = __DIR__ . '/../../../config/storage_config.json';
        if (file_exists($configFile)) {
            $fileConfig = json_decode(file_get_contents($configFile), true);
            if ($fileConfig) {
                return $fileConfig;
            }
        }
        */
        // Default configuration for local development
        $config = [
            'default_provider' => self::STORAGE_LOCAL,
            'providers' => [
                self::STORAGE_LOCAL => [
                    'base_path' => '/var/www/cloudeats.app.local/storage',
                    'public_url_base' => '/api/file.php?f='
                ],
                self::STORAGE_GOOGLE_CLOUD => [
                    'project_id' => 'mediabrain-app',
                    'key_file' => '/tmp/storage-sa-key.json',
                    'bucket_prefix' => 'mediabrain-',
                    'bucket_name' => 'cloudeats-system-data',
                    'default_location' => 'US'
                ]
            ]
        ];
        return $config;
    }

    /**
     * Save storage configuration
     * Note: In Cloud Run, storage config should use environment variables instead
     */
    private function saveConfig()
    {

        // Local/Docker: Save to file system
        $configFile = __DIR__ . '/../../../config/storage_config.json';
        $configDir = dirname($configFile);

        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }

        file_put_contents($configFile, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    /**
     * Log file operations for auditing
     */
    private function logFileOperation($operation, $path, $details = [])
    {
        $logEntry = [
            'timestamp' => date('c'),
            'operation' => $operation,
            'path' => $path,
            'provider' => $this->provider->getProviderType(),
            'details' => $details
        ];
        if ($this->logger) {
            $this->logger->info(json_encode($logEntry));
        }
    }
}
