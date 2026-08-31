/**
 * Dashboard Component
 * Manages dashboard page functionality including logout and app navigation
 * Handles user interactions with application cards and logout buttons
 */
mb.registerComponent('dashboard', function($element, data) {
    console.log('Dashboard component initialized');
    
    // Initialize logout functionality
    function initializeLogout() {
        $element.find('.logout-btn').on('click', function(e) {
            e.preventDefault();
            
            if (typeof mb.userLogout === 'function') {
                mb.userLogout();
            } else {
                console.warn('mb.userLogout function not available, performing manual logout');
                // Fallback logout mechanism
                window.location.href = '/oauth/logout.php';
            }
        });
    }
    
    // Initialize app card navigation
    function initializeAppCards() {
        $element.find('.app-card').on('click', function(e) {
            // Don't trigger if clicking on the link itself
            if (e.target.tagName.toLowerCase() === 'a') {
                return;
            }
            
            e.preventDefault();
            const appUrl = $(this).data('app-url');
            
            if (appUrl) {
                window.location.href = appUrl;
            } else {
                console.warn('No app URL found for card');
            }
        });
        
        // Add hover effects for better UX
        $element.find('.app-card').hover(
            function() {
                $(this).addClass('hoverable-active');
            },
            function() {
                $(this).removeClass('hoverable-active');
            }
        );
    }
    
    // Initialize dashboard components
    function initializeDashboard() {
        initializeLogout();
        initializeAppCards();
        
        console.log('Dashboard functionality initialized');
    }
    
    // Start initialization
    initializeDashboard();
    
    // Expose public methods
    return {
        refreshAppCards: initializeAppCards,
        triggerLogout: function() {
            if (typeof mb.userLogout === 'function') {
                mb.userLogout();
            }
        }
    };
}, ['mediabrain.js']); // Depends on mediabrain.js for mb.userLogout