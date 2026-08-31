/**
 * Programmatic Header Component
 * Manages header navigation and dialog initialization for programmatic pages
 * Handles share, open, and save dialog functionality
 */
mb.registerComponent('programmatic-header', function($element, data) {
    console.log('Programmatic Header component initialized');
    
    // Initialize dialogs
    function initializeDialogs() {
        // Initialize share dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.share === 'function') {
            mb.dialogs.share('init', 0, function(imports) {
                console.log('Share dialog initialized:', imports);
            });
        } else {
            console.warn('Share dialog not available');
        }
        
        // Initialize open dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.open === 'function') {
            mb.dialogs.open('init', 0, function(imports) {
                console.log('Open dialog initialized:', imports);
            });
        } else {
            console.warn('Open dialog not available');
        }
        
        // Initialize save dialog
        if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.save === 'function') {
            mb.dialogs.save('init', 0, function(imports) {
                console.log('Save dialog initialized:', imports);
            });
        } else {
            console.warn('Save dialog not available');
        }
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
        
        // Save button handler
        $element.find('.save_btn').on('click', function(e) {
            e.preventDefault();
            if (typeof mb.dialogs !== 'undefined' && typeof mb.dialogs.save === 'function') {
                mb.dialogs.save('open');
            } else {
                console.warn('Save dialog not available');
            }
        });
    }
    
    // Wait for dialogs to be available before initialization
    function waitForDialogs() {
        if (typeof mb.dialogs !== 'undefined') {
            initializeDialogs();
            bindHeaderEvents();
        } else {
            // Wait a bit longer for dialogs to load
            setTimeout(waitForDialogs, 100);
        }
    }
    
    // Start initialization
    waitForDialogs();
    
    // Expose public methods
    return {
        reinitializeDialogs: initializeDialogs,
        bindEvents: bindHeaderEvents
    };
}, ['mediabrain.js']); // Depends on mediabrain.js for mb.dialogs