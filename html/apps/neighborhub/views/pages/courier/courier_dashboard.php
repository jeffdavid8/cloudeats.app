<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Courier Dashboard
 * 
 * Displays available delivery jobs, active route tracking, and delivery history
 * for authenticated courier drivers.
 * 
 * Context variables available:
 * @var Object $app
 * - $_SESSION['user']['courier_id'] - current courier ID
 * - $app->get('courier_profile') - courier driver profile details
 * - $app->get('available_jobs') - list of READY_FOR_PICKUP orders
 * - $app->get('active_deliveries') - IN_TRANSIT assignments
 * - $app->get('delivery_history') - completed deliveries
 */

/*
if (!isset($_SESSION['user']['courier_id'])) {
    header('Location: /?app=neighborhub&p=dashboard&view=customer');
    exit;
}
*/
$app = App::getInstance();
$courierId = $_SESSION['user']['courier_id'];
$userId = $_SESSION['user']['id'];
$userName = isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Courier';

$courierProfile = $app->get('courier_profile', array());
$availableJobs = $app->get('available_jobs', array());
$activeDeliveries = $app->get('active_deliveries', array());
$deliveryHistory = $app->get('delivery_history', array());

// Display any session notifications
$notification = isset($_SESSION['notification']) ? $_SESSION['notification'] : null;
if ($notification) {
    unset($_SESSION['notification']);
}

// Determine if courier has active delivery
$hasActiveDelivery = !empty($activeDeliveries);
$activeDelivery = $hasActiveDelivery ? $activeDeliveries[0] : null;
?>


