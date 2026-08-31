# Neighborhub Application Architecture Plan (SQLite Fluid-Role Ledger Edition)

## TL;DR
Build Neighborhub as a single modular app within MediaBrain's /html/apps/neighborhub/ directory. Utilizing an additive fluid-role permission matrix, it allows verified users to toggle seamlessly between Customer, Merchant, and Courier dashboards. Access to specific merchant terminal views is cryptographically secured via a relational staff bridge table (neighborhub_merchant_users), while core consumer functions remain available to all profiles. Features a transactional API layer using SQLite IMMEDIATE transactions for safe courier state machine processing and integrates Wonder City Dispatch via Stitch's memory_anchors table.

---

## Phase 1: Core App Registration & Initialization

### 1.1 Create /html/apps/neighborhub/neighborhub.app.php
This file implements the three required hooks (info, init, render_body) following MediaBrain patterns. Ensure that the app's database connection initialization runs PRAGMA foreign_keys = ON; to enforce constraints in SQLite.

Key functions:
- neighborhub_info() – Returns app metadata (title: "Neighborhub", requires_auth: true, styles/scripts)
- neighborhub_init(&$app) – Context-aware routing ($page = get_var('p', 'dashboard'), $view = get_var('view', 'customer'))
- neighborhub_render_body() – Evaluates view parameters, confirms role badges, and renders the requested dashboard template.

Fluid Permission Matrix Routing:
All accounts in the system hold standard customer privileges by default (ordering food, tracking deliveries). Users navigate to specialized utility panels by appending a view argument to the controller (?app=neighborhub&p=dashboard&view=merchant), which initiates context-specific database verification:
1. customer (Default): Open access. Load active local shops, track personal orders.
2. merchant: Validated against the neighborhub_merchant_users staff matrix. Manages menu, fulfills orders, coordinates deliveries.
3. courier: Validated against presence in the neighborhub_couriers profile table. Accepts open town jobs, logs geolocation logs.

Merchant View Switching with Staff Verification:
When a neighbor switches to the merchant dashboard context (view=merchant):
1. Capture target merchant_id from the context or the user's default relationship.
2. Query: SELECT merchant_id, staff_role FROM neighborhub_merchant_users WHERE user_id = ? AND merchant_id = ? AND status = 'active'
3. If a valid relationship entry exists:
   - Cache context data: $_SESSION['user']['active_merchant_id'] = $merchant_id
   - Cache scope clearance: $_SESSION['user']['merchant_staff_role'] = $staff_role ('owner' or 'clerk')
   - Load store profile parameters and render the merchant console dashboard views.
4. If no relation record is verified:
   - Fail safe to standard customer dashboard routing layout.
   - Display warning notification: "Access Denied: You do not hold active staff clearance for this merchant storefront."
   - Explicitly purge active_merchant_id and merchant_staff_role from the active session scope.

Context-Specific Init Logic:
1. customer: Load customer orders, active merchants; clear active_merchant_id and merchant_staff_role from the active context.
2. merchant: Execute staff-to-merchant relationship queries, load store parameters, active orders ledger, menu catalog arrays.
3. courier: Query neighborhub_couriers to confirm driver status, parse active delivery routes, scan open dispatch pools.

---

## Phase 2: Security & Order State Management

### 2.1 Order Lifecycle State Machine (SQLite Atomic Processing)
Orders follow atomic state transitions to prevent multi-device race conditions (e.g., two couriers accepting the same order simultaneously).

States:
PENDING_CONFIRMATION -> CONFIRMED -> READY_FOR_PICKUP -> IN_TRANSIT -> DELIVERED
(or CANCELLED at any point before transit, FAILED at terminal state)

Atomic Update Protocol (SQLite Database File Isolation):
1. Open a direct SQLite immediate database transaction block.
2. Inject a BEGIN IMMEDIATE TRANSACTION; statement to instantly freeze write capabilities across competing connections.
3. Verify the order's state and courier availability parameters match expectations.
4. Write state mutation and courier bindings within a single execution instruction.
5. Commit transaction block to release the file lock safely.

