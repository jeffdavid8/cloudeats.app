/**
 * Runtime Errors Component
 * Handles display and logging of runtime errors from the MediaBrain application
 * Processes error arrays passed from PHP and logs them to console
 */
mb.registerComponent('runtime-errors', function($element, data) {
    console.log('Runtime Errors component initialized');
    
    // Process runtime errors
    function processRuntimeErrors() {
        const errors = data.errors || [];
        
        if (Array.isArray(errors) && errors.length > 0) {
            console.group('MediaBrain Runtime Errors');
            errors.forEach(function(error, index) {
                console.error(`Error ${index + 1}:`, error);
            });
            console.groupEnd();
            
            // Optional: Display errors in UI for development mode
            if (data.isDevelopment || mb.isDevelopment) {
                displayErrorsInUI(errors);
            }
        } else {
            console.log('No runtime errors detected');
        }
    }
    
    // Display errors in UI for development mode
    function displayErrorsInUI(errors) {
        // Create error display container if it doesn't exist
        let $errorContainer = $('#runtime-errors-display');
        
        if (!$errorContainer.length) {
            $errorContainer = $('<div id="runtime-errors-display" style="position:fixed;bottom:10px;right:10px;background:#f44336;color:white;padding:10px;border-radius:5px;max-width:300px;z-index:9999;font-size:12px;"></div>');
            $('body').append($errorContainer);
        }
        
        // Add errors to container
        let errorHtml = '<strong>Runtime Errors:</strong><br>';
        errors.forEach(function(error, index) {
            errorHtml += `${index + 1}. ${JSON.stringify(error)}<br>`;
        });
        
        // Add close button
        errorHtml += '<button onclick="$(this).parent().remove()" style="background:none;border:1px solid white;color:white;margin-top:5px;cursor:pointer;">Close</button>';
        
        $errorContainer.html(errorHtml);
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            $errorContainer.fadeOut();
        }, 10000);
    }
    
    // Initialize error processing
    processRuntimeErrors();
    
    // Expose public methods
    return {
        processErrors: processRuntimeErrors,
        getErrors: function() {
            return data.errors || [];
        }
    };
}, ['jquery']); // Depends on jQuery