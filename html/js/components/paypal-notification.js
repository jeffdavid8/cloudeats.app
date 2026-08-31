/**
 * PayPal Notification Component
 * Manages PayPal donation modal and notification functionality
 * Handles automatic display, hiding, and user interactions
 */
mb.registerComponent('paypal-notification', function($element, data) {
    console.log('PayPal Notification component initialized');
    
    // Component configuration
    const config = {
        requestDonations: data.requestDonations || false,
        timeout: data.timeout || 30000,
        autoHideDelay: 10000,
        autoDismissDelay: data.autoDismissDelay
    };
    
    // Initialize the PayPal modal
    function initializePaypalModal() {
        const $modal = $element.find('#paypalDonateModal');
        
        if ($modal.length && typeof M !== 'undefined' && M.Modal) {
            // Initialize the modal
            const modalInstance = M.Modal.init($modal[0]);
            
            // Show then hide function
            function showThenHide() {
                console.log('Showing PayPal donation modal');
                modalInstance.open();
                
                // Auto-hide after delay
                setTimeout(function() {
                    console.log('Auto-hiding PayPal donation modal');
                    modalInstance.close();
                }, config.autoHideDelay);
            }
            
            // Schedule showing the modal if donations are requested
            if (config.requestDonations) {
                console.log(`Scheduling PayPal modal to show in ${config.timeout}ms`);
                setTimeout(showThenHide, config.timeout);
            }
            
            return modalInstance;
        } else {
            console.warn('PayPal modal element not found or Materialize not available');
            return null;
        }
    }
    
    // Initialize notification functionality (existing functionality)
    function initializeNotification() {
        // Close button functionality
        $element.find('.close-notification-btn').on('click', function() {
            $element.fadeOut(300);
        });
        
        // Auto-dismiss after delay if specified
        if (config.autoDismissDelay && parseInt(config.autoDismissDelay) > 0) {
            setTimeout(function() {
                $element.fadeOut(500);
            }, parseInt(config.autoDismissDelay));
        }
        
        // Track notification interaction
        $element.find('a').on('click', function() {
            console.log('PayPal notification link clicked:', $(this).attr('href'));
        });
    }
    
    // Wait for Materialize to be available
    function waitForMaterialize() {
        if (typeof M !== 'undefined' && M.Modal) {
            const modalInstance = initializePaypalModal();
            initializeNotification();
            return modalInstance;
        } else {
            // Initialize notification even without modal functionality
            initializeNotification();
            // Wait for Materialize to load for modal
            setTimeout(function() {
                if (typeof M !== 'undefined' && M.Modal) {
                    initializePaypalModal();
                }
            }, 100);
        }
    }
    
    // Start initialization
    const modalInstance = waitForMaterialize();
    
    console.log('PayPal notification component initialized');
    
    // Expose public methods
    return {
        showDonationModal: function() {
            if (modalInstance && typeof modalInstance.open === 'function') {
                modalInstance.open();
            } else {
                console.warn('PayPal modal instance not available');
            }
        },
        hideDonationModal: function() {
            if (modalInstance && typeof modalInstance.close === 'function') {
                modalInstance.close();
            }
        }
    };
}, ['materialize.min.js']); // Depends on Materialize