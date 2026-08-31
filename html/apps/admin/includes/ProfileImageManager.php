<?php
/**
 * Profile Image Manager using Universal File Storage System
 * Supports both local and Google Cloud Storage through FileStorageManager
 */

require_once __DIR__ . '/../../../includes/storage/FileStorageManager.php';

class ProfileImageManager {
    private $storage;
    private $maxFileSize = 512000; // 500KB max
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $maxWidth = 400;
    private $maxHeight = 400;
    
    public function __construct($storageType = null) {
        // Initialize with current configured storage or override
        $this->storage = FileStorageManager::getInstance($storageType);
    }
    
    /**
     * Upload and process profile image
     */
    public function uploadProfileImage($file, $username) {
        try {
            // Use the FileStorageManager with profile images category
            $options = [
                'prefix' => 'profile_' . $username,
                'process_image' => true,
                'max_width' => $this->maxWidth,
                'max_height' => $this->maxHeight,
                'quality' => 85
            ];
            
            $result = $this->storage->uploadFile(
                $file, 
                FileStorageManager::CATEGORY_PROFILE_IMAGES, 
                null, 
                $options
            );
            
            return $result;
            
        } catch (Exception $e) {
            log_error('Profile image upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Delete profile image
     */
    public function deleteProfileImage($filename) {
        try {
            if (empty($filename)) {
                return ['success' => true]; // Nothing to delete
            }
            
            return $this->storage->deleteFile(FileStorageManager::CATEGORY_PROFILE_IMAGES, $filename);
            
        } catch (Exception $e) {
            log_error('Profile image delete error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Delete failed'];
        }
    }
    
    /**
     * Get profile image URL
     */
    public function getProfileImageUrl($filename) {
        if (empty($filename)) {
            return self::getDefaultProfileImage();
        }
        
        return $this->storage->getFileUrl(FileStorageManager::CATEGORY_PROFILE_IMAGES, $filename);
    }
    
    /**
     * Get profile image data
     */
    public function getProfileImage($filename) {
        if (empty($filename)) {
            return ['success' => false, 'error' => 'No filename provided'];
        }
        
        return $this->storage->getFile(FileStorageManager::CATEGORY_PROFILE_IMAGES, $filename);
    }
    
    /**
     * List all profile images
     */
    public function listProfileImages($limit = 100, $offset = 0) {
        return $this->storage->listFiles(FileStorageManager::CATEGORY_PROFILE_IMAGES, $limit, $offset);
    }
    
    /**
     * Migrate profile images to different storage provider
     */
    public function migrateToProvider($targetStorageType, $targetConfig = []) {
        try {
            $files = $this->listProfileImages(1000);
            if (!$files['success']) {
                return $files;
            }
            
            $results = [
                'success' => true,
                'total' => count($files['files']),
                'migrated' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            foreach ($files['files'] as $file) {
                $copyResult = $this->storage->copyToProvider(
                    FileStorageManager::CATEGORY_PROFILE_IMAGES,
                    $file['name'],
                    $targetStorageType,
                    $targetConfig
                );
                
                if ($copyResult['success']) {
                    $results['migrated']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to migrate {$file['name']}: " . $copyResult['error'];
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            log_error('Profile image migration error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Migration failed'];
        }
    }
    
    /**
     * Get storage provider information
     */
    public function getStorageInfo() {
        return $this->storage->getProviderInfo();
    }
    
    /**
     * Switch storage provider
     */
    public function switchStorageProvider($storageType, $config = []) {
        try {
            $result = $this->storage->switchProvider($storageType, $config);
            if ($result['success']) {
                // Reinitialize with new provider
                $this->storage = FileStorageManager::getInstance($storageType, $config);
            }
            return $result;
            
        } catch (Exception $e) {
            log_error('Storage provider switch error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Provider switch failed'];
        }
    }
    
    /**
     * Validate uploaded image (moved from FileStorageManager for backwards compatibility)
     */
    public function validateImage($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error occurred'];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'Image too large (max 500KB)'];
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid image type. Use JPEG, PNG, GIF, or WebP'];
        }
        
        // Verify it's actually an image
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return ['valid' => false, 'error' => 'File is not a valid image'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Get default profile image (base64 SVG)
     */
    public static function getDefaultProfileImage() {
        return 'data:image/svg+xml;base64,' . base64_encode('
            <svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="50" fill="#e0e0e0"/>
                <circle cx="50" cy="35" r="15" fill="#bdbdbd"/>
                <path d="M20 85 C 20 70, 35 60, 50 60 S 80 70, 80 85" fill="#bdbdbd"/>
            </svg>');
    }
}