Atomic Query Pattern Example (SQLite Integration):
$db->exec("BEGIN IMMEDIATE TRANSACTION;");
$order = $db->query("SELECT state, locked_by_courier_id FROM neighborhub_orders WHERE id = ?", [$order_id])->fetch();
if ($order && $order['state'] === 'READY_FOR_PICKUP' && empty($order['locked_by_courier_id'])) {
    $db->query("UPDATE neighborhub_orders SET state = 'IN_TRANSIT', locked_by_courier_id = ?, updated_at = datetime('now') WHERE id = ?", [$courier_id, $order_id]);
    $db->exec("COMMIT;");
} else {
    $db->exec("ROLLBACK;");
}

### 2.2 Courier Assignment Logic (Hybrid)
1. Automatic: Geographic proximity (calculate haversine distance, auto-assign nearest available courier).
2. Manual override: Merchant can manually select courier if auto-assignment blocked.
3. Assignment state: Store delivery_assignment_mode in neighborhub_orders table ('auto' | 'manual') for audit trail.

---

## Phase 3: SQLite SQL Schema Design

### 3.1 New Tables (SQLite Syntax Restructured)

neighborhub_merchants Table Schema:
CREATE TABLE neighborhub_merchants (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  business_name TEXT NOT NULL,
  address TEXT,
  latitude REAL,
  longitude REAL,
  phone TEXT,
  status TEXT DEFAULT 'active' CHECK(status IN ('active', 'paused', 'suspended')),
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

neighborhub_merchant_users Table Schema:
CREATE TABLE neighborhub_merchant_users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  merchant_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  staff_role TEXT DEFAULT 'clerk' CHECK(staff_role IN ('owner', 'clerk')),
  invited_at TEXT DEFAULT (datetime('now')),
  joined_at TEXT,
  status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'active', 'inactive')),
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE (merchant_id, user_id)
);
CREATE INDEX idx_nh_mu_user ON neighborhub_merchant_users(user_id, status);
CREATE INDEX idx_nh_mu_merch ON neighborhub_merchant_users(merchant_id, status);

neighborhub_products Table Schema:
CREATE TABLE neighborhub_products (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  merchant_id INTEGER NOT NULL,
  name TEXT NOT NULL,
  description TEXT,
  price REAL NOT NULL,
  category TEXT,
  is_available INTEGER DEFAULT 1 CHECK(is_available IN (0, 1)),
  image_url TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE
);
CREATE INDEX idx_nh_prod_merch ON neighborhub_products(merchant_id, is_available);

neighborhub_orders Table Schema:
CREATE TABLE neighborhub_orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_number TEXT UNIQUE NOT NULL,
  customer_id INTEGER NOT NULL,
  merchant_id INTEGER NOT NULL,
  courier_id INTEGER,
  total_amount REAL NOT NULL,
  state TEXT DEFAULT 'PENDING_CONFIRMATION' CHECK(state IN ('PENDING_CONFIRMATION', 'CONFIRMED', 'READY_FOR_PICKUP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED', 'FAILED')),
  delivery_assignment_mode TEXT DEFAULT 'auto' CHECK(delivery_assignment_mode IN ('auto', 'manual')),
  locked_by_courier_id INTEGER,
  locked_at TEXT,
  pickup_address TEXT,
  delivery_address TEXT,
  customer_notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  confirmed_at TEXT,
  ready_at TEXT,
  picked_up_at TEXT,
  delivered_at TEXT,
  cancelled_at TEXT,
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (merchant_id) REFERENCES neighborhub_merchants(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL,
  FOREIGN KEY (locked_by_courier_id) REFERENCES neighborhub_couriers(id) ON DELETE SET NULL
);
CREATE INDEX idx_nh_orders_num ON neighborhub_orders(order_number);
CREATE INDEX idx_nh_orders_cust ON neighborhub_orders(customer_id, state);
CREATE INDEX idx_nh_orders_merch ON neighborhub_orders(merchant_id, state);
CREATE INDEX idx_nh_orders_cour ON neighborhub_orders(courier_id, state);
CREATE INDEX idx_nh_orders_state_time ON neighborhub_orders(state, created_at);

neighborhub_order_items Table Schema:
CREATE TABLE neighborhub_order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL,
  price_at_order REAL NOT NULL,
  subtotal REAL NOT NULL,
  FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES neighborhub_products(id)
);

