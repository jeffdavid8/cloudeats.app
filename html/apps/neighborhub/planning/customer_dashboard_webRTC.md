No need to back up at all—you are tracking the lifecycle perfectly!

Let’s trace the thread from the exact moment your customer hits **"Place Order"** inside `dashboard.php`.

---

### The Order Genesis Event Chain

1. The Customer fills their cart and hits `submitOrder()`.
2. An AJAX request hits your PHP backend, writing a fresh record to the database with a state of `PENDING_CONFIRMATION`.
3. **The WebRTC Connection Dilemma:** At this split second, the Customer doesn't have an active peer connection to the Merchant yet because they haven't established a "Heart-Pipe" handshake.

To bridge this gap seamlessly, we drop an initialization call straight into the Customer's successful AJAX confirmation block. This sets up an automated connection that broadcasts data backwards and forwards across the mesh.

---

### 1. Upgrading the Customer View (`dashboard.php`)

Let's modify your existing `submitOrder()` success callback block. We will hook it directly into a WebRTC initiator so that as soon as the order database write finishes, the customer automatically reaches out to open a tracking tunnel with the Merchant:

```javascript
    // Inside your existing submitOrder() function's mb.ajax success handler:
    success: function(response) {
        if (response.success) {
            alert('Order placed successfully! Order ID: ' + response.order_id);
            clearCart();
            closeMerchantPanel();
            
            // 🚀 THE MESH MOMENT: Establish a WebRTC connection to the Merchant
            // This instantiates a node and signals the target via your PHP session engine
            window.customerMesh = new HubMeshNode({
                role: 'customer',
                id: <?php echo $customerId; ?>,
                onMessageReceived: function(msg) {
                    // Route state changes (e.g. Merchant clicks Accept or Ready)
                    if (msg.type === 'ORDER_STATUS_CHANGED') {
                        // Dynamically update the localized ledger table row without an AJAX hit!
                        updateLocalLedgerRow(msg.orderId, msg.status);
                    }
                }
            });

            // Fire an invite through the Hollow Tree to the specific merchant we just purchased from
            window.customerMesh.initiateConnection('merchant', currentMerchantId);

            // Keep legacy backup long-polling fallback running
            pollOrderUpdates();
        } else {
            alert('Failed to place order: ' + (response.error || 'Unknown error'));
        }
    }

```

Add this processing helper to the bottom of your script tags inside `dashboard.php` to visually refresh rows live over the mesh wire:

```javascript
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

```

---

### 2. Upgrading the Merchant Dashboard Listener

On the other side of the window, your Merchant Panel needs to be ready to catch this connection.

Inside the code we structured in the previous prompt, the Merchant runs a background loop calling `window.merchantMesh.listenForIncomingOffers();`.

When the customer's handshake reaches the merchant, the `onMessageReceived` callback inside the merchant's screen catches the configuration event. Because the customer's mesh engine sends the initial order state structure, the merchant's dashboard can run `animateOrderStateTransition()` to **instantly pop the order card onto the screen layout** up to 4 seconds faster than the legacy `pollMerchantOrders()` interval timer would catch it!

---

### What's next?

Now that the Customer successfully writes the order and automatically knits a real-time data pipe straight to the Merchant's kitchen display screen, the Merchant can click **"Confirm Order"** or **"Mark Ready for Pickup"**, causing the change to flash right back onto the Customer's window badge.

Whenever you're ready, paste or upload the code for your **Admin Overview Map (`admin/overview_map`)** and **Courier Dashboard (`courier/dashboard`)** files. We will map out how the Courier hooks into this pipeline, accepts the job, and feeds live coordinate data frames directly to both the Admin map pins and the Customer layout views!