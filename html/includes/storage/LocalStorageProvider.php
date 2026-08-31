<?php
/**
 * Local File Storage Provider
 * Handles file storage on local filesystem
 */

class LocalStorageProvider implements StorageProviderInterface {
    private $config;
    private $basePath;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'base_path' => '/var/data/mediabrain/storage',
            'public_url_base' => '/api/file.php?f=',
            'permissions' => 0644,
            'directory_permissions' => 0755
        ], $config);
        
        $this->basePath = $this->config['base_path'];
        $this->ensureBaseDirectory();
    }
    
    public function uploadFile($file, $path, $options = []) {
        try {
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return ['success' => false, 'error' => 'Invalid upload file'];
            }
            
            $fullPath = $this->getFullPath($path);
            $this->ensureDirectory(dirname($fullPath));
            
            // Process image if needed
            if (isset($options['process_image']) && $options['process_image']) {
                $processedData = $this->processImage($file['tmp_name'], $options);
                if (!$processedData['success']) {
                    return $processedData;
                }
                
                if (file_put_contents($fullPath, $processedData['data']) === false) {
                    return ['success' => false, 'error' => 'Failed to save processed image'];
                }
            } else {
                if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                    return ['success' => false, 'error' => 'Failed to move uploaded file'];
                }
            }
            
            chmod($fullPath, $this->config['permissions']);
            
            return [
                'success' => true,
                'url' => $this->getFileUrl($path),
                'path' => $path,
                'size' => filesize($fullPath)
            ];
            
        } catch (Exception $e) {
            log_error('Local storage upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed'];
        }
    }
    
    public function uploadFileData($data, $path, $options = []) {
        try {
            $fullPath = $this->getFullPath($path);
            $this->ensureDirectory(dirname($fullPath));
            
            if (file_put_contents($fullPath, $data) === false) {
                return ['success' => false, 'error' => 'Failed to write file'];
            }
            
            chmod($fullPath, $this->config['permissions']);
            
            return [
                'success' => true,
                'url' => $this->getFileUrl($path),
                'path' => $path,
                'size' => strlen($data)
            ];
            
        } catch (Exception $e) {
            log_error('Local storage data upload error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Upload failed'];
        }
    }
    
    public function getFile($path) {
        try {
            $fullPath = $this->getFullPath($path);
            if (!file_exists($fullPath)) {
                return ['success' => false, 'error' => 'File not found'];
            }
            
            $data = file_get_contents($fullPath);
            if ($data === false) {
                return ['success' => false, 'error' => 'Failed to read file'];
            }
            
            return [
                'success' => true,
                'data' => $data,
                'size' => filesize($fullPath),
                'modified' => filemtime($fullPath)
            ];
            
        } catch (Exception $e) {
            log_error('Local storage get file error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to get file'];
        }
    }
    
    public function getFileUrl($path) {
        return $this->config['public_url_base'] . urlencode($path);
    }
    
    public function deleteFile($path) {
        try {
            $fullPath = $this->getFullPath($path);
            
            if (!file_exists($fullPath)) {
                return ['success' => true]; // Already gone
            }
            
            if (!unlink($fullPath)) {
                return ['success' => false, 'error' => 'Failed to delete file'];
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            log_error('Local storage delete error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Delete failed'];
        }
    }
    
    public function listFiles($path, $limit = 100, $offset = 0) {
        try {
            $fullPath = $this->getFullPath($path);
            
            if (!is_dir($fullPath)) {
                return ['success' => true, 'files' => []];
            }
            
            $rawFiles = array_diff(scandir($fullPath), array('.', '..'));
            $pagedFiles = array_slice($rawFiles, $offset, $limit);
            $files = [];
            foreach ($pagedFiles as $filename) {
                $filePath = $fullPath . '/' . $filename;
                if (is_file($filePath)) {
                    $relativePath = $path . '/' . $filename;
                    $files[] = [
                        'name' => $filename,
                        'path' => $relativePath,
                        'size' => filesize($filePath),
                        'modified' => filemtime($filePath),
                        'url' => $this->getFileUrl($relativePath)
                    ];
                }
            }
            
            return ['success' => true, 'files' => $files];
            
        } catch (Exception $e) {
            log_error('Local storage list files error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Failed to list files'];
        }
    }
    
    public function fileExists($path) {
        return file_exists($this->getFullPath($path));
    }
    
    public function getProviderType() {
        return 'local';
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function getStatus() {
        $status = [
            'available' => true,
            'writable' => is_writable($this->basePath),
            'space_free' => disk_free_space($this->basePath),
            'space_total' => disk_total_space($this->basePath)
        ];
        
        $status['healthy'] = $status['available'] && $status['writable'];
        
        return $status;
    }
    
    private function getFullPath($path) {
        return $this->basePath . '/' . ltrim($path, '/');
    }
    
    private function ensureBaseDirectory() {
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, $this->config['directory_permissions'], true);
        }
    }
    
    private function ensureDirectory($path) {
        if (!is_dir($path)) {
            mkdir($path, $this->config['directory_permissions'], true);
        }
    }
    
    private function processImage($tempPath, $options = []) {
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
                    $source = imagecreatefromwebp($tempPath);
                    break;
                default:
                    return ['success' => false, 'error' => 'Unsupported image type'];
            }
            
            if (!$source) {
                return ['success' => false, 'error' => 'Could not process image'];
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
            
            // Preserve transparency
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
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
                case IMAGETYPE_JPEG:
                    imagejpeg($destination, null, $quality);
                    break;
                case IMAGETYPE_PNG:
                    imagepng($destination, null, 6);
                    break;
                case IMAGETYPE_GIF:
                    imagegif($destination);
                    break;
                case IMAGETYPE_WEBP:
                    imagewebp($destination, null, $quality);
                    break;
            }
            $imageData = ob_get_contents();
            ob_end_clean();
            
            // Clean up
            imagedestroy($source);
            imagedestroy($destination);
            
            return ['success' => true, 'data' => $imageData];
            
        } catch (Exception $e) {
            log_error('Image processing error: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Image processing failed'];
        }
    }
}