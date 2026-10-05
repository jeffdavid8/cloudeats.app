<?php
if (!defined('MB_RUNNING')) exit;
?>

<?
if (!isset($_SESSION['user'])): ?>
    <div id="neighborhub-signup-modal" class="modal mb-modal-fixed" role="dialog" aria-labelledby="neighborhub-signup-title" aria-modal="true"
        data-recaptcha-site-key="<?= htmlspecialchars($this->config['recaptcha_site_key'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-content">
            <h3><span class="brand-cloud">Cloud</span><span class="brand-eats">Eats</span><span class="brand-extension">.app</span></h3>
            <h4 id="neighborhub-signup-title">Create your account</h4>
            <p>Sign up to order from your neighborhood.</p>
            <form id="neighborhub-signup-form" novalidate>
                <div class="input-field">
                    <input id="neighborhub-signup-name" name="name" type="text" autocomplete="name" maxlength="255" required>
                    <label for="neighborhub-signup-name">Name</label>
                </div>
                <div class="input-field">
                    <input id="neighborhub-signup-email" name="email" type="email" autocomplete="email" maxlength="255" required>
                    <label for="neighborhub-signup-email">Email</label>
                </div>
                <div class="input-field">
                    <input id="neighborhub-signup-password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                    <label for="neighborhub-signup-password">Password (12 characters minimum)</label>
                </div>
                <div class="input-field">
                    <input id="neighborhub-signup-password-confirm" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
                    <label for="neighborhub-signup-password-confirm">Confirm password</label>
                </div>
                <p id="neighborhub-signup-message" role="status" aria-live="polite"></p>
                <button id="neighborhub-signup-submit" class="btn waves-effect waves-light" type="submit">Create account</button>
            </form>
                <div class="row center-align">
                    <div id="recaptcha-inline-container" class="" style="display: inline-block;margin: 23px 0 0 0;"></div>
                </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-close btn-flat">Close</button>
        </div>
    </div>
<?php endif; ?>
