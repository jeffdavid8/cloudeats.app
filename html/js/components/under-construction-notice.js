/**
 * Under Construction Component
 * Displays maintenance/development notice
 */

mb.registerComponent('under-construction-notice', function($element, data) {
    console.log('Initializing under-construction-notice component', $element);
    
    // Show/hide toggle functionality
    $element.find('.toggle-notice-btn').on('click', function() {
        $element.find('.notice-content').slideToggle();
        $(this).text($(this).text() === 'Show Details' ? 'Hide Details' : 'Show Details');
    });
    
    // Dismiss functionality
    $element.find('.dismiss-btn').on('click', function() {
        $element.fadeOut();
        
        // Remember dismissal in localStorage
        if (data.rememberDismissal) {
            localStorage.setItem('under-construction-dismissed', 'true');
        }
    });
    
    // Check if previously dismissed
    if (data.rememberDismissal && localStorage.getItem('under-construction-dismissed') === 'true') {
        $element.hide();
    }
    
    console.log('Under construction notice component initialized');
}, ['jquery']);