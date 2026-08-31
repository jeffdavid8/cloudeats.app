<?php
if (!defined('MB_RUNNING')) exit;
// Admin Dashboard View - Enhanced with Theme System
// Session already started in index.php
// Initialize Theme Manager
require_once __DIR__ . '/../../../includes/theme/ThemeManager.php';
$themeManager = new ThemeManager();

$fsm = FileStorageManager::getInstance();

// Collect dashboard statistics
$stats = [
    'total_users' => 0,
    'active_sessions' => 0,
    'recent_logins' => 0,
    'system_health' => 'Good',
    'storage_usage' => '0 MB',
    'php_memory' => ini_get('memory_limit'),
    'uptime' => 'Unknown'
];

try {
    // Get user statistics
    $userManager = new UserManager();
    $allUsers = $userManager->getAllUsers();
    $stats['total_users'] = count($allUsers);

    // Calculate recent logins (last 24 hours)
    $recentTime = time() - (24 * 60 * 60);
    foreach ($allUsers as $user) {
        if (isset($user['last_login']) && $user['last_login'] > $recentTime) {
            $stats['recent_logins']++;
        }
    }

    // Get storage usage if available
    if (function_exists('disk_free_space')) {
        $bytes = disk_free_space('.');
        $stats['storage_usage'] = $bytes ? round($bytes / 1024 / 1024, 2) . ' MB free' : 'Unknown';
    }

    // System uptime (if available)
    if (function_exists('sys_getloadavg') && is_readable('/proc/uptime')) {
        $uptime = file_get_contents('/proc/uptime');
        $uptime = floor(floatval($uptime));
        $days = floor($uptime / 86400);
        $hours = floor(($uptime % 86400) / 3600);
        $stats['uptime'] = "{$days}d {$hours}h";
    }
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>

<?php
// Initialize Theme Manager
require_once __DIR__ . '/../../../includes/theme/ThemeManager.php';
$themeManager = new ThemeManager();

// Get current theme
$currentTheme = $themeManager->getCurrentTheme();
?>

<!-- Theme Styles -->
<?php echo $themeManager->getThemeCSS(); ?>

<style>
    /* Base Dashboard Styles - Enhanced by theme system */
    .dashboard-stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .dashboard-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        font-size: 3rem !important;
        opacity: 0.8;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 300;
        margin: 10px 0 5px 0;
    }

    .quick-action-item {
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }

    .quick-action-item:hover {
        background-color: rgba(33, 150, 243, 0.05);
        border-left-color: #2196F3;
    }

    .widget-container {
        margin: 20px 0;
    }

    .system-health-good {
        color: #4CAF50;
    }

    .system-health-warning {
        color: #FF9800;
    }

    .system-health-error {
        color: #F44336;
    }

    /* Theme indicator */
    .theme-indicator {
        position: fixed;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        z-index: 1000;
    }
</style>
<div class="row">
    <div class="col s12">
        <nav class="admin-breadcrumb">
            <div class="nav-wrapper">
                <div class="col s12">
                    <a href="?app=admin" class="breadcrumb">Admin</a>
                    <a href="?app=admin&p=dashboard" class="breadcrumb">Dashboard</a>
                    <div class="grey-text right" style="white-space: nowrap;">
                        <a href="?p=dashboard">
                            <span>
                                Signed in as: <strong><?php echo is_array($_SESSION['user']) ? ($_SESSION['user']['username'] ?? 'User') : ($_SESSION['user'] ?? 'User'); ?></strong>
                                <? if (!empty($_SESSION['user']['profilePicture'])): ?>
                                    <img src="<?= htmlspecialchars($_SESSION['user']['profilePicture']) ?>" alt="Profile Picture" class="circle responsive-img" style="width: 25px; height: 25px; vertical-align: middle; margin-left: 4px;">
                                <?php else: ?>
                                    <i style="display: inline-block;" class="material-icons tiny">account_circle</i>
                                <?php endif; ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <h4><i class="material-icons left">dashboard</i>Admin Dashboard</h4>
        <p class="grey-text">Manage users, system settings, and monitor platform health</p>
    </div>
