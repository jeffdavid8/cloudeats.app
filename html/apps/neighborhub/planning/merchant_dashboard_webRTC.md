That makes perfect sense, and this is the exact piece of the puzzle we needed! Looking at your `polling.js` and `dashboard.php`, your current app architecture heavily separates layouts based on state variables like `data-orders-section="pending"` and `data-order-id`.

Because your current setup utilizes these clean data attributes, we can seamlessly wire them into our WebRTC infrastructure.

Let's begin by converting your Merchant view code. This establishes the hybrid approach: **AJAX updates the database, while WebRTC pushes instant UI animations to any connected peers** (Admins, Customers, and Couriers).

---

### Step 1: Upgrading your Dashboard Class Instance

We need to add a specialized WebRTC mesh connection hub right alongside your legacy `NeighborhubPoller` when the dashboard loads.

Replace the script block at the bottom of your `dashboard.php` with this updated initialization logic:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // 1. Maintain your legacy polling systems so nothing breaks
    window.neighborhubPoller = new NeighborhubPoller('merchant', <?php echo intval($merchantId); ?>);
    pollNewOrders();

    // 2. 🚀 INITIATE THE WEBRTC MESH INSTANCE FOR THIS MERCHANT
    window.merchantMesh = new HubMeshNode({
        role: 'merchant',
        id: <?php echo intval($merchantId); ?>,
        
        // Handle incoming instant signals from Couriers or Customers
        onMessageReceived: function(msg) {
            if (msg.type === 'ORDER_STATUS_CHANGED') {
                console.log(`[Mesh Status Update] Order #${msg.orderId} moved to ${msg.status}`);
                // Dynamically move cards across the dashboard without waiting for the 4s poll loop
                animateOrderStateTransition(msg.orderId, msg.status, msg.htmlPayload);
            }
        },
        
        onConnectionStateChange: function(state) {
            console.log(`[Mesh Network Status]: ${state}`);
        }
    });

    // 3. Keep the "Hollow Tree" ears open for incoming tracking requests every 4 seconds
    setInterval(() => {
        if (window.merchantMesh && !window.merchantMesh.peerConnection) {
            window.merchantMesh.listenForIncomingOffers();
        }
    }, 4000);
});

```

---

### Step 2: The Core Instant UI Animation Engine

Instead of running a slow `location.reload()` or waiting up to 4 seconds for the standard HTTP request, your dashboard can instantly slide HTML cards across containers using standard data attributes:

```javascript
/**
 * Instantly shifts an order card between DOM buckets when a peer shouts an update
 */
function animateOrderStateTransition(orderId, newStatus, incomingHtml = null) {
    // 1. Audio cues for desktop workflow operators
    if (typeof mp3 === 'function') mp3('computerbeep_44');

    // 2. Locate the existing order card in the DOM layout
    let orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
    
    // If the card doesn't exist yet (e.g. a completely new customer checkout order just dropped)
    if (!orderCard && incomingHtml) {
        orderCard = $(incomingHtml)[0];
    }
    
    if (!orderCard) return; // Guard clause

    // 3. Determine the destination lane container
    let targetSectionName = 'pending';
    if (newStatus === 'CONFIRMED' || newStatus === 'IN_TRANSIT') targetSectionName = 'confirmed';
    if (newStatus === 'READY_FOR_PICKUP') targetSectionName = 'ready';

    const destinationContainer = document.querySelector(`[data-orders-section="${targetSectionName}"] [data-orders-list]`);
    if (!destinationContainer) return;

    // 4. Update the visual badge internal states within the card
    const badge = orderCard.querySelector('.nh-badge');
    if (badge) {
        badge.className = `nh-badge badge-${newStatus.toLowerCase()}`;
        badge.textContent = newStatus.replace('_', ' ');
    }

    // 5. Update the footer workflow buttons inside the card dynamically
    const footer = orderCard.querySelector('.nh-card-footer');
    if (footer) {
        if (newStatus === 'CONFIRMED') {
            footer.innerHTML = `
                <button type="button" class="nh-btn" onclick="markReadyForPickup(${orderId})" style="flex: 1;">
                    Mark Ready for Pickup
                </button>`;
        } else if (newStatus === 'READY_FOR_PICKUP' || newStatus === 'IN_TRANSIT') {
            footer.innerHTML = `
                <button type="button" class="nh-btn" disabled style="flex: 1;">
                    Awaiting Courier Pickup
                </button>`;
        }
    }

    // 6. Smoothly shift the DOM node to its new home layout container
    orderCard.style.opacity = '0';
    setTimeout(() => {
        destinationContainer.appendChild(orderCard);
        orderCard.style.transition = 'opacity 300ms ease-in-out';
        orderCard.style.opacity = '1';
        
        // Recalculate your column counter badges at the top of the panels
        updateDashboardCounters();
    }, 1500);
}

