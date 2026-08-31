/**
 * jQuery Ready Utility
 * Ensures jQuery is loaded before executing jQuery-dependent code
 * Provides a consistent way to handle jQuery dependency across the application
 */
window.jQueryReady = (function() {
    var waitingCallbacks = [];
    var isJQueryReady = false;
    
    function checkJQuery() {
        if (typeof jQuery !== 'undefined') {
            isJQueryReady = true;
            // Execute all waiting callbacks
            while (waitingCallbacks.length > 0) {
                var callback = waitingCallbacks.shift();
                try {
                    callback(jQuery);
                } catch (e) {
                    console.error('Error executing jQuery callback:', e);
                }
            }
        } else {
            setTimeout(checkJQuery, 50);
        }
    }
    
    // Start checking for jQuery
    checkJQuery();
    
    return function(callback) {
        if (typeof callback !== 'function') {
            console.error('jQueryReady expects a function');
            return;
        }
        
        if (isJQueryReady) {
            // jQuery is already ready, execute immediately
            try {
                callback(jQuery);
            } catch (e) {
                console.error('Error executing jQuery callback:', e);
            }
        } else {
            // Add to waiting list
            waitingCallbacks.push(callback);
        }
    };
})();

// Convenience alias for shorter syntax
window.jqReady = window.jQueryReady;