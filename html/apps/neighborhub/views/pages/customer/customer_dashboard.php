<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Customer Dashboard
 * 
 * Displays merchant browser, product selection, and order tracking ledger
 * with live polling for real-time order status updates.
 * 
 * Context variables available:
 * @var Object $app
 * - $_SESSION['user']['id'] - authenticated customer ID
 * - $app->get('available_merchants') - active merchant list
 * - $app->get('customer_orders') - recent customer orders
 */
$app = App::getInstance();
$customer = $app->get('customer');
$customerId = $customer->id ?? 0;
$userName = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Customer';
$availableMerchants = $this->get('available_merchants', array());
$customerOrders = $this->get('customer_orders', array());

if (isset($_SESSION[get_var('session_key')]) && get_var('action', false) == 'checkout_success') {
  $pendingOrder = $_SESSION[get_var('session_key')];
  $merchant_id = $pendingOrder['merchant_id'];
  unset($_SESSION[get_var('session_key')]);
?>
  <script>
    $(document).ready(function() {
      NHCart.activeMerchantId = <?= ($merchant_id) ? $merchant_id : 'null' ?>;
      NHCart.clear();
    });
  </script>
<?
}

// Display any session notifications
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
if ($notification) {
  unset($_SESSION['notification']);
}
?>