neighborhub_couriers Table Schema:
CREATE TABLE neighborhub_couriers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL UNIQUE,
  business_name TEXT,
  phone TEXT,
  vehicle_type TEXT DEFAULT 'car' CHECK(vehicle_type IN ('bike', 'scooter', 'car', 'van')),
  status TEXT DEFAULT 'offline' CHECK(status IN ('available', 'on_delivery', 'offline')),
  latitude REAL,
  longitude REAL,
  last_location_update TEXT,
  total_deliveries INTEGER DEFAULT 0,
  rating REAL,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_nh_cour_geo ON neighborhub_couriers(status, latitude, longitude);

neighborhub_delivery_tracking Table Schema:
CREATE TABLE neighborhub_delivery_tracking (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  courier_id INTEGER NOT NULL,
  latitude REAL,
  longitude REAL,
  status_update TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE CASCADE
);
CREATE INDEX idx_nh_track_order ON neighborhub_delivery_tracking(order_id, created_at);

---

## Phase 4: Model Classes & Data Access Layer

### 4.1 Directory Structure
/html/apps/neighborhub/
├── neighborhub.app.php
├── neighborhub.api.php
├── css/
│   └── neighborhub.css
├── js/
│   ├── neighborhub.js
│   └── polling.js
├── views/
│   ├── customer/
│   │   ├── dashboard.php
│   │   ├── order_detail.php
│   │   └── browse_merchants.php
│   ├── merchant/
│   │   ├── dashboard.php
│   │   ├── menu_management.php
│   │   └── pending_orders.php
│   ├── courier/
│   │   ├── dashboard.php
│   │   └── active_deliveries.php
│   └── wondercity/
│       └── dispatch_feed.php
└── includes/
    └── models/
        ├── Order.php
        ├── Merchant.php
        ├── Courier.php
        ├── Product.php
        └── DeliveryTracking.php

---

## Phase 5: API Routing Layer

### 5.1 Create /html/apps/neighborhub/neighborhub.api.php
Implements action-based routing. Every endpoint confirms relational permission bounds before committing database operations. Ensure sqlite statements pass proper connection references.

### 5.2 Merchant Staff Authorization Helper
All merchant actions must validate user has active staff record.

Execution Code Example:
function validateMerchantStaff($userId, $merchantId) {
  $stmt = $GLOBALS['db']->prepare("SELECT id, staff_role FROM neighborhub_merchant_users WHERE user_id = ? AND merchant_id = ? AND status = 'active'");
  $stmt->execute([$userId, $merchantId]);
  $result = $stmt->fetch();
  if (!$result) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized: No active staff record for this merchant']));
  }
  return $result;
}

---

## Phase 6: Wonder City Dispatch Integration
Wonder City Dispatch queries memory_anchors table where content_type = 'wonder_city_dispatch'.

---

## Phase 7: Authentication & Role Authorization

### 7.1 Unified Session Initialization
The identity system drops rigid isolated delivery groups. Every account initializes into standard platform user space, granting dynamic, additive badge authorization capabilities:
$_SESSION['user'] = [
  'id' => user_id,
  'username' => username,
  'role' => 'user'
];

---

## Phase 8: Verification Checklist

1. Database Relations: Verification ledger tables establish proper foreign connections without data leaks using SQLite CHECK constraints.
2. Dynamic Context Switching: Toggling interface modes (customer, courier, merchant) processes fluidly via URL parameters without wiping active user session data.
3. Omnipresent Consumer Utilities: Store owners and courier drivers maintain standard access privileges to generate new consumer purchases while on shift.
4. Staff Scope Containment: The system safely rejects any attempt by a non-authorized user to view or manage a merchant's private terminal workspace.
5. Atomic Assignment Integrity: Concurrent worker accept attempts trigger SQLite BEGIN IMMEDIATE TRANSACTION file locks, successfully mitigating double-assignment conflicts.
6. Sovereign Local Ledgers: Orders complete processing using offline peer-to-peer balance tracking or physical Cash on Delivery hooks.

C:\Users\jeff\AppData\Roaming\Code\User\workspaceStorage\a9464f262351faf80e322668cab04afd\GitHub.copilot-chat\memory-tool\memories\repo\neighborhub.plan.md