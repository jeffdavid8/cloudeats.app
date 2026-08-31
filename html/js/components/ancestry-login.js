/**
 * Ancestry Login Component
 * Handles app-scoped authentication with AJAX login and finalization via whoami
 */

mb.registerComponent('ancestry-login', function($element, data) {
    
    // Set return_url from query parameter
    const params = new URLSearchParams(window.location.search);
    const returnUrl = params.get('return_url');
    if (returnUrl) {
        document.getElementById('returnUrlField').value = returnUrl;
    }
    
    document.getElementById('ancestryLoginForm').addEventListener('submit', async function(ev) {
        ev.preventDefault();
        const f = new FormData(this);
        const creds = {
            user: f.get('user'),
            pass: f.get('pass'),
            return_url: f.get('return_url')
        };
        const status = document.getElementById('loginStatus');
        status.textContent = '';
        
        try {
            const r = await fetch('/apps/ancestry/auth/login.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams(creds),
                credentials: 'same-origin'
            });
            
            const ct = r.headers.get('Content-Type') || '';
            if (ct.indexOf('application/json') !== -1) {
                const j = await r.json();
                if (j.ok && j.login_token) {
                    // finalize via whoami
                    const who = await fetch('/apps/ancestry/auth/whoami.json.php?login_token=' + encodeURIComponent(j.login_token), {
                        credentials: 'same-origin',
                        cache: 'no-store'
                    });
                    if (who.ok) {
                        const whoData = await who.json();
                        // Use redirect_url from server response or fall back to admin index
                        const redirectUrl = whoData.redirect_url || '?app=ancestry&p=admin/index';
                        window.location.assign(redirectUrl);
                        return;
                    }
                }
                status.textContent = j.error || 'Login failed';
            } else {
                // non-json fallback: redirect
                if (r.redirected) window.location = r.url;
                else status.textContent = 'Unexpected response';
            }
        } catch (e) {
            status.textContent = e.message || 'Network error';
        }
    });

    // Load external auth.js script
    if (!document.querySelector('script[src="/apps/ancestry/js/auth.js"]')) {
        const script = document.createElement('script');
        script.src = '/apps/ancestry/js/auth.js';
        script.async = true;
        document.head.appendChild(script);
    }

}, []);