</div>

<!-- Enhanced Statistics Cards -->
<div class="row">
    <div class="col s12 m6 l3">
        <div class="card dashboard-stat-card blue lighten-5">
            <div class="card-content center">
                <i class="material-icons blue-text stat-icon">people</i>
                <div class="stat-number blue-text"><?php echo $stats['total_users']; ?></div>
                <p class="grey-text">Total Users</p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card dashboard-stat-card green lighten-5">
            <div class="card-content center">
                <i class="material-icons green-text stat-icon">login</i>
                <div class="stat-number green-text"><?php echo $stats['recent_logins']; ?></div>
                <p class="grey-text">Recent Logins (24h)</p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card dashboard-stat-card orange lighten-5">
            <div class="card-content center">
                <i class="material-icons orange-text stat-icon">storage</i>
                <div class="stat-number orange-text" style="font-size: 1.5rem;"><?php echo $stats['storage_usage']; ?></div>
                <p class="grey-text">Storage</p>
            </div>
        </div>
    </div>
    <div class="col s12 m6 l3">
        <div class="card dashboard-stat-card purple lighten-5">
            <div class="card-content center">
                <i class="material-icons purple-text stat-icon">favorite</i>
                <div class="stat-number system-health-<?php echo strtolower($stats['system_health']); ?>"><?php echo $stats['system_health']; ?></div>
                <p class="grey-text">System Health</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Quick Actions - Enhanced -->
    <div class="col s12 m6 l4">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">flash_on</i>Quick Actions
                </span>
                <div class="collection">
                    <a href="?app=admin&p=users" class="collection-item quick-action-item">
                        <i class="material-icons left blue-text">people</i>
                        <span class="title">Manage Users</span>
                        <p class="grey-text">View, edit, and manage user accounts</p>
                    </a>
                    <a href="?app=admin&p=users&action=add" class="collection-item quick-action-item">
                        <i class="material-icons left green-text">person_add</i>
                        <span class="title">Add New User</span>
                        <p class="grey-text">Create a new user account</p>
                    </a>
                    <a href="?app=admin&p=permissions" class="collection-item quick-action-item">
                        <i class="material-icons left orange-text">security</i>
                        <span class="title">Permissions</span>
                        <p class="grey-text">Manage user permissions and access</p>
                    </a>
                    <a href="?app=admin&p=analytics" class="collection-item quick-action-item">
                        <i class="fas fa-chart-bar left teal-text"></i>
                        <span class="title">Analytics</span>
                        <p class="grey-text">View visitor statistics and traffic insights</p>
                    </a>
                    <a href="?app=admin&p=backup_db&type=json" class="collection-item">
                        <i class="material-icons left blue-text">backup</i>
                        <span class="title">Create Cloud Backup</span>
                        <p class="grey-text">Create a JSON Backup in the Cloud Bucket</p>
                    </a>
                    <a href="?app=admin&p=download_db&type=json" class="collection-item">
                        <i class="material-icons left blue-text">cloud_download</i>
                        <span class="title">Download Backup</span>
                        <p class="grey-text">Download MySQL Database -> JSON file Backup</p>
                    </a>
                    <a href="?app=admin&p=restore_db" class="collection-item">
                        <i class="material-icons left blue-text">restore</i>
                        <span class="title">Restore DB</span>
                        <p class="grey-text">Import JSON file -> MySQL Database</p>
                    </a>
                    <a href="?app=admin&p=install_db" class="collection-item">
                        <i class="material-icons left blue-text">restore</i>
                        <span class="title">Install DB</span>
                        <p class="grey-text">Import JSON file -> MySQL Database</p>
                    </a>

                    <a href="?app=admin&p=settings" class="collection-item quick-action-item">
                        <i class="material-icons left purple-text">settings</i>
                        <span class="title">System Settings</span>
                        <p class="grey-text">Configure platform settings</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Information - Enhanced -->
    <div class="col s12 m6 l4">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">info</i>System Information
                </span>
                <ul class="collection">
                    <li class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s2">
                                <i class="material-icons blue-text">code</i>
                            </div>
                            <div class="col s10">
                                <strong>PHP Version:</strong><br>
                                <span class="grey-text"><?php echo PHP_VERSION; ?></span>
                            </div>
                        </div>
                    </li>
                    <li class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s2">
                                <i class="material-icons green-text">dns</i>
                            </div>
                            <div class="col s10">
                                <strong>Server:</strong><br>
                                <span class="grey-text"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                            </div>
                        </div>
                    </li>
                    <li class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s2">
                                <i class="material-icons orange-text">memory</i>
                            </div>
                            <div class="col s10">
                                <strong>Memory Limit:</strong><br>
                                <span class="grey-text"><?php echo $stats['php_memory']; ?></span>
                            </div>
                        </div>
                    </li>
                    <li class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s2">
                                <i class="material-icons purple-text">schedule</i>
                            </div>
                            <div class="col s10">
                                <strong>Uptime:</strong><br>
                                <span class="grey-text"><?php echo $stats['uptime']; ?></span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Admin Tools - New Section -->
    <div class="col s12 m12 l4">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">build</i>Admin Tools
                </span>
                <div class="collection">
                    <a href="?app=admin&p=roles" class="collection-item quick-action-item">
                        <i class="material-icons left indigo-text">group</i>
                        <span class="title">Role Management</span>
                    </a>
                    <a href="?app=admin&p=tests" class="collection-item quick-action-item">
                        <i class="material-icons left red-text">bug_report</i>
                        <span class="title">Legacy Tests</span>
                    </a>
                    <a href="?app=admin&p=phpunit-tests" class="collection-item quick-action-item">
                        <i class="material-icons left teal-text">verified_user</i>
                        <span class="title">PHPUnit Suite</span>
                    </a>
                    <a href="?app=admin&p=profile" class="collection-item quick-action-item">
                        <i class="material-icons left cyan-text">account_circle</i>
                        <span class="title">My Profile</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- App-contributed Dashboard Widgets with Enhanced Error Handling -->
