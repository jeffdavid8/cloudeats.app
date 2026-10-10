<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Customer Dashboard
 * 
 * Orders Ledger with search, status filtering, pagination, and instant reordering.
 */
$customer = $this->get('customer');
$customerId = $customer->id ?? 0;
$userName = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Customer';
$customerOrders = $this->get('customer_orders', array());

// Display session notifications
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
if ($notification) {
  unset($_SESSION['notification']);
}
?>

<style>
  .nh-order-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }

  .nh-order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  }

  .filter-bar {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
  }

  .pagination-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
  }
</style>

<div class="nh-wrapper">
  <main class="nh-main">
    <div class="nh-container">

      <!-- Display Session Notifications -->
      <?php if ($notification): ?>
        <div class="nh-alert nh-alert-<?php echo htmlspecialchars($notification['type']); ?>" style="margin-bottom: 2rem;">
          <div class="nh-alert-icon">
            <?= $notification['type'] === 'success' ? '✓' : ($notification['type'] === 'error' ? '✕' : 'ℹ') ?>
          </div>
          <div class="nh-alert-content">
            <p class="nh-alert-message"><?php echo htmlspecialchars($notification['message']); ?></p>
          </div>
        </div>
      <?php endif; ?>

      <section class="nh-tracking-ledger">
        <div class="valign-wrapper" style="justify-content: space-between; margin-bottom: 1.5rem;">
          <h2 style="margin: 0;">Your Order History</h2>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
          <div class="row" style="margin-bottom: 0;">
            <div class="col s12 m6 l4 input-field" style="margin-top: 0;">
              <input type="text" id="order-search" placeholder="Search merchant or order #..." onkeyup="filterOrders()" style="color: #fff;">
            </div>
            <div class="col s12 m6 l4 input-field" style="margin-top: 0;">
              <select id="status-filter" onchange="filterOrders()" class="browser-default" style="background: #222; color: #fff; border: 1px solid #444; padding: 8px; border-radius: 4px;">
                <option value="ALL">All Order Statuses</option>
                <option value="ACTIVE">In Progress / Active</option>
                <option value="DELIVERED">Delivered / Completed</option>
                <option value="CANCELLED">Cancelled</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Orders Container -->
        <div class="nh-content">
          <div class="row" id="orders-list" style="overflow-x: hidden;">
            <!-- Rendered dynamically via JS or PHP initial load -->
          </div>

          <!-- Pagination Controls -->
          <div class="pagination-container" id="pagination-controls" style="display: none;">
            <button type="button" class="btn btn-small grey darken-3" id="prev-page-btn" onclick="changePage(-1)">
              <i class="fas fa-chevron-left left"></i> Prev
            </button>
            <span id="page-indicator" class="grey-text text-lighten-1" style="font-size: 0.9rem;">Page 1 of 1</span>
            <button type="button" class="btn btn-small grey darken-3" id="next-page-btn" onclick="changePage(1)">
              Next <i class="fas fa-chevron-right right"></i>
            </button>
          </div>
        </div>
      </section>

    </div>
  </main>
</div>

<!-- Modal for order detail view -->
<div id="order-detail-modal" class="modal mb-modal-fixed">
  <div class="modal-header">
    <h3 style="margin: 0;">Order Details</h3>
    <button type="button" onclick="closeOrderDetail()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; position: absolute; right: 1rem; margin: 0; padding: 0;">✕</button>
  </div>
  <div class="modal-content" style="max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
    <div id="order-detail-content"></div>
  </div>
  <div class="modal-footer">
    <button class="btn red" onclick="closeOrderDetail()">Close</button>
  </div>
</div>