/**
 * Recalculates column counts across sections
 */
function updateDashboardCounters() {
    ['pending', 'confirmed', 'ready'].forEach(section => {
        const list = document.querySelector(`[data-orders-section="${section}"] [data-orders-list]`);
        const countBadge = document.querySelector(`[data-${section}-count]`);
        if (list && countBadge) {
            countBadge.textContent = list.querySelectorAll('[data-order-id]').length;
        }
    });
}

```

---

### Step 3: Upgrading your Dashboard Event Actions (`confirm` & `markReady`)

Now we tie the AJAX database write to the WebRTC broadcast pipe. When a clerk clicks **"Confirm Order"**, the database is written to via standard AJAX, and your browser instantly tells any connected peer (like the Admin or Courier app screen) to shift states:

```javascript
function confirmOrder(orderId) {
    if (!confirm('Confirm this order? You will be responsible for its fulfillment.')) return;

    mb.ajax({
        type: 'POST',
        url: '/?api=neighborhub&action=confirm_order',
        data: JSON.stringify({
            order_id: orderId,
            merchant_id: <?php echo intval($merchantId); ?>
        }),
        success: function(response) {
            if (response.success) {
                M.toast({ html: '<i class="fas fa-check"></i> Order confirmed database update save!' });

                // 🚀 BROADCAST THE TRUTH INSTANTLY DOWN THE DATA CARD TUNNEL
                if (window.merchantMesh) {
                    window.merchantMesh.send({
                        type: 'ORDER_STATUS_CHANGED',
                        orderId: orderId,
                        status: 'CONFIRMED'
                    });
                }

                // Animate change on our screen right away
                animateOrderStateTransition(orderId, 'CONFIRMED');
            } else {
                M.toast({ html: 'Failed: ' + (response.error || 'Unknown error'), displayLength: 5000 });
            }
        }
    });
}

function markReadyForPickup(orderId) {
    if (!confirm('Mark this order as ready for pickup? A courier will be notified.')) return;

    mb.ajax({
        type: 'POST',
        url: '/?api=neighborhub&action=mark_ready_for_pickup',
        data: JSON.stringify({
            order_id: orderId,
            merchant_id: <?php echo intval($merchantId); ?>
        }),
        success: function(response) {
            if (response.success) {
                M.toast({ html: '<i class="fas fa-box"></i> Order ready for pickup!' });

                // 🚀 BROADCAST THE TRUTH INSTANTLY DOWN THE DATA CARD TUNNEL
                if (window.merchantMesh) {
                    window.merchantMesh.send({
                        type: 'ORDER_STATUS_CHANGED',
                        orderId: orderId,
                        status: 'READY_FOR_PICKUP'
                    });
                }

                // Animate layout shift natively
                animateOrderStateTransition(orderId, 'READY_FOR_PICKUP');
            } else {
                M.toast({ html: 'Failed: ' + (response.error || 'Unknown error'), displayLength: 5000 });
            }
        }
    });
}

```

---

### Ready for the next views!

This fully updates your Merchant Dashboard into a zero-latency responsive grid.

Go ahead and paste the code/views for your **`admin/overview_map`** and **`courier/dashboard`** pages whenever you're ready! I'll break them down, bridge them into this identical system schema, and show you how to tie the location marker telemetry streams directly into the active order state changes.