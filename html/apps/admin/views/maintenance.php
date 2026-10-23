<?php
if (!defined('MB_RUNNING')) {
    exit;
}

admin_require_admin();
$app = App::getInstance();
$maintenanceStatus = MaintenanceMode::getStatus($app->db);
$maintenanceError = '';
$successMessage = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!AuthManager::validateCsrf($_POST['_csrf'] ?? '')) {
        http_response_code(403);
        $maintenanceError = 'Your session token is invalid. Refresh the page and try again.';
    } elseif ($maintenanceStatus['environment_override']) {
        $maintenanceError = 'Maintenance mode is controlled by the MAINTENANCE_MODE environment variable.';
    } else {
        $message = trim((string)($_POST['message'] ?? ''));
        $eta = trim((string)($_POST['eta'] ?? ''));
        if (strlen($message) > 500 || strlen($eta) > 100) {
            $maintenanceError = 'The maintenance message must be 500 characters or fewer, and the ETA must be 100 characters or fewer.';
        } else {
            $enabled = ($_POST['enabled'] ?? '') === '1';
            if ($enabled && $message === '') {
                $message = 'We are performing scheduled maintenance. Please check back soon.';
            }
            $username = is_array($_SESSION['user'] ?? null)
                ? (string)($_SESSION['user']['username'] ?? 'admin')
                : (string)($_SESSION['user'] ?? 'admin');

            MaintenanceMode::saveSettings($app->db, $enabled, $message, $eta, $username);
            error_log(sprintf(
                'Maintenance mode %s by %s.',
                $enabled ? 'enabled' : 'disabled',
                $username
            ));
            echo '<script>window.location.href = "?app=admin&p=maintenance&updated=1";</script>';
            exit;
        }
    }
}

if (($_GET['updated'] ?? '') === '1') {
    $successMessage = 'Maintenance settings saved.';
    $maintenanceStatus = MaintenanceMode::getStatus($app->db);
}

$csrfToken = htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$messageValue = htmlspecialchars($maintenanceStatus['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$etaValue = htmlspecialchars($maintenanceStatus['eta'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>

<div class="container">
    <h4>Maintenance Mode</h4>
    <p>Pause customer-facing pages and APIs while administrators continue to access the app.</p>

    <?php if ($maintenanceError !== ''): ?>
        <div class="card red lighten-4"><div class="card-content"><?= htmlspecialchars($maintenanceError, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div></div>
    <?php endif; ?>
    <?php if ($successMessage !== ''): ?>
        <div class="card green lighten-4"><div class="card-content"><?= htmlspecialchars($successMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div></div>
    <?php endif; ?>
    <?php if ($maintenanceStatus['environment_override']): ?>
        <div class="card amber lighten-4"><div class="card-content">
            The active mode is controlled by the deployment environment. Set <code>MAINTENANCE_MODE</code> to <code>true</code> or <code>false</code> to override the stored setting.
        </div></div>
    <?php endif; ?>

    <div class="card"><div class="card-content">
        <span class="card-title">
            Status: <?= $maintenanceStatus['enabled'] ? 'Enabled' : 'Disabled' ?>
        </span>
        <form method="post" action="?app=admin&p=maintenance">
            <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">

            <p>
                <label>
                    <input type="checkbox" name="enabled" value="1"
                        <?= $maintenanceStatus['enabled'] ? 'checked' : '' ?>
                        <?= $maintenanceStatus['environment_override'] ? 'disabled' : '' ?>>
                    <span>Enable maintenance mode</span>
                </label>
            </p>

            <div class="input-field">
                <textarea id="maintenance-message" name="message" class="materialize-textarea" maxlength="500"
                    <?= $maintenanceStatus['environment_override'] ? 'disabled' : '' ?>><?= $messageValue ?></textarea>
                <label for="maintenance-message" class="active">Customer notice</label>
            </div>

            <div class="input-field">
                <input id="maintenance-eta" name="eta" type="text" maxlength="100" value="<?= $etaValue ?>"
                    <?= $maintenanceStatus['environment_override'] ? 'disabled' : '' ?>>
                <label for="maintenance-eta" class="active">Estimated return (optional)</label>
                <span class="helper-text">Enter a specific date and time, for example: Tuesday, June 18 at 3:00 PM (local time).</span>
            </div>

            <button class="btn waves-effect waves-light" type="submit"
                <?= $maintenanceStatus['environment_override'] ? 'disabled' : '' ?>>
                Save maintenance settings
            </button>
        </form>
    </div></div>
</div>
