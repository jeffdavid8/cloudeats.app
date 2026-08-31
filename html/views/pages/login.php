<?php
// Login Page - Form UI only
// Login processing is handled by auth API: /?api=auth&action=login

$app = App::getInstance();
$error = [];
$redirectUrl = get_var('return', '?p=dashboard');

// Detect if logged in already, and redirect to dashboard
if ($app->getAuthManager()::isUserLoggedIn()) {
    header('Location: ' . $redirectUrl, true, 302);
    exit();
}

// Handle app-specific access requests
$requestedApp = $_GET['app'] ?? null;
$appDisplayName = $requestedApp ? ucfirst($requestedApp) : 'MediaBrain';
$appMessage = '';

if ($requestedApp) {
    switch ($requestedApp) {
        case 'ancestry':
            $appMessage = 'This is a private family genealogy collection. Please sign in with your authorized account to access our family tree and research.';
            break;
        default:
            $appMessage = "Access to {$appDisplayName} requires authentication. Please sign in to continue.";
    }
}

// Handle OAuth errors
if (isset($_GET['oauth_error'])) {
    $error[] = 'OAuth Login Failed: ' . htmlspecialchars($_GET['oauth_error']);
}

// Check for error messages from session (e.g., from rate limiting)
if (!empty($_SESSION['login_error'])) {
    $error[] = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>

<!-- Font Awesome for OAuth icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">

<? include_theme_css('startrek', ['components.css', 'lcars-base.css']); ?>

<style>
    /* Override main site styles for login page */
    body {
        background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        min-height: 100vh;
        align-items: center;
        justify-content: center;
        margin: 0;
    }

    .login-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 475px;
        margin: 20px auto;
    }

    .login-header {
        margin-bottom: 30px;
        text-align: center;
        clear: both;
    }

    .login-header h4 {
        color: #333;
        margin-bottom: 10px;
    }

    .login-header p {
        color: #666;
        margin: 0;
    }

    .input-field {
        margin: 1.5em 0;
    }

    .input-field input {
        height: 1.75em !important;
    }

    .input-field input:focus+label {
        color: #667eea !important;
    }

    .input-field input:focus {
        border-bottom: 1px solid #667eea !important;
        box-shadow: 0 1px 0 0 #667eea !important;
    }

    .btn-login {
        background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        width: 100%;
        margin-top: 20px;
    }

    .demo-users {
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        margin-top: 20px;
    }

    .demo-users h6 {
        margin-top: 0;
        color: #666;
    }

    .demo-users ul {
        margin: 10px 0 0 0;
    }

    .demo-users li {
        color: #888;
        font-size: 0.9em;
    }

    /* OAuth Styles */
    .oauth-divider {
        text-align: center;
        margin: 25px 0 20px 0;
        position: relative;
    }

    .oauth-divider:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: #e0e0e0;
    }

    .oauth-divider span {
        background: white;
        padding: 0 15px;
        color: #999;
        font-size: 0.9em;
    }

    .oauth-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .oauth-btn {
        flex: 1;
        height: 48px;
        border: 1px solid #ddd;
        border-radius: 6px;
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        text-transform: none;
        font-size: 14px;
    }

    .oauth-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .oauth-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .google-btn {
        color: #4285f4;
        border-color: #4285f4;
    }

    .google-btn:hover:not(:disabled) {
        background-color: #4285f4;
        color: white;
    }

    .apple-btn {
        color: #000;
        border-color: #000;
    }

    .apple-btn:hover:not(:disabled) {
        background-color: #000;
        color: white;
    }

    .facebook-btn {
        color: #1877f2;
        border-color: #1877f2;
    }

    .oauth-btn:hover:not(:disabled) {
        background-color: #1877f2;
        color: white;
    }

    .oauth-btn i {
        font-size: 18px;
    }

    .oauth-status {
        text-align: center;
        margin-bottom: 10px;
    }

    /* Theme Toggle Button */
    .theme-toggle {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(174, 174, 174, 0.46);
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        z-index: 10;
    }

    button:focus {
        outline: none;
        background-color: #cecece;
    }

    .theme-toggle:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: scale(1.1);
    }

    .theme-toggle i {
        color: white;
        font-size: 20px;
    }

    /* Base transitions for smooth theme switching */
    body {
        transition: background 0.3s ease !important;
        background-attachment: fixed !important;
        background-repeat: no-repeat !important;
        min-height: 100vh;
    }

    .login-container {
        transition: background-color 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
        position: relative;
    }

    .login-header h4,
    .login-header p {
        transition: color 0.3s ease;
    }

    .input-field input,
    .input-field label,
    .material-icons.prefix {
        transition: color 0.3s ease, border-color 0.3s ease;
    }

    /* Night Mode Styles */
    body.nightMode {
        background-image: linear-gradient(135deg, #000 0%, #121828 50%, #2d263e 100%) !important;
    }

    .nightMode .login-container {
        /* background-image: linear-gradient(135deg, #0b0a18 0%, #663e141f 50%, #26212f 100%) !important; 
        border: 1px solid #404060;
        */
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    }

    .nightMode .theme-toggle {
        background: rgba(0, 0, 0, 0.2);
    }

    .nightMode .theme-toggle:hover {
        background: rgba(0, 0, 0, 0.3);
    }

    .nightMode .login-header h4 {
        color: #ffffff;
    }

    .nightMode .login-header p {
        color: #b0b0b0;
    }

    .nightMode input,
    .nightMode textarea,
    .nightMode select {
        background-color: #2d2d2d52 !important;
    }

    .nightMode .input-field input {
        color: #ffffff;
        border-bottom: 1px solid #555;
    }

    .nightMode .input-field input:focus {
        border-bottom: 1px solid #667eea !important;
        box-shadow: 0 1px 0 0 #667eea !important;
    }

    .nightMode .input-field label {
        color: #888;
    }

    .nightMode .input-field label.active {
        color: #667eea;
    }

    .nightMode .input-field input:focus+label {
        color: #667eea !important;
    }

    .nightMode .material-icons.prefix {
        color: #888;
    }

    .nightMode .btn-login {
        background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .nightMode .demo-users {
        background-color: #1a1a2e;
        border: 1px solid #404060;
    }

    .nightMode .demo-users h6 {
        color: #ffffff;
    }

    .nightMode .demo-users li {
        color: #b0b0b0;
    }

    .nightMode .oauth-divider:before {
        background: #555;
    }

    .nightMode .oauth-divider span {
        background: #2d2d44;
        color: #888;
    }

    .nightMode .oauth-btn {
        background: #1a1a2e;
        border: 1px solid #555;
        color: #ffffff;
    }

    .nightMode .card-panel.red.lighten-4 {
        background-color: #3d1a1a !important;
        color: #ff6b6b !important;
        border: 1px solid #5d2a2a;
    }

    .nightMode .card-panel.green.lighten-4 {
        background-color: #1a3d1a !important;
        color: #6bff6b !important;
        border: 1px solid #2a5d2a;
    }

    .nightMode .grey-text {
        color: #888 !important;
    }

    .nightMode a.grey-text:hover {
        color: #b0b0b0 !important;
    }

    .nightMode #oauth-status {
        color: #888;
    }
</style>
<div style="width: 100%; min-height: 110vh;" data-component="login-page">
    <div class="lcars-console">
        <div class="left" style="position: relative; top: -10px;">
            <a class="go-back-btn" href="javascript:history.back()"></a>
        </div>
        <button class="theme-toggle" title="Toggle theme">
            <i class="material-icons">dark_mode</i>
        </button>
        <div class="login-header">
            <h4><?php echo $requestedApp ? "Access {$appDisplayName}" : 'MediaBrain'; ?></h4>
        </div>
        <p><?php echo $appMessage ?: 'Sign in to your account'; ?></p>

        <?php if (!empty($error)): ?>
            <div class="card-panel red lighten-4 red-text text-darken-2 error-message" style="margin-bottom: 20px;">
                <i class="material-icons left">error</i><?php echo htmlspecialchars(implode("\n", $error)); ?>
            </div>
        <?php else: ?>
            <div class="error-message" style="display: none; margin-bottom: 20px;"></div>
        <?php endif; ?>

        <!-- OAuth Login Section -->
        <div class="oauth-buttons">
            <button onclick="loginWithGoogle()" class="btn-flat oauth-btn google-btn waves-effect">
                <i class="fab fa-google"></i>
                <span class="hide-on-small-only">Google</span>
            </button>
            <button onclick="loginWithFacebook()" class="btn-flat oauth-btn facebook-btn waves-effect">
                <i class="fab fa-facebook-f"></i>
                <span class="hide-on-small-only">Facebook</span>
            </button>
            <button onclick="loginWithLinkedin()" class="btn-flat oauth-btn linkedin-btn waves-effect">
                <i class="fab fa-linkedin-in"></i>
                <span class="hide-on-small-only">LinkedIn</span>
            </button>
        </div>

        <? /*
            <div class="oauth-status" id="oauth-status" <?php echo $requestedApp ? 'style="display: none;"' : ''; ?>>
                <small class="grey-text"><?php echo $requestedApp ? "Sign in with your authorized account" : "OAuth providers not configured"; ?></small>
            </div>
            */ ?>

        <? /* Enable divider text only if OAuth is available
        <div class="oauth-divider">
            <span>or continue with</span>
        </div>
        */ ?>
        <hr>

        <form id="loginForm" method="POST" action="/?api=auth">
            <input type="hidden" name="return" value="<?php echo htmlspecialchars($redirectUrl); ?>">
            <input type="hidden" name="action" value="login">

            <? if ((is_development() || is_production() || $_GET['login_form'] === 1)) { ?>

                <div class="input-field">
                    <i class="material-icons prefix">account_circle</i>
                    <input id="username" name="username" type="text" class="validate" required>
                    <label for="username">Username</label>
                </div>

                <div class="input-field">
                    <i class="material-icons prefix">lock</i>
                    <input id="password" name="password" type="password" class="validate" required>
                    <label for="password">Password</label>
                </div>

                <button class="btn waves-effect waves-light btn-login" type="submit" id="submitBtn">
                    Sign In
                    <i class="material-icons right">send</i>
                </button>

            <? } ?>

            <div class="center-align" style="margin-top: 20px;">
                <a href="/" class="grey-text"><i class="fas fa-home"></i> Home</a>
            </div>
        </form>

        <script>
            function loginWithGoogle() {
                const state = generateRandomString(32);
                //play('audio/star trek sounds/computerbeep_18.mp3');
                sessionStorage.setItem("oauth_state", state);
                // get return URL from query param if exists
                const urlParams = new URLSearchParams(window.location.search);
                let returnUrl = getQueryParam('return') || '/?p=dashboard';
                window.location.href = `oauth/google.php?action=login&state=${state}&return_url=${encodeURIComponent(returnUrl)}`;
            }

            function loginWithApple() {
                const state = generateRandomString(32);
                //play('audio/star trek sounds/computerbeep_18.mp3');
                sessionStorage.setItem("oauth_state", state);
                const urlParams = new URLSearchParams(window.location.search);
                let returnUrl = getQueryParam('return') || '/?p=dashboard';
                window.location.href = `oauth/apple.php?action=login&state=${state}&return_url=${encodeURIComponent(returnUrl)}`;
            }

            function loginWithFacebook() {
                const state = generateRandomString(32);
                //play('audio/star trek sounds/computerbeep_18.mp3');
                sessionStorage.setItem("oauth_state", state);
                const urlParams = new URLSearchParams(window.location.search);
                let returnUrl = getQueryParam('return') || '/?p=dashboard';
                window.location.href = `oauth/facebook.php?action=login&state=${state}&return_url=${encodeURIComponent(returnUrl)}`;
            }

            function loginWithLinkedin() {
                const state = generateRandomString(32);
                //play('audio/star trek sounds/computerbeep_18.mp3');
                sessionStorage.setItem("oauth_state", state);
                const urlParams = new URLSearchParams(window.location.search);
                let returnUrl = getQueryParam('return') || '/?p=dashboard';
                window.location.href = `oauth/linkedin.php?action=login&state=${state}&return_url=${encodeURIComponent(returnUrl)}`;
            }

            function getQueryParam(param) {
                const urlParams = new URLSearchParams(window.location.search);
                return urlParams.get(param);
            }

            function generateRandomString(length) {
                const charset =
                    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
                let result = "";
                for (let i = 0; i < length; i++) {
                    result += charset.charAt(Math.floor(Math.random() * charset.length));
                }
                return result;
            }

            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = document.getElementById('submitBtn');
                const originalText = submitBtn.innerHTML;

                // 🛰️ Visual Feedback (The "Wait" Signal)
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="material-icons">hourglass_empty</i> Signing in...';

                // 🕵️ Convert FormData to a plain object for mb.post
                const formData = new FormData(this);
                const dataObject = JSON.stringify(Object.fromEntries(formData.entries()));

                // 🏗️ Using the mb.post wrapper
                // We point to "auth" api with "login" action
                mb.post('?api=auth', dataObject)
                    .then(data => {
                        if (data && data.success) {
                            // 🎷 Success! Play the "Genuine" Signal
                            play('audio/star trek sounds/computer_work_beep.mp3');

                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                window.location.href = '/?p=dashboard';
                            }
                        } else {
                            // 🛑 The "Goobery" Error Signal
                            play('audio/star trek sounds/computer_error.mp3');

                            const errorMsg = data.error || 'Login failed';
                            const errorDiv = document.querySelector('.error-message');

                            if (errorDiv) {
                                errorDiv.textContent = errorMsg;
                                errorDiv.style.display = 'block';
                            } else {
                                alert(errorMsg);
                            }

                            // Reset the button so the Architect can try again
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    })
                    .fail(error => { // 🩹 Using .fail() to match your mb toolkit!
                        console.error('Login error:', error);
                        play('audio/star trek sounds/computer_error.mp3');
                        alert('Login failed: ' + (error.statusText || 'Server Error'));

                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
            });
        </script>

        <?php if ($requestedApp): ?>
            <div class="demo-users">
                <h6>App Access Required:</h6>
                <p style="color: #888; font-size: 0.9em; margin: 10px 0;">
                    <i class="material-icons" style="font-size: 16px; vertical-align: text-bottom;">info</i>
                    Only authorized users can access <?php echo $appDisplayName; ?>.
                    Please contact the administrator if you believe you should have access.
                </p>
            </div>
        <?php else: ?>

            <? if ((is_development() || $_GET['login_form'] === 1)): ?>

                <div class="demo-users">
                    <h6>Demo Accounts:</h6>
                    <ul>
                        <li><strong>User:</strong> user / password123</li>
                        <li><strong>Demo:</strong> demo / demo123</li>
                        <li><strong>Guest:</strong> guest / guest123</li>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>