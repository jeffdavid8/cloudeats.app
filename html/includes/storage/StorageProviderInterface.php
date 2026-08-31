<?php
/**
 * Storage Provider Interface
 * Defines contract for all storage providers
 */

interface StorageProviderInterface {
    /**
     * Upload file from $_FILES array
     */
    public function uploadFile($file, $path, $options = []);
    
    /**
     * Upload file from binary data
     */
    public function uploadFileData($data, $path, $options = []);
    
    /**
     * Get file data
     */
    public function getFile($path);
    
    /**
     * Get public URL for file
     */
    public function getFileUrl($path);
    
    /**
     * Delete file
     */
    public function deleteFile($path);
    
    /**
     * List files in path
     */
    public function listFiles($path, $limit = 100, $offset = 0);
    
    /**
     * Check if file exists
     */
    public function fileExists($path);
    
    /**
     * Get provider type identifier
     */
    public function getProviderType();
    
    /**
     * Get provider configuration
     */
    public function getConfig();
    
    /**
     * Get provider status
     */
    public function getStatus();
}