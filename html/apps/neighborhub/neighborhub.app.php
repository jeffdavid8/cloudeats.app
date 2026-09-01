<?php
if (!defined('MB_RUNNING')) exit;

use Google\ApiCore\ArrayTrait;

/**
 * Neighborhub Application Front-Controller
 * 
 * Implements the three required MediaBrain hooks:
 * - neighborhub_info()
 * - neighborhub_init()
 * - neighborhub_render_body()
 * 
 * Provides fluid role-based context switching with atomic
 * permission matrix verification against SQLite staff ledgers.
 */

/**
 * Hook: neighborhub_info()
 * 
 * Returns application metadata and configuration attributes.
 * Called by the MediaBrain app loader during plugin discovery.
 * 
 * @return array Application metadata
 */
function neighborhub_info(&$app)
{

  return array(
    'db_type' => 'mysql',
    'title' => "Cloud Eats",
    'description' => "Local food, local products, local services, local businesses, local people.",
    'image' => $app->config['base_url'] . '/apps/neighborhub/images/neighborhub-app-index-logo-lg.png',
    'image_height' => '752',
    'image_width' => '1424',
    'requires_auth' => false,
    'requires_admin' => false,
    'no_header' => false,
    'public_app' => true,
    'version' => "0.1",
    'favicon' => array(
      'favicon' => $app->config['base_url'] . '/images/favicon.ico',
      'favicon-32x32' => $app->config['base_url'] . '/images/favicon-32x32.png',
      'favicon-16x16' => $app->config['base_url'] . '/images/favicon-16x16.png',
      'android-chrome-512x512' => $app->config['base_url'] . '/images/android-chrome-512x512.png',
      'android-chrome-192x192' => $app->config['base_url'] . '/images/android-chrome-192x192.png',
      'apple-touch-icon-180x180' => $app->config['base_url'] . '/images/apple-touch-icon.png',
    ),
    'styles' => array(
      './css/leaflet.css',
      'apps/neighborhub/css/neighborhub.css',
    ),
    'scripts' => array(
      'apps/neighborhub/js/neighborhub.init.js',
      'apps/neighborhub/js/nh.audio.js',
      //'./js/vis-network.min.js',
      './js/leaflet.js',
      //'https://www.youtube.com/iframe_api',
    ),
  );
}

/**
 * Hook: neighborhub_init()
 * 
 * Initializes application context, routes pages, and prepares
 * the active view layer. Performs role-based permission matrix
 * verification and session state management.
 * 
 * @param object $app MediaBrain application context reference
 * @return void
 */
function neighborhub_init(&$app)
{
  // Extract routing parameters from request
  $app->includeModel('customer');
  $app->includeModel('merchant');
  $app->includeModel('menu'); // Include class if using custom loader
  $app->includeModel('menucategory'); // Include class if using custom loader
  $app->includeModel('menuitem'); // Include class if using custom loader
  $app->includeModel('product');
  $customer = 0;
  if ($app->user->id) {
    $customer = Customer::getCustomerByUserId($app->user->id);
    if (!$customer) {
      $new_customer = array(
        'user_id' => $app->user->id,
        'display_name' => '',
        'phone' => '',
        'status' => 'active',
        'delivery_locations' => null,
      );
      $newCustomerId = Customer::create($new_customer);
      $customer = Customer::getCustomerById($newCustomerId);
    }
  }
  $app->set('customer', $customer);
  $landing_page = ((get_var('view', 'customer') === 'customer') && !$customer) ? 'onboarding' : 'dashboard';
  $page = isset($_GET['p']) ? sanitize_text_field($_GET['p']) : $landing_page;
  $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'customer';
  $app->set('page', $page);
  $app->set('view', $view);
  $merchantId = isset($_GET['merchant_id']) ? intval($_GET['merchant_id']) : null;

  // Validate view parameter against allowed values
  $allowed_views = array('admin', 'public', 'customer', 'merchant', 'courier', 'wondercity');
  if (!in_array($view, $allowed_views)) {
    $view = 'customer';
  }

  App::getInstance('neighborhub')->includeClass('assetmanager');

  // Store routing context in app object for use in render_body
  $app->set('page', $page);
  $app->set('current_view', $view);
  $app->set('user_id', $_SESSION['user']['id']);
  $app->set('merchant_id', $merchantId);
  $meta = array(
    'image' => config('base_url') . '/images/android-chrome-512x512.png',
    'image_width' => '600',
    'image_height' => '600',
  );
  $day_night_mode = (isset($_COOKIE['day_night_mode'])) ? $_COOKIE['day_night_mode'] : 'day';
  $app->set('night_mode_class', $day_night_mode . 'Mode');

  $scripts = array();
  $styles = array();


  // Initialize view-specific context and permissions
  switch ($view) {

    case 'admin':
      $scripts = array(
        'apps/neighborhub/js/gallery_manager.js',
        'apps/neighborhub/js/polling.js',
        'js/HubMeshNode.js'
      );
      neighborhub_init_admin_context($app);
      break;

    case 'merchant':
      //error_log(print_r($app->config, true));
      if ($page == 'screen') {
        $app->app_config['no_header'] = true;
      }
      neighborhub_init_merchant_context($app, $merchantId);
      $styles = array(
        'apps/neighborhub/css/merchant.css',
      );
      $scripts = array(
        'apps/neighborhub/js/multiTenantShoppingCart.js',
        'apps/neighborhub/js/merchantStorefront.js',
        'apps/neighborhub/js/customOrderBuilder.js',
        'apps/neighborhub/js/gallery_manager.js',
        'apps/neighborhub/js/polling.js',
        'js/HubMeshNode.js'
      );
      break;

    case 'courier':
      $app->includeClass('HubRouteFactory');
      $scripts = array(
        'apps/neighborhub/js/gallery_manager.js',
        'apps/neighborhub/js/polling.js',
        'apps/neighborhub/js/hubGuidanceEngine.js',
        'js/HubMeshNode.js'
      );
      neighborhub_init_courier_context($app);
      break;

    case 'wondercity':
      neighborhub_init_wonder_city_context($app);
      break;

    case 'customer':
    default:
      $scripts = array(
        'apps/neighborhub/js/multiTenantShoppingCart.js',
        'apps/neighborhub/js/merchantStorefront.js',
        'apps/neighborhub/js/gallery_manager.js',
        'apps/neighborhub/js/polling.js',
        'apps/neighborhub/js/customOrderBuilder.js',
        'js/HubMeshNode.js'
      );
      neighborhub_init_customer_context($app);
      break;
  }

  $app->app_info['scripts'] = array_merge($app->app_info['scripts'], $scripts);
  $app->app_info['styles'] = array_merge($app->app_info['styles'], $styles);
}


