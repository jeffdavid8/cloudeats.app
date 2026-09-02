<?php
if (!defined('MB_RUNNING')) exit;

class AppController
{
    private $appName;
    private $app;
    public $config;

    public function __construct($appName)
    {
        $this->appName = (!empty($appName)) ? $appName : 'neighborhub';
        $this->app = App::getInstance($this->appName);
        $this->config = $this->getAppConfig();
        $this->app->app_config = $this->config;
    }

    /**
     * Get app configuration from the app's info() function
     */
    private function getAppConfig()
    {
        static $config = null;
        if ($config !== null) {
            return $config;
        }

        try {
            // Load the app file directly without creating App instance yet
            $appPath = __DIR__ . '/../apps/' . $this->appName . '/' . $this->appName . '.app.php';
            if (file_exists($appPath)) {
                require_once $appPath;

                // Call the info function directly
                $infoFunction = $this->appName . '_info';
                if (function_exists($infoFunction)) {
                    $config = call_user_func($infoFunction, $this->app);
                    if (is_array($config)) {
                        return $config;
                    }
                }
            }
        } catch (Exception $e) {
            // App doesn't exist or info function failed
            error_log("Failed to get app config for {$this->appName}: " . $e->getMessage());
        }

        // Return defaults for unknown apps
        $config = [
            'requires_auth' => false,
            'requires_admin' => false,
            'no_header' => false,
            'public_app' => true
        ];

        return $config;
    }

    public function handleRequest()
    {
        // 1. Start session early
        if (!isset($_SESSION)) {
            session_start();
        }

        // 2. Check if app exists
        if (!$this->appExists()) {
            $this->render404();
            return;
        }

        if (!str_starts_with(get_var('p', ''), 'public.')) {
            // 3. Handle authentication BEFORE any output
            $this->handleAuthentication();
            // 4. Check app-specific permissions
            $this->checkAppPermissions();
        }

        $this->setMeta();

        app_invoke($this->appName, 'init', $this->app);

        // 6. Render with proper layout control
        $this->renderApp();
    }

    private function setMeta()
    {
        $meta = [
            'title' => $this->config['title'],
            'description' => $this->config['description'],
            'type' => 'article',
            'image' => $this->config['imageUrl'] ?? ($this->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png'),
            'image_width' => $this->config['imageWidth'] ?? '1200',
            'image_height' => $this->config['imageHeight'] ?? '630',
        ];

        $this->app->set('meta', $meta);
    }

    private function appExists()
    {
        // Check if the app file exists in the apps directory
        $appPath = __DIR__ . '/../apps/' . $this->appName . '/' . $this->appName . '.app.php';
        return file_exists($appPath);
    }

    private function handleAuthentication()
    {
        $config = $this->getAppConfig();

        // Check if app requires any authentication
        if ($config['requires_auth'] ?? false) {
            if (!$this->isUserLoggedIn()) {
                $returnUrl = $_SERVER['REQUEST_URI'];
                header('Location: ?p=login&return=' . urlencode($returnUrl), true, 302);
                exit();
            }
        }

        // Check if app requires admin privileges
        if ($config['requires_admin'] ?? false) {
            if (!$this->isUserAdmin()) {
                header('Location: ?p=dashboard&msg=' . urlencode('Administrator permissions are required to access that page.'), true, 302);
                exit();
            }
        }
    }

    private function checkAppPermissions()
    {
        $config = $this->getAppConfig();

        // Skip permission check for public apps
        if ($config['public_app'] ?? false) {
            return;
        }

        // Skip if user is not logged in (will be handled by authentication check)
        if (!$this->isUserLoggedIn()) {
            return;
        }

        // If user is admin, skip permissions check
        if ($this->isUserAdmin()) {
            return;
        }

        // For non-admin authenticated apps, check permissions using PermissionsMatrix
        try {
            require_once __DIR__ . '/../apps/admin/includes/PermissionsMatrix.php';
            $permissions = new PermissionsMatrix();

            // Get username from unified session format
            $username = is_array($_SESSION['user']) ? $_SESSION['user']['username'] : $_SESSION['user'];
            $userApps = $permissions->getUserApps($username);

            // Fix: check array key existence, not in_array
            if (!isset($userApps[$this->appName])) {
                header('Location: ?p=dashboard', true, 302);
                exit();
            }
        } catch (Exception $e) {
            // If permissions system fails, deny access for non-admin users
            header('Location: ?p=dashboard', true, 302);
            exit();
        }
    }

    private function renderApp()
    {
        $config = $this->config;
        $user = $this->app->user;
        $isCommander = $user->is_admin ? 'COMMANDER' : 'OBSERVER';

        // Set up app metadata and styling

        $day_night_mode = get_var('day_night_mode', $day_night_mode);
        $day_night_mode_class = ($day_night_mode == 'day') ? 'dayMode' : ' nightMode';
        $nightModeClass =  $this->app->get('night_mode_class', $day_night_mode_class);
        $bg_image = $this->app->get('bg_image');
        $base_url = $this->app->config['base_url'];
        $app_title = (!empty($app_meta['title'])) ? $app_meta['title'] : (isset($this->app->app_info['title']) ? $this->app->app_info['title'] : '');
        $page_title = get_var('page_title', $app_title);
        $page_title = (!empty($page_title)) ? ucwords(strtolower($page_title)) : '';
        $site_meta = array(
            'title' => (!empty($page_title)) ? $page_title : (isset($this->app->config['site_title']) ? $this->app->config['site_title'] : 'MediaBrain'),
            'site_name' => isset($this->app->config['site_name']) ? $this->app->config['site_name'] : 'MediaBrain',
            'description' => (isset($this->app->config['site_description']) ? $this->app->config['site_description'] : ''),
            'url' => $this->app->config['base_url'],
            'type' => 'website',
            'image' => $this->app->config['base_url'] . '/images/mb-logo-black-circle-2020-600.png',
            'image_width' => '600',
            'image_height' => '600',
        );
        //$site_meta = array_merge($site_meta, $app_meta);
        $app_meta = $this->app->get('meta', $site_meta);
        $this->app->set('meta', $app_meta);

        // Render the complete page
        render('components/head.php', array('meta' => $app_meta));

        render('components/open_body_tag.php', array('nightModeClass' => $nightModeClass));
?>

        <? render('components/cloudeats_preloader.php', array('text' => 'Loading CloudEats...')); ?>

        <?php render('components/audio_interfaces.php'); ?>

        <div id="loadingIndicator">
            <?php render('components/loading_indicator.php'); ?>
        </div>

        <?
        // Control header rendering based on app needs
        if ((!$this->app->app_config['no_header'])) {
            render('components/header/header.php');
        }
        // Render the app content
        app_invoke($this->appName, 'render_body', $this->app);

        render('components/footer.php');

        if ($_SERVER['HTTP_HOST'] == config('domain')) {
            render('components/google_analytics.php');
        }

        render('components/runtime-errors.php');
        ?>

        </body>

        </html>
<?php
    }

    private function render404()
    {
        render('components/header/header.php');
        render('pages/error/404.php', array(
            'message' => "Sorry, could not load the {$this->appName} app<br/> App not found"
        ));
    }

    private function isUserLoggedIn()
    {
        return isset($_SESSION['user']) &&
            (is_array($_SESSION['user']) ? !empty($_SESSION['user']['username']) : !empty($_SESSION['user']));
    }

    private function isUserAdmin()
    {
        // Use unified authentication system
        return $this->app->authManager->isAdmin();
    }

    public function getAppName()
    {
        return $this->appName;
    }
}
