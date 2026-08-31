document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('lcarsContactForm');
    const status = document.getElementById('lcarsStatus');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        status.textContent = 'Transmitting to Mediabrain Command...';
        status.style.color = '#FF9900';
        // AJAX POST to backend
        fetch('/lcars-contact-handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                name: form.name.value,
                email: form.email.value,
                interest: form.interest.value,
                message: form.message.value
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                status.textContent = data.message;
                status.style.color = '#66ff99';
            } else {
                status.textContent = data.message;
                status.style.color = '#FF9900';
            }
        })
        .catch(() => {
            status.textContent = 'Transmission failed. Please try again.';
            status.style.color = '#FF9900';
        });
    });
    // OAuth button handlers
    function openOAuthWindow(provider) {
        // Use a popup for OAuth login
        let url = '';
        if (provider === 'google') {
            url = '/oauth/google.php?action=login&app=splash';
        } else if (provider === 'apple') {
            url = '/oauth/apple.php?action=login&app=splash';
        } else if (provider === 'facebook') {
            url = '/oauth/facebook.php?action=login&app=splash';
        }
        const w = 500, h = 600;
        const left = (screen.width/2)-(w/2);
        const top = (screen.height/2)-(h/2);
        const win = window.open(url, provider + 'OAuth', `width=${w},height=${h},top=${top},left=${left}`);
        status.textContent = `Authenticating with ${provider.charAt(0).toUpperCase() + provider.slice(1)}...`;
        // Poll for authentication completion
        const pollInterval = setInterval(() => {
            if (win.closed) {
                clearInterval(pollInterval);
                // After OAuth, try to fetch user info from session
                fetch('/api/user-info.php')
                    .then(res => res.json())
                    .then(user => {
                        if (user && user.email) {
                            form.name.value = user.name || '';
                            form.email.value = user.email || '';
                            status.textContent = 'Authenticated! User info autofilled.';
                            status.style.color = '#66ff99';
                        } else {
                            status.textContent = 'Authentication failed or cancelled.';
                            status.style.color = '#FF9900';
                        }
                    })
                    .catch(() => {
                        status.textContent = 'Authentication failed.';
                        status.style.color = '#FF9900';
                    });
            }
        }, 1000);
    }
    document.getElementById('auth-google').onclick = function() {
        openOAuthWindow('google');
    };
    document.getElementById('auth-apple').onclick = function() {
        openOAuthWindow('apple');
    };
    document.getElementById('auth-facebook').onclick = function() {
        openOAuthWindow('facebook');
    };
});
