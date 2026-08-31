-- Neighborhub SQLite Database Initialization Script
-- Parent tables first, then dependent tables, indexes last

CREATE TABLE neighborhub_merchants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL UNIQUE,
  business_name TEXT NOT NULL,
  address TEXT,
  latitude REAL,
  longitude REAL,
  phone TEXT,
  status TEXT DEFAULT 'active' CHECK(status IN ('active', 'paused', 'suspended')),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE neighborhub_merchant_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  staff_role TEXT DEFAULT 'clerk' CHECK(staff_role IN ('owner', 'clerk')),
  invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  joined_at DATETIME,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'active', 'inactive')),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (merchant_id, user_id)
);

CREATE TABLE neighborhub_products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  price REAL NOT NULL,
  category TEXT,
  is_available INTEGER DEFAULT 1 CHECK(is_available IN (0, 1)),
  image_url TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE
);

CREATE TABLE neighborhub_couriers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INTEGER NOT NULL UNIQUE,
  business_name TEXT,
  phone TEXT,
  vehicle_type TEXT DEFAULT 'car' CHECK(vehicle_type IN ('bike', 'scooter', 'car', 'van')),
  status TEXT DEFAULT 'offline' CHECK(status IN ('available', 'on_delivery', 'offline')),
  latitude REAL,
  longitude REAL,
  last_location_update DATETIME,
  total_deliveries INTEGER DEFAULT 0,
  rating REAL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE neighborhub_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_number TEXT UNIQUE NOT NULL,
  customer_id INTEGER NOT NULL,
  merchant_id INTEGER NOT NULL,
  courier_id INTEGER,
  total_amount REAL NOT NULL,
  state TEXT DEFAULT 'PENDING_CONFIRMATION' CHECK(state IN ('PENDING_CONFIRMATION', 'CONFIRMED', 'READY_FOR_PICKUP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED', 'FAILED')),
  delivery_assignment_mode TEXT DEFAULT 'auto' CHECK(delivery_assignment_mode IN ('auto', 'manual')),
  locked_by_courier_id INTEGER,
  locked_at DATETIME,
  pickup_address TEXT,
  delivery_address TEXT,
  customer_notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  confirmed_at DATETIME,
  ready_at DATETIME,
  picked_up_at DATETIME,
  delivered_at DATETIME,
  cancelled_at DATETIME,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL,
  FOREIGN KEY (locked_by_courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL
);

CREATE TABLE neighborhub_order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL,
  price_at_order REAL NOT NULL,
  subtotal REAL NOT NULL,
  FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES neighborhub_products(id)
);

CREATE TABLE neighborhub_delivery_tracking (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INTEGER NOT NULL,
  courier_id INTEGER NOT NULL,
  latitude REAL,
  longitude REAL,
  status_update TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE CASCADE
);

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