<?php

return [
    'tables' => [
        'neighborhub_merchants',
        'neighborhub_customers',
        'neighborhub_registration_tokens',
        'neighborhub_registration_attempts',
        'neighborhub_merchant_users',
        'neighborhub_products',
        'neighborhub_menus',
        'neighborhub_menu_categories',
        'neighborhub_menu_items',
        'neighborhub_couriers',
        'neighborhub_orders',
        'neighborhub_order_items',
        'neighborhub_images',
        'neighborhub_delivery_tracking',
        'neighborhub_webrtc_sessions',
        'neighborhub_usage_events',
    ],
    'up' => static function (PDO $db): void {
        $tableSql = "
            CREATE TABLE neighborhub_merchants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            business_name VARCHAR(255) NOT NULL,
            description TEXT,
            keywords TEXT,
            store_hours TEXT,
            address TEXT,
            city VARCHAR(100),
            state VARCHAR(100),
            zip_code VARCHAR(20),
            latitude DOUBLE,
            longitude DOUBLE,
            location POINT NOT NULL DEFAULT (ST_PointFromText('POINT(0 0)')),
            phone VARCHAR(50),
            email VARCHAR(255),
            messenger VARCHAR(255),
            website VARCHAR(255),
            facebook VARCHAR(255),
            google VARCHAR(255),
            image_url VARCHAR(2048),
            platform_flat_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            platform_fee_rate DECIMAL(10,2) NOT NULL DEFAULT 0.04,
            stripe_api_key TEXT,
            stripe_percent_fee DECIMAL(10,2),
            stripe_flat_fee DECIMAL(10,2),
            status VARCHAR(20) DEFAULT 'active' CHECK(status IN ('online', 'offline', 'active', 'paused', 'suspended', 'disabled')),
            sandbox_mode TINYINT DEFAULT 0 CHECK(sandbox_mode IN (0, 1)),
            delivery_assignment_mode VARCHAR(20) DEFAULT 'auto' CHECK(delivery_assignment_mode IN ('auto', 'manual', 'disabled')),
            delivery_max_distance DECIMAL(10,2) DEFAULT 7.00,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            display_name VARCHAR(255) NOT NULL,
            delivery_locations JSON NOT NULL,
            phone VARCHAR(50),
            terms_accepted_at DATETIME,
            order_notes TEXT,
            rating DECIMAL(3,2),
            status VARCHAR(20) DEFAULT 'active' CHECK(status IN ('active', 'paused', 'suspended', 'disabled')),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_registration_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            token_hash CHAR(64) NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            verified_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_neighborhub_registration_user (user_id),
            CONSTRAINT fk_neighborhub_registration_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_registration_attempts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            ip_hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_neighborhub_registration_attempt_ip_created (ip_hash, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_merchant_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            merchant_id INT NOT NULL,
            user_id INT NOT NULL,
            staff_role VARCHAR(20) DEFAULT 'clerk' CHECK(staff_role IN ('owner', 'staff', 'delivery', 'screen')),
            invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            joined_at DATETIME,
            status VARCHAR(20) DEFAULT 'pending' CHECK(status IN ('pending', 'active', 'inactive')),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_merchant_user (merchant_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            merchant_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            id_required TINYINT DEFAULT 0 CHECK(id_required IN (0, 1)),
            tags TEXT,
            sku VARCHAR(100) DEFAULT '',
            is_available TINYINT DEFAULT 1 CHECK(is_available IN (0, 1)),
            image_url VARCHAR(2048),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_menus (
            id INT AUTO_INCREMENT PRIMARY KEY,
            merchant_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) DEFAULT 'inactive' CHECK(status IN ('active', 'inactive')),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL DEFAULT (JSON_OBJECT()),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_merchant_menus (merchant_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_menu_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) DEFAULT 'inactive' CHECK(status IN ('active', 'inactive')),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL DEFAULT (JSON_OBJECT()),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (menu_id) REFERENCES neighborhub_menus(id) ON DELETE CASCADE,
            INDEX idx_menu_categories (menu_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            product_id INT NOT NULL,
            override_price DECIMAL(10,2) DEFAULT NULL,
            is_available TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL DEFAULT (JSON_OBJECT()),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES neighborhub_menu_categories(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES neighborhub_products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_category_product (category_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_couriers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            business_name VARCHAR(255),
            phone VARCHAR(50),
            vehicle_type VARCHAR(20) DEFAULT 'car' CHECK(vehicle_type IN ('bike', 'scooter', 'car', 'van', 'truck')),
            status VARCHAR(20) DEFAULT 'offline' CHECK(status IN ('available', 'on_delivery', 'offline')),
            latitude DOUBLE,
            longitude DOUBLE,
            location POINT NOT NULL DEFAULT (ST_PointFromText('POINT(0 0)')),
            last_location_update DATETIME,
            total_deliveries INT DEFAULT 0,
            rating DECIMAL(3,2),
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(100) UNIQUE NOT NULL,
            customer_id INT NOT NULL,
            merchant_id INT NOT NULL,
            courier_id INT,
            subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            processing_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            platform_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sales_tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tips DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(30) NOT NULL DEFAULT 'STRIPE',
            state VARCHAR(50) NOT NULL DEFAULT 'PENDING_CONFIRMATION',
            stripe_payment_intent_id VARCHAR(255),
            delivery_assignment_mode VARCHAR(20) DEFAULT 'auto' CHECK(delivery_assignment_mode IN ('auto', 'manual', 'disabled')),
            locked_by_courier_id INT,
            locked_at DATETIME,
            delivery_requested TINYINT DEFAULT 0 CHECK(delivery_requested IN (0, 1)),
            pickup_address TEXT,
            delivery_address TEXT,
            order_phone VARCHAR(50),
            order_notes TEXT,
            sandbox_mode TINYINT DEFAULT 0 CHECK(sandbox_mode IN (0, 1)),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            confirmed_at DATETIME,
            ready_at DATETIME,
            picked_up_at DATETIME,
            delivered_at DATETIME,
            cancelled_at DATETIME,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
            FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL,
            FOREIGN KEY (locked_by_courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            customer_notes VARCHAR(50),
            price_at_order DECIMAL(10,2) DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES neighborhub_products(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            parent_type VARCHAR(50) NOT NULL,
            parent_id INT NOT NULL,
            image_url VARCHAR(2048) NOT NULL,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_delivery_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            courier_id INT NOT NULL,
            latitude DOUBLE,
            longitude DOUBLE,
            location POINT NOT NULL DEFAULT (ST_PointFromText('POINT(0 0)')),
            status_update VARCHAR(255),
            details JSON NOT NULL,
            type VARCHAR(50) DEFAULT 'default',
            meta JSON NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            
            CREATE TABLE neighborhub_webrtc_sessions (
            id int(11) NOT NULL AUTO_INCREMENT,
            session_id varchar(64) NOT NULL,
            initiator_role enum('admin','merchant','customer','courier') NOT NULL,
            initiator_id int(11) NOT NULL,
            target_role enum('admin','merchant','customer','courier') NOT NULL,
            target_id int(11) DEFAULT NULL,
            offer_sdp text DEFAULT NULL,
            answer_sdp text DEFAULT NULL,
            status enum('waiting','offered','answered','connected','closed') DEFAULT 'waiting',
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_session_id (session_id),
            KEY idx_lookup (target_role, target_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE neighborhub_usage_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(64) NOT NULL,
            actor_role VARCHAR(16) NOT NULL DEFAULT 'guest',
            merchant_id INT DEFAULT NULL,
            properties JSON NOT NULL,
            dedupe_key CHAR(64) DEFAULT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_nh_usage_created (created_at),
            INDEX idx_nh_usage_type_created (event_type, created_at),
            INDEX idx_nh_usage_merchant_created (merchant_id, created_at),
            CONSTRAINT fk_nh_usage_merchant FOREIGN KEY (merchant_id)
                REFERENCES neighborhub_merchants(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE INDEX idx_nh_merch_user ON neighborhub_merchants(user_id, status);
            CREATE INDEX idx_nh_merch_status ON neighborhub_merchants(status);
            CREATE INDEX idx_nh_cust_user ON neighborhub_customers(user_id, status);
            CREATE INDEX idx_nh_cust_status ON neighborhub_customers(status);
            CREATE SPATIAL INDEX idx_merchants_spatial ON neighborhub_merchants(location);
            CREATE SPATIAL INDEX idx_tracking_spatial ON neighborhub_delivery_tracking(location);
            CREATE SPATIAL INDEX idx_couriers_spatial ON neighborhub_couriers(location);
            CREATE INDEX idx_nh_mu_user ON neighborhub_merchant_users(user_id, status);
            CREATE INDEX idx_nh_mu_merch ON neighborhub_merchant_users(merchant_id, status);
            CREATE INDEX idx_nh_prod_merch ON neighborhub_products(merchant_id, is_available);
            CREATE INDEX idx_nh_orders_num ON neighborhub_orders(order_number);
            CREATE INDEX idx_nh_orders_cust ON neighborhub_orders(customer_id, state);
            CREATE INDEX idx_nh_orders_merch ON neighborhub_orders(merchant_id, state);
            CREATE INDEX idx_nh_orders_cour ON neighborhub_orders(courier_id, state);
            CREATE INDEX idx_nh_orders_state_time ON neighborhub_orders(state, created_at);
            CREATE INDEX idx_nh_cour_geo ON neighborhub_couriers(status, latitude, longitude);
            CREATE INDEX idx_nh_track_order ON neighborhub_delivery_tracking(order_id, created_at);
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
            'neighborhub_merchants' => [
                'id', 'user_id', 'business_name', 'description', 'keywords', 'store_hours', 'address',
                'city', 'state', 'zip_code', 'latitude', 'longitude', 'location', 'phone', 'email',
                'messenger', 'website', 'facebook', 'google', 'image_url', 'platform_flat_fee',
                'platform_fee_rate', 'stripe_api_key', 'stripe_percent_fee', 'stripe_flat_fee', 'status',
                'sandbox_mode', 'delivery_assignment_mode', 'delivery_max_distance', 'type', 'meta',
                'created_at', 'updated_at',
            ],
            'neighborhub_customers' => [
                'id', 'user_id', 'display_name', 'delivery_locations', 'phone', 'terms_accepted_at',
                'order_notes', 'rating', 'status', 'type', 'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_registration_tokens' => [
                'id', 'email', 'user_id', 'token_hash', 'expires_at', 'verified_at', 'created_at',
            ],
            'neighborhub_registration_attempts' => ['id', 'ip_hash', 'created_at'],
            'neighborhub_merchant_users' => [
                'id', 'merchant_id', 'user_id', 'staff_role', 'invited_at', 'joined_at', 'status',
                'type', 'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_products' => [
                'id', 'merchant_id', 'name', 'description', 'price', 'id_required', 'tags', 'sku',
                'is_available', 'image_url', 'type', 'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_menus' => [
                'id', 'merchant_id', 'name', 'description', 'is_active', 'sort_order', 'status', 'type',
                'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_menu_categories' => [
                'id', 'menu_id', 'name', 'sort_order', 'status', 'type', 'meta', 'created_at',
            ],
            'neighborhub_menu_items' => [
                'id', 'category_id', 'product_id', 'override_price', 'is_available', 'sort_order',
                'type', 'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_couriers' => [
                'id', 'user_id', 'business_name', 'phone', 'vehicle_type', 'status', 'latitude',
                'longitude', 'location', 'last_location_update', 'total_deliveries', 'rating', 'type',
                'meta', 'created_at', 'updated_at',
            ],
            'neighborhub_orders' => [
                'id', 'order_number', 'customer_id', 'merchant_id', 'courier_id', 'subtotal_amount',
                'processing_fee', 'platform_fee', 'sales_tax', 'delivery_fee', 'tips', 'total_amount',
                'payment_method', 'state', 'stripe_payment_intent_id', 'delivery_assignment_mode',
                'locked_by_courier_id', 'locked_at', 'delivery_requested', 'pickup_address',
                'delivery_address', 'order_phone', 'order_notes', 'sandbox_mode', 'created_at',
                'confirmed_at', 'ready_at', 'picked_up_at', 'delivered_at', 'cancelled_at', 'updated_at',
                'type', 'meta',
            ],
            'neighborhub_order_items' => [
                'id', 'order_id', 'product_id', 'quantity', 'customer_notes', 'price_at_order',
                'subtotal', 'type', 'meta',
            ],
            'neighborhub_images' => [
                'id', 'parent_type', 'parent_id', 'image_url', 'type', 'meta', 'sort_order', 'created_at',
            ],
            'neighborhub_delivery_tracking' => [
                'id', 'order_id', 'courier_id', 'latitude', 'longitude', 'location', 'status_update',
                'details', 'type', 'meta', 'created_at',
            ],
            'neighborhub_webrtc_sessions' => [
                'id', 'session_id', 'initiator_role', 'initiator_id', 'target_role', 'target_id',
                'offer_sdp', 'answer_sdp', 'status', 'created_at', 'updated_at',
            ],
            'neighborhub_usage_events' => [
                'id', 'event_type', 'actor_role', 'merchant_id', 'properties', 'dedupe_key', 'created_at',
            ],
        ], [
            'neighborhub_merchants' => [['user_id', 'users', 'id']],
            'neighborhub_registration_tokens' => [['user_id', 'users', 'id']],
            'neighborhub_merchant_users' => [
                ['merchant_id', 'neighborhub_merchants', 'id'],
                ['user_id', 'users', 'id'],
            ],
            'neighborhub_products' => [['merchant_id', 'neighborhub_merchants', 'id']],
            'neighborhub_menu_categories' => [['menu_id', 'neighborhub_menus', 'id']],
            'neighborhub_menu_items' => [
                ['category_id', 'neighborhub_menu_categories', 'id'],
                ['product_id', 'neighborhub_products', 'id'],
            ],
            'neighborhub_couriers' => [['user_id', 'users', 'id']],
            'neighborhub_orders' => [
                ['merchant_id', 'neighborhub_merchants', 'id'],
                ['courier_id', 'neighborhub_couriers', 'id'],
                ['locked_by_courier_id', 'neighborhub_couriers', 'id'],
            ],
            'neighborhub_order_items' => [
                ['order_id', 'neighborhub_orders', 'id'],
                ['product_id', 'neighborhub_products', 'id'],
            ],
            'neighborhub_delivery_tracking' => [
                ['order_id', 'neighborhub_orders', 'id'],
                ['courier_id', 'neighborhub_couriers', 'id'],
            ],
            'neighborhub_usage_events' => [['merchant_id', 'neighborhub_merchants', 'id']],
        ]);
    },
];
