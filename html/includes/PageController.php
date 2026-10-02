<?php

class PageController
{
    private $page;
    private $app;
    private $requiresAuth = ['dashboard', 'edit', 'bookmarks'];
    private $layouts = [
        'dashboard' => 'default',  // Use default layout with header
        'login' => 'minimal',
        'edit' => 'default',
        'bookmarks' => 'default',
        'search' => 'default',
        'search_results' => 'default',
        'home' => 'default',
        'privacy-policy' => 'minimal',
        'data-deletion-notice' => 'minimal',
        'thank-you' => 'minimal'
    ];

    public function __construct($page)
    {
        $this->page = $page;
        $this->app = App::getInstance();
    }

    public function handleRequest()
    {
        // Enable output buffering to allow headers to be sent after rendering
        if (!ob_get_level()) {
            ob_start();
        }

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Handle authentication check BEFORE any output
        if (in_array($this->page, $this->requiresAuth)) {
            $this->checkAuthentication();
        }

        $redirectUrl = get_var('return', '?p=dashboard');

        // Detect if logged in already, and redirect to dashboard
        if ($this->app->getAuthManager()::isUserLoggedIn()) {
            header('Location: ' . $redirectUrl, true, 302);
            exit();
        }

        // Now it's safe to render with appropriate layout
        $this->render();

        // Flush output buffer
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    private function checkAuthentication()
    {
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            header('Location: ?p=login', true, 302);
            // Fallback JavaScript redirect in case headers don't work
            echo '<script>window.location.href = "?p=login";</script>';
            echo '<p>Redirecting to login page... <a href="?p=login">Click here if not redirected automatically</a></p>';
            exit();
        }
    }

    private function render()
    {
        // Set up App instance for proper head rendering
        $user = $this->app->user;
        $day_night_mode = (isset($_COOKIE['day_night_mode'])) ? $_COOKIE['day_night_mode'] : 'night';
        $this->app->setCookie('day_night_mode', $day_night_mode);
        $this->app->set('day_night_mode', $day_night_mode);

        $day_night_mode = get_var('day_night_mode', $day_night_mode);
        $day_mode = ($day_night_mode == 'day');
        $bg_image = $this->app->get('bg_image');
        $app_meta = $this->app->get('meta', array());
        $base_url = $this->app->config['base_url'];
        $page_title = get_var('page_title', ucwords(str_replace('-', ' ', $this->page)));

        $site_meta = array(
            'title' => (!empty($page_title)) ? $page_title : $app->config['site_title'],
            'site_name' => $this->app->config['site_name'],
            'description' => (!empty($app_meta['description'])) ? $app_meta['description'] : $app->config['site_description'],
            'url' => $this->app->config['base_url'],
            'type' => 'website',
            'image' => $this->app->config['site_logo_url'],
            'image_width' => '600',
            'image_height' => '600',
        );
        $site_meta = array_merge($site_meta, $app_meta);

        $layout = $this->getLayout();

        if ($this->page == 'login') {
            // get app_name from return_url and get app_info() data using app_invoke()
            $returnUrl = get_var('return', '');
            if (!empty($returnUrl)) {
                // 1. Get the query string part of the return URL (e.g., "app=stitch")
                $queryString = parse_url($returnUrl, PHP_URL_QUERY);

                if ($queryString) {
                    // 2. Parse that query string into an array
                    parse_str($queryString, $returnParams);

                    // 3. Extract the 'app' value
                    if (isset($returnParams['app'])) {
                        $appName = $returnParams['app'];
                        $appInfo = app_invoke($appName, 'info', $this->app);
                        if (is_array($appInfo)) {
                            $site_meta['title'] = $appInfo['title'] . ' | Login';
                            $site_meta['description'] = $appInfo['description'];
                            $site_meta['image'] = $appInfo['image'];
                            $site_meta['image_width'] = $appInfo['image_width'];
                            $site_meta['image_height'] = $appInfo['image_height'];
                        }
                    }
                }
            }
        }


        // Render complete page with head
        $this->app->render('components/head.php', array('meta' => $site_meta));

        $this->app->render('components/open_body_tag.php', array('page_name' => $this->page));

        $this->app->render('components/audio_interfaces.php'); ?>

        <div id="loadingIndicator">
            <?php render('components/loading_indicator.php'); ?>
        </div>

        <?php
        switch ($layout) {
            case 'default':
                $this->renderWithDefaultLayout();
                break;
            case 'minimal':
                $this->renderWithMinimalLayout();
                break;
            default:
                $this->renderWithDefaultLayout();
        }

        $this->app->render('components/footer.php');

        if ($_SERVER['HTTP_HOST'] == config('domain')) {
            render('components/google_analytics.php');
        }

        $this->app->render('components/runtime-errors.php');
        ?>

        </body>

        </html>
<?php
    }

    public function getApp() {
        return $this->app;
    }

    private function getLayout()
    {
        return isset($this->layouts[$this->page]) ? $this->layouts[$this->page] : 'default';
    }

    private function renderWithDefaultLayout()
    {
        render('components/header/header.php');
        $this->renderPageContent();
    }

    private function renderWithMinimalLayout()
    {
        // Just render the page content without header
        $this->renderPageContent();
    }

    private function renderPageContent()
    {
        $pagePath = "pages/{$this->page}.php";
        $fullPath = "views/{$pagePath}";

        echo '<div class="page-container">';

        if (file_exists($fullPath)) {
            render($pagePath);
        } else {
            render('pages/error/404.php', array(
                'message' => "Sorry, could not load the {$this->page} page<br/> views/{$pagePath} not found"
            ));
        }

        echo '</div>';
    }

    public function pageExists()
    {
        $pagePath = "views/pages/{$this->page}.php";
        return file_exists($pagePath);
    }
}
