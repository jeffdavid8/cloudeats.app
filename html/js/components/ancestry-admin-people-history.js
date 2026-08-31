/**
 * Ancestry Admin People History Component
 * Handles CSRF token setup and loads external history management scripts
 */

mb.registerComponent('ancestry-admin-people-history', function($element, data) {
    // Setup CSRF token
    window.CSRF_TOKEN = window.CSRF_TOKEN || '';

    // Load external scripts dynamically
    const scripts = [
        '/apps/ancestry/js/admin_history.js',
        '/apps/ancestry/js/auth.js'
    ];

    scripts.forEach(src => {
        if (!document.querySelector(`script[src="${src}"]`)) {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            document.head.appendChild(script);
        }
    });

}, []);