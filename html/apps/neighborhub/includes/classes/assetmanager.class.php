<?php
if (!defined('MB_RUNNING')) exit;

/**
 * Neighborhub Asset Manager Model 
 * Handles database tracking and file storage synchronization for multi-image support
 */
class AssetManager
{
    /**
     * Upload and attach multiple images to a specific parent entity
     * * @param string $parentType ('product', 'merchant', 'courier')
     * @param int $parentId
     * @param int $merchantId Used to build the GCS folder structure
     * @param array $filesArray Typically $_FILES['gallery_images']
     * @return array List of successfully uploaded public URLs
     */
    public static function uploadMultipleImages($parentType, $parentId, $merchantId, $filesArray)
    {
        $uploadedUrls = array();
        try {
            $db = App::getInstance()->db;
            //require_once __DIR__ . '/../../includes/storage/FileStorageManager.php';
            $storageManager = new FileStorageManager('google_cloud');

            // Normalize PHP's weird multi-file $_FILES structure into a clean list of files
            $files = self::rearrangeFilesArray($filesArray);
            
            // Build the destination folder layout dynamically
            $targetPath = '';
            switch ($parentType) {
                case 'merchant':
                    $targetPath = "apps/neighborhub/merchants/" . intval($merchantId) . "/images";
                    break;
                case 'product':
                    $targetPath = "apps/neighborhub/merchants/" . intval($merchantId) . "/products/" . intval($parentId) . "/images";
                    break;
                case 'courier':
                    $targetPath = "apps/neighborhub/couriers/" . intval($parentId) . "/images";
                    break;
                default:
                    throw new Exception("Invalid parent type for image upload");
            }

            foreach ($files as $fileData) {
                if ($fileData['error'] !== UPLOAD_ERR_OK) continue;

                $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4'))) continue;

                // Create a unique name
                $uniqueFilename = bin2hex(random_bytes(16)) . '.' . $extension;
                
                if ($extension === 'mp4') {
                    $uploadOptions = array(
                        'process_image' => false,
                        'convert_to_webp' => false
                    );
                } else {
                    $uploadOptions = array(
                        'process_image' => true,
                        'max_width' => 1000,
                        'max_height' => 1000,
                        'quality' => 85,
                        'convert_to_webp' => true
                    );
                }

                $uploadResult = $storageManager->uploadFile($fileData, $targetPath, $uniqueFilename, $uploadOptions);
                
                if ($uploadResult['success']) {
                    $publicUrl = $storageManager->getFileUrl($targetPath, $uniqueFilename);
                    
                    // Insert tracking reference into database
                    $stmt = $db->prepare(
                        "INSERT INTO neighborhub_images (parent_type, parent_id, image_url, type, meta) 
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $parentType, 
                        intval($parentId), 
                        $publicUrl,
                        'default',
                        '{}']);
                    
                    $uploadedUrls[] = $publicUrl;
                }
            }
            
            return $uploadedUrls;
        } catch (Exception $e) {
            error_log("AssetManager::uploadMultipleImages Exception: " . $e->getMessage());
            return $uploadedUrls;
        }
    }

    /**
     * Get all images attached to an entity
     */
    public static function getImagesByEntity($parentType, $parentId)
    {
        try {
            $db = App::getInstance()->db;
            $stmt = $db->prepare(
                "SELECT id, image_url 
                 FROM neighborhub_images 
                 WHERE parent_type = ? AND parent_id = ? 
                 ORDER BY id ASC"
            );
            $stmt->execute([$parentType, intval($parentId)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AssetManager::getImagesByEntity Error: " . $e->getMessage());
            return array();
        }
    }

    /**
     * Rearranges PHP's multi-file array structure from $_FILES into an easy loop
     */
    private static function rearrangeFilesArray($filePost)
    {
        $fileAry = array();
        if (!isset($filePost['name']) || !is_array($filePost['name'])) {
            return array($filePost); // Single file upload fallback
        }
        $fileCount = count($filePost['name']);
        $fileKeys = array_keys($filePost);

        for ($i = 0; $i < $fileCount; $i++) {
            foreach ($fileKeys as $key) {
                $fileAry[$i][$key] = $filePost[$key][$i];
            }
        }
        return $fileAry;
    }
}