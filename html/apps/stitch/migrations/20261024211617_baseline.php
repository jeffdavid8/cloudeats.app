<?php

return [
    'tables' => ['memory_anchors', 'vouches', 'stitch_nexus', 'pasture_handshakes'],
    'up' => static function (PDO $db): void {
        require_once dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'includes'
            . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'storage.model.php';

        $allowedTypes = array_keys(Storage::getAllowedTypes());
        $quotedTypes = array_map(
            static function (string $type) use ($db): string {
                $quotedType = $db->quote($type);
                if ($quotedType === false) {
                    throw new RuntimeException('Unable to quote Stitch content type for schema constraint.');
                }
                return $quotedType;
            },
            $allowedTypes
        );
        $checkConstraint = implode(', ', $quotedTypes);

        $tableSql = "
            CREATE TABLE memory_anchors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                uuid VARCHAR(36) UNIQUE,
                content JSON NOT NULL, 
                lat DOUBLE,
                lng DOUBLE,
                projected_to DATETIME DEFAULT CURRENT_TIMESTAMP,
                architect_id INT NOT NULL,
                content_type VARCHAR(50) NOT NULL DEFAULT 'observation' CHECK (content_type IN ($checkConstraint)),
                status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'disputed', 'verified', 'deleted')),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (architect_id) REFERENCES users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE vouches (
                id INT AUTO_INCREMENT PRIMARY KEY, 
                anchor_id INT NOT NULL,
                stitch_id VARCHAR(100) DEFAULT 'Architect',
                fidelity_score DECIMAL(3,2) DEFAULT 1.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (anchor_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE stitch_nexus (
                stitch_id INT NOT NULL,
                nexus_id INT NOT NULL, 
                nexus_label VARCHAR(255) NOT NULL DEFAULT '', 
                weight FLOAT DEFAULT 1.0,
                last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (stitch_id, nexus_id, nexus_label), 
                FOREIGN KEY (stitch_id) REFERENCES memory_anchors(id) ON DELETE CASCADE,
                FOREIGN KEY (nexus_id) REFERENCES memory_anchors(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE pasture_handshakes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) UNIQUE NOT NULL, 
                offer TEXT,
                answer TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE INDEX idx_nexus_label ON stitch_nexus(nexus_label);
            CREATE INDEX idx_stitch_id ON stitch_nexus(stitch_id);
            CREATE INDEX idx_nexus_dynamic ON stitch_nexus(weight, last_accessed);
            ";

        foreach (explode(';', $tableSql) as $q) {
            $q = trim($q);
            if ($q) {
                $db->exec($q);
            }
        }
    },
    'verify' => static function (PDO $db): void {
        DatabaseMigrationRunner::assertSchema($db, [
            'memory_anchors' => [
                'id', 'uuid', 'content', 'lat', 'lng', 'projected_to', 'architect_id',
                'content_type', 'status', 'created_at',
            ],
            'vouches' => ['id', 'anchor_id', 'stitch_id', 'fidelity_score', 'created_at'],
            'stitch_nexus' => ['stitch_id', 'nexus_id', 'nexus_label', 'weight', 'last_accessed'],
            'pasture_handshakes' => ['id', 'session_id', 'offer', 'answer', 'created_at'],
        ], [
            'memory_anchors' => [['architect_id', 'users', 'id']],
            'vouches' => [['anchor_id', 'memory_anchors', 'id']],
            'stitch_nexus' => [
                ['stitch_id', 'memory_anchors', 'id'],
                ['nexus_id', 'memory_anchors', 'id'],
            ],
        ]);
    },
];