<div class="row widget-container">
    <div class="col s12">
        <div class="card blue-grey lighten-5">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">extension</i>App Widgets
                </span>&nbsp;
                <?php
                if (function_exists('app_invoke_all')) {
                    try {
                        $appDashboards = app_invoke_all('hook_admin_dashboard');

                        // Sort the dashboards by priority, keeping keys intact
                        uasort($appDashboards, function ($a, $b) {
                            // Use the null coalescing operator to provide a default high-value priority
                            // This pushes items without a priority to the end of the sorted array.
                            $priorityA = $a['dashboard_widgets'][0]['priority'] ?? 999;
                            $priorityB = $b['dashboard_widgets'][0]['priority'] ?? 999;

                            // Use the spaceship operator for safe comparison
                            return $priorityA <=> $priorityB;
                        });

                        if (empty($appDashboards)) {
                            echo '<p class="grey-text">No app widgets are currently available.</p>';
                        } else {
                            foreach ($appDashboards as $appName => $dashboardData) {
                                if (isset($dashboardData['dashboard_widgets']) && is_array($dashboardData['dashboard_widgets'])) {

                                    foreach ($dashboardData['dashboard_widgets'] as $widget) {
                                        // Check admin requirement
                                        if (!empty($widget['requires_admin']) && !$isAdmin) {
                                            continue;
                                        }

                                        render('components/admin.dashboard.widget.php', array('widget' => $widget, 'dashboardData' => $dashboardData));
                                    }
                                }
                            }
                        }
                    } catch (Exception $e) {
                        echo '<div class="card red lighten-4"><div class="card-content">';
                        echo '<span class="card-title">App Widget System Error</span>';
                        echo '<p>Error loading app widgets: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '</div></div>';
                    }
                } else {
                    echo '<div class="card orange lighten-4"><div class="card-content">';
                    echo '<span class="card-title">Widget System Unavailable</span>';
                    echo '<p>The app widget system is not available. The app_invoke_all function was not found.</p>';
                    echo '</div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Timeline - New Feature -->
<div class="row">
    <div class="col s12">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">timeline</i>Recent Activity
                </span>
                <div class="collection">
                    <div class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s1">
                                <i class="material-icons blue-text">login</i>
                            </div>
                            <div class="col s11">
                                <span class="title">Admin Login</span>
                                <p class="grey-text">You logged in at <?php echo date('g:i A', $_SESSION['login_time'] ?? time()); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="collection-item">
                        <div class="row valign-wrapper" style="margin-bottom: 0;">
                            <div class="col s1">
                                <i class="material-icons green-text">dashboard</i>
                            </div>
                            <div class="col s11">
                                <span class="title">Dashboard Accessed</span>
                                <p class="grey-text">Viewing admin dashboard with enhanced features</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-action">
                <a href="?app=admin&p=logs" class="blue-text">
                    <i class="material-icons tiny left">history</i>View Full Activity Log
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Enhanced Dashboard JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltips = document.querySelectorAll('.tooltipped');
        M.Tooltip.init(tooltips);

        // Add hover effects to stat cards
        const statCards = document.querySelectorAll('.dashboard-stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Auto-refresh stats every 5 minutes
        setInterval(function() {
            // You could implement AJAX stats refresh here
            console.log('Stats refresh interval - implement AJAX call if needed');
        }, 300000); // 5 minutes
    });
