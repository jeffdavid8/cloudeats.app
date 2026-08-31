<?
if (!defined('MB_RUNNING')) exit;
/**
 * @var Object $meta
 */
$user = ($this->user) ? $this->user->data() : [];

$protocol = protocol();
$host = $_SERVER['HTTP_HOST'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query_string = !empty($_GET) ? '?' . http_build_query($_GET, '', '&') : '';
$og_url = $protocol . '://' . $host . $path . $query_string;
$site_manifest_url = $this->get('sitemanifest', '/site.webmanifest');

$bg_image_name = $this->get('bg_image_name');
$share_image_name = $this->get('share_image_name');
$share_image_bucket_dir = config('share_image_bucket_dir');
$bg_image_url = '';
if (!empty($bg_image_name)) {
  $bg_image_url = $share_image_bucket_dir . '/' . $bg_image_name . '.jpg';
}
if (!empty($share_image_name)) {
  $bg_image_url = $share_image_bucket_dir . '/' . $share_image_name . '.jpg';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <meta name="google" value="notranslate">

  <meta name="title" content="<?= $meta['title']; ?>">
  <meta name="description" content="<?= $meta['description']; ?>">
  <meta name="author" content="Mediabrain">
  <meta property="og:type" content="<?= $meta['type'] ? $meta['type'] : 'website'; ?>" />
  <meta property="og:title" content="<?= $meta['title']; ?>" />
  <meta property="og:description" content="<?= $meta['description']; ?>" />
  <meta property="og:url" content="<?= $og_url ?>" />
  <meta property="fb:app_id" content="<?= $this->config['fb_app_id'] ?>" />
  <?php
  $csrfToken = AuthManager::csrfToken();
  ?>
  <meta name="csrf-token" content="<?= $csrfToken; ?>" />
  <?php
  if ((isset($meta['video'])) && (!isset($meta['image']))) {
  ?>
    <meta property="og:video" content="<?= $meta['video']; ?>" />
    <? /*
    <meta property="og:video:width" content="<?= $meta['video_width']; ?>" />
    <meta property="og:video:height" content="<?= $meta['video_height']; ?>" />
    */ ?>
  <?php
  } else {
  ?>
    <meta property="image" content="<?= $meta['image']; ?>" />
    <meta property="og:image" content="<?= $meta['image']; ?>" />
    <? /*
    */ ?>
    <meta property="og:image:type" content="<?= $meta['image_type'] ?>">
    <meta property="og:image:width" content="<?= $meta['image_width']; ?>" />
    <meta property="og:image:height" content="<?= $meta['image_height']; ?>" />
  <?php
  }
  ?>

  <title><?= $meta['title']; ?></title>
  <?
  $favicon = array_merge(array(
    'favicon' => $this->config['base_url'] . '/favicon.ico',
    'favicon-16x16' => $this->config['base_url'] . '/favicon-16x16.png',
    'favicon-32x32' => $this->config['base_url'] . '/favicon-32x32.png',
    'apple-touch-icon-180x180' => $this->config['base_url'] . '/apple-touch-icon-180x180.png',
    'android-chrome-192x192' => $this->config['base_url'] . '/android-chrome-192x192.png',
    'android-chrome-512x512' => $this->config['base_url'] . '/android-chrome-512x512.png',
  ), $this->get('favicon', []));
  ?>
  <!-- Standard Icons -->
  <link rel="icon" type="image/x-icon" href="<?= $favicon['favicon'] ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $favicon['favicon-16x16'] ?>">
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $favicon['favicon-32x32'] ?>">

  <!-- Android / Chrome -->
  <link rel="icon" type="image/png" sizes="192x192" href="<?= $favicon['android-chrome-192x192'] ?>">
  <link rel="icon" type="image/png" sizes="512x512" href="<?= $favicon['android-chrome-512x512'] ?>">

  <!-- Apple iOS -->
  <link rel="apple-touch-icon" sizes="180x180" href="<?= $favicon['apple-touch-icon-180x180'] ?>">
  <link rel="manifest" href="<?= $site_manifest_url ?>">
  <!-- CSS  -->
  <!--Import Google Icon Font-->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <!--<link rel="stylesheet" href="/cdn/materializecss/1.0/css/materialize-icons.css"/>-->
  <link rel="stylesheet" href="css/icons.css" />
  <!-- Add Material Symbols font for newer icons -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
  <!--Import materialize.css-->
  <link rel="stylesheet" href="css/materialize.min.css" />
  <!--Import font-awesome.css-->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/brands.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" />
  <!--Import primary css-->
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/respond.css" />
  <link rel="stylesheet" href="css/jquery.json-viewer.css">
  <!--Import night mode css-->
  <link rel="stylesheet" href="css/nightmode.css" />
  <script>
    var mb = {
      dialogs: [],
      isDevelopment: <?= json_encode(is_development()); ?>,
      isProduction: <?= json_encode(is_production()); ?>,
    };
    // Initialize CSRF token from meta tag 
    (function() {
      mb.meta = document.querySelector('meta[name="csrf-token"]');
      mb.csrf_token = mb.meta ? mb.meta.getAttribute('content') : null;
    })();
  </script>

  <!-- jQuery with CDN fallback to local -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@2.1.1/dist/jquery.min.js"></script>
  <script>
    // Fallback to local jQuery if CDN fails
    if (typeof jQuery == 'undefined') {
      document.write('<script src="js/jquery-2.1.1.min.js"><\/script>');
    }
  </script>

  <script src="js/mediabrain.js"></script>

  <!-- jQuery Ready Utility - Load after jQuery for dependency management -->
  <script src="js/jquery-ready.js"></script>

  <!-- Materialize JavaScript - Load after jQuery -->
  <script src="js/materialize.min.js"></script>

  <!-- Wait for jQuery to be fully loaded before loading dependent scripts -->
  <script>
    $(document).ready(function() {
      // Ensure jQuery is available globally
      if (typeof $ === 'undefined') {
        window.$ = jQuery;
      }
    });
  </script>
  <script src="js/Sortable.min.js" type="text/javascript"></script>
  <? /* 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" integrity="sha512-yVvxUQV0QESBt1SyZbNJMAwyKvFTLMyXSyBHDO4BG5t7k/Lw34tyqlSDlKIrIENIzCl+RVUNjmCPG+V/GMesRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
*/ ?>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

  <!-- Load jQuery-dependent scripts in order -->
  <script>
    <? if (isset($user)) { ?>
      mb.user = <?= json_encode($user, true) ?>;
      mb.isCommander = <?= ($user['is_admin']) ? 'true' : 'false' ?>;
    <? } ?>
    // Function to load scripts sequentially after jQuery is ready
    function loadScriptsSequentially() {
      var components = [
        'js/init.js', // Load init.js first as it initializes UI components
        'js/jquery.json-viewer.js',
        'js/cycle.js',
        'js/json2.js',
        'js/overlay.js',
        //'js/mediabrain.js',
        'js/component-registry.js', // Load component registry after mediabrain.js to extend mb object
        'js/component-loader.js', // Load component auto-loader after registry
        'js/auto-components.js',
        'js/commands.js'
      ];

      // Add app-specific scripts to the queue
      <?
      $components = isset($this->app_info['components']) ? $this->app_info['components'] : [];
      if (!empty($components)) {
        foreach ($components as $component) {
          echo "components.push('$component');\n        ";
          echo "mb.log('Added app script to queue: $component');\n        ";
        }
      } else {
        echo "mb.log('No app components found');\n        ";
      }
      ?>

      var loadScript = function(index) {
        if (index >= components.length) {
          mb.log('All scripts loaded successfully');
          return;
        }

        mb.log('Loading script: ' + components[index]);
        var script = document.createElement('script');
        var cacheBuster = '?v=' + new Date().getTime();
        script.src = components[index] + cacheBuster;
        script.onload = function() {
          mb.log('Loaded: ' + components[index]);
          loadScript(index + 1);
        };
        script.onerror = function() {
          console.warn('Failed to load script: ' + components[index]);
          loadScript(index + 1); // Continue loading other scripts
        };
        document.head.appendChild(script);
      };

      loadScript(0);
    }

    // Ensure jQuery is loaded before dependent scripts
    $(document).ready(function() {
      loadScriptsSequentially();
    });
  </script>
  <!--<script src="/js/dialogs/create_new_bookmark.js"></script>-->

  <?
  // Load app-specific scripts in head as well
  $scripts = isset($this->app_info['scripts']) ? $this->app_info['scripts'] : [];
  if (!empty($scripts)) {
    foreach ($scripts as $script) {
      $script = $script . '?v=' . time();
      echo "<script src=\"$script\"></script>";
    }
  }

  logger($this->app_info['styles']);
  $styles = isset($this->app_info['styles']) ? $this->app_info['styles'] : [];
  if (!empty($styles)) {
    foreach ($styles as $style) {
      $style = $style . '?v=' . time();
      echo "<link rel=\"stylesheet\" href=\"$style\" />";
    }
  }
  // Scripts are now loaded via sequential loading above, not here
  if (!empty($bg_image_url)) {
  ?>
    <style type="text/css">
      body {
        background-image: url('<?= $bg_image_url ?>');
        background-repeat: no-repeat;
        background-position: center top;
        background-size: cover;
        background-attachment: fixed;
      }
    </style>
  <?
  }
  ?>


</head>