<?php

/**
 * 🛠️ SERVICE MODEL
 * 
 * The Root Engine for a "Service Provider."
 * Defines the tiers and offerings that generate Memberships.
 */
class Service extends Storage
{
    // Properties specific to a Service Provider
    public $service_name;
    public $service_description;
    public $tiers = []; // Custom tier definitions stored in JSON
    public $updated_at;

    public function __construct($data = [])
    {
        parent::__construct($data);
        
        // Hydrate from JSON content
        $content = $this->getContent(true);
        $this->service_name = $content['service_name'] ?? 'New Service';
        $this->service_description = $content['service_description'] ?? '';
        $this->tiers = $content['tiers'] ?? [];
        $this->updated_at = $content['updated_at'] ?? date('Y-m-d H:i:s');
    }

    protected static function getTableName() { return 'memory_anchors'; }

    /**
     * Create a new Service Offering
     */
    public static function create($data)
    {
        $uuid = self::generateUuid();
        $content = [
            'service_name' => $data['service_name'] ?? 'Unnamed Service',
            'service_description' => $data['service_description'] ?? '',
            'tiers' => $data['tiers'] ?? [], // The user-defined Bronze/Silver/Gold
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return parent::create([
            'architect_id' => $data['architect_id'],
            'content_type' => 'service',
            'content' => $content
        ]);
    }
}