<?php
// Dashboard Page - Authentication handled by PageController

// Get username from unified session
$username = 'unknown';
$userData = [];

if (isset($_SESSION['user'])) {
    if (is_array($_SESSION['user'])) {
        $username = $_SESSION['user']['username'] ?? 'unknown';
        $userData = $_SESSION['user'];
    } else {
        $username = $_SESSION['user'];
    }
}

$isAdmin = isset($_SESSION['user']) && isset($_SESSION['user']['is_admin']) ? $_SESSION['user']['is_admin'] : false;

// Initialize permissions
$permissionsMatrix = null;
$userApps = [];
$permissionsError = null;
$permissionsPath = __DIR__ . '/../../apps/admin/includes/PermissionsMatrix.php';
if (file_exists($permissionsPath)) {
    try {
        require_once $permissionsPath;
        if (class_exists('PermissionsMatrix')) {
            $permissionsMatrix = new PermissionsMatrix();
            if (method_exists($permissionsMatrix, 'getUserApps')) {
                $userApps = $permissionsMatrix->getUserApps($username);
            }
        }
    } catch (Exception $e) {
        $permissionsError = 'Permission system error: ' . $e->getMessage();
    }
}
//echo '<pre>' . print_r($_SESSION, true) . '</pre>';
//echo '<pre>' . print_r($userApps, true) . '</pre>';
//echo '<pre>' . print_r($structure['apps'], true) . '</pre>';
//$mergedApps = array_merge($this->structure()['apps'], $userApps);
//echo '<pre>' . print_r($mergedApps, true) . '</pre>';

?>

<style>
    .dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 0;
    }

    .user-info .logout-btn {
        display: flex;
    }

    .user-info .logout-btn i {
        margin-right: 0;
    }

    .app-card {
        cursor: pointer;
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }

    .app-card:hover {
        transform: translateY(-2px);
    }

    .user-info {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        margin-bottom: 2rem;
    }

    .user-info h5,
    .user-info p {
        margin: 10px 0;
    }

    .card-content {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .card h5 {
        margin: 0;
        padding: 1rem 0 0 1rem;
    }

    /* Keyframe animation for a "bounce" effect */
    @keyframes bounceIn {
        0% {
            opacity: 0;
            transform: translateY(100px) scale(0.5);
        }

        60% {
            opacity: 1;
            /* Overshoot the final position */
            transform: translateY(-10px) scale(1.05);
        }

        80% {
            /* Bounce back down slightly */
            transform: translateY(5px) scale(0.95);
        }

        100% {
            /* Settle into the final position */
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .app-menu-item a {
        opacity: 0;
        display: block;
        position: relative;
        color: #333;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 5px;
        transform: translateY(50px) scale(0.5);
        text-align: center;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 8px;
        background-color: #eeeae6;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        /* Animation properties */
        animation: bounceIn 0.8s forwards;
        animation-delay: var(--delay-amount, 0s);
        /* Use a CSS variable for staggering */
        transition: transform 0.2s ease-out, box-shadow 0.2s ease-out, border;
    }

    .nightMode .app-menu-item a {
        background-color: #2a2e44;
        border: 1px solid #5b69b3;
        box-shadow: 0 4px 6px rgba(60, 189, 248, 0.2);
        color: #ddd;
    }

    .app-menu-item a span {
        display: block;
        transition: transform 0.2s ease-out;
    }

    .app-menu-item a:hover {
        background-color: #fff;
        /* Light gray on hover */
        box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
    }

    .app-menu-item a:hover span {
        transform: translateY(0px) scale(1.1);
    }

    .nightMode .app-menu-item a:hover {
        background: linear-gradient(0deg, #302b6a 0%, #667eea 100%);
        border: 1px solid #90cef1ff;
        box-shadow: 0 8px 12px rgba(137, 202, 231, 0.3);
    }

    .app-menu-item a:hover i {
        transform: translateY(-3px) scale(1.05);
    }

    .app-menu-item a i {
        transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
        display: block;
        clear: both;
    }

    .error-card {
        border-left: 5px solid #f44336;
    }
</style>

<div class="dashboard-header" data-component="dashboard">
    <div class="container">

        <div class="user-info center">
            <?php if (!empty($_SESSION['user']['profilePicture'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" alt="Profile Picture" class="circle responsive-img" style="width: 115px; height: 115px;">
            <?php else: ?>
                <i class="material-icons large">account_circle</i>
            <?php endif; ?>
            <h5><?= htmlspecialchars($username) ?></h5>
            <p><?= $isAdmin ? 'Administrator' : 'User' ?> Account</p>
            <button class="btn red logout-btn right">
                <i class="material-icons left">exit_to_app</i> Logout
            </button>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <div class="col s12">
            <div class="card" style="position: relative;top: -2rem;border-radius: 22px;">
                <h5 class="hide-on-small-only">Applications</h5>
                <div class="card-content ">
                    <?
                    // Render Applications menu launcher based on permissions
                    if ($permissionsMatrix) {
                        foreach ($userApps as $appId => $appInfo) {
                            echo '<div class="app-menu-item"><a class="app-menu-link" href="?app=' . htmlspecialchars($appId) . '" title="' . htmlspecialchars($appInfo['description'] ?? 'No description available') . '"><span>' . $appInfo['icon'] . htmlspecialchars($appInfo['name'] ?? ucfirst($appId)) . '</span></a></div>';
                        }
                    } else {
                        echo '<h5>Welcome to your Dashboard</h5>';
                        echo '<p class="grey-text">Manage your applications and settings from here</p>';
                    }


                    ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($permissionsError): ?>
        <div class="row">
            <div class="col s12">
                <div class="card error-card">
                    <div class="card-content">
                        <i class="material-icons left red-text">error</i>
                        <span class="card-title red-text">Permission System Error</span>
                        <p><?= htmlspecialchars($permissionsError) ?></p>
                        <p><em>Showing fallback applications. Please contact administrator if this persists.</em></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
    $(document).ready(function() {
        const gridItems = document.querySelectorAll('.app-menu-link');

        gridItems.forEach((item, index) => {
            // Calculate a unique delay for each item (e.g., 50ms per item)
            const delay = index * 50;

            // Set the CSS variable for the delay
            item.style.setProperty('--delay-amount', `${delay}ms`);

            // Ensure they start animating on load (if not using an observer)
            item.style.opacity = 1; /* This will trigger the animation defined above */
        });
    });
</script>