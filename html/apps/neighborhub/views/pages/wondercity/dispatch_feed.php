<?php
if (!defined('MB_RUNNING')) exit;
/**
 * Neighborhub Wonder City Dispatch Feed
 * 
 * Cross-application logging and dispatch stream that connects Neighborhub
 * to the broader ecosystem via the shared Stitch model integration.
 * 
 * Displays real-time platform operations including order placements,
 * courier assignments, transit updates, and delivery completions.
 * 
 * @var Object $app
 * 
 */


$userId = $this->user->id;

// Include Stitch model for dispatch feed querying
$this->includeModel('stitch');

// Query all Wonder City Dispatch entries, ordered by most recent first
$dispatchEntries = Stitch::query(array(
    'where' => "a.content_type = 'wonder_city_dispatch' AND a.status = 'active'",
    'order_by' => 'a.projected_to DESC',
    'limit' => 100,
    'binds' => array()
));

// Initialize dispatch entries if query fails
if ($dispatchEntries === false) {
    $dispatchEntries = array();
}
?>

<div class="nh-wrapper">
    
    <!-- Role Header with Navigation -->
    <header class="nh-role-header">
        <div class="nh-container">
            <div class="nh-role-header-content">
                <div>
                    <h1 style="margin: 0; font-size: 1.875rem;">Neighborhub</h1>
                    <p style="margin: 0; color: var(--gray-500); font-size: 0.875rem;">
                        Wonder City Dispatch - Live Operations Feed
                    </p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <div class="nh-role-badge">Dispatch</div>
                    <a href="/?app=neighborhub&p=dashboard&view=customer" class="nh-btn nh-btn-secondary nh-btn-sm">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="nh-main">
        <div class="nh-container">

            <!-- Dispatch Feed Title Section -->
            <section class="nh-dispatch-header" style="margin-bottom: 2rem;">
                <h2 style="margin-bottom: 0.5rem;">Sovereign Ledger Online</h2>
                <p style="color: var(--gray-500); margin-bottom: 1.5rem;">
                    Real-time platform operations stream. Updates refresh every 8 seconds.
                </p>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span id="dispatch-status" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--gray-600);">
                        <span style="width: 0.5rem; height: 0.5rem; background: var(--success-color); border-radius: 50%; animation: pulse 2s infinite;"></span>
                        Stream Active
                    </span>
                    <span id="dispatch-count" style="color: var(--gray-500); font-size: 0.875rem;">
                        <?php echo count($dispatchEntries); ?> entries
                    </span>
                </div>
            </section>

            <!-- Dispatch Feed Container (Updated via AJAX) -->
            <section id="dispatch-feed-container" class="nh-dispatch-feed">
                
                <?php if (empty($dispatchEntries)): ?>
                    
                    <!-- Empty State Fallback -->
                    <div class="nh-card" style="padding: 3rem; text-align: center;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">📡</div>
                        <h3 style="margin-bottom: 1rem; color: var(--gray-700);">Sovereign Ledger Online</h3>
                        <p style="color: var(--gray-500); margin-bottom: 1.5rem;">
                            Awaiting incoming local dispatch logs via Stitch...
                        </p>
                        <p style="color: var(--gray-400); font-size: 0.875rem;">
                            The dispatch stream is ready to receive platform operations.
                        </p>
                    </div>

                <?php else: ?>

                    <!-- Dispatch Entries Grid -->
                    <div class="nh-grid nh-grid-auto" style="gap: 1.5rem;">

                        <?php foreach ($dispatchEntries as $stitch): ?>

                            <?php
                            // Extract content payload from decoded JSON
                            $content = is_array($stitch->content) ? $stitch->content : array();
                            
                            // Parse tracking status
                            $trackingStatus = isset($content['tracking_status']) ? strtoupper($content['tracking_status']) : 'UNKNOWN';
                            $description = isset($content['description']) ? htmlspecialchars($content['description']) : 'No description provided';
                            $orderNumber = isset($content['order_number']) ? htmlspecialchars($content['order_number']) : null;
                            $merchantName = isset($content['merchant_name']) ? htmlspecialchars($content['merchant_name']) : 'Unknown Merchant';
                            $courierName = isset($content['courier_name']) ? htmlspecialchars($content['courier_name']) : null;
                            $customerName = isset($content['customer_name']) ? htmlspecialchars($content['customer_name']) : 'Unknown Customer';
                            
                            // Determine badge color based on tracking status
                            $badgeClass = 'badge-pending_confirmation';
                            $badgeEmoji = '📋';
                            
                            if (strpos($trackingStatus, 'ORDER') !== false) {
                                $badgeClass = 'badge-pending_confirmation';
                                $badgeEmoji = '📦';
                            } elseif (strpos($trackingStatus, 'COURIER') !== false || strpos($trackingStatus, 'ASSIGNED') !== false) {
                                $badgeClass = 'badge-confirmed';
                                $badgeEmoji = '🚗';
                            } elseif (strpos($trackingStatus, 'TRANSIT') !== false) {
                                $badgeClass = 'badge-in_transit';
                                $badgeEmoji = '🛣️';
                            } elseif (strpos($trackingStatus, 'DELIVERY') !== false || strpos($trackingStatus, 'COMPLETED') !== false) {
                                $badgeClass = 'badge-delivered';
                                $badgeEmoji = '✓';
                            }
                            
                            // Format spatial coordinates if available
                            $spatialInfo = '';
                            if (!empty($stitch->lat) && !empty($stitch->lng)) {
                                $spatialInfo = number_format($stitch->lat, 4) . ', ' . number_format($stitch->lng, 4);
                            }
                            ?>

                            <!-- Dispatch Entry Card -->
                            <div class="nh-card" data-stitch-id="<?php echo intval($stitch->id); ?>" style="display: flex; flex-direction: column; border-left: 4px solid var(--primary-color);">
                                
                                <!-- Card Header -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">
                                    <div>
                                        <h4 style="margin: 0; font-size: 1rem; margin-bottom: 0.25rem;">
                                            <?php echo $badgeEmoji; ?> 
                                            <?php 
                                            if ($orderNumber) {
                                                echo htmlspecialchars($orderNumber);
                                            } else {
                                                echo 'Dispatch Event';
                                            }
                                            ?>
                                        </h4>
                                        <p style="margin: 0; color: var(--gray-500); font-size: 0.75rem;">
                                            <?php echo $stitch->getFormattedDate('M j, Y H:i'); ?>
                                        </p>
                                    </div>
                                    <span class="nh-badge <?php echo $badgeClass; ?>" style="flex-shrink: 0;">
                                        <?php echo str_replace('_', ' ', $trackingStatus); ?>
                                    </span>
                                </div>

                                <!-- Card Body -->
                                <div style="margin-bottom: 1rem;">
                                    <p style="margin: 0 0 1rem 0; color: var(--gray-900); line-height: 1.5;">
                                        <?php echo $description; ?>
                                    </p>

                                    <!-- Dispatch Details Grid -->
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem;">
                                        
                                        <!-- Merchant -->
                                        <div>
                                            <p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Merchant</p>
                                            <p style="margin: 0; color: var(--gray-900);">
                                                <?php echo $merchantName; ?>
                                            </p>
                                        </div>

                                        <!-- Customer -->
                                        <div>
                                            <p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Customer</p>
                                            <p style="margin: 0; color: var(--gray-900);">
                                                <?php echo $customerName; ?>
                                            </p>
                                        </div>

                                        <!-- Courier (if applicable) -->
                                        <?php if ($courierName): ?>
                                            <div>
                                                <p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Courier</p>
                                                <p style="margin: 0; color: var(--gray-900);">
                                                    <?php echo $courierName; ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Additional Metadata -->
                                        <?php if (isset($content['amount'])): ?>
                                            <div>
                                                <p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Amount</p>
                                                <p style="margin: 0; color: var(--gray-900); font-weight: 600;">
                                                    $<?php echo number_format(floatval($content['amount']), 2); ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>

                                <!-- Spatial Coordinates (if available) -->
                                <?php if ($spatialInfo): ?>
                                    <div style="padding: 0.75rem; background: var(--gray-50); border-radius: var(--border-radius-base); margin-bottom: 1rem; font-size: 0.75rem;">
                                        <span style="color: var(--gray-500);">📍</span>
                                        <span style="color: var(--gray-700); margin-left: 0.5rem;">
                                            <?php echo $spatialInfo; ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <!-- Card Footer -->
                                <div style="display: flex; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.75rem;">
                                    <span style="color: var(--gray-400);">Era: <?php echo $stitch->getEra(); ?></span>
                                    <?php if ($stitch->vouch_count > 0): ?>
                                        <span style="color: var(--gray-400); margin-left: auto;">
                                            👍 <?php echo intval($stitch->vouch_count); ?> vouches
                                        </span>
                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        </div>
    </main>

