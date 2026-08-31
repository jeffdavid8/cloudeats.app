<?php
class ImporterEngine {
    private $app;
    private $strategies = ['gedcom', 'ancestry_json']; // Names of our sub-models

    public function __construct($app) {
        $this->app = $app;
    }

    public function analyze($file_data, $file_name) {
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        foreach ($this->strategies as $strategyName) {
            // Use your cascading includeModel to load the strategy
            $this->app->includeModel("importers/" . $strategyName);
            
            $className = ucfirst($strategyName) . "Importer";
            if (class_exists($className)) {
                $strategy = new $className();
                
                if ($strategy->canHandle($extension)) {
                    return [
                        'status' => 'success',
                        'type' => $strategyName,
                        'data' => $strategy->parse($file_data)
                    ];
                }
            }
        }

        return ['status' => 'error', 'message' => 'No suitable importer found for this file type.'];
    }
}