<div class="nh-wrapper">

    <!-- Role Header with Navigation -->
    <header class="nh-role-header">
        <div class="nh-container">
            <div class="nh-role-header-content">
                <div>
                    <h1 style="margin: 0; font-size: 1.875rem;">Neighborhub</h1>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
                        Delivery Courier Dashboard
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="nh-role-badge">Courier</div>
                    <a href="/?app=neighborhub&p=dashboard&view=customer" class="nh-btn nh-btn-secondary nh-btn-sm">
                        Switch to Customer
                    </a>
                </div>
            </div>
        </div>
    </header>

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

            <!-- Courier Profile Stats Block -->
            <section class="nh-courier-stats" style="margin-bottom: 3rem;">
                <div class="nh-grid nh-grid-4" style="gap: 1.5rem;">

                    <!-- Driver Name Card -->
                    <div class="nh-card" style="padding: 1.5rem;">
                        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Driver Name</p>
                        <h3 style="margin: 0; color: var(--gray-900);"><?php echo htmlspecialchars($courierProfile['business_name'] ?? $userName); ?></h3>
                    </div>

                    <!-- Vehicle Type Card -->
                    <div class="nh-card" style="padding: 1.5rem;">
                        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Vehicle Type</p>
                        <h3 style="margin: 0; color: var(--gray-900); text-transform: capitalize;">
                            <?php echo htmlspecialchars($courierProfile['vehicle_type'] ?? 'car'); ?>
                        </h3>
                    </div>

                    <!-- Total Deliveries Card -->
                    <div class="nh-card" style="padding: 1.5rem;">
                        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Total Deliveries</p>
                        <h3 style="margin: 0; color: var(--gray-900);"><?php echo intval($courierProfile['total_deliveries'] ?? 0); ?></h3>
                    </div>

                    <!-- Rating Card -->
                    <div class="nh-card" style="padding: 1.5rem;">
                        <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Rating</p>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <h3 style="margin: 0; color: var(--gray-900);">
                                <?php
                                $rating = isset($courierProfile['rating']) ? floatval($courierProfile['rating']) : 0;
                                echo $rating > 0 ? number_format($rating, 1) : 'New';
                                ?>
                            </h3>
                            <?php if ($rating > 0): ?>
                                <span style="color: var(--status-delivered-bg);">★</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </section>

            <!-- Active Delivery Panel (Prominently Displayed) -->
            <?php if ($hasActiveDelivery && $activeDelivery): ?>
                <section class="nh-active-delivery" style="margin-bottom: 3rem; border: 3px solid var(--status-in_transit-bg); border-radius: var(--border-radius-base); overflow: hidden; background: var(--gray-50);">

                    <div style="padding: 2rem; background: var(--status-in_transit-bg); color: white;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <p style="margin: 0; font-size: 0.875rem; opacity: 0.9;">Active Delivery Route</p>
                                <h2 style="margin: 0.5rem 0 0 0; font-size: 1.5rem;">Order <?php echo htmlspecialchars($activeDelivery['order_number']); ?></h2>
                            </div>
                            <div style="text-align: right;">
                                <span class="nh-badge" style="background: rgba(255,255,255,0.2); color: white;">In Transit</span>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Extracted variables from your MySQL order row query
                    $customerPorch = $activeDelivery['delivery_address'];

                    //$shoppingStops[] = ($activeDelivery['business_name'] ?? '') .','. $activeDelivery['pickup_address']; // e.g. "Dollar General, Gaston, IN"
                    $shoppingStops[] = $activeDelivery['pickup_address']; // e.g. "Dollar General, Gaston, IN"

                    // Build the complete universal route link via our factory class
                    $gpsDeepLink = HubRouteFactory::createCourierRoute($customerPorch, $shoppingStops);
                    ?>

                    <div class="nh-guidance-card" style="padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <p style="margin: 0 0 10px 0;"><strong>🗺️ Route Manifest Coordinates Ready</strong></p>

                        <a href="<?php echo $gpsDeepLink; ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="nh-btn"
                            style="background: #10b981; color: white; display: block; text-align: center; font-weight: bold; text-decoration: none;">
                            <i class="fas fa-location-arrow"></i> Engage Turn-by-Turn GPS Guidance
                        </a>
                    </div>

                    <div style="padding: 2rem;">
                        <div class="nh-grid nh-grid-2" style="gap: 2rem; margin-bottom: 2rem;">

                            <!-- Pickup Details -->
                            <div>
                                <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--gray-900);">Pickup Location</h4>
                                <div style="padding: 1.5rem; border-radius: var(--border-radius-base); border: 1px solid var(--gray-200);">
                                    <p style="margin: 0 0 0.5rem 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Merchant</p>
                                    <h5 style="margin: 0 0 1rem 0;"><?php echo htmlspecialchars($activeDelivery['business_name'] ?? 'Merchant'); ?></h5>
                                    <p style="margin: 0; color: var(--gray-900); line-height: 1.6;">
                                        <?php echo htmlspecialchars($activeDelivery['pickup_address']); ?>
                                    </p>
                                    <?php if (!empty($activeDelivery['merchant_lat']) && !empty($activeDelivery['merchant_lng'])): ?>
                                        <p style="margin: 0.5rem 0 0 0; color: var(--gray-500); font-size: 0.875rem;">
                                            📍 <?php echo htmlspecialchars($activeDelivery['merchant_lat']); ?>, <?php echo htmlspecialchars($activeDelivery['merchant_lng']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Delivery Details -->
                            <div>
                                <h4 style="margin-top: 0; margin-bottom: 1rem; color: var(--gray-900);">Delivery Destination</h4>
                                <div style="padding: 1.5rem; border-radius: var(--border-radius-base); border: 1px solid var(--gray-200);">
                                    <p style="margin: 0 0 0.5rem 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase;">Customer Address</p>
                                    <p style="margin: 0; color: var(--gray-900); line-height: 1.6;">
                                        <?php echo htmlspecialchars($activeDelivery['delivery_address']); ?>
                                    </p>
                                </div>
                            </div>

                        </div>

                        <!-- Order Summary -->
                        <div style="padding: 1.5rem; border-radius: var(--border-radius-base); border: 1px solid var(--gray-200); margin-bottom: 2rem;">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem;">
                                <div>
                                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Order Amount</p>
                                    <h3 style="margin: 0; color: var(--gray-900);">$<?php echo number_format($activeDelivery['total_amount'], 2); ?></h3>
                                </div>
                                <div>
                                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem; font-weight: 600; text-transform: uppercase; margin-bottom: 0.5rem;">Assigned At</p>
                                    <p style="margin: 0; color: var(--gray-900);">
                                        <?php
                                        if (!empty($activeDelivery['locked_at'])) {
                                            echo date('M d, h:i A', strtotime($activeDelivery['locked_at']));
                                        } else {
                                            echo 'Recently assigned';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button -->
                        <button type="button" class="nh-btn nh-btn-success" onclick="completeDelivery(<?php echo intval($activeDelivery['id']); ?>)" style="width: 100%; padding: 1rem; font-size: 1.125rem;">
                            ✓ Mark Order as Delivered
                        </button>
                    </div>

                </section>
            <?php else: ?>

                <!-- Available Jobs Pool Section -->
                <section class="nh-available-jobs" style="margin-bottom: 3rem;">
                    <h2 style="margin-bottom: 1.5rem;">Available Delivery Jobs</h2>

                    <?php if (empty($availableJobs)): ?>
                        <div data-available-jobs class="nh-grid" style="gap: 1.5rem;">
                            <div class="nh-alert nh-alert-info" style="width: 100%">
                                <div class="nh-alert-icon">ℹ</div>
                                <div class="nh-alert-content">
                                    <p class="nh-alert-message">No available delivery jobs at the moment. Check back soon!</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <? /*
                        <div style="color: var(--gray-600); font-size: 0.875rem; margin-bottom: 1.5rem;">
                            Showing <?php echo count($availableJobs); ?> available job<?php echo count($availableJobs) !== 1 ? 's' : ''; ?>
                        </div>
                        */ ?>

                        <div data-available-jobs class="nh-grid nh-grid-2" style="gap: 1.5rem;">
                            <?php foreach ($availableJobs as $job): ?>
                                <div class="nh-card nh-order-card" data-order-id="<?php echo intval($job['id']); ?>">

                                    <div class="nh-card-header">
                                        <div>
                                            <p class="nh-order-number"><?php echo htmlspecialchars($job['order_number']); ?></p>
                                            <h4 style="margin: 0; margin-bottom: 0.5rem;">Delivery Job</h4>
                                        </div>
                                        <span class="nh-badge badge-ready_for_pickup">Available</span>
                                    </div>

                                    <div class="nh-card-body">
                                        <div class="nh-order-details">

                                            <div class="nh-order-detail-item">
                                                <span class="nh-order-detail-label">Merchant</span>
                                                <span class="nh-order-detail-value"><?php echo htmlspecialchars($job['business_name']); ?></span>
                                            </div>

                                            <div class="nh-order-detail-item">
                                                <span class="nh-order-detail-label">Order Amount</span>
                                                <span class="nh-order-detail-value">$<?php echo number_format($job['total_amount'], 2); ?></span>
                                            </div>

                                            <div class="nh-order-detail-item">
                                                <span class="nh-order-detail-label">Pickup</span>
                                                <span class="nh-order-detail-value" style="font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars(substr($job['pickup_address'], 0, 50)); ?>...
                                                </span>
                                            </div>

                                            <div class="nh-order-detail-item">
                                                <span class="nh-order-detail-label">Delivery</span>
                                                <span class="nh-order-detail-value" style="font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars(substr($job['delivery_address'], 0, 50)); ?>...
                                                </span>
                                            </div>

                                            <div class="nh-order-detail-item">
                                                <span class="nh-order-detail-label">Posted</span>
                                                <span class="nh-order-detail-value">
                                                    <?php echo date('M d, h:i A', strtotime($job['created_at'])); ?>
                                                </span>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="nh-card-footer">
                                        <button type="button" class="nh-btn" onclick="acceptDeliveryJob(<?php echo intval($job['id']); ?>)" style="flex: 1;">
                                            Accept Delivery Job
                                        </button>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>

                </section>

            <?php endif; ?>

            <!-- Delivery History Section -->
            <section class="nh-delivery-history" style="margin-bottom: 3rem;">
                <h2 style="margin-bottom: 1.5rem;">Delivery History</h2>

                <?php if (empty($deliveryHistory)): ?>
                    <div class="nh-alert nh-alert-info">
                        <div class="nh-alert-icon">ℹ</div>
                        <div class="nh-alert-content">
                            <p class="nh-alert-message">You haven't completed any deliveries yet. Accept a job to get started!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="nh-content" style="overflow-x: auto;">
                        <table class="nh-table">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Merchant</th>
                                    <th>Amount</th>
                                    <th>Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deliveryHistory as $delivery): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($delivery['order_number']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($delivery['business_name']); ?></td>
                                        <td>$<?php echo number_format($delivery['total_amount'], 2); ?></td>
                                        <td>
                                            <?php
                                            if (!empty($delivery['delivered_at'])) {
                                                echo date('M d, Y h:i A', strtotime($delivery['delivered_at']));
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </section>

        </div>
    </main>

</div>

<!-- Location Tracking Modal (Minimalist) -->
<div id="location-tracking-indicator">
    <div style="width: 0.75rem; height: 0.75rem; background: var(--status-ready-bg); border-radius: 50%; animation: pulse 2s infinite;"></div>
    <span style="color: var(--gray-700); font-size: 0.875rem;">📍 Location tracking initializing...</span>
</div>

<script>
    // ============================================================================
    // NEIGHBORHUB COURIER DASHBOARD - CLIENT-SIDE LOGIC
    // ============================================================================

    // ============================================================================
    // NEIGHBORHUB COURIER DASHBOARD - WEBRTC MESH IMPLEMENTATION
    // ============================================================================

    var courierLocationInterval = null;
    nh.courier = {
        lastLocationLat: null,
        lastLocationLng: null,
    };
    var isTrackingLocation = false;
    window.courierMesh = null; // Our direct P2P pipeline node instance

    /**
     * Accept a delivery job (Augmented with WebRTC Initiation)
     */
    function acceptDeliveryJob(jobId) {
        if (!confirm('Accept this delivery job? You will be locked into this assignment until completion.')) {
            return;
        }

        var btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Processing...';

        mb.ajax({
            type: 'POST',
            url: '/?api=neighborhub&action=accept_delivery',
            data: JSON.stringify({
                order_id: jobId,
                courier_id: <?php echo intval($courierId); ?>
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    nh.notify('Job accepted! You are now assigned to this delivery.', 'green');

                    // 🚀 INITIALIZE COURIER MESH BROADCASTER TOWER
                    setupCourierMeshBroadcaster();

                    startLocationTracking();

                    // Smoothly update the UI card container structure right away
                    /*
                     */
                    setTimeout(function() {
                        location.reload(); // Quick reload to switch to active template view
                    }, 1000);
                } else {
                    alert('Failed to accept job: ' + (response.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = 'Accept Delivery Job';
                }
            },
            error: function(xhr, status, error) {
                console.error('Job acceptance error:', error);
                if (xhr.status === 409) {
                    alert('This job has already been accepted by another courier. Try a different job.');
                } else {
                    alert('Error accepting job. Please try again.');
                }
                btn.disabled = false;
                btn.textContent = 'Accept Delivery Job';
            }
        });
    }

    /**
     * Instantiates the P2P pipeline engine for the courier device
     */
    function setupCourierMeshBroadcaster() {
        if (window.courierMesh) return;

        window.courierMesh = new HubMeshNode({
            role: 'courier',
            id: <?php echo intval($courierId); ?>,
            onMessageReceived: function(msg) {
                console.log("[Courier Mesh Received Package]:", msg);
            },
            onConnectionStateChange: function(state) {
                const indicator = document.querySelector('#location-tracking-indicator span');
                let label = {
                    'open': 'live',
                };
                if (indicator) {

                    indicator.textContent = `📍 Location streaming: ${(label[state] ?? state).toUpperCase()}`;
                }
            }
        });

        // Open our "Hollow Tree" line immediately so Admins and Customers can lock tunnels onto us
        setInterval(function() {
            if (window.courierMesh && !window.courierMesh.peerConnection) {
                window.courierMesh.listenForIncomingOffers();
            }
        }, 5000);
    }

    /**
     * Send location updates—upgraded to push directly to WebRTC data channel
     */
    function sendLocationUpdate() {
        if (!nh.courier.lastLocationLat || !nh.courier.lastLocationLng) {
            return false;
        }
        const latNum = parseFloat(nh.courier.lastLocationLat);
        const lngNum = parseFloat(nh.courier.lastLocationLng);

        // 🚀 THE MESH MOMENT: Beam the raw GPS metrics instantly through the air to peers
        if (window.courierMesh && window.courierMesh.dataChannel) {
            window.courierMesh.send({
                type: 'COURIER_GPS_STREAM',
                courierId: <?php echo intval($courierId); ?>,
                latitude: latNum,
                longitude: lngNum,
                meta: {
                    orderId: <?php echo $activeDelivery ? intval($activeDelivery['id']) : 'null'; ?>,
                    speed: 'calculating...'
                }
            });
            console.log('[Mesh] GPS frame pushed directly to peer connections.');
        }

        // Keep the database fallback post running, but on a relaxed loop to protect system bandwidth
        mb.ajax({
            type: 'POST',
            url: '/?api=neighborhub&action=update_location',
            data: JSON.stringify({
                courier_id: <?php echo intval($courierId); ?>,
                latitude: latNum,
                longitude: lngNum
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Server Database Coordinate Backup Saved.');
                }
            }
        });
    }

    /**
     * Complete Delivery Routine (Cleanly closes the Mesh)
     */
    function completeDelivery(orderId) {
        if (!confirm('Mark this order as delivered? This will complete your assignment.')) {
            return;
        }

        var btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Completing...';

        mb.ajax({
            type: 'POST',
            url: '/?api=neighborhub&action=complete_delivery',
            data: JSON.stringify({
                order_id: orderId,
                courier_id: <?php echo intval($courierId); ?>
            }),
            contentType: 'application/json',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    nh.notify('Delivery completed! Thank you for your service.', 'green');

                    // Inform active listeners right before disconnecting the physical line
                    if (window.courierMesh) {
                        window.courierMesh.send({
                            type: 'ORDER_STATUS_CHANGED',
                            orderId: orderId,
                            status: 'DELIVERED'
                        });
                        window.courierMesh.disconnect();
                    }

                    stopLocationTracking();
                    /*
                     */
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Failed to complete delivery: ' + (response.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = '✓ Mark Order as Delivered';
                }
            }
        });
    }

    // Auto-launch the broadcaster tower on load if a delivery route is currently running
    document.addEventListener('DOMContentLoaded', function() {
        window.neighborhubPoller = new NeighborhubPoller('courier', <?php echo intval($courierId); ?>);

        startLocationTracking();

        <?php if ($hasActiveDelivery): ?>
            setupCourierMeshBroadcaster();
        <?php endif; ?>
    });

    /**
     * Start location tracking and send coordinates to server every 15 seconds
     */
    function startLocationTracking() {
        if (isTrackingLocation) {
            return;
        }

        isTrackingLocation = true;

        mb.geoLocate(function(position) {
            nh.courier.lastLocationLat = position.lat;
            nh.courier.lastLocationLng = position.lng;
            sendLocationUpdate();
            courierLocationInterval = setInterval(function() {
                mb.geoLocate(
                    function(position) {
                        nh.courier.lastLocationLat = position.latitude;
                        nh.courier.lastLocationLng = position.longitude;
                        sendLocationUpdate();
                    }
                );
                return false;
            }, 15000); // 15 seconds
        });
    }

    /**
     * Stop location tracking
     */
    function stopLocationTracking() {
        isTrackingLocation = false;

        if (courierLocationInterval) {
            clearInterval(courierLocationInterval);
            courierLocationInterval = null;
        }
    }

    /**
     * Display notification toast message
     */
    nh.notify = function(message, type) {
        type = type || 'info';

        var toast = document.createElement('div');
        toast.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: ${type === 'green' ? 'var(--status-delivered-bg)' : 'var(--primary-color)'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: var(--border-radius-base);
        box-shadow: var(--shadow-lg);
        z-index: 2000;
        max-width: 400px;
        animation: slideIn 300ms ease-in-out;
    `;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(function() {
            toast.style.animation = 'slideOut 300ms ease-in-out';
            setTimeout(function() {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }


    /**
     * Clean up tracking when page unloads
     */
    window.addEventListener('unload', function() {
        stopLocationTracking();
    });

    /**
     * CSS for animations
     */
    var style = document.createElement('style');
    style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
`;
    document.head.appendChild(style);
</script>