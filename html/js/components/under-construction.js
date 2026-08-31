/**
 * Under Construction Component
 * Manages the development notice modal that appears for new sessions
 * Shows notification when features are under development
 */
mb.registerComponent('under-construction', function($element, data) {
    console.log('Under Construction component initialized');
    
    // Initialize the development notice modal
    function initializeDevelopmentNotice() {
        // Find the modal element
        const $modal = $element.find('#under_construction');
        
        if ($modal.length && typeof M !== 'undefined' && M.Modal) {
            // Initialize the modal
            const modalInstance = M.Modal.init($modal[0], {
                onOpenStart: true
            });
            
            // Check if we should show the notice
            const showNotice = data.showNotice || false;
            
            if (showNotice) {
                console.log('Showing development notice');
                modalInstance.open();
            }
            
            return modalInstance;
        } else {
            console.warn('Modal element not found or Materialize not available');
            return null;
        }
    }
    
    // Wait for Materialize to be available
    function waitForMaterialize() {
        if (typeof M !== 'undefined' && M.Modal) {
            const modalInstance = initializeDevelopmentNotice();
            return modalInstance;
        } else {
            // Wait for Materialize to load
            setTimeout(waitForMaterialize, 100);
        }
    }
    
    // Start initialization
    const modalInstance = waitForMaterialize();
    
    // Expose public methods
    return {
        showNotice: function() {
            if (modalInstance && typeof modalInstance.open === 'function') {
                modalInstance.open();
            } else {
                console.warn('Modal instance not available');
            }
        },
        hideNotice: function() {
            if (modalInstance && typeof modalInstance.close === 'function') {
                modalInstance.close();
            }
        }
    };
}, ['materialize.min.js']); // Depends on Materialize