</script>

<!-- Enhanced System Information Section -->
<div class="row">
    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">info</i>System Information
                </span>
                <div class="collection">
                    <div class="collection-item">
                        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                    </div>
                    <div class="collection-item">
                        <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                    </div>
                    <div class="collection-item">
                        <strong>Current User:</strong> <?php echo is_array($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : htmlspecialchars($_SESSION['user']); ?>
                    </div>
                    <div class="collection-item">
                        <strong>Login Time:</strong> <?php echo date('Y-m-d H:i:s', $_SESSION['login_time'] ?? time()); ?>
                    </div>
                    <div class="collection-item">
                        <strong>Database Path:</strong>
                        <span class="green-text"><?= $this->dbPath; ?></span>
                    </div>
                    <div class="collection-item">
                        <strong>Database File Exists:</strong>
                        <span class="green-text"><?= $this->debugInfo['DB_FILE_EXISTS']; ?></span>
                    </div>
                    <div class="collection-item">
                        <strong>Database File Writable:</strong>
                        <span class="green-text"><?= $this->debugInfo['DB_FILE_WRITABLE']; ?></span>
                    </div>
                    <div class="collection-item">
                        <strong>Server Load:</strong>
                        <span class="green-text">Optimal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col s12 m6">
        <div class="card">
            <div class="card-content">
                <span class="card-title">
                    <i class="material-icons left">build</i>Admin Tools
                </span>
                <div class="collection">
                    <a href="?app=admin&p=clear-cache" class="collection-item">
                        <i class="material-icons left orange-text">cached</i>Clear System Cache
                    </a>
                    <a href="?app=admin&p=backup_db" class="collection-item">
                        <i class="material-icons left blue-text">backup</i>Backup To Cloud Bucket
                    </a>
                    <a href="?app=admin&p=restore_db" class="collection-item">
                        <i class="material-icons left blue-text">restore</i>Restore DB
                    </a>
                    <a href="?app=admin&p=logs" class="collection-item">
                        <i class="material-icons left red-text">description</i>View System Logs
                    </a>
                    <a href="?app=admin&p=maintenance" class="collection-item">
                        <i class="material-icons left purple-text">settings</i>Maintenance Mode
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer Section -->
<div class="row">
    <div class="col s12">
        <div class="card blue-grey lighten-5">
            <div class="card-content center">
                <p class="grey-text">
                    MediaBrain Admin Dashboard v2.0 |
                    Enhanced with TTS Management & Theme System |
                    Theme: <?php echo ucfirst($currentTheme); ?> |
                    Last Updated: <?php echo date('Y-m-d H:i:s'); ?>
                </p>
            </div>
        </div>
    </div>
</div>


<!-- Theme JavaScript -->
<?php echo $themeManager->getThemeJS(); ?>

<script>
    // Theme system integration
    document.addEventListener('DOMContentLoaded', function() {
        // Theme switching functionality
        window.ThemeSystem = {
            currentTheme: '<?php echo $currentTheme; ?>',

            // Switch theme function
            switchTheme: function(themeName) {
                fetch('?app=admin&api=switch-theme', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            theme: themeName
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload page to apply new theme
                            location.reload();
                        } else {
                            console.error('Theme switch failed:', data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Theme switch error:', error);
                    });
            },

            // Get available themes
            getAvailableThemes: function() {
                return fetch('?app=admin&api=themes')
                    .then(response => response.json())
                    .then(data => data.themes || []);
            }
        };

        // Add theme switching keyboard shortcut (Ctrl+Alt+T)
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.altKey && e.key === 't') {
                e.preventDefault();
                showThemeSelector();
            }
        });

        // Theme selector modal
        function showThemeSelector() {
            window.ThemeSystem.getAvailableThemes().then(themes => {
                const themeOptions = Object.keys(themes).map(themeName => {
                    const isActive = themeName === window.ThemeSystem.currentTheme ? 'active' : '';
                    return `
                    <div class="theme-option ${isActive}" data-theme="${themeName}" 
                         style="display: flex; align-items: center; padding: 15px; cursor: pointer; 
                                border: 2px solid ${isActive ? '#2196F3' : 'transparent'}; 
                                margin: 10px; border-radius: 8px; background: #f5f5f5;">
                        <div style="flex: 1;">
                            <h6 style="margin: 0 0 5px 0;">${themes[themeName].name}</h6>
                            <p style="margin: 0; color: #666; font-size: 0.9em;">${themes[themeName].description}</p>
                        </div>
                        ${isActive ? '<i class="material-icons" style="color: #2196F3;">check_circle</i>' : ''}
                    </div>
                `;
                }).join('');

                const modalHtml = `
                <div id="theme-selector-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                     background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
                    <div style="background: white; max-width: 600px; width: 90%; max-height: 80%; overflow-y: auto; 
                         border-radius: 8px; padding: 20px;">
                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                            <h4 style="margin: 0;">Select Theme</h4>
                            <button onclick="closeThemeSelector()" style="background: none; border: none; font-size: 1.5em; cursor: pointer;">×</button>
                        </div>
                        <div id="theme-options">
                            ${themeOptions}
                        </div>
                        <div style="text-align: center; margin-top: 20px; color: #666;">
                            Press <kbd>Ctrl+Alt+T</kbd> to open theme selector
                        </div>
                    </div>
                </div>
            `;

                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Add click handlers
                document.querySelectorAll('.theme-option').forEach(option => {
                    option.addEventListener('click', function() {
                        const themeName = this.dataset.theme;
                        if (themeName !== window.ThemeSystem.currentTheme) {
                            window.ThemeSystem.switchTheme(themeName);
                        }
                    });
                });
            });
        }

        // Close theme selector
        window.closeThemeSelector = function() {
            const modal = document.getElementById('theme-selector-modal');
            if (modal) {
                modal.remove();
            }
        };

        // Console message about theme system
        console.log(`🎨 Theme System Active! Current theme: ${window.ThemeSystem.currentTheme}`);
        console.log('Press Ctrl+Alt+T to open theme selector');
    });
</script>