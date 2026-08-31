/**
 * Admin Auth Sidenav Component
 * Handles authentication links in the admin sidenav
 * Manages logout functionality and user session display
 */
mb.registerComponent('admin-auth-sidenav', function($element, data) {
    console.log('Admin Auth Sidenav component initialized');
    
    // Initialize logout functionality
    function initializeLogout() {
        $element.find('.logout-btn').on('click', function(e) {
            e.preventDefault();
            
            if (typeof mb.userLogout === 'function') {
                mb.userLogout();
            } else {
                console.warn('mb.userLogout function not available');
                // Fallback logout
                window.location.href = '/oauth/logout.php';
            }
        });
    }
    
    // Initialize dashboard navigation
    function initializeDashboardNav() {
        $element.find('.dashboard-btn').on('click', function(e) {
            // Let the default navigation work, just add logging
            console.log('Dashboard navigation clicked');
        });
    }
    
    // Initialize authentication functionality
    function initializeAuth() {
        initializeLogout();
        initializeDashboardNav();
        
        console.log('Admin auth sidenav functionality initialized');
    }
    
    // Start initialization
    initializeAuth();
    
    // Expose public methods
    return {
        triggerLogout: function() {
            if (typeof mb.userLogout === 'function') {
                mb.userLogout();
            }
        }
    };
}, ['mediabrain.js']); // Depends on mediabrain.js for mb.userLogout