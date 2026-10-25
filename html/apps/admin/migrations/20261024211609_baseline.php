<?php

return [
    'tables' => ['users', 'permissions_registry', 'user_permissions_map', 'app_system_settings'],
    'up' => static function (PDO $db): void {
        $tableSql = "
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(180) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                picture VARCHAR(2048),
                oauth_provider VARCHAR(50),
                oauth_profile_url VARCHAR(2048),
                oauth_providers JSON NOT NULL, -- Upgraded to Native MySQL JSON Engine Workspace
                role VARCHAR(50) DEFAULT 'user',
                is_admin TINYINT DEFAULT 0,
                stripe_connect_id VARCHAR(255),
                active TINYINT DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT NULL,
                modified_at TIMESTAMP NULL DEFAULT NULL,
                last_login TIMESTAMP NULL DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE permissions_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                perm_key VARCHAR(180) UNIQUE NOT NULL, 
                title VARCHAR(255),
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE user_permissions_map (
                user_id INT NOT NULL,
                perm_id INT NOT NULL,
                action VARCHAR(50) NOT NULL, 
                PRIMARY KEY (user_id, perm_id, action),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (perm_id) REFERENCES permissions_registry(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE app_system_settings (
                setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_by VARCHAR(180) NULL,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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
            'users' => [
                'id', 'username', 'password', 'email', 'picture', 'oauth_provider', 'oauth_profile_url',
                'oauth_providers', 'role', 'is_admin', 'stripe_connect_id', 'active', 'created_at',
                'modified_at', 'last_login',
            ],
            'permissions_registry' => ['id', 'perm_key', 'title', 'description'],
            'user_permissions_map' => ['user_id', 'perm_id', 'action'],
            'app_system_settings' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
        ], [
            'user_permissions_map' => [
                ['user_id', 'users', 'id'],
                ['perm_id', 'permissions_registry', 'id'],
            ],
        ]);
    },
];
