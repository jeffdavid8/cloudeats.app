<?php

function help_info()
{
    return array(
        'title' => "Help Center",
        'description' => "Comprehensive help and documentation for MediaBrain applications",
        'image' => config('base_url') . '/apps/help/images/help-logo.png',
        'image_width' => '1200',
        'image_height' => '630',
        'version' => "1.0",
        'requires_auth' => false,
        'requires_admin' => false,
        'no_header' => false,
        'public_app' => true,
        'styles' => array(
            "apps/help/css/help.css"
        ),
        'scripts' => array(
            "apps/help/js/help.js"
        ),
    );
}

function help_init()
{
    $app = App::getInstance('help');


    $meta = array(
        'title' => 'MediaBrain Help Center',
        'description' => 'Get help with MediaBrain applications and features',
        'type' => 'website',
        'url' => config('base_url') . '/?app=help',
        'image' => config('base_url') . '/apps/help/images/help-social.png',
    );
    $app->set('meta', $meta);
}

function help_render_body()
{
    $app = App::getInstance();
    $currentUser = null;
    $userRole = 'guest';

    // Get current user and role for context-sensitive help
    if (isset($_SESSION['user'])) {
        $currentUser = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];

        // Determine user role using AuthManager
        require_once __DIR__ . '/../../includes/AuthManager.php';
        if (AuthManager::userIsAdmin($_SESSION['user'])) {
            $userRole = 'admin';
        } else {
            $userRole = 'user';
        }
    }

    $section = get_var('section', 'overview');
    $topic = get_var('topic', '');

    // Main help content
?>
    <div class="help-container">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col s12 m12 l4">
                <div class="help-sidebar">
                    <h5>Help Topics</h5>
                    <div class="collection">
                        <a href="?app=help&section=overview" class="collection-item <?php echo $section === 'overview' ? 'active' : ''; ?>">
                            <i class="material-icons">home</i> Overview
                        </a>
                        <a href="?app=help&section=setup" class="collection-item <?php echo $section === 'setup' ? 'active' : ''; ?>">
                            <i class="material-icons">settings</i> Setup & Configuration
                        </a>
                        <a href="?app=help&section=biblebot" class="collection-item <?php echo $section === 'biblebot' ? 'active' : ''; ?>">
                            <i class="fa fa-robot"></i> BibleBot
                        </a>
                        <a href="?app=help&section=recipes" class="collection-item <?php echo $section === 'recipes' ? 'active' : ''; ?>">
                            <i class="material-icons">restaurant</i> Recipes
                        </a>
                        <a href="?app=help&section=weather" class="collection-item <?php echo $section === 'weather' ? 'active' : ''; ?>">
                            <i class="material-icons">wb_sunny</i> Weather
                        </a>
                        <a href="?app=help&section=ancestry" class="collection-item <?php echo $section === 'ancestry' ? 'active' : ''; ?>">
                            <i class="fas fa-tree"></i> Ancestry
                        </a>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="?app=help&section=admin" class="collection-item <?php echo $section === 'admin' ? 'active' : ''; ?>">
                                <i class="fas fa-users-cog"></i> Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="?app=help&section=troubleshooting" class="collection-item <?php echo $section === 'troubleshooting' ? 'active' : ''; ?>">
                            <i class="material-icons">help_outline</i> Troubleshooting
                        </a>
                    </div>

                    <!-- Quick Links -->
                    <div class="help-quick-links">
                        <h6>Quick Links</h6>
                        <ul>
                            <li><a href="?p=dashboard">Dashboard</a></li>
                            <li><a href="?p=login">Login</a></li>
                            <li><a href="?app=admin" target="_blank">Admin Panel</a></li>
                        </ul>
                    </div>

                    <!-- User Context -->
                    <div class="help-user-context">
                        <div class="card-panel grey lighten-4">
                            <small>
                                <strong>Current Role:</strong> <?php echo ucfirst($userRole); ?><br>
                                <?php if ($currentUser): ?>
                                    <strong>User:</strong> <?php echo htmlspecialchars($currentUser); ?>
                                <?php else: ?>
                                    <strong>Status:</strong> Not logged in
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col s12 m12 l8">
                <div class="help-content">
                    <?php
                    // Load the appropriate help section
                    $sectionFile = "apps/help/views/pages/{$section}.php";
                    if (file_exists($sectionFile)) {
                        include $sectionFile;
                    } else {
                        include 'apps/help/views/pages/overview.php';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pass user context to JavaScript
        window.helpContext = {
            userRole: '<?php echo $userRole; ?>',
            currentUser: '<?php echo htmlspecialchars($currentUser ?? ''); ?>',
            section: '<?php echo $section; ?>'
        };
    </script>
<?php
}
?>