/**
 * Hook: neighborhub_render_body()
 * 
 * Renders the appropriate view template based on the resolved
 * active view context (customer, merchant, courier, or wondercity).
 * 
 * @return void
 */
function neighborhub_render_body(&$app)
{
  // Get active view context from app instance
  $currentView = $app->get('current_view', 'customer');
  $currentPage = $app->get('page');
  $merchant = $app->get('merchant');
  $merchantId = $app->get('merchant_id', null);

  if (($app->get('show_header_shopping_basket')) && ($merchantId) && ($merchant->status == 'online')) {
    render('components/shopping_cart_init_js.php');
    render('components/modals/leaflet_address_picker_modal.php');
    render('components/sidenav/shopping_cart.php', array('classList' => 'sidenav right-aligned'));
  }

  echo '<div class="app-container neighborhub-app-container">';

  // Determine template path based on view and page
  $templatePath = null;
  $vars = [];

  switch ($currentView) {

    case 'admin':
      $adminDir = 'admin/';
      switch ($currentPage) {
        case 'add_merchant':
          $templatePath = $adminDir . 'add_merchant.php';
          break;
        case 'edit_merchant':
          $app->includeModel('merchant');
          $merchantId = get_var('merchant_id', -1);
          $merchant = Merchant::getMerchantWithGallery($merchantId, 'object');
          if (!$merchant) {
            $merchant = new Merchant();
          }
          //error_log(print_r($merchant, true));
          $templatePath = $adminDir . 'edit_merchant.php';
          $owner_email = User::getById($merchant->user_id)->email;
          $vars = array(
            'merchant_id' => $merchantId,
            'merchant' => $merchant,
            'owner_email' => $owner_email
          );
          break;
        case 'add_courier':
          $templatePath = $adminDir . 'add_courier.php';
          break;
        case 'edit_courier':
          $app->includeModel('courier');
          $courierId = get_var('courier_id', null);
          $courier = courier::getCourierById($courierId);
          $templatePath = $adminDir . 'edit_courier.php';
          $courier_email = User::getById($courier['user_id']->email);
          $vars = array(
            'courier' => $courier,
            'courier_email' => $courier_email
          );
          break;
        case 'overview_map':
          $templatePath = $adminDir . 'overview_map.php';
          break;
        case 'dashboard':
        default:
          $templatePath = $adminDir . 'admin_dashboard.php';
          break;
      }
      break;

    case 'merchant':
      $merchantDir = 'merchant/';
      switch ($currentPage) {
        case 'pos':
          $templatePath = $merchantDir . 'pos.php';
          break;
        case 'merchant_management':
          $templatePath = $merchantDir . 'merchant_management.php';
          $db = $app->db;

          // Context Capture: Safely get current merchant context (e.g., from query string or default fallback)
          $merchantId = get_var('merchant_id', null);

          // 2. Fetch Merchant Configuration Data
          $merchant_stmt = $db->prepare("SELECT * FROM neighborhub_merchants WHERE id = ? LIMIT 1");
          $merchant_stmt->execute([$merchantId]);
          $merchant = $merchant_stmt->fetch(PDO::FETCH_ASSOC);

          if (!$merchant) {
            die("Merchant account not found.");
          }

          // 3. Fetch Active Staff Roster with a user table join to get names/emails
          $staff_stmt = $db->prepare("
              SELECT mu.user_id, mu.staff_role, u.email, u.username 
              FROM neighborhub_merchant_users mu
              JOIN users u ON mu.user_id = u.id
              WHERE mu.merchant_id = ? AND mu.status = 'active'
          ");
          $staff_stmt->execute([$merchantId]);
          $staff_roster = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

          $vars = array(
            'merchant' => $merchant,
            'staff_roster' => $staff_roster,
          );

          break;
        case 'products':
          $templatePath = $merchantDir . 'products.php';
          break;
        case 'pending_orders':
          $templatePath = $merchantDir . 'pending_orders.php';
          break;
        case 'screen':
          $templatePath = $merchantDir . 'merchant_screen.php';
          break;
        case 'menus':
          $templatePath = $merchantDir . 'merchant_menus.php';
          break;
        case 'dashboard':
        default:
          $templatePath = $merchantDir . 'merchant_dashboard.php';
          break;
      }
      break;

    case 'courier':
      $courierDir = 'courier/';
      switch ($currentPage) {
        case 'active_deliveries':
          $templatePath = $courierDir . 'active_deliveries.php';
          break;
        case 'dashboard':
        default:
          $templatePath = $courierDir . 'courier_dashboard.php';
          break;
      }
      break;

    case 'wondercity':
      $wondercityDir = 'wondercity/';
      switch ($currentPage) {
        case 'dispatch_feed':
        case 'dashboard':
        default:
          $templatePath = $wondercityDir . 'dispatch_feed.php';
          break;
      }
      break;

    case 'customer':
      $customerDir = 'customer/';
      switch ($currentPage) {
        case 'browse_merchants':
          $templatePath = $customerDir . 'browse_merchants.php';
          break;

        case 'merchant_products': // Changed to merchant_items for generic compatibility
          $merchantId = get_var('merchant_id', false);
          $customMechantViewOverrideDir = $customerDir . 'merchant_' . $merchantId . '/';

          if ($merchantId && file_exists($app->app_dir . '/views/pages/' . $customMechantViewOverrideDir . $currentPage . '.php')) {
            $templatePath = $customMechantViewOverrideDir . $currentPage . '.php';
          } else {
            $templatePath = $customerDir . $currentPage . '.php';
          }
          break;

        case 'order_detail':
          $templatePath = $customerDir . 'order_detail.php';
          break;
        case 'dashboard':
          $templatePath = $customerDir . 'customer_dashboard.php';
          break;

        default:
          $templatePath = $customerDir . $currentPage . '.php';
          break;
      }
      break;

    case 'public':
      switch ($currentPage) {

        case 'public.splash':
          $templatePath = 'public.splash.php';
          break;

        default:
          break;
      }
      break;


    default:
      break;
  }
//logger("Rendering template for view: $currentView, page: $currentPage, templatePath: $templatePath");
logger($app->dir . '/views/pages/' . $currentPage . '.php');
  // Render the template if it exists
  if ($templatePath && file_exists($app->app_path . '/views/pages/' . $templatePath)) {
    render('pages/' . $templatePath, $vars);
  } elseif (file_exists($app->dir . '/views/pages/' . $currentPage . '.php')) {
    render('pages/' . $currentPage . '.php', $vars);
  }
  else {
    // Fallback error message
    echo '<div class="nh-alert nh-alert-error">';
    echo '<div class="nh-alert-icon">✕</div>';
    echo '<div class="nh-alert-content">';
    echo '<h3>Template Not Found</h3>';
    echo '<p>The requested view template could not be located.</p>';
    echo '<p>Expected path: <code>' . htmlspecialchars($app->app_path . '/views/pages/' . $templatePath) . '</code></p>';
    echo '</div>';
    echo '</div>';
    error_log("Template not found: " . $app->app_path . '/views/pages/' . $templatePath);
  }

  echo '</div>';
}


/**
 * Initialize admin view context
 * 
 * Admin view is always accessible to admin users.
 * 
 * 
 * @param object $app MediaBrain application context
 * @return void
 */
function neighborhub_init_admin_context(&$app)
{
  $app->set('user_role_badge', 'admin');
  $page = get_var('page', 'dashboard');
  $db = $app->db;
  $user_id = $app->user->id;
  $merchantId = null;
  // 2. Ensure $db is a valid PDO object before querying attributes
  if ($db instanceof PDO) {
    try {
      $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

      if ($driver !== 'mysql') {
        // Handle incompatibility or log a warning
        error_log("Neighborhub Warning: Expected 'mysql' driver, but detected '{$driver}'. Running in legacy fallback mode.");

        // Optional: Force execution termination if MySQL is strictly required
        /*
                http_response_code(500);
                exit(json_encode([
                    'success' => false,
                    'error' => 'System configuration mismatch: MySQL/MariaDB database engine required.'
                ]));
                */
      } else {
        // Database is confirmed to be MySQL/MariaDB.
        // Safely execute engine-specific commands or setup configurations.
        // (e.g., setting session timezones, SQL modes, etc.)
        $db->exec("SET time_zone = '+00:00';");
      }
    } catch (PDOException $e) {
      error_log("Failed to inspect PDO database driver context: " . $e->getMessage());
    }
  } else {
    error_log("Neighborhub Error: Database connection instance is not a valid PDO object.");
  }
  switch ($page) {
    case 'dashboard':
      // If the user is a platform administrator, pull cross-platform system ledgers
      if ($app->user->is_admin) {
        $app->includeModel('merchant');
        $app->includeModel('order');
        $app->includeModel('courier');
        $app->includeClass('assetmanager');

        // 1. Fetch system operational statistics
        $stats = array(
          'total_merchants' => 0,
          'pending_merchants' => 0,
          'total_orders' => 0,
          'active_couriers' => 0
        );

        // Calculate count metrics securely using PDO aggregates
        $qMerchants = $db->query("SELECT status, COUNT(*) as cnt FROM neighborhub_merchants GROUP BY status");
        while ($row = $qMerchants->fetch(PDO::FETCH_ASSOC)) {
          if ($row['status'] === 'active') $stats['total_merchants'] += $row['cnt'];
          if ($row['status'] === 'pending') $stats['pending_merchants'] = intval($row['cnt']);
        }
        $stats['total_merchants'] += $stats['pending_merchants']; // Combine for grand total

        $qOrders = $db->query("SELECT COUNT(*) as cnt FROM neighborhub_orders");
        if ($row = $qOrders->fetch(PDO::FETCH_ASSOC)) {
          $stats['total_orders'] = intval($row['cnt']);
        }

        $qCouriers = $db->query("SELECT COUNT(*) as cnt FROM neighborhub_couriers WHERE status = 'available'");
        if ($row = $qCouriers->fetch(PDO::FETCH_ASSOC)) {
          $stats['active_couriers'] = intval($row['cnt']);
        }
        $app->set('admin_stats', $stats);

        // 2. Load complete merchant rosters (Paginated or bounded)
        // Fetches all businesses to manage editing, staff assignment, and status approvals
        $allMerchants = Merchant::getAllMerchants(null, null, 0, 'business_name ASC', 'array');
        $app->set('admin_merchants_list', $allMerchants);

        break; // Exit case safely
      }

      // --- EXISTING MERCHANT STAFF / CUSTOMER DASHBOARD ROUTING CONTINUES BELOW ---
      $merchantId = $_SESSION['user']['active_merchant_id'] ?? null;
      if ($merchantId) {
        $app->includeModel('merchant');
        $app->includeModel('order');
        $merchantProfile = Merchant::getMerchantById($merchantId);
        $app->set('merchant', $merchantProfile);

        $pendingOrders = Order::getOrdersByMerchantId($merchantId, 'PENDING_CONFIRMATION', 50, 0, true, 'components/cards/pending_order_card.php');
        $confirmedOrders = Order::getOrdersByMerchantId($merchantId, 'CONFIRMED', 50, 0, true, 'components/cards/confirmed_order_card.php');
        $readyOrders = Order::getOrdersByMerchantId($merchantId, 'READY_FOR_PICKUP', 50, 0, true, 'components/cards/ready_order_card.php');
        $app->set('pending_orders', $pendingOrders);
        $app->set('confirmed_orders', $confirmedOrders);
        $app->set('ready_orders', $readyOrders);

        $staffTeam = Merchant::getStaffRelations($merchantId);
        $app->set('staff_team', $staffTeam);
      }
      break;

    default:
      break;
  }
}

/**
 * Initialize customer view context
 * 
 * Customer view is always accessible to authenticated users.
 * Load customer orders, available merchants, and general shop browsing context.
 * 
 * @param object $app MediaBrain application context
 * @return void
 */
function neighborhub_init_customer_context(&$app)
{
  try {
    $user_id = $app->user->id;
    $app->includeModel('merchant');
    $app->includeModel('order');
    $customer = $app->get('customer');
    $customer_id = ($customer) ? $customer->id : 0;

    $app->set('show_header_shopping_basket', true);
    $app->set('shopping_basket_class_list', 'sidenav right-aligned');

    $page = $app->get('page', 'dashboard');

    $action = get_var('action', false);

    switch ($action) {

      case 'checkout_success':
        $app->processAction('order/create.order');
        break;

      case 'checkout_cancelled':
        //$app->processAction('order/cancel.order');
        break;
    }

    // Clear any merchant-specific context from session
    if (isset($_SESSION['user']['active_merchant_id'])) {
      unset($_SESSION['user']['active_merchant_id']);
    }
    if (isset($_SESSION['user']['merchant_staff_role'])) {
      unset($_SESSION['user']['merchant_staff_role']);
    }

    // Load customer's recent orders
    $recentOrders = ($user_id) ? Order::getOrdersByCustomerId($customer_id) : array();
    //error_log(print_r($recentOrders, true));
    $app->set('customer_orders', $recentOrders ? $recentOrders : array());

    // Load available merchants (active status)
    $activeMerchants = Merchant::getAllMerchants(null, null, 0, 'business_name ASC', 'object');
    $app->set('available_merchants', $activeMerchants ? $activeMerchants : array());
    // Set role badge for UI display
    $app->set('user_role_badge', 'customer');
  } catch (Exception $e) {
    error_log("Customer context initialization failed: " . $e->getMessage());
    $app->set('customer_orders', array());
    $app->set('available_merchants', array());
  }

  $meta = array(
    'title' => 'CloudEats',
    'type' => 'article',
    'og:type' => 'article',
    'image' => config('base_url') . '/images/android-chrome-512x512.png',
    'image_width' => '600',
    'image_height' => '600',
  );

  if ($page == 'merchant_products') {

    $app->includeModel('product');
    $merchantId = get_var('merchant_id', false);
    $merchant = Merchant::getMerchantById($merchantId);
    $app->set('merchant', $merchant);
    $menus = Menu::getMenusWithProductsByMerchantId($merchantId);
    //error_log(print_r($menus, true));
    $app->set('menus', $menus);

    $products = Product::getProductsByMerchant($merchantId, true, 'array');
    $catalogProducts = [];
    foreach ($products as $p) {
      $tags = !empty($p['tags']) ? explode(',', $p['tags']) : [];
      if (!empty($tags)) {
        foreach ($tags as $tag) {
          $catalogProducts[$tag][] = $p;
        }
      }
    }
    $app->set('products', $products);
    // Unassigned / catalog items for the left-hand pool
    $productCatalog = [];
    foreach ($products as $p) {
      $tags = !empty($p['tags']) ? explode(',', $p['tags']) : [];
      if (!empty($tags)) {
        foreach ($tags as $tag) {
          $productCatalog[$tag][] = $p;
        }
      }
    }
    $app->set('productCatalog', $productCatalog);

    $image = $merchant->image_url;
    $image_info = getimagesize($image);
    list($width, $height, $image_type, $attr) = $image_info;
    $meta = array(
      'title' => $merchant->business_name,
      'type' => 'article',
      'og:type' => 'article',
      'image' => $image,
      'image_width' => $width,
      'image_height' => $height,
      'image_type' => $image_info['mime'],
    );

    $productId = get_var('product_id', false);
    $product = Product::getProductWithGallery($productId, 'object');
    if ($productId && !empty($product->image_url)) {
      $image = $product->image_url;
      $image_info = getimagesize($image);
      list($width, $height, $image_type, $attr) = $image_info;
      $title = $product->name . ' | ' . $merchant->business_name;

      $meta = array(
        'title' => htmlspecialchars($title, ENT_QUOTES, 'UTF-8'),
        //'title' => $product->name,
        'description' => $product->description,
        'type' => 'article',
        'og:type' => 'article',
        'image' => $image,
        'image_width' => $width,
        'image_height' => $height,
        'image_type' => $image_info['mime'],
      );
    }
  }
  if ($page == 'public.splash') {
    $image = 'images/neighborhub-app-index-logo-lg.png';
    $image_info = getimagesize($app->app_dir . '/' . $image);
    list($width, $height, $image_type, $attr) = $image_info;
    $meta = array(
      'title' => 'Neighborhub',
      'description' => 'Local food, local products, local services, local businesses, local people.',
      'type' => 'article',
      'og:type' => 'article',
      'image' =>  config('base_url') . '/apps/neighborhub/' . $image,
      'image_width' => $width,
      'image_height' => $height,
    );
  }

  $app->set('meta', $meta);
}

/**
 * Initialize merchant view context
 * 
 * Merchant view requires active staff relationship verification
 * via the neighborhub_merchant_users bridge table.
 * 
 * @param object $app MediaBrain application context
 * @param int $merchantId Optional merchant ID from URL parameter
 * @return void
 */
function neighborhub_init_merchant_context(&$app, $merchantId = null)
{
  try {
    $db = $app->db;
    $user_id = $app->user->id;
    $app->includeModel('merchant');
    $app->includeModel('prouduct');
    $app->includeModel('order');

    // Get target merchant ID from parameter or session
    $merchant_id = $merchantId ? intval($merchantId) : (
      isset($_SESSION['user']['active_merchant_id']) ? $_SESSION['user']['active_merchant_id'] : null
    );

    // If no merchant_id available, query for first active merchant relationship
    if (!$merchant_id) {
      $merchantListStmt = $db->prepare(
        "SELECT nm.id FROM neighborhub_merchants nm
                JOIN neighborhub_merchant_users nmu ON nm.id = nmu.merchant_id
                WHERE nmu.user_id = ? AND nmu.status = 'active' AND nm.status = 'active'
                LIMIT 1"
      );
      $merchantListStmt->execute([$user_id]);
      $merchantRecord = $merchantListStmt->fetch(PDO::FETCH_ASSOC);
      $merchant = Merchant::getMerchantById($merchantRecord['id'], 'object');
      $merchant_id = $merchantRecord['id'] ?? null;
    } else {
      $merchant = Merchant::getMerchantById($merchant_id, 'object');
    }

    $app->set('merchant', $merchant);

    // Init POS
    if ($app->get('page') === 'pos') {
      $app->set('show_header_shopping_basket', true);
      $app->set('header_shopping_basket_class_list', 'waves-effect waves-light shopping-cart-sidenav-trigger accent-4 shadow-lift round-header-action hide-on-large-only');
    }

    // If still no merchant_id available, fall back to customer view
    if (!$merchant_id) {
      $_SESSION['notification'] = array(
        'type' => 'warning',
        'message' => 'Access Denied: You must select a merchant location to access the merchant dashboard.'
      );
      $app->set('current_view', 'customer');
      neighborhub_init_customer_context($app);
      return;
    }

    // Query staff relationship from neighborhub_merchant_users
    $staffStmt = $db->prepare(
      "SELECT DISTINCT 
                    nm.id, nm.business_name, nmu.staff_role
                FROM neighborhub_merchants nm
                JOIN neighborhub_merchant_users nmu ON nm.id = nmu.merchant_id
                WHERE nm.user_id = ? AND nmu.merchant_id = ? AND nmu.status = 'active' AND nm.status = 'active'
                ORDER BY nm.business_name ASC LIMIT 1"

    );
    $staffStmt->execute([$user_id, $merchant_id]);
    $staffRecord = $staffStmt->fetch(PDO::FETCH_ASSOC);

    // Verify staff relationship exists and is active
    if ((!$staffRecord) && (!$app->user->is_admin)) {
      // Clear invalid context and display error
      if (isset($_SESSION['user']['active_merchant_id'])) {
        unset($_SESSION['user']['active_merchant_id']);
      }
      if (isset($_SESSION['user']['merchant_staff_role'])) {
        unset($_SESSION['user']['merchant_staff_role']);
      }

      $_SESSION['notification'] = array(
        'type' => 'error',
        'message' => 'Access Denied: You do not hold active staff clearance for this merchant storefront. Returning to customer view.'
      );
      $app->set('current_view', 'customer');
      neighborhub_init_customer_context($app);
      return;
    }

    // Cache verified staff context in session
    $_SESSION['user']['active_merchant_id'] = $merchant_id;
    $_SESSION['user']['merchant_staff_role'] = ($app->user->is_admin) ? 'Admin' : $staffRecord['staff_role'];

    // Load pending orders (CONFIRMED and READY_FOR_PICKUP states)
    $pendingOrders = Order::getOrdersByMerchantId($merchantId, 'PENDING_CONFIRMATION', 50, 0, true, 'components/cards/pending_order_card.php');
    $confirmedOrders = Order::getOrdersByMerchantId($merchantId, 'CONFIRMED', 50, 0, true, 'components/cards/confirmed_order_card.php');
    $readyOrders = Order::getOrdersByMerchantId($merchantId, 'READY_FOR_PICKUP', 50, 0, true, 'components/cards/ready_order_card.php');
    $app->set('pending_orders', $pendingOrders ? $pendingOrders : array());
    $app->set('confirmed_orders', $confirmedOrders ? $confirmedOrders : array());
    $app->set('ready_orders', $readyOrders ? $readyOrders : array());

    // Load merchant product catalog
    $productCatalog = Product::getProductsByMerchant($merchant_id);
    $app->set('product_catalog', $productCatalog ? $productCatalog : array());

    // Load staff team members (active only)
    $staffStmt = $db->prepare(
      "SELECT 
                mu.id, mu.user_id, mu.staff_role, mu.status, mu.joined_at,
                u.username, u.email
            FROM neighborhub_merchant_users mu
            JOIN users u ON mu.user_id = u.id
            WHERE mu.merchant_id = ? AND mu.status = 'active'
            ORDER BY mu.staff_role DESC, mu.joined_at ASC"
    );
    $staffStmt->execute([$merchant_id]);
    $staffTeam = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    $app->set('staff_team', $staffTeam ? $staffTeam : array());

    // Set role badge for UI display
    $app->set('user_role_badge', $_SESSION['user']['merchant_staff_role']);
  } catch (Exception $e) {
    error_log("Merchant context initialization failed: " . $e->getMessage());
    $_SESSION['notification'] = array(
      'type' => 'error',
      'message' => 'An error occurred while loading the merchant dashboard.'
    );
    $app->set('current_view', 'customer');
    neighborhub_init_customer_context($app);
  }
}

/**
 * Initialize courier view context
 * 
 * Courier view requires user registration in the neighborhub_couriers table.
 * 
 * @param object $app MediaBrain application context
 * @return void
 */
function neighborhub_init_courier_context(&$app)
{
  try {
    $db = $app->db;
    $user_id = $app->get('user_id');

    // Query courier profile from neighborhub_couriers
    $courierStmt = $db->prepare(
      "SELECT 
                id, user_id, business_name, phone, vehicle_type, status, 
                latitude, longitude, last_location_update, total_deliveries, rating, created_at, updated_at
            FROM neighborhub_couriers
            WHERE user_id = ?
            LIMIT 1"
    );
    $courierStmt->execute([$user_id]);
    $courierProfile = $courierStmt->fetch(PDO::FETCH_ASSOC);

    // Verify courier profile exists
    if (!$courierProfile) {
      $_SESSION['notification'] = array(
        'type' => 'error',
        'message' => 'Access Denied: You are not registered as a delivery driver. Contact support to activate courier access.'
      );
      $app->set('current_view', 'customer');
      neighborhub_init_customer_context($app);
      return;
    }

    // Cache courier ID in session
    $_SESSION['user']['courier_id'] = $courierProfile['id'];

    // Set profile in app context
    $app->set('courier_profile', $courierProfile);

    // Load available delivery jobs (READY_FOR_PICKUP, unlocked)
    $availableJobsStmt = $db->prepare(
      "SELECT 
                o.id, o.order_number, o.customer_id, o.merchant_id, o.total_amount,
                o.state, o.pickup_address, o.delivery_address, o.created_at,
                m.business_name, m.latitude as merchant_lat, m.longitude as merchant_lng
            FROM neighborhub_orders o
            JOIN neighborhub_merchants m ON o.merchant_id = m.id
            WHERE o.state = 'READY_FOR_PICKUP' AND o.locked_by_courier_id IS NULL
            ORDER BY o.created_at ASC
            LIMIT 50"
    );
    $availableJobsStmt->execute();
    $availableJobs = $availableJobsStmt->fetchAll(PDO::FETCH_ASSOC);
    $app->set('available_jobs', $availableJobs ? $availableJobs : array());

    // Load active courier deliveries (IN_TRANSIT assignments)
    $activeDeliveriesStmt = $db->prepare(
      "SELECT 
                o.id, o.order_number, o.customer_id, o.merchant_id, o.total_amount,
                o.state, o.pickup_address, o.delivery_address, o.locked_at, o.updated_at,
                m.business_name, m.latitude as merchant_lat, m.longitude as merchant_lng
            FROM neighborhub_orders o
            JOIN neighborhub_merchants m ON o.merchant_id = m.id
            WHERE o.courier_id = ? AND o.state = 'IN_TRANSIT'
            ORDER BY o.updated_at DESC"
    );
    $activeDeliveriesStmt->execute([$courierProfile['id']]);
    $activeDeliveries = $activeDeliveriesStmt->fetchAll(PDO::FETCH_ASSOC);
    $app->set('active_deliveries', $activeDeliveries ? $activeDeliveries : array());

    // Load recent delivery history (completed jobs)
    $historyStmt = $db->prepare(
      "SELECT 
                o.id, o.order_number, o.customer_id, o.merchant_id, o.total_amount,
                o.state, o.delivered_at, o.created_at,
                m.business_name
            FROM neighborhub_orders o
            JOIN neighborhub_merchants m ON o.merchant_id = m.id
            WHERE o.courier_id = ? AND o.state = 'DELIVERED'
            ORDER BY o.delivered_at DESC
            LIMIT 20"
    );
    $historyStmt->execute([$courierProfile['id']]);
    $deliveryHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    $app->set('delivery_history', $deliveryHistory ? $deliveryHistory : array());

    // Set role badge for UI display
    $app->set('user_role_badge', 'courier');
  } catch (Exception $e) {
    error_log("Courier context initialization failed: " . $e->getMessage());
    $_SESSION['notification'] = array(
      'type' => 'error',
      'message' => 'An error occurred while loading the courier dashboard.'
    );
    $app->set('current_view', 'customer');
    neighborhub_init_customer_context($app);
  }
}

/**
 * Initialize Wonder City dispatch view context (admin-only)
 * 
 * @param object $app MediaBrain application context
 * @return void
 */
function neighborhub_init_wonder_city_context(&$app)
{
  // Verify admin access
  if (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin']) {
    $_SESSION['notification'] = array(
      'type' => 'error',
      'message' => 'Access Denied: Wonder City Dispatch is admin-only.'
    );
    $app->set('current_view', 'customer');
    neighborhub_init_customer_context($app);
    return;
  }

  $app->set('user_role_badge', 'wondercity');
}

function neighborhub_render_card($filename, $data, $return = false)
{
  if ($return)
    return render('components/cards/' . $filename . '.php', $data, $return);
  else
    render('components/cards/' . $filename . '.php', $data);
}

/**
 * Utility helper: Sanitize text field input
 * 
 * @param string $input Raw input string
 * @return string Sanitized string
 */
function sanitize_text_field($input)
{
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
  return $input;
}

function neighborhub_db_tables()
{
  return array(
    'neighborhub_merchants',
    'neighborhub_merchant_users',
    'neighborhub_customers',
    'neighborhub_products',
    'neighborhub_menus',
    'neighborhub_menu_categories',
    'neighborhub_menu_items',
    'neighborhub_images',
    'neighborhub_orders',
    'neighborhub_order_items',
    'neighborhub_couriers',
    'neighborhub_delivery_tracking',
    'neighborhub_webrtc_sessions'
  );
}

/**
 * Database schema initialization for Neighborhub tables
 */
function neighborhub_install_db()
{
  $app = App::getInstance('neighborhub');
  $tableSql = "
-- Neighborhub MySQL/MariaDB Database Initialization Script
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS neighborhub_delivery_tracking;
DROP TABLE IF EXISTS neighborhub_images;
DROP TABLE IF EXISTS neighborhub_order_items;
DROP TABLE IF EXISTS neighborhub_orders;
DROP TABLE IF EXISTS neighborhub_couriers;
DROP TABLE IF EXISTS neighborhub_products;
DROP TABLE IF EXISTS neighborhub_menus;
DROP TABLE IF EXISTS neighborhub_menu_categories;
DROP TABLE IF EXISTS neighborhub_menu_items;
DROP TABLE IF EXISTS neighborhub_merchant_users;
DROP TABLE IF EXISTS neighborhub_merchants;
DROP TABLE IF EXISTS neighborhub_customers;
DROP TABLE IF EXISTS neighborhub_webrtc_sessions;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE neighborhub_merchants (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  business_name VARCHAR(255) NOT NULL,
  store_hours TEXT,
  address TEXT,
  latitude DOUBLE,
  longitude DOUBLE,
  phone VARCHAR(50),
  email VARCHAR(255),
  messenger VARCHAR(255),
  website VARCHAR(255),
  facebook VARCHAR(255),
  google VARCHAR(255),
  image_url VARCHAR(2048),
  menus TEXT,
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

CREATE TABLE IF NOT EXISTS neighborhub_menus (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  merchant_id INT UNSIGNED NOT NULL,
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

CREATE TABLE IF NOT EXISTS neighborhub_menu_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  menu_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(20) DEFAULT 'inactive' CHECK(status IN ('active', 'inactive')),
  type VARCHAR(50) DEFAULT 'default',
  meta JSON NOT NULL DEFAULT (JSON_OBJECT()),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (menu_id) REFERENCES neighborhub_menus(id) ON DELETE CASCADE,
  INDEX idx_menu_categories (menu_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS neighborhub_menu_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  product_id INT NOT NULL, -- Changed from INT UNSIGNED to INT
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
  payment_method VARCHAR(30) NOT NULL DEFAULT 'STRIPE',     -- 'CASH', 'EXTERNAL_CARD'
  state VARCHAR(50) NOT NULL DEFAULT 'PENDING_CONFIRMATION',
  stripe_payment_intent_id VARCHAR(255),
  delivery_assignment_mode VARCHAR(20) DEFAULT 'auto' CHECK(delivery_assignment_mode IN ('auto', 'manual', 'disabled')),
  locked_by_courier_id INT,
  locked_at DATETIME,
  pickup_address TEXT,
  delivery_address TEXT,
  order_phone VARCHAR(50),
  order_notes TEXT,
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
  status_update VARCHAR(255),
  details JSON NOT NULL,
  type VARCHAR(50) DEFAULT 'default',
  meta JSON NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES neighborhub_orders(id) ON DELETE CASCADE,
  FOREIGN KEY (courier_id) REFERENCES neighborhub_couriers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `neighborhub_webrtc_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(64) NOT NULL,
  `initiator_role` enum('admin','merchant','customer','courier') NOT NULL,
  `initiator_id` int(11) NOT NULL,
  `target_role` enum('admin','merchant','customer','courier') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `offer_sdp` text DEFAULT NULL,
  `answer_sdp` text DEFAULT NULL,
  `status` enum('waiting','offered','answered','connected','closed') DEFAULT 'waiting',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_id` (`session_id`),
  KEY `idx_lookup` (`target_role`, `target_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_nh_mu_user ON neighborhub_merchant_users(user_id, status);
CREATE INDEX idx_nh_mu_merch ON neighborhub_merchant_users(merchant_id, status);
CREATE INDEX idx_nh_prod_merch ON neighborhub_products(merchant_id, is_available);
CREATE INDEX idx_nh_prod_menu_cat ON neighborhub_products(merchant_id, menu, category, is_available);
CREATE INDEX idx_nh_orders_num ON neighborhub_orders(order_number);
CREATE INDEX idx_nh_orders_cust ON neighborhub_orders(customer_id, state);
CREATE INDEX idx_nh_orders_merch ON neighborhub_orders(merchant_id, state);
CREATE INDEX idx_nh_orders_cour ON neighborhub_orders(courier_id, state);
CREATE INDEX idx_nh_orders_state_time ON neighborhub_orders(state, created_at);
CREATE INDEX idx_nh_cour_geo ON neighborhub_couriers(status, latitude, longitude);
CREATE INDEX idx_nh_track_order ON neighborhub_delivery_tracking(order_id, created_at);
  ";
  $log = [];
  foreach (explode(';', $tableSql) as $q) {
    $q = trim($q);
    $cleaned = str_replace("\r\n", "\n", $q);
    $cleanSQL = preg_replace('/\s+/', ' ', $cleaned);
    if ($q) {
      try {
        error_log('-----------------------------------------------------');
        error_log('Running Neighborhub Query - ');
        error_log($cleanSQL);
        error_log('-----------------------------------------------------');
        $app->db->exec($q);

        $log[] = '-----------------------------------------------------';
        $log[] = "Running Neighborhub Query - ";
        $log[] = $cleanSQL;
        $log[] = '-----------------------------------------------------';
      } catch (PDOException $e) {
        error_log('-----------------------------------------------------');
        error_log($e->getMessage());
        error_log('-----------------------------------------------------');
        if (strpos($e->getMessage(), 'duplicate column name') === false) {
          error_log("Neighborhub Installer Warning: " . $e->getMessage());
          $log[] = "Neighborhub Installer Warning: " . $e->getMessage();
        }
      }
    }
  }
  return [
    'success' => true,
    'log'     => $log
  ];
}


function neighborhub_restore_db()
{
  $app = App::getInstance();
  $db = $app->db;
  $targetMap = array('neighborhub' => neighborhub_db_tables());

  $result = BackupManager::importFromJsonFile('./json/default_db.json', $targetMap);

  return $result;
}