<div class="nh-wrapper">
<? /* 
  <!-- Sticky Role Header with Navigation -->
  <header class="nh-role-header">
    <div class="nh-container">
      <div class="nh-role-header-content">`
        <div>
          <h1 style="margin: 0; font-size: 1.875rem;">Neighborhub</h1>
          <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Welcome<?= (isset($app->user->id)) ? ' back' : '' ?>, <?php echo $userName; ?>!</p>
        </div>
      </div>
    </div>
  </header>
 */ ?>

  <!-- Main Content Area -->
  <main class="nh-main">
    <div class="nh-container">

      <!-- Display Session Notifications -->
      <?php if ($notification): ?>
        <div class="nh-alert nh-alert-<?php echo htmlspecialchars($notification['type']); ?>" style="margin-bottom: 2rem;">
          <div class="nh-alert-icon">
            <?php if ($notification['type'] === 'success'): ?>
              ✓
            <?php elseif ($notification['type'] === 'error'): ?>
              ✕
            <?php elseif ($notification['type'] === 'warning'): ?>
              ⚠
            <?php else: ?>
              ℹ
            <?php endif; ?>
          </div>
          <div class="nh-alert-content">
            <p class="nh-alert-message"><?php echo htmlspecialchars($notification['message']); ?></p>
          </div>
        </div>
      <?php endif; ?>

      <!-- Product Selection Panel (Hidden by Default) -->
      <section id="product-panel" class="nh-content" style="margin-bottom: 4rem; display: none; padding: 2rem;">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
          <div>
            <h2 id="merchant-name-display" style="margin: 0 0 0.5rem 0;">Merchant Products</h2>
            <p id="merchant-address-display" style="margin: 0; color: var(--gray-500); font-size: 0.875rem;"></p>
          </div>
          <button type="button" class="nh-btn nh-btn-secondary" onclick="closeMerchantPanel()">Close</button>
        </div>

        <div class="nh-grid nh-grid-4" id="product-grid">
          <!-- Products will be loaded here via AJAX -->
        </div>

        <!-- Checkout Form (Appears when products are selected) -->
        <div id="checkout-form-container" style="display: none; margin-top: 3rem; padding-top: 3rem; border-top: 2px solid var(--border-color);">

          <h3>Order Summary</h3>

          <div id="checkout-items-summary" style="background: var(--gray-50); border-radius: var(--border-radius-base); padding: 1.5rem; margin-bottom: 2rem;">
            <table class="nh-table" style="margin: 0;">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Price</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody id="checkout-items-list">
                <!-- Items will be populated here -->
              </tbody>
            </table>
          </div>

          <div style="background: var(--gray-50); border-radius: var(--border-radius-base); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
              <span style="font-weight: 600;">Subtotal:</span>
              <span id="checkout-subtotal">$0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
              <span style="font-weight: 600;">Tax:</span>
              <span id="checkout-tax">$0.00</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 1rem;">
              <span style="font-weight: 700; font-size: 1.125rem;">Total:</span>
              <span id="checkout-total" style="font-weight: 700; font-size: 1.125rem;">$0.00</span>
            </div>
          </div>

          <div class="nh-form-group">
            <label for="delivery-address" class="nh-form-label">Delivery Address</label>
            <textarea id="delivery-address" class="nh-form-input" placeholder="Enter your delivery address..." style="min-height: 80px;"></textarea>
          </div>

          <div class="nh-form-group">
            <label for="order-notes" class="nh-form-label">Special Instructions (Optional)</label>
            <textarea id="order-notes" class="nh-form-input" placeholder="Any special requests or instructions..." style="min-height: 80px;"></textarea>
          </div>

          <div style="display: flex; gap: 1rem;">
            <button type="button" class="nh-btn nh-btn-danger" onclick="clearCart()" style="flex: 1;">Clear Cart</button>
            <button type="button" class="nh-btn" onclick="submitOrder()" style="flex: 1;">Place Order</button>
          </div>

        </div>

      </section>

      <!-- Active Tracking Ledger Section -->
      <section class="nh-tracking-ledger">
        <h2 style="margin-bottom: 2rem;">Your Orders</h2>

        <div class="nh-alert nh-alert-info<?= (!empty($customerOrders)) ? ' hide' : ''; ?>" style="">
          <div class="nh-alert-icon">ℹ</div>
          <div class="nh-alert-content">
            <p class="nh-alert-message"><?= (!$customerId) ? 'You are not logged in.  Please <a href="?p=login&return=' . $_SERVER['REQUEST_URI'] . '" />login</a> to save and review your orders.' : "You haven't placed any orders yet. Browse merchants above to get started!" ?></p>
          </div>
        </div>
        <div class="nh-content<?= (empty($customerOrders)) ? ' hide' : ''; ?>" style="overflow-x: auto; ">
          <table class="nh-table" id="orders-table">
            <thead>
              <tr>
                <th>Order Number</th>
                <th>Merchant</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Placed</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="orders-list">
              <? if (!empty($customerOrders)) :
                foreach ($customerOrders as $order):  ?>
                  <tr>
                    <td>
                      <span style="font-family: monospace; font-size: 0.875rem; color: var(--gray-500);">
                        <a href="javascript: void(0);" onclick="viewOrderDetail(<?= intval($order['id']) ?>)"><?php echo htmlspecialchars($order['order_number']); ?></a>
                      </span>
                    </td>
                    <td>
                      <?php
                      echo $order['business_name'] ? htmlspecialchars($order['business_name']) : 'Unknown';
                      ?>
                    </td>
                    <td>
                      $<?php echo number_format($order['total_amount'], 2); ?>
                    </td>
                    <td>
                      <span class="nh-badge badge-<?php echo strtolower(str_replace('_', '_', $order['state'])); ?>">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $order['state'])); ?>
                      </span>
                    </td>
                    <td>
                      <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                    </td>
                    <td>
                      <button type="button" class="nh-btn nh-btn-sm" onclick="viewOrderDetail(<?php echo intval($order['id']); ?>)">
                        View
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </section>
      <? /*
      */ ?>
      <!-- Merchant Browser Section -->
      <section class="nh-merchant-browser" style="margin-bottom: 4rem;">
        <h2 style="margin-bottom: 2rem;">Browse Local Merchants</h2>

        <?php if (empty($availableMerchants)): ?>
          <div class="nh-alert nh-alert-info">
            <div class="nh-alert-icon">ℹ</div>
            <div class="nh-alert-content">
              <p class="nh-alert-message">No merchants are currently available in your area. Check back soon!</p>
            </div>
          </div>
        <?php else: ?>
          <div class="nh-grid nh-grid-3" id="merchant-grid">
            <?php foreach ($availableMerchants as $merchant):
              if ($merchant->status !== 'disabled'):
            ?>
                <div class="nh-card nh-merchant-card"
                  data-merchant-id="<?php echo intval($merchant->id); ?>"
                  data-merchant-name="<?php echo $merchant->business_name; ?>"
                  style="cursor: pointer; transition: all 200ms ease-in-out;">

                  <div class="nh-merchant-card-image">
                    <?= (!empty($merchant->image_url)) ? '<img class="circle" style="max-width: 150px; max-height: 150px;" src="' . $merchant->image_url . '" />' : '🏪' ?>
                  </div>

                  <div class="nh-card-header" style="margin-bottom: 1rem; padding-bottom: 1rem;">
                    <div>
                      <h3 class="nh-merchant-name" style="margin: 0 0 0.5rem 0;"><?php echo $merchant->business_name; ?></h3>
                      <p class="nh-merchant-address" style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($merchant->address ?? 'Address not provided'); ?></p>
                      <p class="nh-merchant-phone" style="margin: 0;"><?php echo htmlspecialchars($merchant->phone ?? 'Phone not provided'); ?></p>
                    </div>
                  </div>

                  <div class="nh-card-footer">
                    <a href="?app=neighborhub&view=customer&p=merchant_products&merchant_id=<?= $merchant->id ?>" class="nh-btn nh-btn-sm" style="margin: auto; display: block;">
                      Browse
                    </a>
                  </div>

                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>


    </div>
  </main>

