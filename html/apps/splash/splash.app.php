<?
if (!defined('MB_RUNNING')) exit;
/**
 * 
 * 
 */

function splash_info()
{
  // Add cache-busting timestamp
  $cache_bust = time();
  
  $app_info = array(
    'title' => "Mediabrain",
    'description' => '',
    'version' => "1.0",
    'requires_auth' => false,
    'requires_admin' => false,
    'no_header' => true,
    'public_app' => true,
    'cache_bust' => $cache_bust,
    'styles' => array(
      "apps/splash/css/app.css?v=" . $cache_bust,
      "apps/splash/css/star-trek-achievements.css?v=" . $cache_bust,
    ),
    'scripts' => array(
      "apps/splash/js/app.js?v=" . $cache_bust,
      "apps/splash/js/star-trek-achievements.js?v=" . $cache_bust,
    ),
    'components' => array(
      'apps/splash/js/components/modals/applications-modal.js',
    ),
  );
  
  return $app_info;
}


function splash_init(&$app)
{
  $version = App::getInstance()->config['version'];
  $meta = $app->getDefaultMetaImageArray();
  $meta['title'] = 'Mediabrain - App Services';
  $meta['description'] = "Written from scratch.  Using modern technologies.";
  $app->set('meta', $meta);
}

function splash_render_body()
{
  $page = get_var('p', 'splash');

  render('pages/' . $page . '.php');
  render('components/modals.php');
}

