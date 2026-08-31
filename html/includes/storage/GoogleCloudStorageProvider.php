<?php

/**
 * Google Cloud Storage Provider
 * Handles file storage using Google Cloud Storage
 */

use Google\Cloud\Storage\StorageClient;

class GoogleCloudStorageProvider implements StorageProviderInterface
{
    private $config;
    private $storage;
    private $buckets = [];

    public function __construct($config = [])
    {
        $this->config = array_merge([
            'project_id' => 'mediabrain-app',
            'key_file' => '/var/www/cloudeats.app.local/secrets/service-account-key.json',
            'bucket_name' => 'cloudeats-system-data',
            'bucket_prefix' => 'mediabrain-',
            'default_location' => 'US',
            'storage_class' => 'STANDARD',
            'lifecycle_days' => 365
        ], $config);

        $this->initializeStorage();
    }

    public function uploadFile($file, $path, $options = [])
    {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'error' => 'Invalid upload file'];
            }

            // Process image if needed
            if (isset($options['process_image']) && $options['process_image']) {
                $processedData = $this->processImage($file['tmp_name'], $options);
                if (!$processedData['success']) {
                    error_log("Image processing failed: " . $processedData['error']);
                    return $processedData;
                }
                $data = $processedData['data'];
                $contentType = $file['type'];
            } else {
                $data = file_get_contents($file['tmp_name']);
                $contentType = $file['type'];
            }

            return $this->uploadFileData($data, $path, array_merge($options, ['content_type' => $contentType]));
        } catch (Exception $e) {
            error_log('Google Cloud Storage upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed'];
        }
    }

    public function uploadFileData($data, $path, $options = [])
    {
        try {
            $bucket = $this->getBucket($path);
            $objectName = $this->getObjectName($path);
            //error_log("Uploading to GCS: bucket={$bucket->name()}, object={$objectName}");
            $metadata = [
                'name' => $objectName,
                'metadata' => [
                    'uploadTime' => date('c'),
                    'originalPath' => $path
                ]
            ];

            if (isset($options['content_type'])) {
                $metadata['metadata']['contentType'] = $options['content_type'];
            }

            if (isset($options['cache_control'])) {
                $metadata['metadata']['cacheControl'] = $options['cache_control'];
            } else {
                $metadata['metadata']['cacheControl'] = 'public, max-age=3600';
            }

            $object = $bucket->upload($data, $metadata);

            // Make object publicly readable (skip if uniform bucket-level access is enabled)
            try {
                $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);
            } catch (Exception $e) {
                // Ignore ACL errors when uniform bucket-level access is enabled
                if (strpos($e->getMessage(), 'uniform bucket-level access') === false) {
                    throw $e; // Re-throw if it's not a uniform bucket-level access error
                }
            }

            $publicUrl = sprintf(
                'https://storage.googleapis.com/%s/%s',
                $bucket->name(),
                $objectName
            );

            return [
                'success' => true,
                'url' => $publicUrl,
                'path' => $path,
                'bucket' => $bucket->name(),
                'object' => $objectName,
                'size' => strlen($data)
            ];
        } catch (Exception $e) {
            error_log('Google Cloud Storage data upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }

    public function getFile($path)
    {
        try {
            $bucket = $this->getBucket($path);
            $objectName = $this->getObjectName($path);
            $object = $bucket->object($objectName);

            if (!$object->exists()) {
                return ['success' => false, 'error' => 'File not found'];
            }

            $data = $object->downloadAsString();
            $info = $object->info();

            return [
                'success' => true,
                'data' => $data,
                'size' => $info['size'],
                'modified' => strtotime($info['updated'])
            ];
        } catch (Exception $e) {
            error_log('Google Cloud Storage get file error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to get file'];
        }
    }

    public function getFileUrl($path)
    {
        $bucket = $this->getBucketName($path);
        $objectName = $this->getObjectName($path);

        return sprintf(
            'https://storage.googleapis.com/%s/%s',
            $bucket,
            $objectName
        );
    }

    public function deleteFile($path)
    {
        try {
            $bucket = $this->getBucket($path);
            $objectName = $this->getObjectName($path);
            $object = $bucket->object($objectName);

            if ($object->exists()) {
                $object->delete();
            }

            return ['success' => true];
        } catch (Exception $e) {
            error_log('Google Cloud Storage delete error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Delete failed'];
        }
    }

    public function listFiles($path, $limit = 100, $offset = 0)
    {
        try {
            $bucket = $this->getBucket($path);
            $prefix = $this->getObjectName($path);

            $options = [
                'prefix' => $prefix,
                'maxResults' => $limit
            ];

            if ($offset > 0) {
                // GCS doesn't support offset directly, we'd need to implement pagination
                // For now, we'll get all and slice
                $options['maxResults'] = $limit + $offset;
            }

            $objects = $bucket->objects($options);
            $files = [];
            $count = 0;

            foreach ($objects as $object) {
                if ($count < $offset) {
                    $count++;
                    continue;
                }

                if (count($files) >= $limit) {
                    break;
                }

                $info = $object->info();
                $files[] = [
                    'name' => basename($info['name']),
                    'path' => $this->reverseObjectName($info['name']),
                    'size' => $info['size'],
                    'modified' => strtotime($info['updated']),
                    'url' => sprintf('https://storage.googleapis.com/%s/%s', $bucket->name(), $info['name'])
                ];
                $count++;
            }

            return ['success' => true, 'files' => $files, 'bucket' => $bucket->name()];
        } catch (Exception $e) {
            error_log('Google Cloud Storage list files error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to list files'];
        }
    }

    public function fileExists($path)
    {
        try {
            $bucket = $this->getBucket($path);
            $objectName = $this->getObjectName($path);
            $object = $bucket->object($objectName);

            return $object->exists();
        } catch (Exception $e) {
            return false;
        }
    }

    public function getProviderType()
    {
        return 'google_cloud';
    }

    public function getConfig()
    {
        $safeConfig = $this->config;
        unset($safeConfig['key_file']); // Don't expose sensitive data
        return $safeConfig;
    }

    public function getStatus()
    {
        try {
            // Test connectivity by listing buckets
            $buckets = $this->storage->buckets(['maxResults' => 1]);
            $buckets->current(); // Force the API call

            return [
                'available' => true,
                'healthy' => true,
                'project_id' => $this->config['project_id'],
                'authenticated' => true
            ];
        } catch (Exception $e) {
            return [
                'available' => false,
                'healthy' => false,
                'error' => $e->getMessage(),
                'authenticated' => false
            ];
        }
    }

    private function initializeStorage()
    {
        try {
            // Check if we're in Cloud Run environment
            $isCloudRun = (getenv('K_SERVICE') !== false) || (getenv('GOOGLE_CLOUD_PROJECT') !== false);

            if ($isCloudRun) {
                // In Cloud Run, use Application Default Credentials (ADC)
                $this->storage = new StorageClient([
                    'projectId' => $this->config['project_id']
                ]);
            } else {
                
                // In local/Docker environment, use key file if it exists
                if (file_exists($this->config['key_file'])) {
                    $this->storage = new StorageClient([
                        'keyFilePath' => $this->config['key_file'],
                        'projectId' => $this->config['project_id']
                    ]);
                } else {
                    // Fallback to ADC even in local environment
                    $this->storage = new StorageClient([
                        'projectId' => $this->config['project_id']
                    ]);
                }
            }
        } catch (Exception $e) {
            error_log('Google Cloud Storage initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getBucket($path)
    {
        $bucketName = $this->getBucketName($path);

        if (!isset($this->buckets[$bucketName])) {
            $bucket = $this->storage->bucket($bucketName);

            if (!$bucket->exists()) {
                $this->createBucket($bucketName);
            }

            $this->buckets[$bucketName] = $bucket;
        }

        return $this->buckets[$bucketName];
    }

    private function getBucketName($path)
    {
        // Always use the configured bucket name
        return $this->config['bucket_name'];
    }

    private function getObjectName($path)
    {
        // Use the full path as the object name
        return ltrim($path, '/');
    }

    private function reverseObjectName($objectName)
    {
        // This would need the bucket context to properly reverse
        // For now, return as-is
        return $objectName;
    }

    private function createBucket($bucketName)
    {
        try {
            $bucket = $this->storage->createBucket($bucketName, [
                'location' => $this->config['default_location'],
                'storageClass' => $this->config['storage_class'],
                'lifecycle' => [
                    'rule' => [
                        [
                            'action' => ['type' => 'Delete'],
                            'condition' => ['age' => $this->config['lifecycle_days']]
                        ]
                    ]
                ]
            ]);

            // Set uniform bucket-level access
            $bucket->update([
                'iamConfiguration' => [
                    'uniformBucketLevelAccess' => [
                        'enabled' => true
                    ]
                ]
            ]);

            return $bucket;
        } catch (Exception $e) {
            error_log('Bucket creation error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processImage($tempPath, $options = [])
    {
        try {
            $maxWidth = $options['max_width'] ?? 400;
            $maxHeight = $options['max_height'] ?? 400;
            $quality = $options['quality'] ?? 85;

            $imageInfo = getimagesize($tempPath);
            if (!$imageInfo) {
                return ['success' => false, 'error' => 'Invalid image'];
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $type = $imageInfo[2];

            // Define modern constants if running an older PHP environment
            if (!defined('IMAGETYPE_WEBP')) {
                define('IMAGETYPE_WEBP', 18);
            }
            if (!defined('IMAGETYPE_AVIF')) {
                define('IMAGETYPE_AVIF', 19);
            }

            $source = false;

            // Create image resource
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($tempPath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($tempPath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($tempPath);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $source = @imagecreatefromwebp($tempPath);
                    }
                    break;
                case IMAGETYPE_AVIF: // Added handling for AVIF (Type 19)
                    if (function_exists('imagecreatefromavif')) {
                        $source = @imagecreatefromavif($tempPath);
                    }
                    break;
                default:
                    return ['success' => false, 'error' => 'Unsupported image type: ' . $type];
            }

            // CRITICAL FALLBACK: If GD natively failed to process the image
            // Bypasses resizing and uploads raw binary payload so cloud execution doesn't fail.
            if (!$source) {
                error_log("GD Decoder failed to read image layout. Bypassing processing to upload raw stream.");
                $imageData = file_get_contents($tempPath);
                if ($imageData === false) {
                    return ['success' => false, 'error' => 'Could not read original file stream'];
                }
                return ['success' => true, 'data' => $imageData];
            }

            // Calculate new dimensions
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            if ($ratio < 1) {
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
            } else {
                $newWidth = $width;
                $newHeight = $height;
            }

            // Create new image
            $destination = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency (Added AVIF to transparency support)
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF || $type == IMAGETYPE_WEBP || $type == IMAGETYPE_AVIF) {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
            }

            // Resize
            imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Output to string
            ob_start();
            switch ($type) {
                case IMAGETYPE_PNG:
                    imagepng($destination, null, 6);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($destination);
                    break;
                case IMAGETYPE_JPEG:
                case IMAGETYPE_WEBP:
                case IMAGETYPE_AVIF: // Re-encode static incoming AVIFs to uniform JPEG syntax
                    imagejpeg($destination, null, $quality);
                    break;
            }
            $imageData = ob_get_contents();
            ob_end_clean();

            // Clean up
            imagedestroy($source);
            imagedestroy($destination);

            return ['success' => true, 'data' => $imageData];
        } catch (Exception $e) {
            error_log('Image processing error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Image processing failed'];
        }
    }
}
