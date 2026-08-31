/**
 * Header Right Component
 * Manages right header navigation including fullscreen toggle, user dropdown, and PHP version sync
 * Handles user authentication display and logout functionality
 */
mb.registerComponent('header-right', function($element, data) {
    mb.log('Header Right component initialized');
    
    // Initialize fullscreen functionality
    function initializeFullscreen() {
        $element.find('.fullscreen-btn').on('click', function(e) {
            e.preventDefault();
            
            const $fullScreenElement = $('body');
            $fullScreenElement.toggleClass('fullscreen');

            if ($fullScreenElement.hasClass('fullscreen')) {
                $(this).html('<i class="fas fa-compress"></i>');
                if (document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen();
                }
            } else {
                $(this).html('<i class="fas fa-expand"></i>');
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });
    }
    
    // Initialize user dropdown
    function initializeUserDropdown() {
        const dropdownElements = $element.find('.dropdown-trigger');
        mb.log('Initializing user dropdown, found elements:', dropdownElements.length);
        
        if (dropdownElements.length && typeof M !== 'undefined' && M.Dropdown) {
            dropdownElements.each(function() {
                // Destroy existing dropdown instance if any
                const existingInstance = M.Dropdown.getInstance(this);
                if (existingInstance) {
                    existingInstance.destroy();
                }
                
                const instance = M.Dropdown.init(this, {
                    constrainWidth: false,
                    coverTrigger: false,
                    alignment: 'right',
                    closeOnClick: true
                });
                
                mb.log('User dropdown initialized:', instance);
            });
        } else {
            console.warn('Materialize Dropdown not available or no dropdown elements found', {
                materializeAvailable: typeof M !== 'undefined',
                dropdownClass: typeof M !== 'undefined' && M.Dropdown,
                elementsFound: dropdownElements.length
            });
        }
    }
    
    // Initialize logout functionality
    function initializeLogout() {
        /* replacing this with inline href link to ?app=auth&action=logout 
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
        */
    }
    
    // Sync PHP version (currently disabled but available for re-enabling)
    function syncPhpVersion() {
        $.ajax({
            url: 'api.php',
            dataType: 'json',
            data: {
                'action': 'info',
                'data': {
                    'type': 'php-version',
                }
            },
            success: function(data) {
                mb.log('PHP version synced:', data);
                $element.find('.phpVersionNumber').html(data.info);
            },
            error: function(response) {
                mb.log('PHP version sync failed:', response);
            },
        });
    }
    
    // Wait for dependencies before initialization
    function waitForDependencies() {
        // Check if Materialize is loaded
        if (typeof M !== 'undefined' && typeof $ !== 'undefined') {
            // Additional wait to ensure DOM is fully ready
            setTimeout(function() {
                initializeUserDropdown();
                initializeFullscreen();
                initializeLogout();
            }, 100);
        } else {
            // Wait for Materialize and jQuery to load
            setTimeout(waitForDependencies, 100);
        }
    }
    
    // Start initialization
    waitForDependencies();
    
    // Expose public methods
    return {
        refreshDropdown: initializeUserDropdown,
        syncPhpVersion: syncPhpVersion,
        toggleFullscreen: function() {
            $element.find('.fullscreen-btn').trigger('click');
        }
    };
}, ['materialize.min.js', 'mediabrain.js']); // Depends on Materialize and MediaBrain