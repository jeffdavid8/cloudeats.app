/**
 * Edit Header Component
 * Manages header navigation for edit pages with unsaved changes detection
 * Handles dialogs and prevents navigation with unsaved changes
 */
mb.registerComponent('edit-header', function($element, data) {
    console.log('Edit Header component initialized');
    
    // Initialize dialogs
    function initializeDialogs() {
        // Initialize share dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.share === 'function') {
            mb.dialogs.share('init', 0, function(imports) {
                console.log('Share dialog initialized:', imports);
            });
        }
        
        // Initialize open dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.open === 'function') {
            mb.dialogs.open('init', 0, function(imports) {
                console.log('Open dialog initialized:', imports);
            });
        }
        
        // Initialize save dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.save === 'function') {
            mb.dialogs.save('init', 0, function(imports) {
                console.log('Save dialog initialized:', imports);
            });
        }
    }
    
    // Setup unsaved changes detection
    function setupUnsavedChangesDetection() {
        // Warn user before leaving page if there are unsaved changes
        window.onbeforeunload = function() {
            if ($('body').hasClass('modified')) {
                // Show tap target notification
                const $tapTarget = $('#save-notify-tap-target');
                if ($tapTarget.length && typeof $tapTarget.tapTarget === 'function') {
                    $tapTarget.tapTarget('open');
                }
                return "You have changes that have not been applied yet. Would you like to discard them?";
            }
        };
    }
    
    // Bind header button events
    function bindHeaderEvents() {
        // Share button handler
        $element.find('.share_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.share === 'function') {
                mb.dialogs.share('open');
            } else {
                console.warn('Share dialog not available');
            }
        });
        
        // Open button handler
        $element.find('.open_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.open === 'function') {
                mb.dialogs.open('open');
            } else {
                console.warn('Open dialog not available');
            }
        });
        
        // Save button handler - handles storage and page reload
        $element.find('.save_btn').on('click', function(e) {
            e.preventDefault();
            
            try {
                // Call storage_set function if available
                if (typeof storage_set === 'function') {
                    storage_set();
                } else {
                    console.warn('storage_set function not available');
                }
                
                // Remove modified class
                $('body').removeClass('modified');
                
                // Reload the page
                location.reload();
                
            } catch (error) {
                console.error('Error during save operation:', error);
            }
        });
    }
    
    // Wait for dialogs to be available before initialization
    function waitForDialogs() {
        if (typeof mb.dialogs !== 'undefined') {
            initializeDialogs();
            bindHeaderEvents();
            setupUnsavedChangesDetection();
        } else {
            // Wait for dialogs to load
            setTimeout(waitForDialogs, 100);
        }
    }
    
    // Start initialization
    waitForDialogs();
    
    // Expose public methods
    return {
        reinitializeDialogs: initializeDialogs,
        bindEvents: bindHeaderEvents,
        checkUnsavedChanges: function() {
            return $('body').hasClass('modified');
        }
    };
}, ['mediabrain.js']); // Depends on mediabrain.js for mb.dialogs