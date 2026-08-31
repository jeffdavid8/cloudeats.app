<?php

class Vouch {
    public $id;
    public $anchor_id;
    public $stitch_id; // The person doing the vouching
    public $fidelity_score;
    public $created_at;

    public function __construct($data) {
        foreach($data as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }

    // Logic for "Team" validation could go here
    // e.g. public function isHighFidelity() { return $this->fidelity_score > 0.8; }
}