<script>
  // Initial Server State
  var allOrders = <?= json_encode($customerOrders) ?>;
  var filteredOrders = [];
  var currentPage = 1;
  var itemsPerPage = 9;
  var pollingInterval = null;

  function getBadgeColor(state) {
    switch (state) {
      case 'DELIVERED':
        return '#2e7d32';
      case 'CANCELLED':
        return '#616161';
      case 'PENDING':
      case 'PENDING_CONFIRMATION':
      case 'PREPARING':
        return '#f57c00';
      default:
        return '#1e88e5';
    }
  }

  // Filter & Search Logic
  function filterOrders() {
    var searchTerm = document.getElementById('order-search').value.toLowerCase().trim();
    var statusTerm = document.getElementById('status-filter').value;

    filteredOrders = allOrders.filter(function(order) {
      var matchesSearch = (order.business_name || '').toLowerCase().includes(searchTerm) ||
        (order.order_number || '').toLowerCase().includes(searchTerm);

      var isPending = ['PENDING', 'PENDING_CONFIRMATION', 'PREPARING', 'IN_TRANSIT'].includes(order.state);
      var matchesStatus = true;

      if (statusTerm === 'ACTIVE') matchesStatus = isPending;
      else if (statusTerm === 'DELIVERED') matchesStatus = (order.state === 'DELIVERED');
      else if (statusTerm === 'CANCELLED') matchesStatus = (order.state === 'CANCELLED');

      return matchesSearch && matchesStatus;
    });

    currentPage = 1;
    renderOrders();
  }

  // Paginated Rendering
  function renderOrders() {
    var ordersList = document.getElementById('orders-list');
    var paginationControls = document.getElementById('pagination-controls');
    if (!ordersList) return;

    if (filteredOrders.length === 0) {
      ordersList.innerHTML = '<div class="col s12 center-align grey-text" style="padding: 3rem;">No matching orders found.</div>';
      paginationControls.style.display = 'none';
      return;
    }

    var totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
    var startIdx = (currentPage - 1) * itemsPerPage;
    var pageOrders = filteredOrders.slice(startIdx, startIdx + itemsPerPage);

    var newHtml = '';
    pageOrders.forEach(function(order) {
      var isPending = ['PENDING', 'PENDING_CONFIRMATION', 'PREPARING'].includes(order.state);
      var badgeBg = getBadgeColor(order.state);
      var dateObj = new Date(order.created_at);
      var dateStr = dateObj.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      });
      var timeStr = dateObj.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
      });

      newHtml += `
    <div class="col s12 m6 l4">
      <div class="card hoverable nh-order-card ${isPending ? 'active-order' : ''}">
        <div class="card-content">
          <!-- Stacked Card Header -->
          <div class="order-header" style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px; margin-bottom: 10px;">
            <span class="new badge ${isPending ? 'pulse' : ''}" style="background-color: ${badgeBg}; margin: 0;" data-badge-caption="">
              ${escapeHtml(order.state.replace(/_/g, ' '))}
            </span>
            <span class="card-title truncate" style="font-weight: 600; font-size: 1.15rem; margin: 0; color: #fff; width: 100%;" title="${escapeHtml(order.business_name || 'Unknown')}">
              ${escapeHtml(order.business_name || 'Unknown')}
            </span>
          </div>

          <!-- Meta Details -->
          <p class="grey-text text-lighten-1" style="font-size: 0.82rem; margin: 6px 0;">
            Order #${escapeHtml(order.order_number)}
          </p>
          <p class="grey-text text-lighten-1" style="font-size: 0.82rem; margin: 0;">
            <i class="far fa-clock"></i> ${dateStr} at ${timeStr}
          </p>

          <div class="divider" style="margin: 12px 0; opacity: 0.2;"></div>

          <!-- Price Row -->
          <div style="margin-bottom: 12px; text-align: left;">
            <span style="font-size: 1.25rem; font-weight: bold; color: #81c784;">
              $${parseFloat(order.total_amount).toFixed(2)}
            </span>
          </div>

          <!-- 50/50 Split Action Buttons -->
          <div style="display: flex; gap: 8px; width: 100%;">
            <button type="button" 
                    class="btn-flat waves-effect waves-light green-text text-accent-3" 
                    style="flex: 1; text-align: center; padding: 0 4px; border: 1px solid rgba(129, 199, 132, 0.3); border-radius: 4px;" 
                    title="Reorder these items" 
                    onclick="reorderItems(${order.id})">
              <i class="fas fa-redo"></i> Reorder
            </button>
            <button type="button" 
                    class="btn-flat waves-effect waves-teal red-text text-lighten-2" 
                    style="flex: 1; text-align: center; padding: 0 4px; border: 1px solid rgba(239, 83, 80, 0.3); border-radius: 4px;" 
                    onclick="viewOrderDetail(${order.id})">
              Details <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>`;
    });

    ordersList.innerHTML = newHtml;

    // Update pagination controls
    if (totalPages > 1) {
      paginationControls.style.display = 'flex';
      document.getElementById('page-indicator').innerText = `Page ${currentPage} of ${totalPages}`;
      document.getElementById('prev-page-btn').disabled = (currentPage === 1);
      document.getElementById('next-page-btn').disabled = (currentPage === totalPages);
    } else {
      paginationControls.style.display = 'none';
    }
  }

  function changePage(direction) {
    var totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
    var newPage = currentPage + direction;
    if (newPage >= 1 && newPage <= totalPages) {
      currentPage = newPage;
      renderOrders();
    }
  }

  /**
   * Reorder Handler: Loads previous order items and routes to merchant catalog
   */
  function reorderItems(orderId) {
    loading(4);

    mb.ajax({
      type: 'GET',
      url: '/?api=neighborhub',
      data: {
        action: 'reorder_items',
        order_id: orderId
      },
      dataType: 'json',
      success: function(response) {
        if (typeof loading === 'function') loading(0);
        if (response.success && response.merchant_id) {
          // Redirect directly to the merchant product storefront to complete/adjust the basket
          window.location.href = `?app=neighborhub&view=customer&p=merchant_products&merchant_id=${response.merchant_id}&reorder_from=${orderId}`;
        } else {
          alert('Unable to process reorder: ' + (response.error || 'Unknown error'));
        }
      },
      error: function() {
        if (typeof loading === 'function') loading(0);
        alert('Failed to connect to reorder service.');
      }
    });
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
          if (typeof loading === 'function') loading(0);
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
                    <td>${escapeHtml(item.product_name)}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.price_at_order).toFixed(2)}</td>
                    <td>$${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
            `;
      });
    } else {
      html += '<tr><td colspan="4" style="text-align: center; color: var(--gray-500);">No items</td></tr>';
    }

    html += `</tbody></table></div>`;

    if (order.order_notes) {
      html += `
            <div style="background: var(--gray-50); border-radius: var(--border-radius-base); padding: 1rem;">
                <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">Special Instructions</p>
                <p style="margin: 0.5rem 0 0 0;">${escapeHtml(order.order_notes)}</p>
            </div>
        `;
    }

    if (order.state === 'PENDING' || order.state === 'PENDING_CONFIRMATION') {
      html += `
        <button type="button" class="btn red" onclick="showContactMerchantAndCancel(${order.id})" style="margin-top: 1rem; width: 100%;">
            Cancel Order & Request Refund
        </button>
        <div id="order-cancelation-container" style="margin-top: 3rem; color: var(--gray-500); font-size: 0.875rem;">
            <div class="cancellation-choice" style="display: none; flex-direction: column; align-items: center; text-align: center;">
              <p><i class="fas fa-exclamation fa-2x"></i> Sometimes merchants may not be able to accept your order immediately. You can call the merchant directly to confirm if they can fulfill your order.</p>
              <div style="margin-top: 1rem;">
                <a class="btn call-merchant-link green" href="tel:${escapeHtml(merchant ? merchant.phone : '')}" style="margin-left: 1rem; text-decoration: none;">
                  <i class="fas fa-phone fa-2x"></i> Call Merchant
                </a>
                <button type="button" class="btn red" onclick="cancelCustomerOrder(${order.id})">
                  Confirm Cancellation
                </button>
              </div>
            </div>
        </div>
      `;
    }

    document.getElementById('order-detail-content').innerHTML = html;
  }

  function closeOrderDetail() {
    document.getElementById('order-detail-modal').style.display = 'none';
  }

  function escapeHtml(text) {
    if (text == null) return '';
    var str = String(text);
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    };
    return str.replace(/[&<>"']/g, function(m) {
      return map[m];
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    filterOrders();
  });
</script>