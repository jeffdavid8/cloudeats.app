<?php

class Nexus
{
    public $stitch_id;   // The Origin
    public $nexus_id;    // The Destination
    public $nexus_label; // Resonance Type (Correction, Validation, etc.)
    public $weight;        // ⚖️ New: Connection strength

    // 🛰️ TARGET DATA
    public $target_content;
    public $target_type;
    public $target_year;
    public $lat;
    public $lng;

    public function __construct($data)
    {
        $this->stitch_id   = $data['stitch_id'] ?? ($data['parent_id'] ?? null);
        $this->nexus_id    = $data['nexus_id'] ?? ($data['id'] ?? null);
        $this->nexus_label = $data['nexus_label'] ?? 'Resonance';
        $this->weight      = (float)($data['weight'] ?? 1.0);

        // Eager-loaded data from JOINs
        $this->target_content = $data['target_content'] ?? ($data['content'] ?? '');
        $this->target_type    = $data['target_type'] ?? ($data['content_type'] ?? 'historical_snapshot');
        $this->target_year    = $data['target_year'] ?? ($data['created_at'] ?? '');

        // 📍 Spatial defaults to prevent Undefined Key errors
        $this->lat = isset($data['lat']) ? (float)$data['lat'] : null;
        $this->lng = isset($data['lng']) ? (float)$data['lng'] : null;
    }

    public function getArcOpacity()
    {
        // Use the weight to return an opacity value between 0.2 and 1.0
        return min(1.0, max(0.2, $this->weight));
    }

    /**
     * Determines if this link should appear as a purple arc on the map
     */
    public function isPlotable()
    {
        return !is_null($this->lat) && !is_null($this->lng);
    }

    /**
     * Returns the CSS class based on the resonance type
     */
    public function getResonanceStyle()
    {
        switch ($this->nexus_label) {
            case 'Correction':
                return 'border: 1px solid #ff5252; color: #ff5252;';
            case 'Validation':
                return 'border: 1px solid #4caf50; color: #4caf50;';
            case 'Branch':
                return 'border: 1px solid #9b59b2; color: #9b59b2;'; // Purple for arcs!
            default:
                return 'border: 1px solid #ffd700; color: #ffd700;';
        }
    }

}
