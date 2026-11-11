<?php

return [
    'tables' => ['neighborhub_merchant_storefronts'],
    'up' => static function (PDO $db): void {
        $db->exec("
            CREATE TABLE neighborhub_merchant_storefronts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                merchant_id INT NOT NULL UNIQUE,
                draft_config JSON NOT NULL,
                published_config JSON NOT NULL,
                draft_updated_at DATETIME NULL,
                published_at DATETIME NULL,
                updated_by INT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_storefront_merchant
                    FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
                CONSTRAINT fk_storefront_updated_by
                    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    },
    'verify' => static function (PDO $db): void {
        DatabaseMigrationRunner::assertSchema($db, [
            'neighborhub_merchant_storefronts' => [
                'id',
                'merchant_id',
                'draft_config',
                'published_config',
                'draft_updated_at',
                'published_at',
                'updated_by',
                'created_at',
                'updated_at',
            ],
        ], [
            'neighborhub_merchant_storefronts' => [
                ['merchant_id', 'neighborhub_merchants', 'id'],
                ['updated_by', 'users', 'id'],
            ],
        ]);
    },
];