</div>

<!-- Hidden modal for order detail view -->
<div id="order-detail-modal" class="modal mb-modal-fixed">
  <div class="modal-header">
    <h3 style="margin: 0;">Order Details</h3>
    <button type="button" onclick="closeOrderDetail()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;position: absolute; right: 0; margin: 0; padding: 0;">✕</button>

  </div>
  <div class="modal-content" style="max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
    <div id="order-detail-content">
      <!-- Order details will be loaded here -->
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn red" onclick="closeOrderDetail()">Close</button>
  </div>
</div>

<script>
  // ============================================================================
  // NEIGHBORHUB CUSTOMER DASHBOARD - CLIENT-SIDE LOGIC
  // ============================================================================

  // Global state for cart management
  var currentMerchantId = null;
  var currentMerchantName = null;
  var currentMerchantAddress = null;
  var cartItems = {};
  var pollingInterval = null;

  /**
   * Select a merchant and load its products
   */
  /**
   * Select a merchant and load its products
   */
  function selectMerchant(element) {
    // Read parameters safely out of the DOM dataset properties
    var merchantId = parseInt(element.getAttribute('data-merchant-id'), 10);
    var merchantName = element.getAttribute('data-merchant-name');

    /**
     * Escape HTML entities in text
     */
    const decodeHTMLEntities = (text) => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(text, 'text/html');
      return doc.documentElement.textContent;
    };
    currentMerchantId = merchantId;
    currentMerchantName = decodeHTMLEntities(merchantName);

    // Show product panel
    document.getElementById('product-panel').style.display = 'block';
    document.getElementById('merchant-name-display').textContent = currentMerchantName + ' - Products';

    // Load merchant details and products
    loadMerchantProducts(merchantId);

    // Scroll to product panel
    document.getElementById('product-panel').scrollIntoView({
      behavior: 'smooth'
    });
  }


  /**
   * Close merchant product panel
   */
  function closeMerchantPanel() {
    document.getElementById('product-panel').style.display = 'none';
    clearCart();
    currentMerchantId = null;
    currentMerchantName = null;
  }

  /**
   * Load products for a specific merchant via AJAX
   */
  function loadMerchantProducts(merchantId) {
    mb.ajax({
      type: 'GET',
      url: '/?api=neighborhub',
      data: {
        action: 'get_merchant_products',
        merchant_id: merchantId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.products) {
          renderProductGrid(response.products);
        } else {
          alert('Failed to load products: ' + (response.error || 'Unknown error'));
        }
      },
      error: function(xhr, status, error) {
        console.error('Product load error:', error);
        alert('Error loading products. Please try again.');
      }
    });
  }

  /**
   * Render products in the grid with quantity selectors
   */
  function renderProductGrid(products) {
    var productGrid = document.getElementById('product-grid');
    productGrid.innerHTML = '';

    if (products.length === 0) {
      productGrid.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: var(--gray-500);">No products available</p>';
      return;
    }

    products.forEach(function(product) {
      var productCard = document.createElement('div');
      productCard.className = 'nh-card nh-product-card';
      product.image = product.image_url ? `<image src="${product.image_url}" alt="${product.name}" />` : '📦';

      productCard.innerHTML = `
            <div class="nh-product-image">
                ${product.image}
            </div>
            <h4 class="nh-product-name">${escapeHtml(product.name)}</h4>
            <p class="nh-product-description">${escapeHtml(product.description || 'No description')}</p>
            <p class="nh-product-category">${escapeHtml(product.category || 'Uncategorized')}</p>
            <div class="nh-product-footer">
                <span class="nh-product-price">$${parseFloat(product.price).toFixed(2)}</span>
                <span class="nh-product-availability ${product.is_available ? '' : 'unavailable'}">
                    ${product.is_available ? 'Available' : 'Out of Stock'}
                </span>
            </div>
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem; align-items: center;">
                <input type="number" class="nh-form-input" value="0" min="0" style="flex: 1; padding: 0.5rem;" onchange="updateCartItem(${product.id}, '${product.name}', this.value, ${product.price})">
                <!--
                <button type="button" class="nh-btn nh-btn-sm" ${product.is_available ? '' : 'disabled'} onclick="addToCart(${product.id}, '${escapeHtml(product.name)}', ${product.price})">
                    Add
                </button>
                -->
            </div>
        `;
      productGrid.appendChild(productCard);
    });
  }

  /**
   * Add item to cart
   */
  function addToCart(productId, productName, price) {
    var quantityInput = event.target.parentElement.querySelector('input[type="number"]');
    var quantity = parseInt(quantityInput.value) || 0;

    if (quantity <= 0) {
      alert('Please enter a quantity');
      return;
    }

    if (!cartItems[productId]) {
      cartItems[productId] = {
        id: productId,
        name: productName,
        price: price,
        quantity: 0
      };
    }

    cartItems[productId].quantity += quantity;
    quantityInput.value = '0';

    updateCheckoutForm();
    document.getElementById('checkout-form-container').style.display = 'block';
  }

  /**
   * Update cart item quantity
   */
  function updateCartItem(productId, name, quantity, price) {
    quantity = parseInt(quantity) || 0;

    if (quantity <= 0) {
      delete cartItems[productId];
    } else {
      if (!cartItems[productId]) {
        cartItems[productId] = {
          id: productId,
          name: name,
          price: price,
          quantity: quantity
        };
      } else {
        cartItems[productId].quantity = quantity;
      }
    }

    updateCheckoutForm();
  }

  /**
   * Clear entire cart
   */
  function clearCart() {
    cartItems = {};
    document.getElementById('checkout-form-container').style.display = 'none';
    updateCheckoutForm();
  }

  /**
   * Update checkout form display with current cart items
   */
  function updateCheckoutForm() {
    var cartEmpty = Object.keys(cartItems).length === 0;

    if (cartEmpty) {
      document.getElementById('checkout-form-container').style.display = 'none';
      $('header .checkout-button').hide();
      return;
    }
    document.getElementById('checkout-form-container').style.display = 'block';
    $('header .checkout-button').show();

    var itemsList = document.getElementById('checkout-items-list');
    itemsList.innerHTML = '';

    var subtotal = 0;

    Object.keys(cartItems).forEach(function(productId) {
      var item = cartItems[productId];
      var itemSubtotal = item.price * item.quantity;
      subtotal += itemSubtotal;

      var row = document.createElement('tr');
      row.innerHTML = `
            <td>${escapeHtml(item.name)}</td>
            <td>${item.quantity}</td>
            <td>$${parseFloat(item.price).toFixed(2)}</td>
            <td>$${parseFloat(itemSubtotal).toFixed(2)}</td>
        `;
      itemsList.appendChild(row);
    });

    // Calculate tax (assuming 8% sales tax)
    var tax = subtotal * 0.08;
    var total = subtotal + tax;

    document.getElementById('checkout-subtotal').textContent = '$' + parseFloat(subtotal).toFixed(2);
    document.getElementById('checkout-tax').textContent = '$' + parseFloat(tax).toFixed(2);
    document.getElementById('checkout-total').textContent = '$' + parseFloat(total).toFixed(2);
  }

  /**
   * Direct UI updates applied via incoming WebRTC data channel events
   */
  function updateLocalLedgerRow(orderId, status) {
    // Find the row inside your existing orders-table
    const tableRow = document.querySelector(`#orders-list tr onclick*="viewOrderDetail(${orderId})"`) ||
      document.querySelector(`[data-order-id="${orderId}"]`);

    if (tableRow) {
      const badge = tableRow.querySelector('.nh-badge');
      if (badge) {
        badge.className = `nh-badge badge-${status.toLowerCase()}`;
        badge.textContent = status.replace('_', ' ');
      }
      if (typeof mp3 === 'function') mp3('computerbeep_44');
    }
  }

  /**
   * View detailed order information
   */
  function viewOrderDetail(orderId) {

    loading(1);

    mb.ajax({
      type: 'GET',
      url: '/?api=neighborhub',
      data: {
        action: 'get_order',
        order_id: orderId
      },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.order) {
          displayOrderDetail(response);
          loading(0);
          document.getElementById('order-detail-modal').style.display = 'flex';
        } else {
          alert('Failed to load order details');
        }
      },
      error: function(xhr, status, error) {
        console.error('Order detail error:', error);
        alert('Error loading order details');
      }
    });
  }

  /**
   * Display order detail in modal
   */
  function displayOrderDetail(response) {
    var order = response.order;
    var merchant = response.merchant;
    var statusBadgeClass = 'badge-' + order.state.toLowerCase();

    var html = `
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <div>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Order Number</p>
                    <p style="margin: 0; font-family: monospace; font-weight: 600;">${escapeHtml(order.order_number)}</p>
                </div>
                <span class="nh-badge ${statusBadgeClass}">
                    ${escapeHtml(order.state.replace(/_/g, ' '))}
                </span>
            </div>
        </div>
        
        <div style="background: var(--gray-50); border-radius: var(--border-radius-base); padding: 1.5rem; margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                <div>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Total Amount</p>
                    <p style="margin: 0.5rem 0 0 0; font-size: 1.5rem; font-weight: 700;">$${parseFloat(order.total_amount).toFixed(2)}</p>
                </div>
                <div>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Placed</p>
                    <p style="margin: 0.5rem 0 0 0; font-weight: 600;">${new Date(order.created_at).toLocaleString()}</p>
                </div>
                <div>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Pickup Address</p>
                    <p style="margin: 0.5rem 0 0 0; font-weight: 600;">${escapeHtml(order.pickup_address)}</p>
                </div>
                <div>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Delivery Address</p>
                    <p style="margin: 0.5rem 0 0 0; font-weight: 600;">${escapeHtml(order.delivery_address)}</p>
                </div>
            </div>
        </div>
        
        <div style="margin-bottom: 2rem;">
            <h4 style="margin-bottom: 1rem;">Order Items</h4>
            <table class="nh-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (order.items && order.items.length > 0) {
      order.items.forEach(function(item) {
        html += `
                <tr>
                    <td>${escapeHtml(item.product_id)}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.price_at_order).toFixed(2)}</td>
                    <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
      });
    } else {
      html += '<tr><td colspan="4" style="text-align: center; color: var(--gray-500);">No items</td></tr>';
    }

    html += `
                </tbody>
            </table>
        </div>
    `;

    if (order.order_notes) {
      html += `
            <div style="background: var(--gray-50); border-radius: var(--border-radius-base); padding: 1rem;">
                <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Special Instructions</p>
                <p style="margin: 0.5rem 0 0 0;">${escapeHtml(order.order_notes)}</p>
            </div>
        `;
    }

    // Append this near the bottom of displayOrderDetail(response) function before setting innerHTML:
    if (order.state === 'PENDING' || order.state === 'PENDING_CONFIRMATION') {
      //$('#order-detail-modal .modal-footer').html(`
      html += `
        <button type="button" class="btn red" onclick="showContactMerchantAndCancel(${order.id})" style="margin-top: 1rem; width: 100%;">
            Cancel Order & Request Refund
        </button>
        <div id="order-cancelation-container" style="margin-top: 3rem; color: var(--gray-500); font-size: 0.875rem;">
            <div class="cancellation-choice" style="display: none; flex-direction: column; align-items: center; text-align: center;">
              <p><i class="fas fa-exclamation fa-2x"></i> Sometimes merchants may not be able to accept your order immediately due to checking stock or increased order volume.  You can call the merchant directly to confirm if they can fulfill your order.</p>
              <p style="margin-top: 1rem;">
                If you are sure you want to cancel this order, click the button below to confirm cancellation.  Your payment authorization will be released since the merchant has not accepted your order yet.
              </p>
              <div style="margin-top: 1rem;">
                <a class="btn call-merchant-link green" href="tel:${escapeHtml(merchant.phone)}" style="margin-left: 1rem; text-decoration: none; color: var(--primary-color);">
                  <i class="fas fa-phone fa-2x"></i> Call Merchant
                </a>
                <button type="button" class="btn red" onclick="cancelCustomerOrder(${order.id})">
                  Confirm Cancellation
                </button>
              </div>
            </div>
          </div>
        </div>
  `;
    }

    document.getElementById('order-detail-content').innerHTML = html;
  }

  function showContactMerchantAndCancel(orderId) {
    const cancelationContainer = document.getElementById('order-cancelation-container');
    const choiceDiv = cancelationContainer.querySelector('.cancellation-choice');

    if (choiceDiv) {
      choiceDiv.style.display = 'flex';
    }

  }

  function cancelCustomerOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order? Since it has not been accepted by the merchant yet, your payment authorization will be released.')) {
      return;
    }
    // append loading indicator to order-detail-content
    document.getElementById('order-cancelation-container').innerHTML = `
      
      <div class="" style="margin-top: 15px; width: 100%; display: flex; justify-content: center; align-items: center;">
        <div class="quantum-spinner" style="height: 75px; margin: 0 auto;"></div>
      </div>
      <div class="center-align gold-text" style="margin-bottom: 15px;">
          [ Processing cancellation... ]
      </div>`;

    mb.ajax({
      type: 'POST',
      url: '/?api=neighborhub&action=cancel_order',
      data: JSON.stringify({
        order_id: orderId
      }),
      contentType: 'application/json',
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          notify('Your order has been canceled and your payment authorization was released.', 'success');
          closeOrderDetail();
          // Refresh customer orders list
          if (typeof pollOrderUpdates === 'function') pollOrderUpdates();
        } else {
          alert('Unable to cancel order: ' + (response.error || 'Merchant may have already accepted it.'));
        }
      },
      error: function(xhr, status, error) {
        console.error('Cancellation error:', error);
        alert('Error processing cancellation request.');
      }
    });
  }

  /**
   * Close order detail modal
   */
  function closeOrderDetail() {
    document.getElementById('order-detail-modal').style.display = 'none';
  }

  /**
   * Poll for order updates every 8 seconds
   */
  function pollOrderUpdates() {
    let customerId = <?= $customerId ?>;

    if (pollingInterval) {
      clearInterval(pollingInterval);
    }

    pollingInterval = setInterval(function() {
      mb.ajax({
        type: 'GET',
        url: '/?api=neighborhub', // Or ?app=neighborhub depending on your framework's module router key
        data: {
          action: 'list_customer_orders',
          customer_id: customerId,
        },
        dataType: 'json',
        success: function(response) {
          if (response.success && response.orders) {
            $('section.nh-tracking-ledger .nh-alert').addClass('hide');
            $('section.nh-tracking-ledger .nh-content').removeClass('hide');

            updateOrdersTable(response.orders);
          }

          if (!response.orders.length) {
            $('section.nh-tracking-ledger .nh-alert').removeClass('hide');
            $('section.nh-tracking-ledger .nh-content').addClass('hide');
          }
        },
        error: function(xhr, status, error) {
          console.error('Polling error:', error);
        }
      });
    }, 8000); // Poll every 8 seconds
  }

  /**
   * Update orders table with latest data
   */
  function updateOrdersTable(orders) {
    var ordersList = document.getElementById('orders-list');
    if (!ordersList) return;

    var newHtml = '';

    if (orders.length === 0) {
      newHtml = '<tr><td colspan="6" style="text-align: center; color: var(--gray-500);">No orders</td></tr>';
    } else {
      orders.forEach(function(order) {
        var statusBadgeClass = 'badge-' + order.state.toLowerCase();
        // 1. Create the date object
        const dateObj = new Date(order.created_at);
        // 2. Format the date part (Jul 22, 2026)
        const dateStr = dateObj.toLocaleDateString('en-US', {
          month: 'short',
          day: 'numeric',
          year: 'numeric',
          // timeZone: 'America/New_York' // <-- Optional: Explicitly force your server/business timezone if needed
        });
        // 3. Format the time part (10:20 PM)
        const timeStr = dateObj.toLocaleTimeString('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true,
          // timeZone: 'America/New_York' // <-- Match the timezone chosen above
        });
        newHtml += `
                <tr>
                    <td>
                        <span style="font-family: monospace; font-size: 0.875rem; color: var(--gray-500);">
                            <a href="javascript: void(0);" onclick="viewOrderDetail(${order.id})">${escapeHtml(order.order_number)}</a>
                        </span>
                    </td>
                    <td>${order.business_name}</td>
                    <td>$${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td>
                        <span class="nh-badge ${statusBadgeClass}">
                            ${escapeHtml(order.state.replace(/_/g, ' '))}
                        </span>
                    </td>
                    <td>${dateStr} ${timeStr}</td>
                    <td>
                        <button type="button" class="nh-btn nh-btn-sm" onclick="viewOrderDetail(${order.id})">
                            View
                        </button>
                    </td>
                </tr>
            `;
      });

      $(ordersList).show();
    }

    ordersList.innerHTML = newHtml;
  }

  /**
   * Escape HTML special characters
   */
  function escapeHtml(text) {
    // Convert text to string first to prevent TypeError
    if (text == null) return ''; // Handles null and undefined safely
    var str = String(text);

    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;' // Fixed unclosed quote syntax
    };

    return str.replace(/[&<>"']/g, function(m) {
      return map[m];
    });
  }

  /**
   * Initialize polling when document is ready
   */
  document.addEventListener('DOMContentLoaded', function() {
    pollOrderUpdates();
    //window.neighborhubPoller = new NeighborhubPoller('customer', <?php echo intval($customerId); ?>);
  });

  /**
   * Clean up polling when page unloads
   */
  window.addEventListener('unload', function() {
    if (pollingInterval) {
      clearInterval(pollingInterval);
    }
  });
</script>