</div>

<!-- JavaScript: Asynchronous Dispatch Feed Ingestion -->
<script>
// ============================================================================
// WONDER CITY DISPATCH FEED - ASYNCHRONOUS STREAMING
// ============================================================================

var DispatchFeed = {
    // Configuration
    pollInterval: 8000, // 8 seconds between refreshes
    pollingHandle: null,
    lastEntryCount: <?php echo count($dispatchEntries); ?>,
    currentEntries: [],

    /**
     * Initialize the dispatch feed polling system
     */
    init: function() {
        console.log('Initializing Wonder City Dispatch Feed polling...');
        this.startPolling();
    },

    /**
     * Start the polling interval for dispatch updates
     */
    startPolling: function() {
        var self = this;
        
        // Perform initial load
        self.refreshFeed();
        
        // Set up periodic refreshing
        self.pollingHandle = setInterval(function() {
            self.refreshFeed();
        }, self.pollInterval);
    },

    /**
     * Stop the polling interval
     */
    stopPolling: function() {
        if (this.pollingHandle) {
            clearInterval(this.pollingHandle);
            this.pollingHandle = null;
        }
    },

    /**
     * Refresh the dispatch feed via AJAX
     */
    refreshFeed: function() {
        var self = this;
        
        mb.ajax({
            type: 'GET',
            url: '/?api=neighborhub',
            data: {
                action: 'get_dispatch_feed',
                limit: 100
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.entries) {
                    self.updateFeedDisplay(response.entries);
                    
                    // Update entry count display
                    var entryCount = response.entries.length;
                    if (entryCount !== self.lastEntryCount) {
                        self.lastEntryCount = entryCount;
                        document.getElementById('dispatch-count').textContent = entryCount + ' entries';
                        
                        // Notify if new entries added
                        if (entryCount > self.lastEntryCount) {
                            self.notifyNewEntries();
                        }
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Dispatch feed refresh error:', error);
                self.displayErrorState();
            }
        });
    },

    /**
     * Update the feed display with new entries
     */
    updateFeedDisplay: function(entries) {
        var container = document.getElementById('dispatch-feed-container');
        if (!container) return;
        
        // If no entries, show empty state
        if (!entries || entries.length === 0) {
            this.displayEmptyState();
            return;
        }
        
        // Build entries HTML
        var html = '<div class="nh-grid nh-grid-auto" style="gap: 1.5rem;">';
        
        entries.forEach(function(entry) {
            html += DispatchFeed.buildEntryCard(entry);
        });
        
        html += '</div>';
        
        // Update container with fade-in effect
        container.style.opacity = '0.7';
        container.innerHTML = html;
        
        // Fade back to full opacity
        setTimeout(function() {
            container.style.opacity = '1';
        }, 100);
    },

    /**
     * Build a single dispatch entry card
     */
    buildEntryCard: function(entry) {
        var content = entry.content || {};
        var trackingStatus = (content.tracking_status || 'UNKNOWN').toUpperCase();
        var description = content.description || 'No description provided';
        var orderNumber = content.order_number || null;
        var merchantName = content.merchant_name || 'Unknown Merchant';
        var courierName = content.courier_name || null;
        var customerName = content.customer_name || 'Unknown Customer';
        
        // Determine badge styling
        var badgeInfo = DispatchFeed.getBadgeInfo(trackingStatus);
        var badgeClass = badgeInfo.class;
        var badgeEmoji = badgeInfo.emoji;
        
        // Format date
        var formattedDate = new Date(entry.projected_to).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Build spatial info
        var spatialInfo = '';
        if (entry.lat && entry.lng) {
            spatialInfo = '<div style="padding: 0.75rem; background: var(--gray-50); border-radius: var(--border-radius-base); margin-bottom: 1rem; font-size: 0.75rem;">' +
                '<span style="color: var(--gray-500);">📍</span>' +
                '<span style="color: var(--gray-700); margin-left: 0.5rem;">' + 
                parseFloat(entry.lat).toFixed(4) + ', ' + parseFloat(entry.lng).toFixed(4) + 
                '</span></div>';
        }
        
        // Build courier section (if applicable)
        var courierSection = '';
        if (courierName) {
            courierSection = '<div>' +
                '<p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Courier</p>' +
                '<p style="margin: 0; color: var(--gray-900);">' + courierName + '</p>' +
                '</div>';
        }
        
        // Build amount section (if applicable)
        var amountSection = '';
        if (content.amount) {
            amountSection = '<div>' +
                '<p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Amount</p>' +
                '<p style="margin: 0; color: var(--gray-900); font-weight: 600;">$' + parseFloat(content.amount).toFixed(2) + '</p>' +
                '</div>';
        }
        
        // Build vouch count
        var vouchCount = entry.vouch_count || 0;
        var vouchDisplay = vouchCount > 0 ? '<span style="color: var(--gray-400); margin-left: auto;">👍 ' + vouchCount + ' vouches</span>' : '';
        
        var card = '<div class="nh-card" data-stitch-id="' + entry.id + '" style="display: flex; flex-direction: column; border-left: 4px solid var(--primary-color);">' +
            '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border-color);">' +
            '<div>' +
            '<h4 style="margin: 0; font-size: 1rem; margin-bottom: 0.25rem;">' + badgeEmoji + ' ' + (orderNumber || 'Dispatch Event') + '</h4>' +
            '<p style="margin: 0; color: var(--gray-500); font-size: 0.75rem;">' + formattedDate + '</p>' +
            '</div>' +
            '<span class="nh-badge ' + badgeClass + '" style="flex-shrink: 0;">' + trackingStatus.replace(/_/g, ' ') + '</span>' +
            '</div>' +
            '<div style="margin-bottom: 1rem;">' +
            '<p style="margin: 0 0 1rem 0; color: var(--gray-900); line-height: 1.5;">' + description + '</p>' +
            '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.875rem;">' +
            '<div>' +
            '<p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Merchant</p>' +
            '<p style="margin: 0; color: var(--gray-900);">' + merchantName + '</p>' +
            '</div>' +
            '<div>' +
            '<p style="margin: 0; color: var(--gray-500); font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Customer</p>' +
            '<p style="margin: 0; color: var(--gray-900);">' + customerName + '</p>' +
            '</div>' +
            courierSection +
            amountSection +
            '</div>' +
            '</div>' +
            spatialInfo +
            '<div style="display: flex; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color); font-size: 0.75rem;">' +
            '<span style="color: var(--gray-400);">Era: ' + new Date(entry.created_at).getFullYear() + '</span>' +
            vouchDisplay +
            '</div>' +
            '</div>';
        
        return card;
    },

    /**
     * Determine badge styling based on tracking status
     */
    getBadgeInfo: function(status) {
        if (status.indexOf('ORDER') !== -1) {
            return { class: 'badge-pending_confirmation', emoji: '📦' };
        } else if (status.indexOf('COURIER') !== -1 || status.indexOf('ASSIGNED') !== -1) {
            return { class: 'badge-confirmed', emoji: '🚗' };
        } else if (status.indexOf('TRANSIT') !== -1) {
            return { class: 'badge-in_transit', emoji: '🛣️' };
        } else if (status.indexOf('DELIVERY') !== -1 || status.indexOf('COMPLETED') !== -1) {
            return { class: 'badge-delivered', emoji: '✓' };
        }
        return { class: 'badge-pending_confirmation', emoji: '📋' };
    },

    /**
     * Display empty state message
     */
    displayEmptyState: function() {
        var container = document.getElementById('dispatch-feed-container');
        if (!container) return;
        
        container.innerHTML = '<div class="nh-card" style="padding: 3rem; text-align: center;">' +
            '<div style="font-size: 3rem; margin-bottom: 1rem;">📡</div>' +
            '<h3 style="margin-bottom: 1rem; color: var(--gray-700);">Sovereign Ledger Online</h3>' +
            '<p style="color: var(--gray-500); margin-bottom: 1.5rem;">Awaiting incoming local dispatch logs via Stitch...</p>' +
            '<p style="color: var(--gray-400); font-size: 0.875rem;">The dispatch stream is ready to receive platform operations.</p>' +
            '</div>';
    },

    /**
     * Display error state
     */
    displayErrorState: function() {
        var container = document.getElementById('dispatch-feed-container');
        if (!container) return;
        
        container.innerHTML = '<div class="nh-alert nh-alert-error">' +
            '<div class="nh-alert-icon">✕</div>' +
            '<div class="nh-alert-content">' +
            '<p class="nh-alert-message">Error refreshing dispatch feed. Retrying in ' + (this.pollInterval / 1000) + ' seconds...</p>' +
            '</div></div>';
    },

    /**
     * Notify user of new dispatch entries
     */
    notifyNewEntries: function() {
        var toast = document.createElement('div');
        toast.style.cssText = 'position: fixed; bottom: 2rem; right: 2rem; background: var(--success-color); color: white; padding: 1rem 1.5rem; border-radius: var(--border-radius-base); box-shadow: var(--shadow-lg); z-index: 2000; animation: slideIn 300ms ease-in-out;';
        toast.textContent = '📡 New dispatch entries received';
        
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.animation = 'slideOut 300ms ease-in-out';
            setTimeout(function() {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
};

// Initialize polling when document is ready
document.addEventListener('DOMContentLoaded', function() {
    DispatchFeed.init();
});

// Clean up polling when page unloads
window.addEventListener('unload', function() {
    DispatchFeed.stopPolling();
});

// CSS for animations
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

    #dispatch-feed-container {
        transition: opacity 300ms ease-in-out;
    }
`;
document.head.appendChild(style);
</script>

