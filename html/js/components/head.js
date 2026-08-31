/**
 * Head Component
 * Manages initialization scripts, jQuery loading, and sequential script loading
 * Contains MediaBrain core initialization and app-specific script loading
 */
mb.registerComponent('head', function($element, data) {
    console.log('Head component initialized');
    
    // MediaBrain global object initialization
    function initializeMediaBrainGlobal() {
        // Initialize mb object if it doesn't exist
        if (typeof window.mb === 'undefined') {
            window.mb = {
                dialogs: [],
                isDevelopment: data.isDevelopment || false,
                isProduction: data.isProduction || false,
            };
        }
        
        // Initialize CSRF token from meta tag if not already set
        if (!window.mb.csrf_token) {
            const meta = document.querySelector('meta[name="csrf-token"]');
            window.mb.csrf_token = meta ? meta.getAttribute('content') : null;
        }
    }
    
    // jQuery fallback handler
    function initializeJQueryFallback() {
        // Check if jQuery loaded from CDN
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery CDN failed, loading local fallback');
            const script = document.createElement('script');
            script.src = 'js/jquery-2.1.1.min.js';
            script.onload = function() {
                console.log('Local jQuery fallback loaded');
                initializeJQueryDependent();
            };
            document.head.appendChild(script);
        } else {
            console.log('jQuery CDN loaded successfully');
            initializeJQueryDependent();
        }
    }
    
    // Initialize jQuery-dependent functionality
    function initializeJQueryDependent() {
        $(document).ready(function() {
            // Ensure jQuery is available globally
            if (typeof $ === 'undefined') {
                window.$ = jQuery;
            }
            
            console.log('jQuery ready, loading dependent scripts');
            loadScriptsSequentially();
        });
    }
    
    // Sequential script loader
    function loadScriptsSequentially() {
        const scripts = [
            'js/init.js',           // Load init.js first as it initializes UI components
            'js/jquery.json-viewer.js',
            'js/cycle.js', 
            'js/json2.js',
            'js/overlay.js',
            'js/mediabrain.js',
            'js/component-registry.js',  // Load component registry after mediabrain.js to extend mb object
            'js/component-loader.js',    // Load component auto-loader after registry
            'js/commands.js'
        ];
        
        // Add app-specific scripts from data
        if (data.appScripts && Array.isArray(data.appScripts)) {
            data.appScripts.forEach(function(script) {
                scripts.push(script);
                console.log('Added app script to queue: ' + script);
            });
        } else {
            console.log('No app scripts found');
        }
        
        function loadScript(index) {
            if (index >= scripts.length) {
                console.log('All scripts loaded successfully');
                return;
            }
            
            console.log('Loading script: ' + scripts[index]);
            const script = document.createElement('script');
            script.src = scripts[index];
            script.onload = function() {
                console.log('Loaded: ' + scripts[index]);
                loadScript(index + 1);
            };
            script.onerror = function() {
                console.warn('Failed to load script: ' + scripts[index]);
                loadScript(index + 1); // Continue loading other scripts
            };
            document.head.appendChild(script);
        }
        
        loadScript(0);
    }
    
    // Initialize everything in sequence
    initializeMediaBrainGlobal();
    
    // Wait for DOM to be ready before checking jQuery
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeJQueryFallback);
    } else {
        initializeJQueryFallback();
    }
});